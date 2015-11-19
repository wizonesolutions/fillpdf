<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfOverviewForm.
 */
namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\Entity\FillPdfFormField;
use Drupal\fillpdf\FillPdfBackendManager;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FillPdfOverviewForm extends FillPdfAdminFormBase {
  /**
   * The backend manager (finds the filling plugin the user selected).
   *
   * @var FillPdfBackendManager
   */
  protected $backendManager;

  /** @var ModuleHandlerInterface $module_handler */
  protected $moduleHandler;

  /** @var AccountInterface $current_user */
  protected $currentUser;

  /** @var QueryFactory $entityQuery */
  protected $entityQuery;

  /** @var FileSystemInterface $fileSystem */
  protected $fileSystem;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'fillpdf_forms_admin';
  }

  public function __construct(ModuleHandlerInterface $module_handler, FillPdfBackendManager $backend_manager, AccountInterface $current_user, QueryFactory $entity_query, FileSystemInterface $file_system) {
    parent::__construct();
    $this->backendManager = $backend_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->entityQuery = $entity_query;
    $this->fileSystem = $file_system;
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      // Load the plugin manager.
      $container->get('plugin.manager.fillpdf_backend'),
      $container->get('current_user'),
      $container->get('entity.query'),
      $container->get('file_system')
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
    // @todo: convert to OOP
    $form['existing_forms'] = views_embed_view('fillpdf_forms', 'block_1');

    $config = $this->config('fillpdf.settings');
    // Only show PDF upload form if fillpdf is configured.
    if ($config->get('backend')) {
      // If using FillPDF Service, ensure XML-RPC module is present.
      if ($config->get('backend') !== 'fillpdf_service' || $this->moduleHandler->moduleExists('xmlrpc')) {
        $form['upload_pdf'] = [
          '#type' => 'file',
          '#title' => 'Upload',
          '#description' => 'Upload a PDF template to create a new form',
        ];

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Upload'),
          '#weight' => 15,
        ];
      }
      else {
        drupal_set_message($this->t('You must install the <a href="@xmlrpc">contributed XML-RPC module</a> in order to use FillPDF Service as your PDF-filling method.', ['@xmlrpc' => Url::fromUri('https://drupal.org/project/xmlrpc')->toString()]), 'error');
      }
    }
    else {
      $form['message'] = [
        '#markup' => '<p>' . $this->t('Before you can upload PDF files, you must !link.', ['!link' => $this->l($this->t('configure FillPDF'), Url::fromRoute('fillpdf.settings'))]) . '</p>',
      ];
      drupal_set_message($this->t('FillPDF is not configured.'), 'error');
    }
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_upload = $this->getRequest()->files->get('files[upload_pdf]', NULL, TRUE);
    /**
     * @var $file_upload UploadedFile
     */
    if ($file_upload && $file_upload->isValid()) {
      // Move it to somewhere we know.
      $uploaded_filename = $file_upload->getClientOriginalName();

      // Ensure the destination is unique; we deliberately use managed files,
      // but they are keyed on file URI, so we can't save the same one twice.
      $scheme = $this->config('fillpdf.settings')->get('scheme');
      $destination = file_destination(FillPdf::buildFileUri($scheme, 'fillpdf/' . $uploaded_filename), FILE_EXISTS_RENAME);

      // Ensure our directory exists.
      $fillpdf_directory = FillPdf::buildFileUri($scheme, 'fillpdf');
      $directory_exists = file_prepare_directory($fillpdf_directory, FILE_CREATE_DIRECTORY + FILE_MODIFY_PERMISSIONS);

      if ($directory_exists) {
        $file_moved = $this->fileSystem->moveUploadedFile($file_upload->getRealPath(), $destination);

        if ($file_moved) {
          // Create a File object from the uploaded file.
          $new_file = File::create([
            'uri' => $destination,
            'uid' => $this->currentUser()->id(),
          ]);

          $errors = file_validate_extensions($new_file, 'pdf');

          if (count($errors)) {
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
    $file->setPermanent();
    $file->save(); // Save the file so we can get an fid

    $fillpdf_form = FillPdfForm::create([
      'file' => $file,
      'title' => $file->filename,
      'scheme' => $this->config('fillpdf.settings')->get('scheme'),
    ]);

    // Save PDF configuration before parsing. We'll add a button to let them attempt
    // re-parsing if it fails.
    $fillpdf_form->save();
    $fid = $fillpdf_form->id();

    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('backend');
    /** @var FillPdfBackendPluginInterface $backend */
    $backend = $this->backendManager->createInstance($fillpdf_service, $config->get());

    // Attempt to parse the fields in the PDF.
    $fields = $backend->parse($fillpdf_form);

    $form_fields = [];
    foreach ((array) $fields as $key => $arr) {
      if ($arr['type']) { // Don't store "container" fields
        $arr['name'] = str_replace('&#0;', '', $arr['name']); // pdftk sometimes inserts random &#0; markers - strip these out. NOTE: This may break forms that actually DO contain this pattern, but 99%-of-the-time functionality is better than merge failing due to improper parsing.
        $field = FillPdfFormField::create(
          [
            'fillpdf_form' => $fillpdf_form,
            'pdf_key' => $arr['name'],
            'value' => '',
          ]
        );

        $form_fields[] = $field;
      }
    }

    // Save the fields that were parsed out (if any). If none were, set a
    // warning message telling the user that.
    foreach ($form_fields as $fillpdf_form_field) {
      /** @var FillPdfFormField $fillpdf_form_field */
      $fillpdf_form_field->save();
    }

    if (count($form_fields) === 0) {
      drupal_set_message($this->t('No fields detected in PDF. Are you sure it contains editable fields?'), 'warning');
    }

    $form_state->setRedirect('entity.fillpdf_form.edit_form', ['fillpdf_form' => $fid]);
  }
}
