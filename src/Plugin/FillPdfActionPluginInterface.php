<?php

/**
 * @file
 * Contains Drupal\fillpdf\Plugin\FillPdfActionPluginInterface.
 */

namespace Drupal\fillpdf\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines an interface for FillPDF action plugins.
 *
 * Action plugins should ultimately return a
 * \Symfony\Component\HttpFoundation\Response. They may provide additional
 * methods to provide callers with additional plugin-specific metadata.
 */
interface FillPdfActionPluginInterface extends PluginInspectionInterface {

  /**
   * Take action according to the plugin configuration. This will vary for each
   * action plugin, but it should do something with the PDF (e.g. prepare a
   * download response, save it to a file, etc.) and return an appropriate
   * Response (or subclass thereof) to the caller.
   *
   * @return Response
   * @todo Document exceptions thrown if something goes wrong.
   */
  public function execute();

}
