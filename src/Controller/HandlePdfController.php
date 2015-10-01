<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Controller\HandlePdfController.
 */

namespace Drupal\fillpdf\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Utility\Token;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\Entity\FillPdfFormField;
use Drupal\fillpdf\FillPdfBackendManager;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfContextManagerInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class HandlePdfController extends ControllerBase {

  /** @var FillPdfLinkManipulatorInterface $linkManipulator */
  protected $linkManipulator;

  /** @var RequestStack $requestStack */
  protected $requestStack;

  /** @var FillPdfBackendManager $backendManager */
  protected $backendManager;

  /** @var QueryFactory $entityQuery */
  protected $entityQuery;

  /** @var EntityManagerInterface $entityManager */
  protected $entityManager;

  /** @var Token $token */
  protected $token;

  /** @var FillPdfContextManagerInterface $contextManager */
  protected $contextManager;

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, RequestStack $request_stack, FillPdfBackendManager $backend_manager, Token $token, QueryFactory $entity_query, EntityManagerInterface $entity_manager) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
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
      $container->get('fillpdf.context_manager'),
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
    $fillpdf_service = $config->get('backend');

    // Load the backend plugin.
    /** @var FillPdfBackendPluginInterface $backend */
    $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());

    // @todo: Emit event (or call alter hook?) before populating PDF. fillpdf_merge_fields_alter -> should be renamed to fillpdf_populate_fields_alter

    // TODO: Extract the PDF parameters, and act accordingly - this is pretty much just calling FillPdfBackendPluginInterface::populateWithFieldData and then reading the FillPdfForm options to determine whether to serve as a file download or fill in the field data.
    /** @var FillPdfFormInterface $fillpdf_form */
    $fillpdf_form = FillPdfForm::load($context['fid']);
    if (!$fillpdf_form) {
      drupal_set_message($this->t('FillPDF Form (fid) not found in the system. Please check the value in your FillPDF Link.'), 'error');
      return new RedirectResponse('/');
    }

    $fields = FillPdfFormField::loadMultiple(
      $this->entityQuery->get('fillpdf_form_field')
        ->condition('fillpdf_form', $fillpdf_form->id())
        ->execute());

    // Populate entities array based on what user passed in
    $entities = $this->contextManager->loadEntities($context);

    $field_mapping = [
      'fields' => [],
      // @todo: Image-filling support. Probably both the local and remote plugins could extend the same class.
      'images' => [],
    ];

    $mapped_fields = &$field_mapping['fields'];
//    $image_data = &$field_mapping['images'];
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

    // Determine the appropriate action for the PDF.


    // TODO: figure out what to do about $token_objects. Should I make buildObjects manually re-run everything or just use the final entities passed of each type? Maybe just the latter, since that is what I do in
    return $this->handlePopulatedPdf($fillpdf_form, $populated_pdf, []);
  }

  /**
   * @todo: Split this up better. There are a few things happening here. 1) We look at the function arguments to determine the action to take. But there's also the concept of a default action. In that case, we have to look at the $context array. 2) The actual handling code for the action, e.g. transmitting a download and saving a file, optionally redirecting to a file. These should be usable at will, albeit specific to routes.
   *
   * Figure out what to do with the PDF and do it.
   *
   * @param FillPdfFormInterface $fillpdf_form
   *   An object containing the loaded record from {fillpdf_forms}.
   *
   * @param string $pdf_data
   *   A string containing the content of the merged PDF.
   *
   * @param array $token_objects
   *   An array of objects to be used in replacing tokens.
   *   Here, specifically, it's for generating the filename of the handled PDF.
   *
   * @param string $action
   *   One of the following keywords: default, download, save,
   *   redirect. These correspond to performing the configured action (from
   *   admin/structure/fillpdf/%), sending the PDF to the user's browser, saving it
   *   to a file, and saving it to a file and then redirecting the user's browser to
   *   the saved file.
   * @todo ^ Make this a plugin too
   *
   * @param array $options
   *   If set, this function will always end the request by
   *   sending the filled PDF to the user's browser.
   *
   * @return NULL|Response
   */
  protected function handlePopulatedPdf(FillPdfFormInterface $fillpdf_form, $pdf_data, array $token_objects, $action, array $options = []) {
    // TODO: Convert rest of this function.
    $force_download = FALSE;
    if (!empty($option['force_download'])) {
      $force_download = TRUE;
    }

    $valid_actions = [
      'default',
      'download',
      'save',
      'redirect'
    ];
    if (!in_array($action, $valid_actions)) {
      // Do nothing if the function is called with an invalid action.
      // TODO: Add an assertion here?
      return NULL;
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
        // TODO: Port this to use its own function that in itself will return a file, period. Then handle the redirect logic in the controller instead of as part of the save-to-file method. Also base it off the new code from Drupal 7
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
