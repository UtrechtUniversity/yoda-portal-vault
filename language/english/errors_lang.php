<?php

$lang['ERROR_NO_INTAKE_ACCESS'] = "<p>You currently do not have intake access to any studies. Ask the owner of the study you need access to, to add you through the group manager.";
$lang['ERR_STUDY_NO_EXIST'] = "<h3>The study '%s' does not exist.</h3><p>You can go to the study <a href='%s'>%s</a>, or select a different study from the menu below</p>";
$lang['ERR_STUDY_NO_ACCESS'] = "<h3>You are not authorized to access the study '%s'.</h3><p>You can go to the study <a href='%s'>%s</a>, or select a different study from the menu below</p>";
$lang['ERROR_FOLDER_NOT_IN_STUDY'] = "<h3>The folder '%s' does not exist</h3><p>The folder '%s' does not exist in the study %s. Click <a href=\"%s\">here</a> to return to the root of this the %s study.</p>";

$lang['FILES_NOT_IN_DATASET'] = "<p>The following files reside in the root of the study. Only files inside a directory are classified as part of a dataset.</p><p>The files will not be deleted, but must be moved to a dataset before they can be backed up.</p>";

if (file_exists(dirname(__FILE__) . '/errors_local_lang.php'))
	include(    dirname(__FILE__) . '/errors_local_lang.php');