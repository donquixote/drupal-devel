<?php

function file_devel_generate($object, $instance, $plugin_definition, $form_display_options) {
  if (isset($plugin_definition['multiple_values']) && $plugin_definition['multiple_values'] === TRUE) {
    return devel_generate_multiple('_file_devel_generate', $object, $instance, $plugin_definition, $form_display_options);
  }
  else {
    return _file_devel_generate($object, $instance, $plugin_definition, $form_display_options);
  }
}

function _file_devel_generate($object, $instance, $plugin_definition, $form_display_options) {
  static $file;
  $settings = $instance->getFieldSettings();

  if (empty($file)) {
    if ($path = devel_generate_textfile()) {
      $source = new stdClass();
      $source->uri = $path;
      $source->uid = 1; // TODO: randomize? use case specific.
      $source->filemime = 'text/plain';
      $source->filename = drupal_basename($path);
      $destination_dir = $settings['uri_scheme'] . '://' . $settings['file_directory'];
      file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
      $destination = $destination_dir . '/' . basename($path);
      $file = file_move($source, $destination, FILE_CREATE_DIRECTORY);
    }
    else {
      return FALSE;
    }
  }
  if (!$file) {
    // In case a previous file operation failed or no file is set, return FALSE
    return FALSE;
  }
  else {
    $object_field['target_id'] = $file->id();
    $object_field['display'] = $settings['display_default'];
    $object_field['description'] = devel_create_greeking(10);

    return $object_field;
  }
}

/**
 * Private function for generating a random text file.
 */
function devel_generate_textfile($filesize = 1024) {
  if ($tmp_file = drupal_tempnam('temporary://', 'filefield_')) {
    $destination = $tmp_file . '.txt';
    file_unmanaged_move($tmp_file, $destination);

    $fp = fopen($destination, 'w');
    fwrite($fp, str_repeat('01', $filesize/2));
    fclose($fp);

    return $destination;
  }
}
