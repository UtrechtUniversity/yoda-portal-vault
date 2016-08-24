<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Yoda Portal Intake Module (institutions) configuration

// No module-specific config yet.
$config = array();

$config["intake-prefix"] 	= "grp-project-";
$config["manager-prefix"]	= "grp-datamanager-";

$config["role:administrator"] = "administrator";
$config["role:contributor"] = "contributor";
$config["role:reader"] = "reader";

$config["default-level"] = array(
	"title" => false,
	"tab" => "directories",
	"glyphicon" => false,
	"canSnapshot" => false,
	"canArchive" => false,
	"metadata" => false
);

$config["base-level"] = array(
	"title" => "projects",
	"glyphicon" => "home",
	"canSnapshot" => false,
	"canArchive" => false,
	"metadata" => false
);

$config["metadata_prefix"] = "ilab_";

$config["level-hierarchy"] = array(
		array(
			"title" => "project",
			"tab" => "projects",
			"glyphicon" => "briefcase",
			"canSnapshot" => false,
			"canArchive" => false,
			"metadata" => array(
				"form" => "project_schema.xml",
				"prefix" => "project_",
				"canView" => $config["role:administrator"], // TODO: rights should inherit
				"canEdit" => $config["role:administrator"]
			)
		),
		array(
			"title" => "study",
			"tab" => "studies",
			"glyphicon" => "education",
			"canSnapshot" => false,
			"canArchive" => false,
			"metadata" => false
		),
		array(
			"title" => "dataset",
			"tab" => "datasets",
			"glyphicon" => "paperclip",
			"canSnapshot" => $config['role:contributor'],
			"canArchive" => $config["role:administrator"],
			"metadata" => array(
				"form" => "datapackage.xml",
				// "form" => "intake_metadata.xml",
				"prefix" => "datapackage_",
				// "canView" => $config["role:reader"],
				"canView" => $config["role:administrator"], // TODO: rights should inherit
				"canEdit" => $config["role:administrator"]
			)
		)
	);

if (file_exists(dirname(__FILE__) . '/config_local.php'))
	include(    dirname(__FILE__) . '/config_local.php');