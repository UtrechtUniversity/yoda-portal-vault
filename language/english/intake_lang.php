<?php

$lang['INTAKE_STUDY'] = "study";
$lang['header:files_not_recognised'] = "Unrecognised files";
$lang['file_locked'] = "This object is locked because it is being prepared for a snapshot. Click to unlock and cancel the snapshot";
$lang['file_frozen'] = "This object is locked because a snapshot is currently being made. The object will be unlocked after the snapshot is completed";
$lang['dataset_locked'] = "This dataset is currently locked because a snapshot is in progress. The dataset is locked to ensure that a single state is snapshot, and no different versions of files can occur in the same snapshot version. Please wait until the snapshot is finished, after which you will have access to your files again.";
$lang['create_snapshot'] = "Snapshot";
$lang['unlock_snapshot'] = "Unlock";
$lang['snapshot_name'] = "Name";
$lang['size'] = "Size";
$lang['files'] 		= "Files";
$lang['created'] = "Created";
$lang['modified'] = "Modified";
$lang['snapshot_latest'] = "Latest snapshot";
$lang['comment'] = "Comment";
$lang['dataset'] = "dataset";
$lang['directory'] = "directory";
$lang['files_in_dirs'] = "%d (in %d directories)";
$lang['no_snapshots'] = "Never";
$lang['latest_snapshot_by'] = "%s by %s";

/** METADATA **/
$lang['header:metadata'] = "Meta data";
$lang['metadata_name'] = "Name";
$lang['metadata_value'] = "Value";



if (file_exists(dirname(__FILE__) . '/intake_local_lang.php'))
	include(    dirname(__FILE__) . '/intake_local_lang.php');