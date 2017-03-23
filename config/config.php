<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*************************************************************************
*																		 *
* Yoda Portal Intake Module (institutions) configuration				 *
*																		 *
*																		 *
* 		***DO NOT MODIFY THIS FILE***									 *
* To override settings in this file, create a new file in the config 	 *
* directory of this module, called "config_local.php". 					 *
* In there, override only those keys that need changing. 				 *
* If the level hierarchy is overridden, it has to be defined again 		 *
* completely. It cannot be overridden in part. 							 *
*																		 *
*************************************************************************/

/**
 * Browser config
 */
$config['browser-items-per-page'] = 10;

/**
 * Revision config
 */

$config['revision-items-per-page'] = 25;
$config['revision-dialog-items-per-page'] = 5;

if (file_exists(dirname(__FILE__) . '/config_local.php')) {
    include(dirname(__FILE__) . '/config_local.php');
}