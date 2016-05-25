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

/**
 * Implode method that allows the last seperation character to be
 * different from the rest. This can be useful for a list that sums
 * some items.
 * @param $array 						The array to implode
 * @param $seperator 					The default seperator character
 * @param $last_seperator (optional) 	If set, this is the seperator 
 *										character used between the last
 *										two items of the array, instead
 *										of the default seperation character
 * @return String containing imploded array
 */
function human_implode($array, $seperator, $last_seperator = null) {
	if($last_seperator == null || sizeof($array) < 2) {
		$str = implode($seperator, $array);
	} else {
		$first_part = array_slice($array, 0, -1);
		$str = implode($seperator, $first_part);
		$str .= $last_seperator;
		$str .= $array[sizeof($array) - 1];	
	}
	return $str;
}

function relativeTimeWithTooltip($time, $short=false) {
	$reltime = getRelativeTime($time, $short);
	$dt = new DateTime();
	$dt->setTimestamp((int)htmlentities($time));

	$result = "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"" . $dt->format('Y-m-d H:i:s') . "\" >";
	$result .= "\t" . $reltime;
	$result .= "</span>";

	return $result;
}

function absoluteTimeWithTooltip($time) {
	$relative = timespan(htmlentities($time));
	$dt = new DateTime();
	$dt->setTimestamp((int)htmlentities($time));
	$reltime = getRelativeTime($time);
	$result = "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"" . $reltime . "\" >";
	$result .= "\t" . $dt->format('Y-m-d H:i:s');
	$result .= "</span>";

	return $result;
}

function getRelativeTime($time, $short=false) {
	$relTime = timespan(htmlentities($time));
	$dt = new DateTime();
	$dt->setTimestamp((int)htmlentities($time));
	$larger = $dt > new DateTime();
	$result = $short ? "&plusmn; " : "";
	$result .= $short ? explode(",", $relTime)[0] : $relTime;
	$result .= $larger ? " from now" : " ago";

	return $result;
}