<?php


function fillpdf_token_values($type, $object = NULL, $options = array()) {
  // When making PDFs with fillpdf, this function is called twice, once with $type = 'node' and $object a node object, and once with $type = 'global' and $object = null. During node display, this function is only called once with $type = 'global' */
//  static $tokens = array();
//  static $run_once=false;if($run_once)return $tokens;$run_once=true;

  switch ($type) {
//    case 'global':
    case 'webform':
      $submission = $object;
      
      $tokens['webform-meta-nid'] = $submission->nid;
      $tokens['webform-meta-sid'] = $submission->sid;
      $tokens['webform-meta-uid'] = $submission->uid;
      $tokens['webform-meta-remote_addr'] = $submission->remote_addr;
      $tokens['webform-meta-submitted'] = $submission->submitted;
      
      $fields = array();
      $q = db_query('SELECT cid, form_key, type, extra FROM {webform_component} WHERE nid = %d', $submission->nid);
      while ($component = db_fetch_array($q)){ 
        // initialize empty fields, so they don't show as [webform-val-text_field] in the pdf
        if( !is_array($submission->data[$component['cid']]) )
         $submission->data[$component['cid']] = array();
           
        // add cid, form_key, etc along with ['value']
        $submission->data[$component['cid']] += $component; 
      }
      $tokens += _fillpdf_get_tokens_from_components($submission); // modify the submission
      return $tokens;
  }
}

function fillpdf_token_list($type = 'all') {
  if ($type == 'webform' || $type == 'all') {
    $tokens['webform']['webform-meta-nid'] = t("The webform's node id");
    $tokens['webform']['webform-meta-sid'] = t("The webform's submission id");
    $tokens['webform']['webform-meta-uid'] = t("The user's id who submitted the webform");
    $tokens['webform']['webform-meta-remote_addr'] = t("The user's ip address who submitted the webform");
    $tokens['webform']['webform-meta-submitted'] = t("The date the webform was submitted");
    
    $fields = array();
    $q = db_query('SELECT name, form_key FROM {webform_component}');
    while ($component = db_fetch_array($q)){ 
      $tokens['webform']["webform-val-{$component['form_key']}"] = t("The value for webform component [{$component['name']}]");
    }
    return $tokens;
  }
}

function _fillpdf_get_tokens_from_components($submission){
  $tokens = array();
  foreach($submission->data as $cid => $component){
//    $tokens["webform-val-{$component['form_key']}"] = $submission->data[$component['cid']]['value'][0];
    
    
    // First, unserialize everything so we can work with them as arrays
    switch ($component['type']) {
      case 'fieldset':
      case 'pagebreak':
        break;
      default:
        
        $fullvalue = false;
        // For components with selectable options (including grids), make an array of options in the form array(safe_key => "Some readable option", ...)
        $options = false;
        if (is_string($component['extra'])) {
          $component['extra'] = unserialize($component['extra']);
          // Selects use "items"
          if (is_string($component['extra']['items'])) {
            $component['extra']['options'] = $component['extra']['items'];
          }
          // Grids use "options"
          if (is_string($component['extra']['options'])) {
            foreach (preg_split('/[\r\n]+/', $component['extra']['options']) as $_) {
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
          $component['extra']['options'] = $options;
          unset($options);
        } else {
          $component['extra']['options'] = false;
        }
    }
    
    if ($component['value']) {
      switch ($component['type']) {
        case 'date':
          // Create ISO 8601 date
          if ($component['value'][2] && $component['value'][0] && $component['value'][1]) {
            $value = sprintf('%04d-%02d-%02d', $component['value'][2], $component['value'][0], $component['value'][1]);
          } else {
            $value = '';
          }
          break;
        case 'select':
        case 'grid':
          // Make webform-fullval token
          $fullvalue = array();
          foreach ($component['value'] as $value) {
            if ($component['extra']['options'][$value]) {
              $fullvalue[] = $component['extra']['options'][$value];
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
          $value = implode(', ', $component['value']);
      }
    } else {
      $value = '';
    }
    $tokens['webform-val-' . $component['form_key']] = $value;
    if ($fullvalue) {
      $tokens['webform-fullval-' . $component['form_key']] = $fullvalue;
    }
    
  }
  return $tokens;
}