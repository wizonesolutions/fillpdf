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
use Drupal\fillpdf\Plugin\FillPdfActionPlugin\FillPdfDownloadAction;
use Drupal\fillpdf\Plugin\FillPdfActionPluginInterface;
use Drupal\fillpdf\Plugin\FillPdfActionPluginManager;
use Drupal\fillpdf\TokenResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class HandlePdfController extends ControllerBase {

  /** @var FillPdfLinkManipulatorInterface $linkManipulator */
  protected $linkManipulator;

  /** @var RequestStack $requestStack */
  protected $requestStack;

  /** @var FillPdfBackendManager $backendManager */
  protected $backendManager;

  /** @var QueryFactory $entityQuery */
  protected $entityQuery;

  /** @var Token $token */
  protected $token;

  /** @var FillPdfContextManagerInterface $contextManager */
  protected $contextManager;

  /** @var TokenResolverInterface */
  protected $tokenResolver;

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, TokenResolverInterface $token_resolver, RequestStack $request_stack, FillPdfBackendManager $backend_manager, FillPdfActionPluginManager $action_manager, Token $token, QueryFactory $entity_query) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->tokenResolver = $token_resolver;
    $this->requestStack = $request_stack;
    $this->backendManager = $backend_manager;
    $this->actionManager = $action_manager;
    $this->token = $token;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.link_manipulator'),
      $container->get('fillpdf.context_manager'),
      $container->get('fillpdf.token_resolver'),
      $container->get('request_stack'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('plugin.manager.fillpdf_action.processor'),
      $container->get('token'),
      $container->get('entity.query')
    );
  }

  public function populatePdf() {
    $context = $this->linkManipulator->parseRequest($this->requestStack->getCurrentRequest());

    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('backend');

    // Load the backend plugin.
    /** @var FillPdfBackendPluginInterface $backend */
    $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());

    // @todo: Emit event (or call alter hook?) before populating PDF. fillpdf_merge_fields_alter -> should be renamed to fillpdf_populate_fields_alter

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
        $fill_pattern = $field->value->value;
        $replaced_string = $this->tokenResolver->replace($fill_pattern, $entities);

        $mapped_fields[$pdf_key] = $replaced_string;
      }
    }

    $populated_pdf = $backend->populateWithFieldData($fillpdf_form, $field_mapping, $context);

    // @todo: When Rules integration ported, emit an event or whatever.

    // TODO: figure out what to do about $token_objects. Should I make buildFilename manually re-run everything or just use the final entities passed of each type? Maybe just the latter, since that is what I do in D7. But it wouldn't be that hard to just use whatever helper function I make.
    $action_response =  $this->handlePopulatedPdf($fillpdf_form, $populated_pdf, $context, []);

    return $action_response;
  }

  /**
   * Figure out what to do with the PDF and do it.
   *
   * @param FillPdfFormInterface $fillpdf_form
   *   An object containing the loaded record from {fillpdf_forms}.
   *
   * @param string $pdf_data
   *   A string containing the content of the merged PDF.
   *
   * @param array $context
   *   The FillPDF request context as parsed by
   *   \Drupal\fillpdf\Service\LinkManipulator.
   *
   * @param array $token_objects
   *   An array of objects to be used in replacing tokens.
   *   Here, specifically, it's for generating the filename of the handled PDF.
   * @return NULL|\Symfony\Component\HttpFoundation\Response
   */
  protected function handlePopulatedPdf(FillPdfFormInterface $fillpdf_form, $pdf_data, $context, array $token_objects) {
    $force_download = FALSE;
    if (!empty($context['force_download'])) {
      $force_download = TRUE;
    }

    // Generate the filename of downloaded PDF from title of the PDF set in
    // admin/structure/fillpdf/%fid
    $output_name = $this->buildFilename($fillpdf_form->title->value, $token_objects);

    // Determine the appropriate action for the PDF.
    $destination_path_set = !empty($fillpdf_form->destination_path->value);
    $redirect = !empty($fillpdf_form->destination_redirect->value);
    if ($destination_path_set && !$redirect) {
      $action_plugin_id = 'save';
    }
    elseif ($destination_path_set && $redirect) {
      $action_plugin_id = 'redirect';
    }
    else {
      $action_plugin_id = 'download';
    }

    $action_configuration = [
      'form' => $fillpdf_form,
      'context' => $context,
      'token_objects' => $token_objects,
      'data' => $pdf_data,
      'filename' => $output_name,
    ];

    /** @var FillPdfActionPluginInterface $fillpdf_action */
    $fillpdf_action = $this->actionManager->createInstance($action_plugin_id, $action_configuration);
    $response = $fillpdf_action->execute();

    // If we are forcing a download, then manually get a Response from
    // the download action and return that. Side effects of other plugins will
    // still happen, obviously.
    if ($force_download) {
      /** @var FillPdfDownloadAction $download_action */
      $download_action = $this->actionManager
        ->createInstance('download', $action_configuration);
      $response = $download_action
        ->execute();
    }

    return $response;
  }

  public function buildFilename($original, array $token_objects) {
    // Replace tokens *before* sanitization
    if (count($token_objects)) {
      $original = $this->token->replace($original, $token_objects);
    }

    $output_name = str_replace(' ', '_', $original);
    $output_name = preg_replace('/\.pdf$/i', '', $output_name);
    $output_name = preg_replace('/[^a-zA-Z0-9_.-]+/', '', $output_name) . '.pdf';

    return $output_name;
  }

}
