<?php

/**
 * @file
 * Contains Drupal\fillpdf\Annotation\FillPdfActionPlugin.
 */

namespace Drupal\fillpdf\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FillPDF action plugin item annotation object.
 *
 * @see \Drupal\fillpdf\Plugin\FillPdfActionPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class FillPdfActionPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
