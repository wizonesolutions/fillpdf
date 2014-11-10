<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Service\FillPdfLinkManipulator.
  */

namespace Drupal\fillpdf\Service;

use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Drupal\fillpdf\Request;

class FillPdfLinkManipulator implements FillPdfLinkManipulatorInterface {

  /**
   * @param Request $request The request containing the query string to parse.
   * @return array
   */
  public function parseLink(Request $request) {
    // TODO: Implement parseLink() method.
  }

  /**
   * @param array $parameters
   *   The array of parameters to be converted into a
   *   URL and query string.
   * @return string
   */
  public function generateLink(array $parameters) {
    // TODO: Implement generateLink() method.
  }

}
