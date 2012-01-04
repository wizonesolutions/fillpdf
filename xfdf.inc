<?php

/**
 * @file
 * Provides functions for creating XFDF files.
 */

/**
 * create_xfdf
 *
 * Takes values passed via associative array and generates XFDF file format
 * with that data for the pdf address sullpiled.
 *
 * @param string $file The pdf file - url or file path accepted
 * @param array $info data to use in key/value pairs no more than 2 dimensions
 * @param string $enc default UTF-8, match server output: default_charset in php.ini
 * @return string The XFDF data for acrobat reader to use in the pdf form file
 */
function create_xfdf($file, $info, $enc = 'UTF-8') {
  $data = '<?xml version="1.0" encoding="' . $enc . '"?>' . "\n" .
    '<xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve">' . "\n" .
    '<fields>' . "\n";
  $data .= print_fields($info);
  $data .= '</fields>' . "\n" .
    '<ids original="' . md5($file) . '" modified="' . REQUEST_TIME . '" />' . "\n" .
    '<f href="' . $file . '" />' . "\n" .
    '</xfdf>' . "\n";
  return $data;
}

function escape_string($str) {
  if ($str == NULL) {
    return $str;
  }
  $str = str_replace(array('\\', '\''), array('\\\\', '\\\''), $str);
  return $str;
}

function print_fields($info) {
  $fields = array();
  $str = '';
  foreach ($info as $field => $val) {
    $arr = explode('.', $field);
    $str .= '$fields';
    foreach ($arr as $node) {
      $str .= "['" . escape_string($node) . "']";
    }
    $str .= "='" . escape_string($val) . "';";
  }
  eval($str);

  $str = '';
  foreach ($fields as $field => $val) {
    print_fields_recurse($field, $val, $str);
  }
  return $str;
}

function print_fields_recurse($field, $val, &$str) {
  $str .= '<field name="' . $field . '">' . "\n";
  if (is_array($val)) {
    foreach ($val as $field => $val) {
      print_fields_recurse($field, $val, $str);
    }
  }
  else {
    $str .= '<value>' . $val . '</value>' . "\n";
  }
  $str .= '</field>' . "\n";
}
