<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Controller\HandlePdfController.
 */

namespace Drupal\fillpdf\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Utility\Token;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\Entity\FillPdfFormField;
use Drupal\fillpdf\FillPdfBackendManager;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class HandlePdfController extends ControllerBase {

  /** @var FillPdfLinkManipulatorInterface $linkManipulator */
  protected $linkManipulator;

  /** @var FillPdfBackendManager $backendManager */
  protected $backendManager;

  /** @var QueryFactory $entityQuery */
  protected $entityQuery;

  /** @var EntityManager $entityManager */
  protected $entityManager;

  /** @var Token $token */
  protected $token;

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, RequestStack $request_stack, FillPdfBackendManager $backend_manager, Token $token, QueryFactory $entity_query, EntityManager $entity_manager) {
    $this->linkManipulator = $link_manipulator;
    $this->requestStack = $request_stack;
    $this->backendManager = $backend_manager;
    $this->token = $token;
    $this->entityQuery = $entity_query;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.link_manipulator'),
      $container->get('request_stack'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('token'),
      $container->get('entity.query'),
      $container->get('entity.manager')
    );
  }

  public function populatePdf() {
    $context = $this->linkManipulator->parseLink($this->requestStack->getCurrentRequest());

    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('fillpdf_service_backend');

    // Load the backend plugin.
    /** @var FillPdfBackendPluginInterface $backend */
    $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());

    // @todo: Emit event (or call alter hook?) before populating PDF. fillpdf_merge_fields_alter -> should be renamed to fillpdf_populate_fields_alter

    // TODO: Extract the PDF parameters, and act accordingly - this is pretty much just calling FillPdfBackendPluginInterface::populateWithFieldData and then reading the FillPdfForm options to determine whether to serve as a file download or fill in the field data.
    $fillpdf_form = FillPdfForm::load($context['fid']);
    if (!$fillpdf_form) {
      drupal_set_message($this->t('FillPDF Form (fid) not found in the system. Please check the value in your FillPDF Link.'), 'error');
    }

    $fields = FillPdfFormField::loadMultiple(
      $this->entityQuery->get('fillpdf_form_field')
        ->condition('fillpdf_form', $fillpdf_form->id())
        ->execute());

    // Populate entities array based on what user passed in
    $entities = [];
    foreach ($context['entity_ids'] as $entity_type => $entity_ids) {
      $type_controller = $this->entityManager->getStorage($entity_type);
      $entity_list = $type_controller->loadMultiple($entity_ids);

      if (!empty($entity_list)) {
        // Initialize array.
        $entities += [$entity_type => []];
        $entities[$entity_type] += $entity_list;
      }
    }

    $field_mapping = [
      'fields' => [],
      // @todo: Image-filling support. Probably both the local and remote plugins could extend the same class.
      'images' => [],
    ];

    $mapped_fields = &$field_mapping['fields'];
    $image_data = &$field_mapping['images'];
    foreach ($fields as $field) {
      $pdf_key = $field->pdf_key->value;
      if ($context['sample']) {
        $mapped_fields[$pdf_key] = $pdf_key;
      }
      else {
        // Whichever entity matches the token last wins.
        $replaced_string = '';
        foreach ($entities as $entity_type => $entity_objects) {
          foreach ($entity_objects as $entity_id => $entity) {
            // @todo: Refactor so the token context can be re-used for title replacement later OR do title replacement here so that it works the same way as value replacement and doesn't just use the last value...like it does in Drupal 7 :(
            // @todo: What if one fill pattern has tokens from multiple types in it? Figure out the best way to deal with that and rewrite this section accordingly. Probably some form of parallel arrays. Basically we'd have to run all combinations, although our logic still might not be smart enough to tell if *all* tokens in the source text have been replaced, or in which case both of them have been replaced last (which is what we want). I could deliberately pass each entity context separately and then count how many of them match, and only overwrite it if the match count is higher than the current one. Yeah, that's kind of inefficient but also a good start. I might just be able to scan for tokens myself and then check if they're still in the $uncleaned_base output, or do the cleaning myself so I only have to call Token::replace once. TBD.
            $field_pattern = $field->value->value;
            $maybe_replaced_string = $this->token->replace($field_pattern, [
              $entity_type => $entity
            ], [
              'clean' => TRUE,
              'sanitize' => FALSE,
            ]);
            // Generate a non-cleaned version of the token string so we can
            // tell if the non-empty string we got back actually replaced
            // some tokens.
            $uncleaned_base = $this->token->replace($field_pattern, [
              $entity_type => $entity
            ], [
              'sanitize' => FALSE,
            ]);

            // If we got a result that isn't what we put in, update the value
            // for this field..
            if ($maybe_replaced_string && $field_pattern !== $uncleaned_base) {
              $replaced_string = $maybe_replaced_string;
            }
          }
        }

        $mapped_fields[$pdf_key] = $replaced_string;
      }
    }

    $populated_pdf = $backend->populateWithFieldData($fillpdf_form, $field_mapping, $context);

    // @todo: When Rules integration ported, emit an event or whatever.

    // TODO: Take the appropriate action on the PDF.
    return $this->handlePopulatedPdf($fillpdf_form, $populated_pdf, []);
  }

  /**
   * @todo: Split this up better. There are a few things happening here. 1) We look at the function arguments to determine the action to take. But there's also the concept of a default action. In that case, we have to look at the $context array. 2) The actual handling code for the action, e.g. transmitting a download and saving a file, optionally redirecting to a file. These should be usable at will, albeit specific to routes.
   *
   * Figure out what to do with the PDF and do it.
   *
   * @param \Drupal\fillpdf\Controller\An|\Drupal\fillpdf\FillPdfFormInterface $fillpdf_form An object containing the loaded record from {fillpdf_forms}
   * .
   * @param $pdf_data A string containing the content of the merged PDF.
   * @param array|\Drupal\fillpdf\Controller\An $token_objects An array of objects to be used in replacing tokens.
   * Here, specifically, it's for generating the filename of the handled PDF.
   * @param \Drupal\fillpdf\Controller\One|string $action One of the following keywords: default, download, save,
   * redirect. These correspond to performing the configured action (from
   * admin/structure/fillpdf/%), sending the PDF to the user's browser, saving it
   * to a file, and saving it to a file and then redirecting the user's browser to
   * the saved file.
   * @param array|\Drupal\fillpdf\Controller\If $options If set, this function will always end the request by
   * sending the filled PDF to the user's browser.
   */
  protected function handlePopulatedPdf(FillPdfFormInterface $fillpdf_form, $pdf_data, array $token_objects, $action = 'download', array $options = []) {
    // TODO: Convert rest of this function.
    $force_download = FALSE;
    if (!empty($option['force_download'])) {
      $force_download = TRUE;
    }

    if (in_array($action, [
        'default',
        'download',
        'save',
        'redirect'
      ]) === FALSE
    ) {
      // Do nothing if the function is called with an invalid action.
      return;
    }
    // Generate the filename of downloaded PDF from title of the PDF set in
    // admin/structure/fillpdf/%fid
    $output_name = $this->buildFilename($fillpdf_form->title->value, $token_objects);

    // Now that we have a filename, we can build the response we're most likely
    // to deliver. Content-Disposition is set further down in the actual
    // download case. If it turns out to be a redirect, then we replace this
    // variable with a RedirectResponse.
    $response = new Response($pdf_data);

    if ($action == 'default') {
      // Determine the default action, then re-set $action to that.
      if (empty($fillpdf_form->destination_path) === FALSE) {
        $action = 'save';
      }
      else {
        $action = 'download';
      }
    }

    // Initialize variable containing whether or not we send the user's browser to
    // the saved PDF after saving it (if we are)
    $redirect_to_file = FALSE;

    // Get a load of this switch...they all just fall through!
    switch ($action) {
      case 'redirect':
        $redirect_to_file = $fillpdf_form->destination_redirect;
      case 'save':
        fillpdf_save_to_file($fillpdf_form, $pdf_data, $token_objects, $output_name, !$options, $redirect_to_file);
      // FillPDF classic!
      case 'download':
        $disposition = $response->headers->makeDisposition(
          ResponseHeaderBag::DISPOSITION_ATTACHMENT,
          $output_name
        );
        $response->headers->set('Content-Disposition', $disposition);
        break;
    }

    return $response;
  }

  public function buildFilename($original, array $token_objects) {
    // Replace tokens *before* sanitization
    if (!empty($token_objects)) {
      $original = token_replace($original, $token_objects);
    }

    $output_name = str_replace(' ', '_', $original);
    $output_name = preg_replace('/\.pdf$/i', '', $output_name);
    $output_name = preg_replace('/[^a-zA-Z0-9_.-]+/', '', $output_name) . '.pdf';

    return $output_name;
  }

}
