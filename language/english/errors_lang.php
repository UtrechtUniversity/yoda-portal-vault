<?php

$lang['intake_error_no_projects'] = "<p>You currently do not have intake access to any projects. Ask the owner of the study you need access to, to add you through the group manager.";
$lang['intake_error_project_no_exist'] = "<h3>The project '%s' does not exist.</h3><p>You can go to the study <a href='%s'>%s</a>, or select a different project by going back to 'home'</p>";
$lang['intake_error_no_access'] = "<h3>You are not authorized to access the study '%s'.</h3><p>You can go to the study <a href='%s'>%s</a>, or select a different study from the menu below</p>";

if (file_exists(dirname(__FILE__) . '/errors_local_lang.php'))
	include(    dirname(__FILE__) . '/errors_local_lang.php');