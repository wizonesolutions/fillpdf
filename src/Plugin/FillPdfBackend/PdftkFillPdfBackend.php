<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Plugin\FillPdfBackend\PdftkFillPdfBackend.
 */

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Plugin(
 *   id = "pdftk",
 *   label = @Translation("PDFtk"),
 * )
 */
class PdftkFillPdfBackend implements FillPdfBackendPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /** @var array $configuration */
  protected $configuration;

  /** @var \Drupal\Core\File\FileSystem */
  protected $fileSystem;

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  protected $configFactory;

  public function __construct(FileSystem $file_system, ConfigFactoryInterface $config, array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->fileSystem = $file_system;
    $this->configFactory = $config;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('file_system'), $container->get('config.factory'), $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritdoc
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    /** @var FileInterface $file */
    $file = File::load($fillpdf_form->file->target_id);
    $filename = $file->getFileUri();

    $path_to_pdftk = $this->getPdftkPath();
    $status = FillPdf::checkPdftkPath($path_to_pdftk);
    if ($status === FALSE) {
      drupal_set_message($this->t('pdftk not properly installed.'), 'error');
      return [];
    }

    // Use exec() to call pdftk (because it will be easier to go line-by-line parsing the output) and pass $content via stdin. Retrieve the fields with dump_data_fields.
    $output = [];
    exec($path_to_pdftk . ' ' . escapeshellarg($this->fileSystem->realpath($filename)) . ' dump_data_fields', $output, $status);
    if (count($output) === 0) {
      drupal_set_message($this->t('PDF does not contain fillable fields.'), 'warning');
      return [];
    }

    // Build a simple map of dump_data_fields keys to our own array keys
    $data_fields_map = [
      'FieldType' => 'type',
      'FieldName' => 'name',
      'FieldFlags' => 'flags',
      'FieldJustification' => 'justification',
    ];

    // Build the fields array
    $fields = [];
    $fieldindex = -1;
    foreach ($output as $line => $lineitem) {
      if ($lineitem == '---') {
        $fieldindex++;
        continue;
      }
      // Separate the data key from the data value
      $linedata = explode(':', $lineitem);
      if (in_array($linedata[0], array_keys($data_fields_map), NULL)) {
        $fields[$fieldindex][$data_fields_map[$linedata[0]]] = trim($linedata[1]);
      }
    }

    return $fields;
  }

  /**
   * @return array|mixed|null|string
   */
  protected function getPdftkPath() {
    $path_to_pdftk = $this->configFactory->get('fillpdf.settings')
      ->get('pdftk_path');

    if (empty($path_to_pdftk)) {
      $path_to_pdftk = 'pdftk';
      return $path_to_pdftk;
    }
    return $path_to_pdftk;
  }

  /**
   * @inheritdoc
   */
  public function populateWithFieldData(FillPdfFormInterface $pdf_form, array $field_mapping, array $context) {
    /** @var FileInterface $original_file */
    $original_file = File::load($pdf_form->file->target_id);
    $filename = $original_file->getFileUri();
    $fields = $field_mapping['fields'];

    module_load_include('inc', 'fillpdf', 'xfdf');
    $xfdfname = $filename . '.xfdf';
    $xfdf = create_xfdf(basename($xfdfname), $fields);
    // Generate the file
    $xfdffile = file_save_data($xfdf, $xfdfname, FILE_EXISTS_RENAME);

    // Now feed this to pdftk and save the result to a variable
    $path_to_pdftk = $this->getPdftkPath();
    ob_start();
    passthru($path_to_pdftk . ' ' . escapeshellarg($this->fileSystem->realpath($filename)) . ' fill_form ' . escapeshellarg($this->fileSystem->realpath($xfdffile->getFileUri())) . ' output - ' . ($context['flatten'] ? 'flatten ' : '') . 'drop_xfa');
    $data = ob_get_clean();
    if ($data === FALSE) {
      drupal_set_message($this->t('pdftk not properly installed. No PDF generated.'), 'error');
    }
    $xfdffile->delete();

    if ($data) {
      return $data;
    }

    return FALSE;
  }

}
