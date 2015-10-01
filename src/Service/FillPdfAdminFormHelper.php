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

}
