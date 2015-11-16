<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Component\Helper\FillPdfMappingHelper.
  */

namespace Drupal\fillpdf\Component\Helper;

class FillPdfMappingHelper {

  public static function parseReplacements($replacements_string) {
    if (empty($replacements_string) !== TRUE) {
      $standardized_replacements = str_replace(array("\r\n", "\r"), "\n", $replacements_string);
      $lines = explode("\n", $standardized_replacements);
      $return = array();
      foreach ($lines as $replacement) {
        if (!empty($replacement)) {
          $split = explode('|', $replacement);
          if (count($split) == 2) { // Sometimes it isn't; don't know why.
            $return[$split[0]] = preg_replace('|<br />|', '
', $split[1]);
          }
        }
      }
      return $return;
    }
    else {
      return array();
    }
  }

  /**
   * @param $value
   * The value to replace. Must match the key in a replacements field exactly.
   * @param $form_replacements
   * A list of replacements with form-level priority.
   * @param $field_replacements
   * A list of replacements with field-level priority. These have precedence.
   * @return string $value with any matching replacements applied.
   */
  public static function transformString($value, $form_replacements, $field_replacements) {
    if (empty($form_replacements) && empty($field_replacements)) {
      return $value;
    }
    elseif (!empty($field_replacements) && isset($field_replacements[$value])) {
      return $field_replacements[$value];
    }
    elseif (!empty($form_replacements) && isset($form_replacements[$value])) {
      return $form_replacements[$value];
    }
    else {
      return $value;
    }
  }

}
