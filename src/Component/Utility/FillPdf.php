<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Component\Utility\FillPdf.
 */
namespace Drupal\fillpdf\Component\Utility;
use Drupal\views\Views;
use \Symfony\Component\Process\Process;

class FillPdf {

  public static function checkPdftkPath($pdftk_path = '') {
    // An empty value means we should leave it to the PATH.
    if (empty($pdftk_path)) {
      $pdftk_path = 'pdftk';
    }
    $status = NULL;
    $process = new Process($pdftk_path);
    $process->run();

    if (in_array($process->getExitCode(), [126, 127])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Correctly embed a View with arguments. views_embed_view() does not
   * zero-index.
   *
   * @param $view_name
   * @param string $display_id
   */
  public static function embedView($name, $display_id = 'default') {
    $args = func_get_args();
    // Remove $name and $display_id from the arguments.
    unset($args[0], $args[1]);

    $args = array_values($args);

    $view = Views::getView($name);
    if (!$view || !$view->access($display_id)) {
      return NULL;
    }

    return $view->preview($display_id, $args);
  }

  /**
   * Constructs a URI to FillPDF's default files location given a relative path.
   */
  public static function buildFileUri($scheme, $path) {
    $uri = $scheme . '://' . $path;
    return file_stream_wrapper_uri_normalize($uri);
  }

}
