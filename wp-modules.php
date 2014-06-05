<?php
/**
 * Plugin Name: First+Third Modules
 * Description: Wordpress Module Loader
 */

if(!class_exists('Spyc')) {
  require_once('lib/spyc.php');
}

class ftModules {
  function __construct() {
    $this->module_path = ABSPATH . '/styleguide/modules';

    // Adds this suffix to module directories
    $this->module_suffix = '-modules';

    // Allow theme to override module path and suffix
    add_action('ft_modules_path', array($this, 'set_path'));
    add_action('ft_modules_suffix', array($this, 'set_suffix'));
  }

  function set_path($path) {
    if(!file_exists($path)) {
      return false;
    }

    $this->module_path = $path;
  }

  function set_suffix($suffix) {
    $this->module_suffix = $suffix;
  }
}

$ftModules = new ftModules;
