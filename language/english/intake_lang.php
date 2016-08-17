<?php
/***********************************************
			GENERAL INTAKE LANGUAGE
***********************************************/
$lang['intake_project'] = "project";
$lang['intake_dataset'] = "datapackage";
$lang['intake_directory'] = "directory";


/***********************************************
			INTAKE MAIN VIEW
***********************************************/
$lang['intake_head_files_not_recognised'] = "Unrecognised files";
$lang['intake_files_not_recognized'] = "<p>The following files reside in the root of the study. Only files inside a directory are classified as part of a dataset.</p><p>The files will not be deleted, but must be moved to a dataset before they can be backed up.</p>";

$lang['intake_file_locked'] = "This object is locked because it is being prepared for a snapshot. Click to unlock and cancel the snapshot";
$lang['intake_file_frozen'] = "This object is locked because a snapshot is currently being made. The object will be unlocked after the snapshot is completed";
$lang['intake_dataset_locked'] = "This dataset is currently locked because a snapshot is in progress. The dataset is locked to ensure that a single state is snapshot, and no different versions of files can occur in the same snapshot version. Please wait until the snapshot is finished, after which you will have access to your files again.";
$lang['intake_button_create_snapshot'] = "save as version";
$lang['intake_button_unlock_snapshot'] = "unlock";


/***********************************************
			INTAKE FILE OVERVIEW
***********************************************/
$lang['intake_tab_file_overview'] = "file overview";
$lang['intake_tab_details'] = "details";
$lang['intake_name'] = "name";
$lang['intake_size'] = "size";
$lang['intake_files'] 		= "files";
$lang['intake_created'] = "created";
$lang['intake_modified'] = "modified";
$lang['intake_snapshot_latest'] = "latest version";
$lang['intake_comment'] = "comment";
$lang['intake_n_files_in_n_dirs'] = '%1$d (in %2$d directories)';
$lang['intake_no_snapshots_text'] = "never";
$lang['intake_latest_snapshot_by'] = "%s by %s";

$lang['intake_head_dataset_information'] = '%1$s information';
$lang['intake_head_snapshot_history'] = "version creation history";


/***********************************************
			INTAKE META DATA
***********************************************/
$lang['intake_header_metadata'] = "meta data";
$lang['intake_metadata_key'] = "name";
$lang['intake_metadata_value'] = "value";



if (file_exists(dirname(__FILE__) . '/intake_local_lang.php'))
	include(    dirname(__FILE__) . '/intake_local_lang.php');