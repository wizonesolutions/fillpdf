<?php
// $Id$
 
/**
 * @file
 * Allows mappings of PDFs to site content
 */

/* ---------------- Configuration --------------------*/

/**
 * Settings form for user to place API Key 
 */
function fillpdf_settings(){
  $form['fillpdf_api_key'] = array(
  	'#type' => 'textfield', 
  	'#title' => t('API Key'),
    '#default_value' => variable_get('fillpdf_api_key', ''), 
    '#description' => t('')
  );
  return system_settings_form($form);
}


/* ---------------- Form Create --------------------*/

/**
 * Manage your existing forms, or upload a new one
 */
function fillpdf_forms() {  
  $result = db_query("SELECT title, fid FROM {fillpdf_forms} ORDER BY title");
  $header = array(t('Title'), array('data' => t('Operations'), 'colspan' => '2'));
  while ($pdf_form = db_fetch_object($result)) {
    $row = array(
   		check_plain($pdf_form->title),
    	l(t('Edit'), "admin/content/fillpdf/$pdf_form->fid"),
    	l(t('Delete'), "admin/content/fillpdf/$pdf_form->fid/delete"),
    	);
    $rows[] = $row;
  }
  
  $form['existing_forms'] = array(
      '#type' => 'markup',
      '#value' => theme('table', $header, $rows, array('id' => 'fillpdf')),
  );
  
  $form['#attributes'] = array('enctype' => "multipart/form-data");
  $form['upload_pdf']=array(
  	  '#type' => 'file',
      '#title' => 'Upload',
	  '#description' => 'Upload a PDF template to create a new form',
  );
  $form['submit'] = array('#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 15,
   );
  
  return $form;
}

/**
 * Makes sure the Upload was provided (want to validate .pdf here too) 
 */
function fillpdf_forms_validate($form, &$form_state) {
  if(!$_FILES['files']['name']['upload_pdf']){
  	form_set_error('url', t('A PDF must be provided.'));
  }
}

/**
 * Creates a new Form from the uploaded PDF, including parsed fields 
 */
function fillpdf_forms_submit($form, &$form_state) {	  
  $dir = file_directory_path()."/fillpdf";
  if( file_check_directory($dir, FILE_CREATE_DIRECTORY) ) {
  	  $validators = array('file_validate_extensions' => array('pdf') );
	  if ($file = file_save_upload('upload_pdf', $validators, $dir, FILE_EXISTS_REPLACE)) {
	    drupal_set_message('<strong>' . $file->filename . '</strong> was successfully uploaded');
	  }//else{ drupal_set_message('Error saving file to ' . $dir, 'error'); } //commented out because even though error if file doesn't upload right, not error if they dont' upload a file (& this is still triggered)
  } else {
      //@@TODO: Set error and return, they must not proceed if they can't upload files!
      drupal_set_message( t($dir.' is not accessible. Consult with site admin!', 'warning') );
  }
  
    //@@TODO: rename url to filepath
    $fid = db_last_insert_id('fillpdf', 'fid');
    db_query("INSERT INTO {fillpdf_forms} (fid, title, url) VALUES(%d, '%s', '%s')", 
             $fid, $file->filename, $file->filepath);
  _fillpdf_generate_fields_from_pdf($fid);
  $form_state['redirect'] = "admin/content/fillpdf/$fid";
  //$form_state['nid'] = $node->nid;
}


/* ---------------- Form Edit --------------------*/

/**
 * Edit existing PDF form 
 */
function fillpdf_form_edit(&$form_state, $fid) {
  $pdf_form = db_fetch_object(db_query("SELECT * FROM {fillpdf_forms} WHERE fid = %d", $fid));
 
  $form['title'] = array(
  	  '#type' => 'textfield', 
      '#title' => t('Title'), 
      '#maxlength' => 127,
      '#default_value' => $pdf_form->title,
      '#required' => TRUE,
  );

  // @@TODO: 
  // They can upload a PDF any time, but fields will only be generated on add.  Don't want to purge existing fields, 
  // however a user might have accidently uploaded an old template and discover much later (if it's substantially different, just 
  // create a new Form    
  $form['pdf_info'] = array(
  	'#type' => 'fieldset',
    '#title' => 'PDF Form information',
    '#collapsed' => true,
  );     
  $form['pdf_info']['submitted_pdf'] = array(
    '#type' => 'item',
    '#title' => t('Uploaded PDF'),
    '#value' => $pdf_form->url,
  );
  $form['pdf_info']['sample_populate'] = array(
    '#type' => 'item',
    '#title' => 'Sample PDF',
    '#value' => "<a href='/fillpdf?fid=$fid&sample=true'>See which fields are which in this PDF</a><br/>",
  );
  $form['pdf_info']['form_id'] = array(
    '#type' => 'item',
    '#title' => 'Form Info',
    '#value' => "Form ID: [$fid].  Populate this form with node IDs, such as /fillpdf?fid=$fid&nid=10<br/>",
  );
  
  
  $form['submit'] = array('#type' => 'submit',
      '#value' => t('Submit'),
   );
  $form['delete'] = array('#type' => 'submit',
      '#value' => t('Delete'),
   );
   //$form['#pdf_form'] = &$pdf_form;
  $form['#pdf_form'] = $pdf_form;
  
  // @@TODO: order by weight, and add dragable ala http://www.computerminds.co.uk/quick-guide-using-drupal-add-tabledrag-and-enjoying-jquery-drag-and-drop-loveliness
  $q = db_query('SELECT * FROM {fillpdf_fields} WHERE fid = %d', $fid);
  $header = array(t('Label'), t('PDF-field key'), t('Value'), array('data' => t('Operations'), 'colspan' => 2) );
  while($field = db_fetch_object($q)){
    $row = array(
    	check_plain($field->label), //editable
    	check_plain($field->pdf_key),
    	$field->value, //editable, expandable
    	l(t('Edit'), "admin/content/fillpdf/$fid/edit/{$field->pdf_key}"),
    	l(t('Delete'), "admin/content/fillpdf/$fid/delete/{$field->pdf_key}"),
    	);
    $rows[] = $row;
  }
  
  $form['existing_fields'] = array(
    '#type' => 'markup',
    '#value' => '<br/><br/>'.theme('table', $header, $rows, array('id' => 'fillpdf_fields')),
  );
  
//  $form['tokens_fieldset'] = array(
//    '#type' => 'fieldset',
//    '#title' => 'Tokens',
//    '#collapsible' => TRUE,
//    '#collapsed' => TRUE,
//    '#weight' => 1,
//  );
//  $form['tokens_fieldset']['tokens'] = array(
//    '#value' => theme('token_help'),
//  );
  
  
  return $form;
}

/**
 * Submit Edit or Delete for existing PDF form
 */
function fillpdf_form_edit_submit($form, &$form_state) {
  if ($form_state['values']['op'] == t('Delete')) {
    $form_state['redirect'] = "admin/content/fillpdf/{$form['#pdf_form']->fid}/delete" ;
    return;
  }else{
    db_query('UPDATE {fillpdf_forms} SET title = "%s" WHERE fid = %d', 
             $form_state['values']['title'], $form['#pdf_form']->fid);
    $form_state['redirect'] = "admin/content/fillpdf/{$form['#pdf_form']->fid}";
    drupal_set_message('Successfully updated form');
    //$form_state['nid'] = $node->nid;
  }
}

/**
 * Delete form confirmation 
 */
function fillpdf_form_delete_confirm(&$form_state, $pdf_form) {
  if (is_numeric(arg(3))) {
    $pdf_form = db_fetch_object(db_query("SELECT * FROM {fillpdf_forms} WHERE fid = %d", arg(3)));    
  }
  if (!$pdf_form) {
    drupal_not_found();
    exit;
  }

  $form['#pdf_form'] = $pdf_form;
  return confirm_form($form,
    t('Are you sure you want to delete the form %title?', array('%title' => $pdf_form->title)),
    'admin/content/fillpdf',
    t('Deleting a form will delete all the fields you created in it. This action cannot be undone.'),
    t('Delete'), t('Cancel')
  );
}

/**
 * Delete form submit 
 */
function fillpdf_form_delete_confirm_submit($form, &$form_state) {
  db_query("DELETE FROM {fillpdf_fields} WHERE fid = %d", $form['#pdf_form']->fid);
  db_query("DELETE FROM {fillpdf_forms} WHERE fid = %d", $form['#pdf_form']->fid);
  drupal_set_message('Your form has been deleted.');
  //return 'admin/content/fillpdf';
  $form_state['redirect'] = 'admin/content/fillpdf';
}

/* ---------------- Fields Edit --------------------*/


function fillpdf_field($op, $fid, $pdf_key=NULL) {
  if (is_numeric($fid)) {
    $pdf_form = db_fetch_object(db_query("SELECT * FROM {fillpdf_forms} WHERE fid = %d", $fid));
  }
  if (!$pdf_form) {
    drupal_not_found();
    exit;
  }

  if ($op == 'add') {
    drupal_set_title(check_plain($pdf_form->title));    
  }
  else if ($op != 'edit') {
    return fillpdf_form_overview($pdf_form);
  }
  else if ($pdf_key) {
    $field = db_fetch_object(db_query("SELECT * FROM {fillpdf_fields} WHERE pdf_key = '%s' AND fid = %d", $pdf_key, $fid));
    if (!$field) {
      drupal_not_found();
      exit;
    }
    drupal_set_title(check_plain($field->label));
    //$type = $field->type;
  }

  return drupal_get_form('fillpdf_field_edit', $pdf_form, $field);
}

function fillpdf_field_edit(&$form_state, $pdf_form, $field) {
  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#maxlength' => 255,
    '#default_value' => $field->label,
    '#description' => t('An optional label to help you identify the field.'),
  '#weight' => 0,
  );
  $form['pdf_key'] = array(
    '#type' => 'textfield',
    '#title' => t('PDF Key'),
    '#maxlength' => 255,
    '#default_value' => $field->pdf_key,
  '#required' => TRUE,
    '#description' => t('The field key from the original PDF form.  You likely need Acrobat Pro to discover this.'),
    '#weight' => 1,
  );
//  $form['type'] = array(
//    '#type' => 'radios',
//  '#options' => array('text','int'),
//    '#title' => t('Type'),
//    '#default_value' => ( ($field->type)?($field->type):0 ),
//    '#description' => t('The type of PDF field.'),
//    '#weight' => 3,
//  );
  $form['value'] = array(
    '#type' => 'textarea',
    '#title' => t('Value'),
    '#default_value' => $field->value,
    '#description' => t('The content that will populate this field when the PDF is printed/saved.  This content pulls data via tokens, see below for available tokens.'),
    '#weight' => 4,
  );
  $form['tokens_fieldset'] = array(
    '#type' => 'fieldset',
  '#title' => 'Tokens',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
  '#weight' => 5,
  );
  $form['tokens_fieldset']['tokens'] = array(
    '#value' => theme('token_help'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#weight' => 9,
  );

  if ($field) {
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#weight' => 10,
    );
  }

  $form['#pdf_field'] = $field;
  $form['#pdf_form'] = $pdf_form;
  
  return $form;
}

function fillpdf_field_edit_validate($form, &$form_state) {
  if (db_result( db_query("SELECT * FROM {fillpdf_fields} WHERE fid = %d AND pdf_key = '%s'", 
      $form['#pdf_form']->fid, $form_state['values']['pdf_key'] ))) {
    if ($form['#pdf_field'] && $form['#pdf_field']->pdf_key == $form_state['values']['pdf_key'] ) return;
  else form_set_error('pdf_key', t('A field with this pdf_key already exists. Choose another pdf_key.'));
  }
}

function fillpdf_field_edit_submit($form, &$form_state) {
  if ($form['#pdf_field']) {
    if ($form_state['values']['op'] == t('Delete')) {
      $form_state['redirect'] = 'admin/content/fillpdf/'. $form['#pdf_form']->fid .'/delete/'. $form['#pdf_field']->pdf_key;
      return;
    }
    $edit_field = (object)$form_state['values'];
    fillpdf_update_field($form['#pdf_form'], $edit_field, $form['#pdf_field']->pdf_key);   
  }
  else {
    //add a new field
    $edit_field = (object)$form_state['values'];
    db_query("INSERT INTO {fillpdf_fields} (fid, label, pdf_key, value) VALUES(%d, '%s', '%s', '%s')", 
             $form['#pdf_form']->fid, $form_state['values']['label'], $form_state['values']['pdf_key'], $form_state['values']['value']);
  }
  $form_state['redirect'] = 'admin/content/fillpdf/'. $form['#pdf_form']->fid;
}


function fillpdf_field_delete_confirm(&$form_state, $fid, $pdf_key) {
  $pdf_form = db_fetch_object(db_query("SELECT * FROM {fillpdf_forms} WHERE fid = %d", $fid));  

  if ($pdf_key) {
    $field = db_fetch_object(db_query("SELECT * FROM {fillpdf_fields} WHERE pdf_key = '%s' AND fid = %d", $pdf_key, $fid));
  }
  if (!$field) {
    drupal_not_found();
    exit;
  }

  $form['#pdf_field'] = $field;
  $form['#pdf_form'] = $pdf_form;
  
  return confirm_form($form,
    t('Are you sure you want to delete the field %pdf_key?', array('%pdf_key' => $field->pdf_key)),
    'admin/content/fillpdf/'. $pdf_form->fid,
    t('This action cannot be undone.'), t('Delete'), t('Cancel')
  );
}

function fillpdf_field_delete_confirm_submit($form, &$form_state) {
  db_query("DELETE FROM {fillpdf_fields} WHERE fid = %d AND pdf_key ='%s'",
           $form['#pdf_field']->fid, $form['#pdf_field']->pdf_key);
  drupal_set_message('Your field has been deleted.');
  //return 'admin/content/fillpdf/'. $form['#pdf_field']->fid;
  $form_state['redirect'] = 'admin/content/fillpdf/'. $form['#pdf_field']->fid;
}

/*
 * Stores the updated $field in the database
 */
function fillpdf_update_field(&$pdf_form, &$field, $old_key) {
  db_query("UPDATE {fillpdf_fields} SET label = '%s', pdf_key='%s', 
           value = '%s' WHERE fid = %d AND pdf_key = '%s'",
           $field->label, $field->pdf_key, $field->value, 
           $pdf_form->fid, $old_key);
}