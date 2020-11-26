<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// Yoda Portal Module Statistics configuration {{{
// No module-specific config yet.
$config = array();

/**
 * Browser config
 */
$config['browser-items-per-page'] = 10;

/**
 * Search config
 */
$config['search-items-per-page'] = 10;


if (file_exists(dirname(__FILE__) . '/config_local.php'))
    include(    dirname(__FILE__) . '/config_local.php');
// }}}
