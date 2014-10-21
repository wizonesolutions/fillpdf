<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Component\Utility\Fillpdf.
 */
namespace Drupal\fillpdf\Component\Utility;
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

    if (in_array($process->getExitCode(), array(126, 127))) {
      return FALSE;
    }
    return TRUE;
  }
}