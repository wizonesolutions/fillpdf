<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Controller\HandlePdfController.
  */

namespace Drupal\fillpdf\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
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

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, RequestStack $request_stack, FillPdfBackendManager $backend_manager, QueryFactory $entity_query) {
    $this->linkManipulator = $link_manipulator;
    $this->requestStack = $request_stack;
    $this->backendManager = $backend_manager;
    $this->entityQuery = $entity_query;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.link_manipulator'),
      $container->get('request_stack'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('entity.query')
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
        ->execute()
    );

    $populated_pdf = $backend->populateWithFieldData($fillpdf_form, $fields, $context);

    // @todo: When Rules integration ported, emit an event or whatever.

    // TODO: Take the appropriate action on the PDF.
    $this->handlePopulatedPdf($fillpdf_form, $populated_pdf, array());
  }

  /**
   * @todo: Split this up better. There are a few things happening here. 1) We look at the function arguments to determine the action to take. But there's also the concept of a default action. In that case, we have to look at the $context array. 2) The actual handling code for the action, e.g. transmitting a download and saving a file, optionally redirecting to a file. These should be usable at will, albeit specific to routes.
   *
   * Figure out what to do with the PDF and do it.
   *
   * @return Nothing.
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
  protected function handlePopulatedPdf(FillPdfFormInterface $fillpdf_form, $pdf_data, array $token_objects, $action = 'download', array $options = array()) {
    // TODO: Convert rest of this function.
    $force_download = FALSE;
    if (!empty($option['force_download'])) {
      $force_download = TRUE;
    }

    if (in_array($action, array('default', 'download', 'save', 'redirect')) === FALSE) {
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
//        drupal_add_http_header("Pragma", "public");
//        drupal_add_http_header('Expires', 0);
//        drupal_add_http_header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
//        drupal_add_http_header('Content-type', 'application-download');
//        // This must be strlen(), not drupal_strlen() because the length in bytes,
//        // not in characters, is what is needed here.
//        drupal_add_http_header('Content-Length', strlen($pdf_data));
//        drupal_add_http_header('Content-disposition', 'attachment; filename="' . $output_name . '"');
//        drupal_add_http_header('Content-Transfer-Encoding', 'binary');
//        echo $pdf_data;
//        drupal_exit();
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
