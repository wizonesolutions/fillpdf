<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Plugin\FillPdfBackend\LocalFillPdfBackend.
 */

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Plugin(
 *   id = "local",
 *   label = @Translation("Locally-installed PHP/JavaBridge")
 * )
 */
class LocalFillPdfBackend implements FillPdfBackendPluginInterface, ContainerFactoryPluginInterface {
  /** @var array $configuration */
  protected $configuration;

  /** @var \Drupal\Core\File\FileSystem */
  protected $fileSystem;

  public function __construct(FileSystem $file_system, array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->fileSystem = $file_system;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('file_system'), $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritdoc
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    /** @var FileInterface $file */
    $file = File::load($fillpdf_form->file->target_id);
    $content = file_get_contents($file->getFileUri());

    $require = drupal_get_path('module', 'fillpdf') . '/lib/JavaBridge/java/Java.inc';
    require_once DRUPAL_ROOT . '/' . $require;
    try {
      $fillpdf = new \java('com.ocdevel.FillpdfService', base64_encode($content), 'bytes');
      $fields = java_values($fillpdf->parse());
    }
    catch (\JavaException $e) {
      drupal_set_message(java_truncate((string) $e), 'error');
    }

    return $fields;
  }

  /**
   * @inheritdoc
   */
  public function populateWithFieldData(FillPdfFormInterface $pdf_form, array $field_mapping, array $context) {
    /** @var FileInterface $original_file */
    $original_file = File::load($pdf_form->file->target_id);
    $pdf_data = file_get_contents($original_file->getFileUri());
    $fields = $field_mapping['fields'];

    $require = drupal_get_path('module', 'fillpdf') . '/lib/JavaBridge/java/Java.inc';
    require_once DRUPAL_ROOT . '/' . $require;
    try {
      $fillpdf = new \java('com.ocdevel.FillpdfService', base64_encode($pdf_data), 'bytes');
      foreach ($fields as $key => $field) {
        if (substr($field, 0, 7) == '{image}') {
          // Remove {image} marker.
          $image_filepath = substr($field, 7);
          $image_realpath = $this->fileSystem->realpath($image_filepath);
          $fillpdf->image($key, $image_realpath, 'file');
        }
        else {
          $fillpdf->text($key, $field);
        }
      }
    }
    catch (\JavaException $e) {
      drupal_set_message(java_truncate((string) $e), 'error');
      return NULL;
    }
    try {
      if ($context['flatten']) {
        $populated_pdf = java_values(base64_decode($fillpdf->toByteArray()));
      }
      else {
        $populated_pdf = java_values(base64_decode($fillpdf->toByteArrayUnflattened()));
      }
    }
    catch (\JavaException $e) {
      drupal_set_message(java_truncate((string) $e), 'error');
      return NULL;
    }

    return $populated_pdf;
  }

}
