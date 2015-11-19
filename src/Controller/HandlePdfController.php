<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Controller\HandlePdfController.
 */

namespace Drupal\fillpdf\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Component\Helper\FillPdfMappingHelper;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\EntityHelper;
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

  /** @var EntityHelper */
  protected $entityHelper;

  /** @var FillPdfContextManagerInterface $contextManager */
  protected $contextManager;

  /** @var TokenResolverInterface */
  protected $tokenResolver;

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, EntityHelper $entity_helper, TokenResolverInterface $token_resolver, RequestStack $request_stack, FillPdfBackendManager $backend_manager, FillPdfActionPluginManager $action_manager) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->tokenResolver = $token_resolver;
    $this->requestStack = $request_stack;
    $this->backendManager = $backend_manager;
    $this->actionManager = $action_manager;
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.link_manipulator'),
      $container->get('fillpdf.context_manager'),
      $container->get('fillpdf.entity_helper'),
      $container->get('fillpdf.token_resolver'),
      $container->get('request_stack'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('plugin.manager.fillpdf_action.processor'),
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

    $fields = $this->entityHelper->getFormFields($fillpdf_form);

    // Populate entities array based on what user passed in
    $entities = $this->contextManager->loadEntities($context);

    $field_mapping = [
      'fields' => [],
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
        // Get image fields attached to the entity and derive their token names based on the entity types we are working with at the moment.
        $fill_pattern = $field->value->value;
        $is_image_token = FALSE;
        $transform_string = TRUE;
        foreach ($entities as $entity_type => $entities_of_that_type) {
          $lifo_entities = array_reverse($entities_of_that_type);
          /**
           * @var string $entity_id
           * @var EntityInterface $entity
           */
          foreach ($lifo_entities as $entity_id => $entity) {
            if (method_exists($entity, 'getFields')) {
              /**
               * @var ContentEntityInterface $entity
               * @var string $field_name
               * @var FieldDefinitionInterface $field_definition
               */
              foreach ($entity->getFields() as $field_name => $field_data) {
                $field_definition = $field_data->getFieldDefinition();
                if ($field_definition->getType() === 'image') {
                  if ($fill_pattern === "[{$entity_type}:{$field_name}]") {
                    // It's a match!
                    $is_image_token = TRUE;
                    if (count($entity->{$field_name})) {
                      /** @var FileInterface $image_file */
                      $image_file = File::load($entity->{$field_name}->target_id);
                      $image_path = $image_file->getFileUri();
                      $mapped_fields[$pdf_key] = "{image}{$image_path}";
                      $image_path_info = pathinfo($image_path);
                      // Store the image data to transmit to the remote service if necessary
                      $file_data = file_get_contents($image_path);
                      if ($file_data) {
                        $image_data[$pdf_key] = [
                          'data' => base64_encode($file_data),
                          'filenamehash' => md5($image_path_info['filename']) . '.' . $image_path_info['extension'],
                        ];
                      }
                    }
                  }
                }
              }
            }
          }
        }

        if (!$is_image_token) {
          $replaced_string = $this->tokenResolver->replace($fill_pattern, $entities);

          // Apply field transformations.
          // Replace <br /> occurrences with newlines
          $replaced_string = preg_replace('|<br />|', '
', $replaced_string);

          $form_replacements = FillPdfMappingHelper::parseReplacements($fillpdf_form->replacements->value);
          $field_replacements = FillPdfMappingHelper::parseReplacements($field->replacements->value);

          $replaced_string = FillPdfMappingHelper::transformString($replaced_string, $form_replacements, $field_replacements);

          $mapped_fields[$pdf_key] = $replaced_string;
        }
      }
    }

    $title_pattern = $fillpdf_form->title->value;
    // Generate the filename of downloaded PDF from title of the PDF set in
    // admin/structure/fillpdf/%fid
    $context['filename'] = $this->buildFilename($title_pattern, $entities);

    $populated_pdf = $backend->populateWithFieldData($fillpdf_form, $field_mapping, $context);

    // @todo: When Rules integration ported, emit an event or whatever.

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
   * @param array $entities
   *   An array of objects to be used in replacing tokens.
   *   Here, specifically, it's for generating the filename of the handled PDF.
   * @return NULL|\Symfony\Component\HttpFoundation\Response
   */
  protected function handlePopulatedPdf(FillPdfFormInterface $fillpdf_form, $pdf_data, $context, array $entities) {
    $force_download = FALSE;
    if (!empty($context['force_download'])) {
      $force_download = TRUE;
    }

    $output_name = $context['filename'];

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
      'token_objects' => $entities,
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

  protected function buildFilename($original, array $entities) {
    // Replace tokens *before* sanitization
    if (count($entities)) {
      $original = $this->tokenResolver->replace($original, $entities);
    }

    $output_name = str_replace(' ', '_', $original);
    $output_name = preg_replace('/\.pdf$/i', '', $output_name);
    $output_name = preg_replace('/[^a-zA-Z0-9_.-]+/', '', $output_name) . '.pdf';

    return $output_name;
  }

}
