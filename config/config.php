<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Yoda Portal Intake Module (institutions) configuration

// No module-specific config yet.
$config = array();

$config["intake-prefix"] 	= "grp-intake-";
$config["manager-prefix"]	= "grp-datamanager-";

$config["level-hierarchy"] = array(
		array(
				"enabled" => true,
				"title" => "project",
				"glyphicon" => "briefcase"
			),
		array(
			"enabled" => true,
			"title" => "study",
			"glyphicon" => "education"
		),
		array(
			"enabled" => true,
			"title" => "dataset",
			"glyphicon" => "paperclip"
		)
	);

if (file_exists(dirname(__FILE__) . '/config_local.php'))
	include(    dirname(__FILE__) . '/config_local.php');