<?php

/**
 * @file
 * Contains \Drupal\fillpdf\OutputHandlerInterface.
 */

namespace Drupal\fillpdf;
use Drupal\file\FileInterface;

/**
 * Contains functions to standardize output handling for generated PDFs.
 *
 * @package Drupal\fillpdf
 */
interface OutputHandlerInterface {

  /**
   * @param array $context
   *   An array containing the following properties:
   *     form: The FillPdfForm object from which the PDF was generated.
   *     context: The FillPDF request context as returned by
   *       \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink().
   *     token_objects: The token data from which the PDF was generated.
   *     data: The populated PDF data itself.
   *     filename: The filename (not including path) with which
   *       the PDF should be presented.
   *
   * @param string $destination_path_override
   * @return bool|FileInterface
   */
  public function savePdfToFile(array $context, $destination_path_override = NULL);

}
