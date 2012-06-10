<?php
/**
 * @file fillpdf.rules.inc
 *
 * Come on, you know what this is for! It's in the name!
 * (But if you don't - it's for Rules integration.)
 */

/**
 * *************
 * *Rules hooks*
 * *************
 */


/**
 * Implements hook_rules_data_info().
 * @todo: Define the fillpdf data structure.
 *   See http://drupal.org/node/905632
 */
function fillpdf_rules_data_info() {
  return array();
}

/**
 * Implements hook_rules_event_info().
 * @todo: Define the "Fill PDF has filled the PDF" event
 * @todo: Define the following events:
 *   - Fill PDF is about to prepare PDF-filling data (fillpdf_merge_pre_merge)
 *   - Fill PDF is ready to fill the PDF (fillpdf_merge_fields_alter)
 */
function fillpdf_rules_event_info() {
  $defaults = array(
    'group' => t('Fill PDF'),
    'module' => 'fillpdf',
  );
  return array(
    'fillpdf_pre_handle' => $defaults + array(
      'label' => t('Filled PDF is about to be handled'),
      'variables' => array(
        'fillpdf' => array('type' => 'fillpdf', 'label' => 'Fill PDF metadata'),
      ),
    )
  );;
}

/**
 * Implements hook_rules_action_info().
 * @todo: Define the following actions:
 *   - Fill a PDF
 *   - Send PDF to user's browser
 *   - Save PDF to a file
 *   - Perform the default action on the PDF
 */
function fillpdf_rules_action_info() {
  // @todo: Define the "Save PDF to a file" rule
  return array();
}

/**
 * Implements hook_rules_condition_info().
 * @todo: Define the following conditions:
 *   - A node is being filled
 *   - A Webform is being filled
 */
function fillpdf_rules_condition_info() {

}

/**
 * Implements hook_default_rules_configuration().
 */
function fillpdf_rules_default_rules_configuration() {
  $default_rules = array();
  // @todo: Export the default rule once I create it via the UI and copy the
  // code here.

  // For attachment-based rules
  if (module_exists('mimemail')) {

  }
  return $default_rules;
}

/**
 * *****************
 * *Rules callbacks*
 * *****************
 */

// @todo: Write these.

