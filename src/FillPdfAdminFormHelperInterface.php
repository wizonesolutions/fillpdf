<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfAdminFormHelperInterface.
  */

namespace Drupal\fillpdf;

interface FillPdfAdminFormHelperInterface {

  /**
   * Returns the render array to show a token form with types supported by FillPDF.
   *
   * @return array
   */
  public function getAdminTokenForm();

}
