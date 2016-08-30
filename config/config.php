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

/*
 *  The prefix used for iRODS collections that show up
 * in the portal. Must be set the same in the constants.r
 * file in the irods-ruleset-ilab
 */
$config["intake-prefix"] 	= "grp-project-";

/*
 * The prefix used for groups that contain the group
 * manager users.
 */
$config["manager-prefix"]	= "grp-datamanager-";

/*
 * User role definitions
 */
$config["role:administrator"] = "administrator";
$config["role:contributor"] = "contributor";
$config["role:reader"] = "reader";

/*
 * Relative path from root of module to the directory 
 * that contains the meta data schema definitions
 */
$config["metadataform_location"] = "metadata";

/* 
 * All metadata that is edited through the metadata
 * forms is prefixed with this item. 
 */
$config["metadata_prefix"] = "ilab_";

/*
 * Level hierarchy configuration for the i-lab portal
 * The level-hierarchy configuration is an array of objects.
 * The position in the array corresponds with the depth of a
 * collection in the subtree of the home directory in the iRODS
 * zone. The first item corresponds with the first level of 
 * collections under home, the second item with the second, etc.
 * If no configuration is specified, the default level configuration
 * is used.
 *
 * Each object contains:
 * Title 		The display title of the level
 * Tab 			The title as shown in the tabs (usually plural of title)
 * glyphicon 	The bootstrap glyphicon used as an icon, minus the 
 * 				'glyphicon-' prefix (see getbootstrap.com/components)
 * canSnapshot  Indication for which user roles can create versions of
 * 				the level. False if no versions can be created, one of the
 * 				$config['role:...'] items if otherwise (cumulative)
 * canArchive 	Indication for which user roles can archive the current level
 * 				False if no versions can be created, one of the
 * 				$config['role:...'] items if otherwise (cumulative)
 * metadata 	False if no metadata can be edited for a level, otherwise
 * 				an object width:
 * 		* form 		The name of the file inside the 
 *					$config["metadataform_location"] directory that contains 
 * 					the metadata schema definitions
 * 		* prefix 	The extra prefix that will be appended to the 
 * 					$config["metadata_prefix"] for this level only, when saving
 * 					metadata to iRODS
 * 		* canView 	Indication of the minimum user role that can view the
 * 					metadata for this level
 * 		* canEdit 	Indication of the minimum user role that can edit the
 * 					metadata for this level
 */
$config["level-hierarchy"] = array(
		array(
			"title" => "project",
			"tab" => "projects",
			"glyphicon" => "briefcase",
			"canSnapshot" => false,
			"canArchive" => false,
			"metadata" => array(
				"form" => "example.xml",
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
				"form" => "example.xml",
				// "form" => "intake_metadata.xml",
				"prefix" => "datapackage_",
				// "canView" => $config["role:reader"],
				"canView" => $config["role:administrator"], // TODO: rights should inherit
				"canEdit" => $config["role:administrator"]
			)
		)
	);


/*
 * The default level is a single object with the same keys as the objects
 * in the level hierarchy. It contains the configuration for the levels that
 * have no configuration specified in the level-hierarchy
 */
$config["default-level"] = array(
	"title" => false,
	"tab" => "directories",
	"glyphicon" => false,
	"canSnapshot" => false,
	"canArchive" => false,
	"metadata" => false
);

/**
 * Same as a single object in the level-hierarchy array
 * Contains the configuration for the Home level
 */
$config["base-level"] = array(
	"title" => "projects",
	"glyphicon" => "home",
	"canSnapshot" => false,
	"canArchive" => false,
	"metadata" => false
);

if (file_exists(dirname(__FILE__) . '/config_local.php'))
	include(    dirname(__FILE__) . '/config_local.php');