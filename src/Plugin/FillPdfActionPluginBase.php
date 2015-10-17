<?php

/**
 * @file
 * Contains Drupal\fillpdf\Plugin\FillPdfActionPluginBase.
 */

namespace Drupal\fillpdf\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for FillPDF action plugins.
 */
abstract class FillPdfActionPluginBase extends PluginBase implements FillPdfActionPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public function defaultConfiguration() {
    return [];
  }

  public function calculateDependencies() {
    return [];
  }

  public function getConfiguration() {
    return $this->configuration;
  }

  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

}
