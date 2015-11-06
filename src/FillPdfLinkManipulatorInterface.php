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
   * @todo: Move this elsewhere, maybe to that current_fillpdf_link service I was thinking of or whatever it was.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request The request
   *  containing the query string to parse.
   * @return array
   */
  public function parseRequest(Request $request);

  /**
   * @param \Drupal\Core\Url $link
   *   The valid URL containing the FillPDF generation metadata.
   *   e.g. http://example.com/fillpdf?entities[]=node:1&entities[]=contact:7
   * @todo: Document array. For now, see what FillPdfLinkManipulator::parseLink() does.
   * @see FillPdfLinkManipulator::parseLink()
   * @return array
   */
  public function parseLink(Url $link);

  /**
   * @param string $url
   * The root-relative FillPDF URL that would be used to generate the PDF.
   * e.g. /fillpdf?fid=1&entity_type=node&entity_id=1
   *
   * @return array
   */
  public function parseUrlString($url);

  /**
   * @param array $parameters
   *   The array of parameters to be converted into a
   *   URL and query string.
   * @return \Drupal\Core\Url
   */
  public function generateLink(array $parameters);

}
