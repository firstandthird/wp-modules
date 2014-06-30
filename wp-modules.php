<?php
/**
 * Plugin Name: First+Third Modules
 * Description: Wordpress Module Loader
 */

require 'lib/handlebars/src/Handlebars/Autoloader.php';
Handlebars\Autoloader::register();

use Handlebars\Handlebars;

if(!class_exists('Spyc')) {
  require_once('lib/spyc.php');
}

class ftModules {
  private $cache = array();
  private $config = array();

  function __construct() {
    $this->handlebars = new Handlebars;

    // is helper mimics ui-guide is helper
    $this->handlebars->addHelper('is', function($template, $context, $arg) {
      $args = (array) explode(' ', $arg);
      $val = $args[0];
      $tmp = true;

      if(!is_numeric($val)) {
        $val = $context->get($val);
      }

      if(count($args) > 1) {
        $tmp = $args[1];

        if(!is_numeric($tmp)) {
          $tmp = $context->get($tmp);
        }
      }

      if($val == $tmp) {
        $template->setStopToken('else');
        $buffer = $template->render($context);
        $template->setStopToken(false);
        $template->discard($context);
      } else {
        $template->setStopToken('else');
        $template->discard($context);
        $template->setStopToken(false);
        $buffer = $template->render($context);
      }

      return $buffer;
    });

    $this->module_path = ABSPATH . 'styleguide/modules/';

    // Adds this suffix to module directories
    $this->module_suffix = '-modules';

    // Allow theme to override module path and suffix
    add_action('ft_modules_path', array($this, 'set_path'));
    add_action('ft_modules_suffix', array($this, 'set_suffix'));

    add_action('init', array($this, 'load_data'), 20);

    add_action('ft_modules_render', array($this, 'render_module'), 10, 2);
  }

  function load_data() {
    if(!file_exists($this->module_path)) {
      return false;
    }

    $dir_iterator = new RecursiveDirectoryIterator($this->module_path);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

    foreach($iterator as $file) {
      if(!$file->isFile() || $file->getFilename() !== 'data.yaml') continue;

      $config = array();

      $config = spyc_load_file($file->getRealPath());
      if(!is_array($config)) continue;

      $this->config = array_merge($this->config, $config);
    }
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

  function path_normalize($path) {
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('/\/+/', '/', $path);
    return $path;
  }

  function render_module($module, $data = array()) {
    $module_split = explode('/', $module);
    $data_key = $module;
    $config_data = array();

    if(count($module_split) > 1) {
      $module_split[0] .= $this->module_suffix;
      $data_key = $module_split[count($module_split) -1];
    }

    $module = join('/', $module_split);

    $module_loc = $this->path_normalize($this->module_path . '/' . $module . '.html');

    if(!array_key_exists($module_loc, $this->cache)) {
      if(!file_exists($module_loc)) {
        return;
      }

      $this->cache[$module_loc] = file_get_contents($module_loc);
    }
    if(array_key_exists($data_key, $this->config)) {
      if(is_array($this->config[$data_key])) {
        $config_data = $this->config[$data_key];
      }
    }

    echo $this->handlebars->render($this->cache[$module_loc], array_replace_recursive($config_data, $data));
  }
}

$ftModules = new ftModules;
