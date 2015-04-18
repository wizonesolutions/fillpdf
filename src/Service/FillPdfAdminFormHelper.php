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
    $token_types = array('node', 'webform-tokens', 'submission');

    // If not using Webform Rules, then show potential Webform Tokens
    // webform:-namespaced tokens.
    if ($this->moduleHandler->moduleExists('webform_rules') === FALSE) {
      $token_types[] = 'webform';
    }
    return array(
      '#theme' => 'token_tree',
      '#token_types' => $token_types,
      '#global_types' => FALSE,
    );
  }

}
