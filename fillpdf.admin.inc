<?php
 
/**
 * @file
 * Allows mappings of PDFs to site content
 */

define('FILLPDF_REPLACEMENTS_DESCRIPTION', t("<p>Tokens, such as those from CCK, sometimes output values that need additional
  processing prior to being sent to the PDF. A common example is when a key within a CCK <em>Allowed values</em>
  configuration does not match the field name or option value in the PDF that you would like to be selected but you
  do not want to change the <em>Allowed values</em> key.</p><p>This field will replace any matching values with the
  replacements you specify. Specify <strong>one replacement per line</strong> in the format
  <em>original value|replacement value</em>. For example, <em>yes|Y</em> will fill the PDF with
  <strong><em>Y</em></strong> anywhere that <strong><em>yes</em></strong> would have originally
  been used. <p>Note that omitting the <em>replacement value</em> will replace <em>original value</em>
  with a blank, essentially erasing it.</p>"));

/* ---------------- Configuration --------------------*/

/**
 * Settings form for user to place API Key 
 */
function fillpdf_settings(){
	$form['settings_help'] = array(
	  '#type'=>'markup',
	  '#value' => t("
	    This module requires one of several external PDF manipulation tools -- you can:<ul>
        <li>Sign up for <a href='http://fillpdf-service.com'>Fillpdf as-a-service</a>, and plug your API key in here; <strong>or</strong>
        <li>Deploy locally -- You'll need a VPS or dedicated server so you can deploy PHP/JavaBridge on Tomcat (see README.txt), then select \"Use Local Service\"
        <li>Use a local installation of the pdftk program - you'll need a VPS or a dedicated server so you can install pdftk (see README.txt), then select \"Use locally-installed pdftk\"
      </ul>
	   "),
	);
	$form['remote'] = array(
	  '#type' => 'fieldset',
	  '#title' => t('Use Remote Service'),
	);
	$form['remote']['fillpdf_remote_service'] = array(
	  '#type' => 'checkbox',
	  '#title' => t('Use fillpdf-service.com'),
	  '#default_value' => variable_get('fillpdf_remote_service', true),
	);
	$form['remote']['fillpdf_api_key'] = array(
    '#type' => 'textfield', 
    '#title' => t('API Key'),
    '#default_value' => variable_get('fillpdf_api_key', ''), 
    '#description' => t('')
  );
  $form['remote']['fillpdf_remote_protocol'] = array(
    '#type' => 'radios',
    '#title' => t('Use SSL?'),
    '#description' => t('It is recommended to select <em>Yes</em> for this option. Doing so will help prevent
      sensitive information in your PDFs from being intercepted in transit.'),
    '#default_value' => variable_get('fillpdf_remote_protocol', 'http'),
    '#options' => array(
      'http' => t('No'), 
      'https' => t('Yes'),
      ),
    );

	if(variable_get('fillpdf_api_key', '')==''){
		$form['remote']['warning'] = array(
	    '#type' => 'markup', 
		  '#prefix' => "<div class='warning'>", '#suffix' => "</div>",
	    '#value' => t("You need to sign up for an API key at <a href='http://fillpdf-service.com'>fillpdf-service.com</a>"), 
	  );
	}
  
	$form['local'] = array(
    '#type' => 'fieldset',
    '#title' => t('Use Local Service'),
  );
	$form['local']['fillpdf_local_service'] = array(
	  '#type' => 'checkbox',
    '#title' => t('Use locally-installed PHP/JavaBridge'),
    '#default_value' => (variable_get('fillpdf_local_service', true)),
	);
  if(!(file_exists(drupal_get_path('module', 'fillpdf').'/lib/JavaBridge/java/Java.inc'))){
    $form['local']['warning'] = array(
      '#type' => 'markup', 
      '#prefix' => "<div class='warning'>", '#suffix' => "</div>",
      '#value' => t("JavaBridge isn't installed locally.  See README.txt for setting it up."), 
    );
  }

	$form['local_php'] = array(
    '#type' => 'fieldset',
    '#title' => t('Use Local pdftk'),
  );
	$form['local_php']['fillpdf_local_php'] = array(
	  '#type' => 'checkbox',
    '#title' => t('Use locally-installed pdftk'),
    '#default_value' => (variable_get('fillpdf_local_php', true)),
	);
  /*if(!(file_exists(drupal_get_path('module', 'fillpdf').'/lib/JavaBridge/java/Java.inc'))){
    $form['local']['warning'] = array(
      '#type' => 'markup', 
      '#prefix' => "<div class='warning'>", '#suffix' => "</div>",
      '#value' => t("JavaBridge isn't installed locally.  See README.txt for setting it up."), 
    );
  } */ //TODO: Modify to add check for pdftk installed
	
	$js = <<<JS
Drupal.behaviors.fillpdfSettingsCheckboxes = function (context) {
  $("input#edit-fillpdf-remote-service").click(function(){
    $("input#edit-fillpdf-local-service").attr('checked', false);
    $("input#edit-fillpdf-local-php").attr('checked', false);
  });
  $("input#edit-fillpdf-local-service").change(function(){
    $("input#edit-fillpdf-remote-service").attr('checked', false);
    $("input#edit-fillpdf-local-php").attr('checked', false);
  });
  $("input#edit-fillpdf-local-php").change(function(){
    $("input#edit-fillpdf-local-service").attr('checked', false);
    $("input#edit-fillpdf-remote-service").attr('checked', false);
  });
};
JS;
	drupal_add_js($js, 'inline');
  
  return system_settings_form($form);
}


/* ---------------- Form Create --------------------*/

/**
 * Manage your existing forms, or upload a new one
 */
function fillpdf_forms() {  
  $result = db_query("SELECT title, fid FROM {fillpdf_forms} ORDER BY title");
  $header = array(t('Title'), array('data' => t('Operations'), 'colspan' => '4'));
  while ($pdf_form = db_fetch_object($result)) {
    $row = array(
   		check_plain($pdf_form->title),
    	l(t('Edit'), "admin/content/fillpdf/$pdf_form->fid"),
    	l(t('Delete'), "admin/content/fillpdf/$pdf_form->fid/delete"),
    	l(t('Export field mappings'), "admin/content/fillpdf/$pdf_form->fid/export"),
    	l(t('Import field mappings'), "admin/content/fillpdf/$pdf_form->fid/import"),
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
  
  // uploading anything?
  $file = $_FILES['files']['name']['upload_pdf'];
  if(!$file)
  	form_set_error('url', t('A PDF must be provided.'));

  //from includes/file.inc, line 634, but can't use that function because file not an object yet	
  if (!preg_match('/\.pdf$/i', $file)) {
    form_set_error('url', t('Only PDF files are allowed'));
  }
  
  // directory exist or writeable?
  $dir = file_directory_path()."/fillpdf";
  file_check_directory($dir, FILE_CREATE_DIRECTORY, 'url');
  
}

/**
 * Creates a new Form from the uploaded PDF, including parsed fields 
 */
function fillpdf_forms_submit($form, &$form_state) {	  
  $dir = file_directory_path()."/fillpdf";
  // $validators not working, so I just checked manually in fillpdf_forms_validate()
  $validators = array('file_validate_extensions' => array('pdf') );
  if ($file = file_save_upload('upload_pdf', $validators, $dir, FILE_EXISTS_REPLACE)) {
    drupal_set_message('<strong>' . $file->filename . '</strong> was successfully uploaded');
    file_set_status($file, FILE_STATUS_PERMANENT);
  }
  else {
    drupal_set_message('Error saving file to ' . $dir, 'error');
  }
  db_query("INSERT INTO {fillpdf_forms} (title, url) VALUES('%s', '%s')", $file->filename, $file->filepath);
  $fid = db_last_insert_id('fillpdf_forms', 'fid');
  fillpdf_parse_pdf($fid);
  $form_state['redirect'] = "admin/content/fillpdf/$fid";
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
    '#value' => l("See which fields are which in this PDF", fillpdf_pdf_link($fid, null, null, true)),
    '#description' => t("If you have set a custom path on this PDF, the sample will be saved there silently.
      Also, path token replacement requires a real node or webform. It will not work in sample mode."),
  );
  $form['pdf_info']['form_id'] = array(
    '#type' => 'item',
    '#title' => 'Form Info',
    '#value' => "Form ID: [$fid].  Populate this form with node IDs, such as /fillpdf?fid=$fid&nid=10<br/>",
  );
  
  $form['extra'] = array(
    '#type' => 'fieldset',
    '#title' => t('Additional PDF settings'),
    '#collapsible' => TRUE,
    '#collapsed' => ($pdf_form->destination_path || $pdf_form->replacements ? FALSE : TRUE),
  );
  $form['extra']['destination_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Custom path for generated PDFs'),
    '#description' => t("<p>By default, filled PDFs are not saved to disk; they are simply sent
      directly to the browser for download. Enter a path here to change this behavior (tokens allowed).
      <strong>Warning! Unless you include the &download=1 flag in the Fill PDF URL, PDFs will only
      be saved to disk <em>and won't</em> be sent to the browser as well.</strong></p><p>The path
      you specify must be in one of the following two formats:<br />
        <ul>
          <li><em>path/to/directory</em> (path will be treated as relative to
          your <em>files</em> directory)</li>
          <li><em>/absolute/path/to/directory</em> (path will be treated as relative to your entire
          filesystem)</li>
        </ul>
      Note that, in both cases, you are responsible for ensuring that the user under which PHP is running can write to this path. Do not include a trailing slash.</p>"),
    '#maxlength' => 255,
    '#default_value' => $pdf_form->destination_path,
  );

  $form['extra']['tokens_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => 'Replacement patterns',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['extra']['tokens_fieldset']['tokens'] = array(
    '#value' => theme('token_help', array('node', 'webform')),
  );

  $form['extra']['replacements'] = array(
    '#type' => 'textarea',
    '#title' => t('Transform filled PDF field values'),
    '#wysiwyg' => FALSE,
    '#description' => FILLPDF_REPLACEMENTS_DESCRIPTION,
    '#default_value' => $pdf_form->replacements,
  );

  $form['submit'] = array('#type' => 'submit',
      '#value' => t('Submit'),
   );
  $form['delete'] = array('#type' => 'submit',
      '#value' => t('Delete'),
   );
  $form['#pdf_form'] = $pdf_form;
  
  // @@TODO: order by weight, and add dragable ala http://www.computerminds.co.uk/quick-guide-using-drupal-add-tabledrag-and-enjoying-jquery-drag-and-drop-loveliness
  $q = db_query('SELECT * FROM {fillpdf_fields} WHERE fid = %d', $fid);
  $header = array(t('Label'), t('PDF-field key'), t('Value'), t('Transformed'), array('data' => t('Operations'), 'colspan' => 2) );
  while($field = db_fetch_object($q)){
    $row = array(
    	check_plain($field->label), //editable
    	check_plain($field->pdf_key),
    	$field->value, //editable, expandable
      ($field->replacements ? 'Yes' : 'No'),
    	l(t('Edit'), "admin/content/fillpdf/$fid/edit/{$field->pdf_key}"),
    	l(t('Delete'), "admin/content/fillpdf/$fid/delete/{$field->pdf_key}"),
    	);
    $rows[] = $row;
  }
  
  $form['existing_fields'] = array(
    '#type' => 'markup',
    '#value' => '<br/><br/>'.theme('table', $header, $rows, array('id' => 'fillpdf_fields')),
  );

  $form['export_fields'] = array(
    '#prefix' => '<div>',
    '#value' => l(t('Export these field mappings'), "admin/content/fillpdf/{$pdf_form->fid}/export"),
    '#suffix' => '</div>',
  );

  $form['import_fields'] = array(
    '#prefix' => '<div>',
    '#value' => l(t('Import a previous export into this PDF'), "admin/content/fillpdf/{$pdf_form->fid}/import"),
    '#suffix' => '</div>',
  );
  
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
    db_query('UPDATE {fillpdf_forms} SET title = "%s", destination_path = "%s", replacements = "%s" WHERE fid = %d',
             $form_state['values']['title'], trim($form_state['values']['destination_path']), $form_state['values']['replacements'], $form['#pdf_form']->fid);
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

/**
 * Export an importable array of PDF field key -> Label, Value mappings.
 * The array key is the PDF field key and the value is another array containing the label and 
 * the value (properly keyed).
 * 
 * @param mixed $pdf_form The FillPDF form ID.
 */
function fillpdf_form_export($pdf_form) {
  if (is_numeric($pdf_form)) {
    $fid = db_fetch_object(db_query("SELECT * FROM {fillpdf_forms} WHERE fid = %d", $pdf_form));
  }
  if (!$fid) {
    drupal_not_found();
    exit;
  }
  $fields = db_query('SELECT * FROM {fillpdf_fields} WHERE fid = %d', $fid->fid);
  $export_array = array();
  while ($field = db_fetch_object($fields)) {
    $export_array[$field->pdf_key] = array(
      'label' => $field->label, 
      'value' => $field->value,
      'replacements' => $field->replacements,
    );
  }
  if (function_exists('json_decode')) {
    $fillpdf_code = json_encode($export_array);
  }
  else {
    $fillpdf_code = serialize($export_array);
  }
  return drupal_get_form('fillpdf_export_form', $fillpdf_code);
}

function fillpdf_export_form($form_state, $code) {
  $form = array();
  $form['export'] = array(
    '#type' => 'textarea',
    '#title' => t('Fill PDF Form Mappings'),
    '#default_value' => $code,
    '#rows' => 30,
    '#description' => t('Copy this code and then on the site you want to import to, go to the Edit page for the Fill PDF form for which you want to import these mappings, and paste it in there.'),
    '#attributes' => array(
      'style' => 'width: 97%;',
    ),
  );
  return $form;
}

/**
 * Based loosely on Node Export's import form. Import the code and configure the DB settings.
 * 
 * @param mixed $form_state 
 * @param mixed $pdf_form 
 */
function fillpdf_form_import_form(&$form_state, $pdf_form) {
  if (is_numeric($pdf_form)) {
    $fid = db_fetch_object(db_query("SELECT * FROM {fillpdf_forms} WHERE fid = %d", $pdf_form));
  }
  if (!$fid) {
    drupal_not_found();
    exit;
  }
  $form['fid'] = array(
    '#type' => 'value',
    '#value' => $fid->fid,
  );
  $form['paste'] = array(
    '#type' => 'fieldset',
    '#title' => t('Paste code'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['paste']['code'] = array(
    '#type' => 'textarea',
    '#default_value' => '',
    '#rows' => 30,
    '#description' => t('Cut and paste the results of a <em>Fill PDF Field Mappings export</em> here.'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
    '#suffix' => l(t('Reset the form'), $_GET['q']),
  );
  return $form;
}

/**
 * mixed fillpdf_safe_unserialize(string $serialized)
 * Safely unserialize, that is, only unserialize strings, numbers, and arrays, not objects
 * This prevents the __wakeup method from being called since it could contain arbitrary code.
 *
 * @license Public Domain
 * @author dcz (at) phpbb-seo (dot) com
 */
function fillpdf_safe_unserialize($serialized) {
  // unserialize will return false for object declared with small cap o
  // as well as if there is any ws between O and :
  if (is_string($serialized) && strpos($serialized, "\0") === FALSE) {
    if (strpos($serialized, 'O:') === FALSE) {
      // the easy case, nothing to worry about; let unserialize do the job
      return @unserialize($serialized);
    }
    elseif (!preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized)) {
      // in case we did have a string with O: in it,
      // but it was not a true serialized object
      return @unserialize($serialized);
    }
  }
  return FALSE;
}

function fillpdf_form_import_form_validate($form, &$form_state) {
  if (function_exists('json_decode')) {
    $mappings = json_decode($form_state['values']['code'], TRUE);
  }
  else {
    $mappings = fillpdf_safe_unserialize($form_state['values']['code']);
  }
  if (empty($mappings) || !is_array($mappings)) {
    form_set_error('code', t('There was a problem processing your Fill PDF Field Mappings code. Please do a fresh export from the source and try pasting it again.'));
  }
  else {
    $form_state['values']['mappings'] = $mappings;
  }
}

function fillpdf_form_import_form_submit($form, &$form_state) {
  $pdf_form = new stdClass();
  $pdf_form->fid = $form_state['values']['fid'];
  $mappings = $form_state['values']['mappings'];
  $fields = fillpdf_get_fields($pdf_form->fid);
  $field_keys = array_keys($fields);
  // Process the mappings
  foreach($mappings as $pdf_key => $field_settings) {
    if (in_array($pdf_key, $field_keys)) {
      $field_settings = (object) $field_settings;
      $field_settings->pdf_key = $pdf_key;
      fillpdf_update_field($pdf_form, $field_settings, $pdf_key);
    }
    else {
      drupal_set_message(t('Your code contained field mappings for the PDF field key <em>@pdf_key</em>, but it does not exist on this form. Therefore, it was ignored.', array('@pdf_key' => $pdf_key)), 'warning');
    }
  }
  drupal_set_message(t('Successfully imported matching PDF field keys. If any field mappings failed to import, they are listed above.'));
  $form_state['redirect'] = "admin/content/fillpdf/{$pdf_form->fid}";
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
  $form['value'] = array(
    '#type' => 'textarea',
    '#title' => t('Value'),
    '#default_value' => $field->value,
    '#description' => t('The content that will populate this field when the PDF is printed/saved.  This content pulls data via tokens, see below for available tokens.'),
    '#weight' => 4,
  );
  $form['tokens_fieldset'] = array(
    '#type' => 'fieldset',
  '#title' => 'Replacement patterns',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
  '#weight' => 5,
  );
  $form['tokens_fieldset']['tokens'] = array(
    '#value' => theme('token_help', array('node', 'webform')),
  );

  $form['extra'] = array(
    '#type' => 'fieldset',
    '#title' => t('Transform values on this field'),
    '#collapsible' => TRUE,
    '#collapsed' => ($field->replacements ? FALSE : TRUE),
    '#weight' => 6,
  );

  $form['extra']['replacements'] = array(
    '#type' => 'textarea',
    '#wysiwyg' => FALSE,
    '#description' => FILLPDF_REPLACEMENTS_DESCRIPTION,
    '#default_value' => $field->replacements,
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
    db_query("INSERT INTO {fillpdf_fields} (fid, label, pdf_key, value, replacements) VALUES(%d, '%s', '%s', '%s', '%s')",
      $form['#pdf_form']->fid, $form_state['values']['label'], $form_state['values']['pdf_key'], $form_state['values']['value'], $form_state['values']['replacements']);
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
           value = '%s', replacements='%s' WHERE fid = %d AND pdf_key = '%s'",
           $field->label, $field->pdf_key, $field->value, $field->replacements,
           $pdf_form->fid, $old_key);
}
