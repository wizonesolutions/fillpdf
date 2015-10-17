<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Plugin\FillPdfActionPlugin\FillPdfDownloadAction.
  */

namespace Drupal\fillpdf\Plugin\FillPdfActionPlugin;

use Drupal\Core\Annotation\Translation;
use Drupal\fillpdf\Annotation\FillPdfActionPlugin;
use Drupal\fillpdf\Plugin\FillPdfActionPluginBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class FillPdfDownloadAction
 * @package Drupal\fillpdf\Plugin\FillPdfActionPlugin
 *
 * @FillPdfActionPlugin(
 *   id = "download",
 *   label = @Translation("Download PDF")
 * )
 */
class FillPdfDownloadAction extends FillPdfActionPluginBase {

  public function execute() {
    $response = new Response($this->configuration['data']);

    // This ensures that the browser serves the file as a download.
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $this->configuration['generated_filename']
    );
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

}
