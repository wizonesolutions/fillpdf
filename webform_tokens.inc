<?php

// $Id$

/**
 * @file
 * Exposes Webform values as tokens
 *
 * Exposes Webform values as tokens on Webform submissions when they are viewed
 * allowing them to be used by FillPDF
 */

/**
 * Implementation of hook_nodeapi().
 *
 * This function saves the nid for use later by webformtokens_token_values
 */

/* webformtokens_token_values is sometimes called multitple times when making a page. If a submission has already taken place, webformtokens_nodeapi gets called with $page = null, then webformtokens_token_values us called, then webformtokens_nodeapi us called again with $page = 1. So, tokens may not be generated on the page after a submission. But they are still generated when a PDF is requested. */
function fillpdf_nodeapi(&$node, $op, $teaser, $page) {
  if ($page && $node->type === 'webform') {
    global $nid;
    $nid = $node->nid;
  }
}

/* When making PDFs with fillpdf, this function is called twice, once with $type = 'node' and $object a node object, and once with $type = 'global' and $object = null. During node display, this function is only called once with $type = 'global' */
function fillpdf_token_values($type, $object = NULL, $options = array()) {
  global $nid; // Either saved by webformtokens_nodeapi or below when $type = 'node'

  $tokens = array();
  switch ($type) {
    case 'node':
      // Save the nid for later use when this function is caled without the node object
      if ($object->type === 'webform') {
        $nid = $object->nid;
      }
      break;
    case 'global':
      if ($nid) {
        $fields = array();
        $result = db_query('SELECT cid, form_key, type, extra FROM {webform_component} WHERE nid = %d', $nid);
        // Add entry to $fields for each component in the form
        while ($_ = db_fetch_array($result)) {
          // Use the component cid as the key. Add properties the entry. Exclude fieldset and pagebreak: They don't contain data, so we won't make tokens for them
          switch ($_['type']) {
            case 'fieldset':
            case 'pagebreak':
              break;
            default:
              $cid = $_['cid'];
              $fields[$cid] = $_;

              // For components with selectable options (including grids), make an array of options in the form array(safe_key => "Some readable option", ...)
              $options = false;
              if (is_string($_['extra'])) {
                $fields[$cid]['extra'] = unserialize($_['extra']);
                // Selects use "items"
                if (is_string($fields[$cid]['extra']['items'])) {
                  $fields[$cid]['extra']['options'] = $fields[$cid]['extra']['items'];
                }
                // Grids use "options"
                if (is_string($fields[$cid]['extra']['options'])) {
                  foreach (preg_split('/[\r\n]+/', $fields[$cid]['extra']['options']) as $_) {
                    if (strpos($_, '|') !== false) {
                      $option = explode('|', $_, 2);
                      $options["$option[0]"] = $option[1];
                    } else {
                      // Make sure the keys are strings
                      $options["$_"] = $_;
                    }
                  }
                }
              }
              if ($options) {
                $fields[$cid]['extra']['options'] = $options;
                unset($options);
              } else {
                $fields[$cid]['extra']['options'] = false;
              }
          }
        }
        if ($fields) {
          // Token for nid
          $tokens['webform-meta-nid'] = $nid;

          // Get the data array from the first value submission
          // If there are not submissions for this user, $values will be an empty array
          global $user;
          module_load_include('inc', 'webform', 'webform_submissions');
          $values = webform_get_submissions($nid, null, $user->uid, 1);
          if ($values) {
            // Unwrap the 1-element array from around the submission
            $values = array_shift($values);
            // Make tokens for the submission metadata
            foreach (get_object_vars($values) as $key => $val) {
              if ($key !== 'data') {
                $tokens['webform-meta-' . $key] = $val;
              }
            }
            // Grab the user-submitted form values
            $values = $values->data;
          }
    
          // Make tokens for each field with the user-submitted form values or empty string if no value
          foreach (array_keys($fields) as $cid) {
            $fullvalue = false;
            if ($values && $values[$cid] && $values[$cid]['value']) {
              switch ($fields[$cid]['type']) {
                case 'date':
                  // Create ISO 8601 date
                  if ($values[$cid]['value'][2] && $values[$cid]['value'][0] && $values[$cid]['value'][1]) {
                    $value = sprintf('%04d-%02d-%02d', $values[$cid]['value'][2], $values[$cid]['value'][0], $values[$cid]['value'][1]);
                  } else {
                    $value = '';
                  }
                  break;
                case 'select':
                case 'grid':
                  // Make webform-fullval token
                  $fullvalue = array();
                  foreach ($values[$cid]['value'] as $value) {
                    if ($fields[$cid]['extra']['options'][$value]) {
                      $fullvalue[] = $fields[$cid]['extra']['options'][$value];
                    } else {
                      $fullvalue[] = $value;
                    }
                  }
                  $fullvalue = implode(', ', $fullvalue);
                  // Don't break: every field gets a webform-val token
                default:
                  // Usually there is only one value, so implode just removes the array
                  // Otherwise, separate the values with a comma
                  // BUG: There should probably be better handling for multiple values
                  $value = implode(', ', $values[$cid]['value']);
              }
            } else {
              $value = '';
            }
            $tokens['webform-val-' . $fields[$cid]['form_key']] = $value;
            if ($fullvalue) {
              $tokens['webform-fullval-' . $fields[$cid]['form_key']] = $fullvalue;
            }
          }
        }
      }
      break;
  }
  return $tokens;
}


//function my_user_token_values($type, $object = NULL, $options = array()) {
//  if ($type == 'user') {
//    $user = $object;
//    $tokens['name']      = $user->name;
//    $tokens['mail']      = $user->mail;
//    return $tokens;
//  }
//}

//function my_user_token_list($type = 'all') {
//  if ($type == 'user' || $type == 'all') {
//    $tokens['user']['name']      = t("The user's name");
//    $tokens['user']['mail']      = t("The user's email address");
//    return $tokens;
//  }
//}