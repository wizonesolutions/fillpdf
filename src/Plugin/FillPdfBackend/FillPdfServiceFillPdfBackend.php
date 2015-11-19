<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Plugin\FillPdfBackend\FillPdfServiceFillPdfBackend.
 */

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;

/**
 * @Plugin(
 *   id = "fillpdf_service",
 *   label = @Translation("FillPDF Service")
 * )
 */
class FillPdfServiceFillPdfBackend implements FillPdfBackendPluginInterface {
  /** @var string $fillPdfServiceEndpoint */
  protected $fillPdfServiceEndpoint;

  // @todo: Use PluginBase's $this->configuration after adding a FillPdfBackendBase class.
  /** @var array $config */
  protected $config;

  public function __construct(array $config) {
    // TODO: Remove hardcoding.
    $this->config = $config;
    $this->fillPdfServiceEndpoint = "{$this->config['remote_protocol']}://{$this->config['remote_endpoint']}";
  }

  /**
   * @inheritdoc
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    /** @var FileInterface $file */
    $file = File::load($fillpdf_form->file->target_id);
    $content = file_get_contents($file->getFileUri());

    $result = $this->xmlRpcRequest('parse_pdf_fields', base64_encode($content));

    if ($result->error == TRUE) {
      // @todo: Throw an exception, log a message etc.
      return [];
    } // after setting error message

    $fields = $result->data;

    return $fields;
  }

  /**
   * Make an XML_RPC request.
   *
   * @param $method
   * @return stdClass
   */
  protected function xmlRpcRequest($method/** $args */) {
    $url = $this->fillPdfServiceEndpoint;
    $args = func_get_args();

    // Fix up the array for Drupal 7 xmlrpc() function style
    $args = [$args[0] => array_slice($args, 1)];
    $result = xmlrpc($url, $args);

    $ret = new \stdClass;

    if (isset($result['error'])) {
      drupal_set_message($result['error'], 'error');
      $ret->error = TRUE;
    }
    elseif ($result == FALSE || xmlrpc_error()) {
      $error = xmlrpc_error();
      $ret->error = TRUE;
      drupal_set_message(t('There was a problem contacting the FillPDF service.
      It may be down, or you may not have internet access. [ERROR @code: @message]',
        ['@code' => $error->code, '@message' => $error->message]), 'error');
    }
    else {
      $ret->data = $result['data'];
      $ret->error = FALSE;
    }
    return $ret;
  }

  /**
   * @inheritdoc
   */
  public function populateWithFieldData(FillPdfFormInterface $pdf_form, array $field_mapping, array $context) {
    /** @var FileInterface $original_file */
    $original_file = File::load($pdf_form->file->target_id);
    $original_pdf = file_get_contents($original_file->getFileUri());
    $api_key = $this->config['fillpdf_service_api_key'];

    $result = $this->xmlRpcRequest('merge_pdf_v3', base64_encode($original_pdf), $field_mapping['fields'], $api_key, $context['flatten'], $field_mapping['images']);

    $populated_pdf = base64_decode($result->data);
    return $populated_pdf;
  }

}
