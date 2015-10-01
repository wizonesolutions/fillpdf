<?php

/**
 * @file
 * Contains Drupal\fillpdf\Plugin\FillPdfActionPluginManager.
 */

namespace Drupal\fillpdf\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the FillPDF action plugin plugin manager.
 */
class FillPdfActionPluginManager extends DefaultPluginManager {

  /**
   * Constructor for FillPdfActionPluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FillPdfActionPlugin', $namespaces, $module_handler, 'Drupal\fillpdf\Plugin\FillPdfActionPluginInterface', 'Drupal\fillpdf\Annotation\FillPdfActionPlugin');

    $this->alterInfo('fillpdf_fillpdf_action_info');
    $this->setCacheBackend($cache_backend, 'fillpdf_fillpdf_action_plugins');
  }

}
