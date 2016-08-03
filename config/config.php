<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Yoda Portal Intake Module (institutions) configuration

// No module-specific config yet.
$config = array();

$config["intake-prefix"] 	= "grp-intake-";
$config["manager-prefix"]	= "grp-datamanager-";

$config["role:administrator"] = "administrator";
$config["role:contributor"] = "contributor";
$config["role:reader"] = "reader";

$config["default-level"] = array(
	"title" => false,
	"glyphicon" => false,
	"canSnapshot" => false,
	"canArchive" => false,
	"metadata" => false
);

$config["level-hierarchy"] = array(
		array(
			"title" => "project",
			"glyphicon" => "briefcase",
			"canSnapshot" => false,
			"canArchive" => false,
			"metadata" => false
		),
		array(
			"title" => "study",
			"glyphicon" => "education",
			"canSnapshot" => false,
			"canArchive" => false,
			"metadata" => false
		),
		array(
			"title" => "dataset",
			"glyphicon" => "paperclip",
			"canSnapshot" => $config['role:contributor'],
			"canArchive" => $config["role:administrator"],
			"metadata" => array(
				"form" => "intake_metadata.xml",
				// "canView" => $config["role:reader"],
				"canView" => $config["role:administrator"], // TODO: rights should inherit
				"canEdit" => $config["role:administrator"]
			)
		)
	);

if (file_exists(dirname(__FILE__) . '/config_local.php'))
	include(    dirname(__FILE__) . '/config_local.php');