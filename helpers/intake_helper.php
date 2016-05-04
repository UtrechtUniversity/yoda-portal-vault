<?php
/**
 * @param     $bytes
 * @param int $decimals
 *
 * @return string
 */

function human_filesize($bytes, $decimals = 2) {
	$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function displayMessage($controller, $message, $error=false, $type="info") {
	$controller->data['userIsAllowed'] = false;
	if($error) $controller->session->unset_userdata('studyID');
	$messageObject = (object) array(
			'type' => $error ? "danger" : $type,
			'message' => $message
		);
	$controller->session->set_userdata('information', $messageObject);
}