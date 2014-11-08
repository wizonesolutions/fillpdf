<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfAdminFormBase.
 */

namespace Drupal\fillpdf\Form;
use Drupal\Core\Form\FormBase;

abstract class FillPdfAdminFormBase extends FormBase {
  public $REPLACEMENTS_DESCRIPTION;

  public function __construct() {
    $this->REPLACEMENTS_DESCRIPTION = $this->t("<p>Tokens, such as those from CCK, sometimes output values that need additional
      processing prior to being sent to the PDF. A common example is when a key within a CCK <em>Allowed values</em>
      configuration does not match the field name or option value in the PDF that you would like to be selected but you
      do not want to change the <em>Allowed values</em> key.</p><p>This field will replace any matching values with the
      replacements you specify. Specify <strong>one replacement per line</strong> in the format
      <em>original value|replacement value</em>. For example, <em>yes|Y</em> will fill the PDF with
      <strong><em>Y</em></strong> anywhere that <strong><em>yes</em></strong> would have originally
      been used. <p>Note that omitting the <em>replacement value</em> will replace <em>original value</em>
      with a blank, essentially erasing it.</p>");
  }
}
