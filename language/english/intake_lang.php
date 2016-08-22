<?php
/***********************************************
			GENERAL INTAKE LANGUAGE
***********************************************/
$lang['intake_project'] = "project";
$lang['intake_dataset'] = "datapackage";
$lang['intake_directory'] = "directory";
$lang['intake_and'] 	= 'and';
$lang['intake_bytes'] = 'bytes';
$lang['intake_total'] = 'total';
$lang['intake_info_not_available'] = 'N/A';
$lang['intake_button_back'] = "back";


/***********************************************
			INTAKE MAIN VIEW
***********************************************/
$lang['intake_head_files_not_recognised'] = "Unrecognised files";
$lang['intake_files_not_recognized'] = "<p>The following files reside in a collection of which no versions can be saved and which cannot be archived.</p><p>The files will not be deleted, so they may remain here. However, to be able to create versions of these files, they should be moved to a collection of which versions can be created</p>";

$lang['intake_dataset_locked'] = "<p>This collection and all its contents are currently locked because a the system is creating a version of this collection.</p><p>The system has locked this collection to ensure the files in the version it is creating cannot be changed before the version creation is finnished.</p>";
$lang['intake_button_create_snapshot'] = "Save as Version";
$lang['intake_button_unlock_snapshot'] = "Unlock";


/***********************************************
			INTAKE FILE OVERVIEW
***********************************************/
$lang['intake_tab_file_overview'] = "contents";
$lang['intake_tab_details'] = "version history";
$lang['intake_name'] = "name";
$lang['intake_size'] = "size";
$lang['intake_files'] 		= "files";
$lang['intake_folders'] = "folders";
$lang['intake_created'] = "created";
$lang['intake_modified'] = "latest changes";
$lang['intake_snapshot_latest'] = "latest version";
$lang['intake_comment'] = "comment";
$lang['intake_n_files_in_n_dirs'] = '%1$d (in %2$d directories)';
$lang['intake_no_snapshots_text'] = "never";
$lang['intake_latest_snapshot_by'] = "%s by %s";
$lang['intake_rodspath'] = 'path to dataset';
$lang['intake_version_current'] = 'current version';
$lang['intake_version'] = 'version';
$lang['intake_based_on'] = 'based on';
$lang['intake_snapshot_head_summary'] = "information";
$lang['intake_snapshot_vault_path'] = "vault path";
$lang['intake_snapshot_created_at_by'] = 'Version created by user <b>%2$s</b> at <b>%1$s</b>';


$lang['intake_head_dataset_information'] = '%1$s information';
$lang['intake_head_snapshot_history'] = "version creation history";


/***********************************************
			INTAKE META DATA
***********************************************/
$lang['intake_header_metadata'] = "meta data";
$lang['intake_metadata_key'] = "name";
$lang['intake_metadata_value'] = "value";
$lang['intake_metadata_dir_invalid'] = 'The collection <i>%1$s</i> does not seem to exist. The meta data could not be updated';
$lang['intake_metadata_no_permission'] = 'You do not have permission to update the meta data for <i>%1$s</i>';
$lang['intake_metadata_no_access'] = 'You do not have permission to view or edit metadata for this collection';
$lang['intake_metadata_update_success'] = 'Metadata saved';
$lang['intake_metadata_input_invalid'] = "Incorrect input";
$lang['intake_metadata_update_failed_general'] = "Something went wrong updating the meta data:</b></p>";
$lang['intake_metadata_update_failed_details'] = '<p>nt:Could not %1$s the values for %2$s';
$lang['intake_metadata_button_add_value'] = 'Add value';
$lang['intake_metadata_error_no_schema'] = 'There is no meta data schema defined for this object, so no meta data can be shown';
$lang['intake_metadata_button_edit'] = 'Edit';
$lang['intake_metadata_button_cancel'] = "Cancel editting and undo changes";



/***********************************************
				ACTIONS
***********************************************/
$lang['intake_actions_snapshot_path_invalid'] = '%1$s path does not seem to be a valid directory. No version could be created of this collection';
$lang['intake_actions_no_permission'] = 'You do not have permission to create a version for this collection';
$lang['intake_actions_no_selected_folder'] = 'No collections where selected so no version could be created';
$lang['intake_actions_snapshots_in_progress'] = 'New versions are being created of the collections %1$s. This may take a while.';
$lang['intake_actions_snapshot_in_progress'] = 'A version is currently being created of the collection %1$s';
$lang['intake_actions_snapshot_general_error'] = "Some versions could not be prepared to be moved to the vault. Please try again later. If the problem persists, please contact your system administrator";
$lang['intake_actions_unlock_no_valid_directory'] = '%1$s path does not seem to be a valid directory. The collection could not be unlocked';
$lang['intake_actions_snapshot_cancelled'] = 'Collection <i>%1$s</i> unlocked. No version was created';
$lang['intake_actions_snapshot_cancel_fail'] = 'The collection <i>%1$s</s> could not be unlocked, because the system has already initiated the process of creating a version. After this process is complete, this collection will automatically be unlocked.';


if (file_exists(dirname(__FILE__) . '/intake_local_lang.php'))
	include(    dirname(__FILE__) . '/intake_local_lang.php');