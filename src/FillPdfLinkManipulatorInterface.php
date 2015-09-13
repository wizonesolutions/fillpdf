<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfLinkManipulatorInterface.
  */

namespace Drupal\fillpdf;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface to allow parsing and building FillPDF Links.
 *
 * A guideline for functionality is that calling generateLink on the result
 * of parseLink should return a string that would parse the same way as the
 * original one.
 */
interface FillPdfLinkManipulatorInterface {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request The request
   *  containing the query string to parse.
   * @return array
   *
   * @todo Should this actually take a \Drupal\Core\Url? Or should there be another method that does? What if another caller wants to parse an arbitrary URL?
   */
  public function parseLink(Request $request);

  /**
   * @param array $parameters
   *   The array of parameters to be converted into a
   *   URL and query string.
   * @return \Drupal\Core\Url
   */
  public function generateLink(array $parameters);

}
