<?php

$lang['intake_error_no_projects'] = "<p>You are not a member of any projects. Ask the owner of a project you need access to, to add you through the group manager.";
$lang['intake_error_project_no_exist'] = 'The directory <i>%1$s</i> does not seem to exist.</p>';
$lang['intake_error_no_access'] = "You are not authorized to access the project '<i>%s</i>'";

if (file_exists(dirname(__FILE__) . '/errors_local_lang.php'))
	include(    dirname(__FILE__) . '/errors_local_lang.php');