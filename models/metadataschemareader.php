<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MetadataSchemaReader extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $parser = xml_parser_create();
        $this->load->model('rodsuser');
        $this->load->library('pathlibrary');
    }

    /**
	 * Get the field definitions of the meta data schema
	 * @param $schemaName 	Name of the .xml file (including extension)
	 *						that is placed in the library directory
	 *						of this module.
	 * @param $object 		The iRods object the meta data is stored on
	 * @param $isCollection Boolean, true iff $object is a collection
	 */
	public function getFields($object, $isCollection) {
		$fields = $this->loadXML($object);

		if(!$fields) return false;

		$iRodsAccount = $this->rodsuser->getRodsAccount();
		$keys = array_keys($fields);
		$values = $this->metadatamodel->getValuesForKeys($iRodsAccount, $keys, $object);

		if(!$values) return false;

		foreach($fields as $key => $arr) {
			$this->castToValues($arr);
			if(array_key_exists("multiple", $arr) || $arr["type"] == "checkbox") {
				$arr["value"] = $values[$key];
			} else {
				if(sizeof($values[$key]) > 0 && sizeof($values[$key]) > 0) {
					$arr["value"] = $values[$key][0];
				} else {
					$arr["value"] = "";
				}
			}
			$fields[$key] = $arr;
		}

		return $fields;
	}

	private function loadXML($object) {
		$meta = $this->getMetaForLevel($object);

        if(is_array($meta) && array_key_exists("form", $meta) && $meta["form"] !== false) {
        	$form = $meta["form"];

        	$fdir = realpath(dirname(__FILE__) . '/../' . $this->config->item('metadataform_location')) . "/" . $form;

    		set_error_handler(function(){ /** ugly warnings are disabled this way. Error is shown in view **/ });
    		$result = json_decode(json_encode(simplexml_load_file($fdir)), true);
    		restore_error_handler();
    		return $result;
        } else {
        	return false;
        }
	}

	public function getMetaForLevel($object) {
		$rodsaccount = $this->rodsuser->getRodsAccount();
		$pathStart = $this->pathlibrary->getPathStart($this->config);
        $segments = $this->pathlibrary->getPathSegments($rodsaccount, $pathStart, $object, $prodsdir);
        $this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $level, $depth);

        if(array_key_exists("metadata", $level) && $level["metadata"] !== false) {
        	return $level["metadata"];
        }

        return false;
	}

	/**
	 * Function that recursivily casts int and bool values hidden
	 * in strings inside an (associative) array to their correct
	 * types
	 * @param $arr 	The array to recursively fix (pass by reference)
	 */
	private function castToValues(&$arr) {
		if(is_string($arr)) {
			if (preg_match("/^[0-9]+$/", $arr))
				$arr = (int)$arr;
			elseif (strtolower($arr) == "true")
				$arr = true;
			elseif (strtolower($arr) == "false")
				$arr = false;
			elseif ($arr == "&lt;")
				$arr = str_replace("&lt;", "<", $arr);
			elseif ($arr == "&gt")
				$arr = str_replace("&gt;", ">", $arr);
		} else if(is_array($arr)) {
			if(sizeof($arr) == 0 && $arr[0] === "")
				$arr = "";
			else {
				if(sizeof($arr) == 1 && array_key_exists("option", $arr)) {
					$arr = $arr["option"];
				}
				foreach($arr as $key => $value) {
					$this->castToValues($value);
					$arr[$key] = $value;
				}
			}
		}
	}



}