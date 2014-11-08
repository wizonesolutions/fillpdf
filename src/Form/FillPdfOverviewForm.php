<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfOverviewForm.
 */
namespace Drupal\fillpdf\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\FillPdfBackendManagerInterface;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FillPdfOverviewForm extends FillPdfAdminFormBase {
  /**
   * The backend manager (finds the filling plugin the user selected).
   *
   * @var \Drupal\fillpdf\FillPdfBackendManagerInterface
   */
  protected $backendManager;

  /** @var ModuleHandlerInterface $module_handler */
  protected $module_handler;

  /** @var AccountInterface $current_user */
  protected $current_user;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'fillpdf_forms_admin';
  }

  public function __construct(ModuleHandlerInterface $module_handler, FillPdfBackendManagerInterface $backend_manager, AccountInterface $current_user) {
    parent::__construct();
    $this->backendManager = $backend_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the plugin manager.
      $container->get('module_handler'),
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('current_user')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo: Convert to using data from entity or something.
//    $result = db_query("SELECT admin_title, title, url, fid FROM {fillpdf_forms} ORDER BY admin_title");
    $result = array();
    $header = array(
      $this->t('Administrative title'),
      $this->t('Title'),
     $this->t('Location'),
      array(
        'data' => t('Operations'),
        'colspan' => '4',
      ),
    );
    $rows = array();
    $form['existing_forms'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#attributes' => array('id' => 'fillpdf'),
      '#empty' => $this->t('No content available.'),
    );

    foreach ($result as $pdf_form) {
      $row = array(
        String::checkPlain($pdf_form->admin_title),
        String::checkPlain($pdf_form->title),
        String::checkPlain($pdf_form->url),
        // @todo: Convert to routes.
        $this->l($this->t('Edit'), "admin/structure/fillpdf/$pdf_form->fid"),
        $this->l($this->t('Delete'), "admin/structure/fillpdf/$pdf_form->fid/delete"),
        $this->l($this->t('Export field mappings'), "admin/structure/fillpdf/$pdf_form->fid/export"),
        $this->l($this->t('Import field mappings'), "admin/structure/fillpdf/$pdf_form->fid/import"),
      );
      // @todo: Convert to adding the rows to the table like on
      $rows[] = $row;
    }

    $config = $this->config('fillpdf.settings');
    // Only show PDF upload form if fillpdf is configured.
    if ($config->get('fillpdf_service_backend')) {
      // If using FillPDF Service, ensure XML-RPC module is present.
      if ($config->get('fillpdf_service_backend') != 'fillpdf_service' || $this->moduleHandler->moduleExists('xmlrpc')) {
        $form['upload_pdf'] = array(
          '#type' => 'file',
          '#title' => 'Upload',
          '#description' => 'Upload a PDF template to create a new form',
        );

        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Upload'),
          '#weight' => 15,
        );
      }
      else {
        drupal_set_message($this->t('You must install the <a href="@xmlrpc">contributed XML-RPC module</a> in order to use FillPDF Service as your PDF-filling method.', array('@xmlrpc' => Url::fromUri('https://drupal.org/project/xmlrpc'))), 'error');
      }
    }
    else {
      $form['message'] = array(
        '#markup' => '<p>' . $this->t('Before you can upload PDF files, you must !link.', array('!link' => $this->l($this->t('configure FillPDF'), Url::fromRoute('fillpdf.settings')))) . '</p>',
      );
      drupal_set_message($this->t('FillPDF is not configured.'), 'error');
    }
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_upload = $this->getRequest()->files->get('files[upload_pdf]', NULL, TRUE);
    /**
     * @var $file_upload \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    if ($file_upload && $file_upload->isValid()) {
      // Move it to somewhere we know.
      $uploaded_filename = $file_upload->getClientOriginalName();

      // Ensure the destination is unique; we deliberately use managed files,
      // but they are keyed on file URI, so we can't save the same one twice.
      $destination = file_destination(file_build_uri('fillpdf/' . $uploaded_filename), FILE_EXISTS_RENAME);

      // Ensure our directory exists.
      $fillpdf_directory = file_build_uri('fillpdf');
      $directory_exists = file_prepare_directory($fillpdf_directory, FILE_CREATE_DIRECTORY + FILE_MODIFY_PERMISSIONS);

      if ($directory_exists) {
        $file_moved = drupal_move_uploaded_file($file_upload->getRealPath(), $destination);

        if ($file_moved) {
          // Create a File object from the uploaded file.
          $new_file = File::create(array(
            'uri' => $destination,
            'uid' => $this->currentUser()->id(),
          ));

          // TODO: test this
          $errors = file_validate_extensions($new_file, 'pdf');

          if (!empty($errors)) {
            $form_state->setErrorByName('upload_pdf', $this->t('Only PDF files are supported, and they must end in .pdf.'));
          }
          else {
            $form_state->setValue('upload_pdf', $new_file);
          }
        }
        else {
          $form_state->setErrorByName('upload_pdf', $this->t("Could not move your uploaded file from PHP's temporary location to Drupal file storage."));
        }
      }
      else {
        $form_state->setErrorByName('upload_pdf', $this->t('Could not automatically create the <em>fillpdf</em> subdirectory. Please create this manually before uploading your PDF form.'));
      }
    }
    else {
      $form_state->setErrorByName('upload_pdf', $this->t('Your PDF could not be uploaded. Did you select one?'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the file to get an fid, and then create a FillPdfForm record
    // based off that.
    /** @var \Drupal\file\FileInterface $file */
    $file = $form_state->getValue('upload_pdf');
    $file->save(); // Save the file so we can get an fid

    $fillpdf_form = FillPdfForm::create(array(
      'file' => $file,
      'title' => $file->filename,
    ));

    // Save PDF configuration before parsing. We'll add a button to let them attempt
    // re-parsing if it fails.
    $fillpdf_form->save();

    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('fillpdf_service_backend');

    try {
      /** @var FillPdfBackendPluginInterface $backend */
      $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());
    }
    catch (PluginNotFoundException $exception) {
      $backend = NULL;
    }

    if ($backend) {
      // Attempt to parse the fields in the PDF.
      $fields = $backend->parse($fillpdf_form);

      // Save the fields that were parsed out (if any). If none were, set a
      // warning message telling the user that.
      foreach ($fields as $fillpdf_form_field) {
        $fillpdf_form_field->save();
      }

      if (empty($fields)) {
        drupal_set_message($this->t("No fields detected in PDF. Are you sure it contains editable fields?"), 'warning');
      }
    }
  }
}