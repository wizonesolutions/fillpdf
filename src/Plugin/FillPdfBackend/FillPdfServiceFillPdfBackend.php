<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Plugin\FillPdfBackend\FillPdfServiceFillPdfBackend.
  */

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Entity\FillPdfFormField;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Plugin(
 *   id = "fillpdf_service",
 *   label = @Translation("FillPDF Service")
 * )
 */
class FillPdfServiceFillPdfBackend implements FillPdfBackendPluginInterface {
  /** @var string $fillPdfServiceEndpoint */
  protected $fillPdfServiceEndpoint;

  /** @var array $config */
  protected $config;

  public function __construct(array $config) {
    // TODO: Remove hardcoding.
    $this->config = $config;
    $this->fillPdfServiceEndpoint = "{$this->config['fillpdf_remote_protocol']}://{$this->config['fillpdf_remote_endpoint']}";
  }

  /**
   * @inheritdoc
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    // @todo: Is there a better way to do this?
    $file = File::load($fillpdf_form->file->target_id);
    $content = file_get_contents($file->getFileUri());

    $result = $this->xmlRpcRequest('parse_pdf_fields', base64_encode($content));

    // TODO: Don't do magic error handling. Throw an exception and let the caller decide what to do. Make my own exception class (see PluginNotFoundException for inspiration).
//    if ($result->error == TRUE) {
//      drupal_goto('admin/structure/fillpdf');
//    } // after setting error message

    $fields = $result->data;

    $form_fields = array();

    foreach ((array) $fields as $key => $arr) {
      if ($arr['type']) { // Don't store "container" fields
        $arr['name'] = str_replace('&#0;', '', $arr['name']); // pdftk sometimes inserts random &#0; markers - strip these out. NOTE: This may break forms that actually DO contain this pattern, but 99%-of-the-time functionality is better than merge failing due to improper parsing.
        $field = FillPdfFormField::create(
          array(
            'fillpdf_form' => $fillpdf_form,
            'pdf_key' => $arr['name'],
            'value' => '',
          )
        );

        $form_fields[] = $field;
      }
    }

    return $form_fields;
  }

  /**
   * @inheritdoc
   */
  public function populateWithFieldData(FillPdfFormInterface $pdf_form, array $fields, array $context) {
    /** @var FileInterface $original_file */
    $original_file = File::load($pdf_form->file->target_id);
    $original_pdf = file_get_contents($original_file->getFileUri());

    // @todo: Actually write this
    $api_key = $this->config['fillpdf_api_key'];
    $field_mapping = array();
    // @todo: Image-filling support. Probably both the local and remote plugins
    // could extend the same class.
    $image_data = array();
    $result = $this->xmlRpcRequest('merge_pdf_v3', base64_encode($original_pdf), $field_mapping, $api_key, $context['flatten'], $image_data);
    // @todo: Error handling/exceptions
//    if ($result->error == TRUE) {
//      drupal_goto();
//    }

    $populated_pdf = base64_decode($result->data);
    return $populated_pdf;
  }


  /**
   * Make an XML_RPC request.
   *
   * @param $method
   * @return stdClass
   */
  protected function xmlRpcRequest($method /** $args */) {
    $url = $this->fillPdfServiceEndpoint;
    $args = func_get_args();

    // Fix up the array for Drupal 7 xmlrpc() function style
    $args = array($args[0] => array_slice($args, 1));
    $result = xmlrpc($url, $args);

    $ret = new \stdClass;

    // TODO: Exceptions, not error messages
    if (isset($result['error'])) {
//      drupal_set_message($result['error'], 'error');
      $ret->error = TRUE;
    }
    elseif ($result == FALSE || xmlrpc_error()) {
      $error = xmlrpc_error();
      $ret->error = TRUE;
//      drupal_set_message(t('There was a problem contacting the FillPDF service.
//      It may be down, or you may not have internet access. [ERROR @code: @message]',
//        array('@code' => $error->code, '@message' => $error->message)), 'error');
    }
    else {
      $ret->data = $result['data'];
      $ret->error = FALSE;
    }
    return $ret;
  }

}
