<?php	

/**
 * Get's the prefix that should be used on the metadata
 * of a certain object according to the level hierarchy
 * definitions in the config.php
 * @param $object 	The object where the metadata is 
 * 					(going to be) associated with
 * @return (string) The meta data prefix for the object
 */
function getPrefix($object) {
	$CI =& get_instance();
	$prfx = "";
    if($CI->config->item("metadata_prefix") && $CI->config->item("metadata_prefix") !== false) {
        $prfx .= $CI->config->item("metadata_prefix");
    }
    $meta = $CI->metadataschemareader->getMetaForLevel($object);
    if(array_key_exists("prefix", $meta) && $meta["prefix"] !== false) {
        $prfx .= $meta["prefix"];
    }
    return $prfx;
}

/**
 * Prefix a key with the prefix that should be used on 
 * the metadata of a certain object according to the 
 * level hierarchy definitions in the config.php
 * @param $key 		The metadata key to prefix
 * @param $object 	The object the metadata key is
 * 					(going to be) associated with
 * @return (string) The key with the proper prefix
 */
function prefixKey($key, $object) {
	$prfx = getPrefix($object);
	return $prfx . $key;
}

/**
 * Removes a prefix from a given key that is
 * associated with a certain object, if and
 * only if the key is prefixed with the prefix
 * that should be used for the object
 * @param *key 		The prefixed key that should
 * 					be unprefixed
 * @param *object 	The object the key is associated
 * 					with
 * @return (string) The key with the prefix removed,
 * 					if it is prefixed
 */
function unprefixKey($key, $object) {
	$prfx = getPrefix($object);
	if(strpos($key, $prfx) === 0) {
		return substr($key, strlen($prfx));
	} else {
		return $key;
	}
}

?>