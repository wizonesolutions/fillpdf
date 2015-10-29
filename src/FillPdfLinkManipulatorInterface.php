<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfLinkManipulatorInterface.
  */

namespace Drupal\fillpdf;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface to allow parsing and building FillPDF Links.
 *
 * A guideline for functionality is that calling generateLink on the result
 * of parseRequest should return a string that would parse the same way as the
 * original one.
 */
interface FillPdfLinkManipulatorInterface {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request The request
   *  containing the query string to parse.
   * @return array
   */
  public function parseRequest(Request $request);

  /**
   * @param \Drupal\Core\Url $link
   *   The valid URL containing the FillPDF generation metadata.
   *   e.g. http://example.com/fillpdf?entities[]=node:1&entities[]=contact:7
   * @return mixed
   */
  public function parseLink(Url $link);

  /**
   * @param array $parameters
   *   The array of parameters to be converted into a
   *   URL and query string.
   * @return \Drupal\Core\Url
   */
  public function generateLink(array $parameters);

}
