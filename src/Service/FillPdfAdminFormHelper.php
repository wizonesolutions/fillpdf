<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Service\FillPdfAdminFormHelper.
 */

namespace Drupal\fillpdf\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;

class FillPdfAdminFormHelper implements FillPdfAdminFormHelperInterface {

  /** @var ModuleHandlerInterface $module_handler */
  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminTokenForm() {
    return [
      '#theme' => 'token_tree',
      '#token_types' => 'all',
      '#global_types' => TRUE,
    ];
  }

  /**
   * Returns acceptable file scheme options.
   *
   * Suitable for use with FAPI radio buttons.
   *
   * @return array
   */
  public static function schemeOptions() {
    return [
      'private' => t('Private files'),
      'public' => t('Public files'),
    ];
  }

  public static function getReplacementsDescription() {
    return t('<p>Tokens, such as those from CCK, sometimes output values that need additional
  processing prior to being sent to the PDF. A common example is when a key within a CCK <em>Allowed values</em>
  configuration does not match the field name or option value in the PDF that you would like to be selected but you
  do not want to change the <em>Allowed values</em> key.</p><p>This field will replace any matching values with the
  replacements you specify. Specify <strong>one replacement per line</strong> in the format
  <em>original value|replacement value</em>. For example, <em>yes|Y</em> will fill the PDF with
  <strong><em>Y</em></strong> anywhere that <strong><em>yes</em></strong> would have originally
  been used. <p>Note that omitting the <em>replacement value</em> will replace <em>original value</em>
  with a blank, essentially erasing it.</p>');
  }
}
