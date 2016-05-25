<?php

$lang['INTAKE_STUDY'] = "study";
$lang['HDR_FILES_NOT_RECOGNISED'] = "Unrecognised files";
$lang['HDR_METADATA'] = "Meta data";
$lang['file_locked'] = "This object is locked because it is being prepared for a snapshot. Click to unlock and cancel the snapshot";
$lang['file_frozen'] = "This object is locked because a snapshot is currently being made. The object will be unlocked after the snapshot is completed";

if (file_exists(dirname(__FILE__) . '/intake_local_lang.php'))
	include(    dirname(__FILE__) . '/intake_local_lang.php');