<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfBackendManager.
  */


namespace Drupal\fillpdf;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class FillPdfBackendManager extends DefaultPluginManager implements FillPdfBackendManagerInterface {

  /**
   * Constructs a FillPdfBackendManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // @todo: Add a future FillPdfBackendPluginInterface to this?
    parent::__construct('Plugin/FillPdfBackend', $namespaces, $module_handler);
    $this->alterInfo('fillpdf_backend_info');
    $this->setCacheBackend($cache_backend, 'fillpdf_backend_info_plugins');
  }

}
