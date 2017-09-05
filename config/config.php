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

/**
 * Revision config
 */

$config['revision-items-per-page'] = 25;
$config['revision-dialog-items-per-page'] = 5;

// Version of confirmation text to be signed off by researcher
$config['confirmation-version'] = '1';

if (file_exists(dirname(__FILE__) . '/config_local.php'))
    include(    dirname(__FILE__) . '/config_local.php');
// }}}