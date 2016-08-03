<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define("WARNING_NO_CONF", -1);
define("OK", 0);
define("ERROR_MIN_ENTRIES", -2);
define("ERROR_MAX_ENTRIES", -3);
define("ERROR_SINGLE_ENTRY", -4);
define("ERROR_REQUIRED", -5);
define("ERROR_MAX_LENGTH", -6);
define("ERROR_REGEX", -7);
define("ERROR_NOT_IN_RANGE", -8);
define("ERROR_INVALID_DATETIME_FORMAT", -9);
define("ERROR_DATE_LESS_THAN_FIXED", -10);
define("ERROR_DATE_LESS_THAN_LINKED", -11);
define("ERROR_DATE_HIGHER_THAN_FIXED", -10);
define("ERROR_DATE_HIGHER_THAN_LINKED", -11);

class metadataFields {

	public function __construct() {
		$this->CI =& get_instance();
		$parser = xml_parser_create();
		// $fdir = realpath(dirname(__FILE__)) . "/" . "intake_metadata.xml";
		// $this->fields = json_decode(json_encode(simplexml_load_file($fdir)), true);
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

		$iRodsAccount = $this->CI->rodsuser->getRodsAccount();
		$keys = array_keys($fields);
		$values = $this->CI->metadatamodel->getValuesForKeys2($iRodsAccount, $keys, $object);

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

	private function getMetaForLevel($object) {
		$rodsaccount = $this->CI->rodsuser->getRodsAccount();
		$pathStart = $this->CI->pathlibrary->getPathStart($this->CI->config);
        $segments = $this->CI->pathlibrary->getPathSegments($rodsaccount, $pathStart, $object, $prodsdir);
        $this->CI->pathlibrary->getCurrentLevelAndDepth($this->CI->config, $segments, $level, $depth);

        if(array_key_exists("metadata", $level) && $level["metadata"] !== false) {
        	return $level["metadata"];
        }

        return false;
	}

	private function loadXML($object) {
		
		$meta = $this->getMetaForLevel($object);

        if(is_array($meta) && array_key_exists("form", $meta) && $meta["form"] !== false) {
        	$form = $meta["form"];
        	$fdir = realpath(dirname(__FILE__)) . "/" . $form;

    		set_error_handler(function(){ /** ugly warnings are disabled this way. Error is shown in view **/ });
    		$result = json_decode(json_encode(simplexml_load_file($fdir)), true);
    		restore_error_handler();
    		return $result;
        } else {
        	return false;
        }
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

	/**
	 * Function that checks the dependency properties for a field
	 * @param fieldDependencies 	The dependency object in the field
	 * 								definitions of the field that is to
	 * 								be checked
	 * @param formData 				The posted form data
	 * @return bool 				True iff dependency indicates field
	 * 									was visible
	 */
	public function evaluateRowDependencies($fieldDependencies, $formData){
		if($fieldDependencies == null || $fieldDependencies == false)
			return true;

		$truthVals = array();

		foreach($fieldDependencies["fields"] as $field) {
			array_push($truthVals, $this->evaluateSingleFieldDependency($field, $formData));
		}

		$condition = $this->checkCondition($fieldDependencies["if"], $truthVals);

		return !($condition === false || $fieldDependencies["action"] === "hide");
	}

	/**
	 * Function that checks if a single dependency for a field is met
	 * @param fieldRequirements 	The requirements for the single field
	 * @param formData 	 			The posted form data
	 * @return Bool 				True iff the single requirement is met
	 */
	private function evaluateSingleFieldDependency($fieldRequirements, $formData){
		if($fieldRequirements == null) return true;

		$value = (array_key_exists($fieldRequirements["field_name"], $formData)) ?
			$formData[$fieldRequirements["field_name"]] : "";

		if(array_key_exists("fixed", $fieldRequirements["value"])) {
			return $this->checkOperator(
				$fieldRequirements["operator"], 
				$value, 
				$fieldRequirements["value"]["fixed"]
			);
		} else if(array_key_exists("like", $fieldRequirements["value"])) {
			return $this->checkLikeOperator(
				$fieldRequirements["operator"],
				$value,
				$fieldRequirements["value"]["like"]
			);
		} else if(array_key_exists("regex", $fieldRequirements["value"])) {
			return $this->checkRegexOperator(
				$fieldRequirements["operator"],
				$value,
				$fieldRequirements["value"]["regex"]
			);
		}

		return true;
	}

	private function checkLikeOperator($operator, $a, $b) {
		return call_user_func(array($this, $this->likeOperators[$operator]), $a, $b);
	}

	private function checkRegexOperator($operator, $a, $b) {
		if($b[0] != "/") $b = "/" . $b;
		if(substr($b, -1) != "/") $b .= "/";
		return call_user_func(array($this, $this->regexOperators[$operator]), $a, $b);
	}

	private function checkOperator($operator, $a, $b) {
		return call_user_func(array($this, $this->operators[$operator]), $a, $b);
	}

	private function checkCondition($condition, $arr) {
		return call_user_func(array($this, $condition), $arr);
	}

	private function equals($a, $b) { return $a == $b; }
	private function does_not_equal($a, $b) {return $a != $b; }
	private function is_larger_than($a, $b) {return $a > $b; }
	private function is_larger_than_or_equal($a, $b) {return $a >= $b;}
	private function is_less_than($a, $b) {return $a < $b; }
	private function is_less_than_or_equal($a, $b) {return $a <= $b; }
	private function all($arr) {return count(array_unique($arr)) === 1 && current($arr) === true;}
	private function none($arr) {return count(array_unique($arr)) === 1 && current($arr) === false;}
	private function any($arr) {return in_array(true, $arr); }
	private function is_like($a, $b) {return strpos(strtolower($a), strtolower($b)) !== false; }
	private function is_not_like($a, $b) {return strpos(strtolower($a), strtolower($b)) === false; }
	private function matches_regex($a, $b) {return preg_match($b, $a) > 0; }
	private function not_matches_regex($a, $b) {return preg_match($b, $a) === 0; }

	private $likeOperators = array(
		"==" => "is_like",
		"!=" => "is_not_like"
	);

	private $regexOperators = array(
		"==" => "matches_regex",
		"!=" => "not_matches_regex"
	);

	private $operators = array(
		"==" => "equals",
		"!=" => "does_not_equal",
		">" => "is_larger_than",
		">=" => "is_larger_than_or_equal",
		"<" => "is_less_than",
		"<=" => "is_less_than_or_equal"
	);

	/**
	 * Function that checks the values for a key to see if all values
	 * satisfy all constraints
	 * @param $values 		The value, or value list, of the 
	 * 						field as submitted by the user
	 * @param $definition 	The field definition as defined in the 
	 * 						meta data schema
	 * @param $final (opt) 	True if the final check should be 
	 * 						performed (more strict)
	 * @return bool 		True iff the values satisfie all 
	 * 						constraints
	 */
	public function verifyKey($values, $definition, $final = false) {

		if(array_key_exists("multiple", $definition) || $definition["type"] == "checkbox") {
			$errors = array();

			$mult = array_key_exists("multiple", $definition) ? $definition["multiple"] : $definition["type_configuration"];
			if($final && array_key_exists("min", $mult) && $mult["min"] > 0 && $mult["min"] > sizeof($values)){
				$errors[] = ERROR_MIN_ENTRIES;
			}

			if(
				(!array_key_exists("infinite", $mult) || $mult["infinite"] === false) && 
				array_key_exists("max", $mult) && $mult["max"] !== false && $mult["max"] < sizeof($values)) {
				$errors[] = ERROR_MAX_ENTRIES;
			}

			foreach($values as $value) {
				$errors = array_merge($errors, $this->verifyField($value, $definition, $final));
				// $err = $this->verifyField($value, $definition, $final);
				// if($err < OK) {
				// 	return $errC;
				// }
			}

			return $errors;

		} else {
			if(gettype($values) == gettype(array()) && sizeof($values) > 1) {
				// var_dump($values); echo " fail because it should be a single value but doesn't seem to be single";
				return array(ERROR_SINGLE_ENTRY);
			}
			return $this->verifyField($values, $definition, $final);
		}
	}

	/** 
	 * Function that checks if a field satisfies all constraints
	 * defined in the meta data schema.
	 * @param $value 		The value, or value list, of the 
	 * 						field as submitted by the user
	 * @param $definition 	The field definition as defined in the 
	 * 						meta data schema
	 * @param $formdata 	The posted formdata for reference
	 * @param $final (opt) 	True if the final check should be 
	 * 						performed (more strict)
	 * @return bool 		True iff the value satisfies all 
	 * 						constraints
	 */
	private function verifyField($value, $definition, $formdata, $final = false) {
		$errors = array();

		if($final && $definition["required"] === True && !isset($value)) {
			$errors[] = ERROR_REQUIRED;
			// return false;
		}

		$conf = $definition["type_configuration"];
		if(!isset($conf)) {
			// echo $value . " fails because $conf is not set<br/>";
			$errors[] = WARNING_NO_CONF;
		}

		// Check maximum length
		if(array_key_exists("length", $conf) && $conf["length"] !== false && sizeof($value) > $conf["length"]){
			// echo $value . " fails because the max length (" . $conf["length"] . ") is less than the input length (" . sizeof($value) . ")<br/>";
			// return false;
			$errors[] = ERROR_MAX_LENGTH;
		}

		// Todo: verify this
		if(array_key_exists("pattern", $conf) && $conf["pattern"] != "*") {
			$patt = $conf["pattern"];

			if($patt[0] != "/") $patt = "/" . $patt;
			if(substr($patt, -1) != "/") $patt .= "/";

			if(preg_match($patt, $value) === 0 && !(!$final && (
					(is_array($value) && sizeof($value) === 0) ||
					(is_string($value) && strlen($value) === 0)
				))) {
				// echo "\"" . $value . "\" fails because the pattern " . $conf["pattern"] . " does not match the value";

				$errors[] = ERROR_REGEX;
				// return false;	
			}
		}

		if(array_key_exists("restricted", $conf) && $conf["restricted"] === true) {
			if(!array_key_exists("allow_create", $conf) || $conf["allow_create"] === false) {
				// TODO: check if the value exists for the key
				// Q: This requires another connection to iRODS for each field that uses restricted
				// values. Is this worth it?
			}
		}

		if(array_key_exists("begin", $conf)) {
			if(
				array_key_exists("step", $conf) && 
				$conf["step"] < 0 &&
				$conf["begin"] < $value
			) {
				// echo $value . " fails because the value is not in the specified range";
				// return false;
				$errors[] = ERROR_NOT_IN_RANGE;
			}
			else if($conf["begin"] > $value) {
				// echo $value . " fails because the value is not in the specified range";
				$errors[] = ERROR_NOT_IN_RANGE;
			}
		}

		if(array_key_exists("end", $conf)) {
			if(
				array_key_exists("step", $conf) && 
				$conf["step"] < 0 &&
				$conf["end"] > $value
			) {
				// echo $value . " fails because the value is not in the specified range";
				// return false;
				$errors[] = ERROR_NOT_IN_RANGE;
			}
			else if($conf["end"] < $value) {
				// echo $value . " fails because the value is not in the specified range";
				// return false;
				$errors[] = ERROR_NOT_IN_RANGE;
			}
		}

		if($definition["type"] == "datetime") {
			$err = $this->verifyDateTime($value, $definition, $formdata, $final);
			if($err != OK) {
				// echo $value . " fails because the value does not meet datetime standards?";
				$errors[] = $err;
			}
		}

		if(array_key_exists("options", $conf) && !in_array($value, $conf["options"])) {
			// echo $value . " fails because the value is not in the specified range";
			// return false;
			$errors[] = ERROR_NOT_IN_RANGE;
		}

		return $errors;
	}

	function callback_isNotEmpty($var) {
		return $var !== null && !empty($var) && $var !== "" && !(is_array($var) && sizeof($var) === 0);
	}

	private function verifyDateTime($value, $definition, $formdata, $final = false){

		if(!$this->callback_isNotEmpty($value)) {
			return OK;
		}

		$needsDashReg = "/.+[^:\/ -]$/";
		$regex = "/^";
		$format = "";
		$conf = $definition["type_configuration"];
		if(array_key_exists("show_years", $conf) && $conf["show_years"] !== false){
			$regex .= "\d{4}";
			$format .= "YYYY";
		}
		if(array_key_exists("show_months", $conf) && $conf["show_months"] !== false) {
			if(preg_match($needsDashReg, $regex)){
				$regex .= "-";
				$format .= "-";
			}
			$regex .= "(?:(?:0[1-9])|(?:1(?:0|1|2)))";
			$format .= "MM";
		}
		if(array_key_exists("show_days", $conf) && $conf["show_days"] !== false) {
			if(preg_match($needsDashReg, $regex)){
				$regex .= "-";
				$format .= "-";
			}
			$regex .= "(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))";
			$format .= "DD";
		}
		if(array_key_exists("show_time", $conf) && $conf["show_time"] !== false) {
			if(preg_match($needsDashReg, $regex)){
				$regex .= " ";
				$format .= "  ";
			}
			$regex .= "(?:[0-1][0-9]|2[0-3]):[0-5][0-9]";
			$format .= "HH:ii";
		}
		$regex .= "$/";

		if(!preg_match($regex, $value)){
			// echo "<p>$regex doesn't match $value</p>";
			// return false;
			return ERROR_INVALID_DATETIME_FORMAT;
		}

		if(array_key_exists("min_date_time", $conf) && $conf["min_date_time"] !== false) {
			if(array_key_exists("fixed", $conf["min_date_time"]) && $conf["min_date_time"]["fixed"] !== false) {
				try{
					$valDate = DateTime::createFromFormat($format, $value);
					$referenceDate = DateTime::createFromFormat($format, $conf["min_date_time"]["fixed"]);
					if($valDate < $referenceDate) {
						// echo "<p>$valDate is less than fixed $referenceDate</p>";
						// return false;
						return ERROR_DATE_LESS_THAN_FIXED;
					}
				} catch(exception $e) {
					// do nothing
					// var_dump($e);
				}
			} else if(array_key_exists("linked", $conf["min_date_time"]) && $conf["min_date_time"]["linked"] !== false) {
				if(array_key_exists($conf["min_date_time"]["linked"], $formdata)) {
					try{
						$valDate = DateTime::createFromFormat($format, $value);
						$refVal = array_filter(
							$formdata[$conf["min_date_time"]["linked"]], 
							array($this, "callback_isNotEmpty")
						);
						if(is_array($refVal)) {
							if(!$this->callback_isNotEmpty($refVal)) $refVal = array("");
							$refVal = min($refVal);
						}
						$referenceDate = DateTime::createFromFormat($format, $refVal);
						if($refVal == "" && $final) {
							// return false;
							return ERROR_REQUIRED;
						} else if($refVal != "" && $valDate < $referenceDate) {
							// return false;
							return ERROR_DATE_LESS_THAN_LINKED;
						}
					} catch(Exception $e) {
						// do nothing
						var_dump($e);
					}
				}
			}
		}

		if(array_key_exists("max_date_time", $conf) && $conf["max_date_time"] !== false) {
			if(array_key_exists("fixed", $conf["max_date_time"]) && $conf["max_date_time"]["fixed"] !== false) {
				try{
					$valDate = DateTime::createFromFormat($format, $value);
					$referenceDate = DateTime::createFromFormat($format, $conf["max_date_time"]["fixed"]);
					if($valDate > $referenceDate) {
						// return false;
						return ERROR_DATE_HIGHER_THAN_FIXED;
					}
				} catch(exception $e) {
					// do nothing
				}
			} else if(array_key_exists("linked", $conf["max_date_time"]) && $conf["max_date_time"]["linked"] !== false) {
				if(array_key_exists($conf["max_date_time"]["linked"], $formdata)) {
					try{
						$valDate = DateTime::createFromFormat($format, $value);
						$refVal = array_filter(
							$formdata[$conf["min_date_time"]["linked"]], 
							array($this, "callback_isNotEmpty")
						);
						if(is_array($refVal)) {
							if(!$this->callback_isNotEmpty($refVal)) $refVal = array("");
							$refVal = min($refVal);
						}
						$referenceDate = DateTime::createFromFormat($format, $refVal);
						if($refVal == "" && $final) {
							return ERROR_REQUIRED;
						} else if($refVal != "" && $valDate > $referenceDate) {
							// return false;
							return ERROR_DATE_HIGHER_THAN_LINKED;
						}
					} catch(exception $e) {
						// do nothing
					}
				}
			}
		}

		return OK;
	}

	public function getHtmlForRow($key, $config, $value, $indent = 0, $canEdit, $errors = false, $formdata) {
		$idn = "";
		for($i = 0; $i < $indent; $i++) {
			$idn .= "\t";
		}
		$indent = $idn;

		/**
		 * Template params
		 * 1) key
		 * 2) value
		 * 3) Label
		 * 4) Help text
		 * 5) input
		 * 6) shadowInput
		 * 7) template
		 * 8) next index for multiple
		 * 9) error class
		 * 10) data-attributes for row (depends options)
		 * 11) indent
		 */
		$template =  <<<'EOT'
%11$s<tr class="form-group%9$s"%10$s id="metadata-row-%1$s">
%11$s	<td>
%11$s		<span data-toggle="tooltip" data-placement="top" title="%4$s" data-html="true">
%11$s			%3$s
%11$s 		</span>
%11$s	</td>
%11$s	<td>
%11$s 		<span class="hideWhenEdit" id="label-%1$s">%2$s</span>
%11$s 		%5$s
%11$s 		%6$s
EOT;
		if(array_key_exists("multiple", $config)):
			$template .= <<<'EOT'
%11$s 		<span class="btn btn-default glyphicon glyphicon-plus showWhenEdit" 
%11$s 			data-template="%7$s" data-nextindex="%8$d" onclick="addValueRow('%1$s')" id="addRow-%1$s">
%11$s 			Add value
%11$s 		</span>
EOT;
		endif;
		$template .= <<<'EOT'
%11$s 	</td>
%11$s 	<td width="50">

EOT;
		if($canEdit):
			$template .= <<<'EOT'
%11$s 		<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit button-%1$s" 
%11$s			onclick="edit('%1$s')"></span>
%11$s 		<span type="button"
%11$s			class="btn btn-default glyphicon glyphicon-remove showWhenEdit button-%1$s"
%11$s 			onclick="cancelEdit('%1$s')"></span>

EOT;
		endif;
		$template .= <<<'EOT'
%11$s 	</td>
%11$s</tr>

EOT;

		$input = "";
		$inputName = 'metadata[%1$s]';
		$inputArrayName = $inputName . '[%2$s]';

		if($formdata && array_key_exists($key, $formdata)) {
			$currentValue = $formdata[$key];
		} else {
			$currentValue = $value;
		}

		if($canEdit){
			if (!array_key_exists("multiple", $config) && is_string($currentValue) || $config["type"] == "checkbox") {
				$input = $this->findProperInput($key, sprintf($inputName, $key), $config, $currentValue);
				$rowInputTemplate = $this->findProperInput($key, sprintf($inputName, $key), $config, "");
			} else {
				// 1) key
				// 2) index
				// 3) input
				$deleteRowButtonTemplate = <<<'EOT'
<div id="row-%1$s-%2$d" class="row showWhenEdit fixed-row-%1$s">
	<span class="col-xs-11">
		%3$s
	</span>
	<span class="col-xs-1"><span class="btn btn-default glyphicon glyphicon-trash" onclick="removeFixedRow('#row-%1$s-%2$d');"></span></span>
</div>
EOT;

				$rowInputTemplate = $this->findProperInput($key, sprintf($inputArrayName, $key, "__row_input_id__"), $config, "");
				if((array_key_exists("multiple", $config) || $config["type"] == "checkbox") && is_string($currentValue)) {
					$input = sprintf(
						$deleteRowButtonTemplate,
						$key,
						0,
						$this->findProperInput($key, sprintf($inputArrayName, $key, 0), $config, $currentValue)
					);
				} else {
					foreach(array_keys($currentValue) as $i) {
						$input .= sprintf(
							$deleteRowButtonTemplate,
							$key,
							$i,
							$this->findProperInput($key, sprintf($inputArrayName, $key, $i), $config, $currentValue[$i])
						);
					}
				}
			}
		}

		if(array_key_exists("multiple", $config) || $config["type"] == "checkbox") {
			$v = "<ul class=\"multi-value-list\">";
			if(is_string($currentValue)) {
				$v .= "<li>" . $currentValue . "</li>";
			} else {
				foreach($currentValue as $val) {
					$v .= "<li>" . $val . "</li>";
				}
			}
			$v .= "</ul>";
			$multiValueList = $v;
		}

		$rowDepends = "";

		if(array_key_exists("depends", $config)) {
			$rowDepends .= htmlentities(" data-depends=\"" . json_encode($config["depends"]) . "\"");
		}

		$hasError = sizeof($errors) > 0;

		$errorHelperText = $this->buildErrorExplanation($key, $config, $errors);

		$help = $errorHelperText === "" ? $config["help"] : sprintf("<p>%s</p>%s", $config["help"], $errorHelperText);

		return sprintf(
			$template,
			$key,
			array_key_exists("multiple", $config) || $config["type"] == "checkbox" ? $multiValueList : $currentValue,
			$config["label"],
			$help,
			$input,
			$this->getShadowInput($key, $config, $value),
			$canEdit ? htmlentities($rowInputTemplate) : "",
			is_array($currentValue) && sizeof($currentValue) > 0 ? max(array_keys($currentValue)) + 1 : 1,
			$hasError ? " has-error" : "",
			$rowDepends,
			$indent
		);
	}

	function buildErrorExplanation($key, $definitions, $errors) {
		if(sizeof($errors) === 0) return "";

		$errArr = array();
		if(in_array(ERROR_MIN_ENTRIES, $errors)) {
			if(array_key_exists("multiple", $definitions) && array_key_exists("min", $definitions["multiple"])) {
				$min = $definitions["multiple"]["min"];
			} else {
				$min = 0;
			}
			$errArr[] = sprintf(lang('formError_min_entries'), $min);
		}

		if(in_array(ERROR_MAX_ENTRIES, $errors)) {
			if(array_key_exists("multiple", $definitions) && array_key_exists("max", $definitions["multiple"])) {
				$max = $definitions["multiple"]["max"];
			} else {
				$max = 0;
			}

			$errArr[] = sprintf(lang('formError_max_entries'), $max);
		}

		if(in_array(ERROR_SINGLE_ENTRY, $errors)) {
			$errArr[] = lang('formError_single_entry');
		}

		if(in_array(ERROR_REQUIRED, $errors)) {
			$errArr[] = lang('formError_required');
		}

		if(in_array(ERROR_MAX_LENGTH, $errors)) {
			if(
				in_array("type_configuration", $definitions) 
				&& in_array("length", $definitions["type_configuration"])
				AND $definitions["type_configuration"]["length"] !== false
			) {
				$length = $definitions["type_configuration"]["length"];
			} else {
				$length = 0;
			}

			$errArr[] = sprintf(lang('formError_max_length'), $length);
		}

		if(in_array(ERROR_REGEX, $errors)) {
			$errArr[] = lang('formError_regex');
		}

		if(in_array(ERROR_NOT_IN_RANGE, $errors)) {
			$errArr[] = lang('formError_not_in_range');
		}

		if(in_array(ERROR_INVALID_DATETIME_FORMAT, $errors)) {
			$errArr[] = lang('formError_datetime_format');
		}

		if(in_array(ERROR_DATE_LESS_THAN_FIXED, $errors)) {
			if(
				array_key_exists("type_configuration", $definitions) &&
				array_key_exists("min_date_time", $definitions["type_configuration"]) &&
				$definitions["type_configuration"]["min_date_time"] !== false &&
				array_key_exists("fixed", $definitions["type_configuration"]["min_date_time"])

			) {
				$mindatetime = $definitions["type_configuration"]["min_date_time"]["fixed"];
			} else {
				$mindatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('formError_datetime_less_fixed'), $mindatetime);
		}

		if(in_array(ERROR_DATE_LESS_THAN_LINKED, $errors)) {
			if(
				array_key_exists("type_configuration", $definitions) &&
				array_key_exists("min_date_time", $definitions["type_configuration"]) &&
				$definitions["type_configuration"]["min_date_time"] !== false &&
				array_key_exists("linked", $definitions["type_configuration"]["min_date_time"])

			) {
				$mindatetime = $definitions["type_configuration"]["min_date_time"]["linked"];
			} else {
				$mindatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('formError_datetime_less_linked'), $mindatetime);
		}


		if(in_array(ERROR_DATE_HIGHER_THAN_FIXED, $errors)) {
			if(
				array_key_exists("type_configuration", $definitions) &&
				array_key_exists("max_date_time", $definitions["type_configuration"]) &&
				$definitions["type_configuration"]["max_date_time"] !== false &&
				array_key_exists("fixed", $definitions["type_configuration"]["max_date_time"])

			) {
				$maxdatetime = $definitions["type_configuration"]["maxn_date_time"]["fixed"];
			} else {
				$maxdatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('formError_datetime_higher_fixed'), $maxdatetime);
		}

		if(in_array(ERROR_DATE_HIGHER_THAN_LINKED, $errors)) {
			if(
				array_key_exists("type_configuration", $definitions) &&
				array_key_exists("max_date_time", $definitions["type_configuration"]) &&
				$definitions["type_configuration"]["max_date_time"] !== false &&
				array_key_exists("linked", $definitions["type_configuration"]["max_date_time"])

			) {
				$maxdatetime = $definitions["type_configuration"]["maxn_date_time"]["linked"];
			} else {
				$maxdatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('formError_datetime_higher_linked'), $maxdatetime);
		}

		$html = sprintf("<b>%s</b><ul>", lang('formError_heading'));
		foreach($errArr as $ea) {
			$html .= sprintf("<li>%s</li>", $ea);
		}
		$html .= "</ul>";

		return $html;
	}

	function findProperInput($key, $inputName, $config, $value) {
		switch($config["type"]) {
			case "text":
				$input = $this->getTextInput($key, $inputName, $config, $value);
				break;
			case "select":
				$input = $this->getSelectInput($key, $inputName, $config, $value);
				break;
			case "radio":
				$input = $this->getRadioInput($key, $inputName, $config, $value);
				break;
			case "checkbox":
				$input = $this->getCheckboxesInput($key, $inputName, $config, $value);
				break;
			case "bool":
				$input = $this->getBoolInput($key, $inputName, $config, $value);
				break;
			case "datetime":
				$input = $this->getTimeInput($key, $inputName, $config, $value);
				break;
			case "custom":
				$input = $this->getCustomInput($key, $inputName, $config, $value);
				break;
			default:
				$input = "<p>default value (no type found)</p>";
				break;
		}
		return $input;
	}

	private function getCustomInput($key, $inputName, $config, $value) {
		if(!array_key_exists("custom_type", $config)) {
			return "Field configuration with custom field needs 'custom_type' key";
		}
		$input = "";
		switch($config["custom_type"]) {
			case "userlist" : 
				$input = $this->getUserlistInput($key, $inputName, $config, $value);
				break;
			case "directorylist" :
				$input = $this->getStudielistInput($key, $inputName, $config, $value);
				break;
			default :
				$input = "custom (default)";
				break;
		}

		return $input;
	}

	private function getShadowInput($key, $config, $value) {
		if(is_array($value) && sizeof($value) > 0) {
			$input = "";
			for($i = 0; $i < sizeof($value); $i++) {
				$input .= sprintf(
					'<input type="hidden" name="metadata-shadow[%1$s][%2$d]" value="%3$s"/>',
					$key,
					$i,
					$value[$i]
				);
			}
		} else if(array_key_exists("multiple", $config) || $config["type"] == "checkbox") {
			if(is_array($value)) $value = "";
			$input = sprintf(
					'<input type="hidden" name="metadata-shadow[%1$s][0]" value="%2$s"/>',
					$key,
					$value
				);
		} else {
			$input = sprintf(
				'<input type="hidden" name="metadata-shadow[%1$s]" value="%2$s"/>',
				$key,
				$value
			);
		}

		return $input;
	}



	public function getTextInput($key, $inputName, $config, $value) {
		/**
		 * Template params:
		 * 1) field key (same as metadata key)
		 * 2) Input name
		 * 3) Current value
		 * 4) length="<length>" if not false, "" otherwise
		 */
		if($config["type_configuration"]["longtext"])
			return $this->getLongtextInput($key, $inputName, $config, $value);

		$template ='<input type="text"';
		$template .= ' name="%2$s"';
		$template .= ' %4$s class="showWhenEdit input-%1$s" value="%3$s"/>';

		$length = $config["type_configuration"]["length"] ? 
			sprintf(
				"length=[%d]", 
				$config["type_configuration"]["length"]
			)
			: "";

		return sprintf($template, $key, $inputName, $value, $length);
	}

	public function getLongtextInput($key, $inputName, $config, $value) {
		$template = '<textarea name="%2$s"';
		$template .= ' class="showWhenEdit input-%1$s">%3$s</textarea>';

		return sprintf($template, $key, $inputName, $value);
	}

	public function getTimeInput($key, $inputName, $config, $value) {
		$tc = $config["type_configuration"];
		$template = '<div class="input-group date showWhenEdit input-%1$s" id="metadata-datepicker-%1$s">';
		$template .= '<input type="text" class="form-control metadata-datepicker input-%1$s" ';
		$template .= 'value="%3$s" name="%2$s" %4$s/>';
		$template .= '</div>';

		$extra = "data-typeconfiguration=\"" . htmlentities(json_encode($config["type_configuration"])) . "\"";

		return sprintf(
			$template, 
			$key,
			$inputName,
			$value,
			$extra
		);

	}

	public function getSelectInput($key, $inputName, $config, $value) {
		$tc = $config["type_configuration"]; // tc = TypeConfiguration;
		if($tc["restricted"]) {
			$template = '<input name="%2$s" type="hidden"';
			$template .= ' value="%3$s"';
			$template .= ' class="showWhenEdit meta-suggestions-field input-%1$s"';
			$template .= ' data-placeholder--id="%3$s"';
			$template .= ' data-placeholder--text="%3$s"';
			$template .= ' data-for="%1$s"';
			$template .= ' %4$s';
			$template .= '/>';

			$extra = sprintf(
				'data-allowcreate="%1$b"',
				$tc["allow_create"]
			);

			return sprintf(
				$template, 
				$key,
				$inputName,
				$value,
				$extra
			);
		} else {
			$options = "";
			$optTemplate = '<option value="%1$s">%1$s</option>';
			if(! array_key_exists("options", $tc) || sizeof($tc["options"]) == 0){
				for(
					$i = $tc["begin"]; 
					( $tc["begin"] <= $tc["end"] && $i <= $tc["end"] ) ||
					( $tc["begin"] > $tc["end"] && $i >= $tc["end"] );
					$i += $tc["step"]
				) {
					$options .= sprintf($optTemplate, $i, $i == $value ? ' selected=""' : '');
				}
			} else {
				foreach($tc["options"] as $option) {
					$options .= sprintf($optTemplate, $option, $option == $value ? ' selected=""' : '');
				}
			}
			
			$select ='<select';
			$select .= ' name="%2$s';
			$select .= ' class="showWhenEdit chosen input-%1$s">%4$s</select>';

			return sprintf(
					$select,
					$key,
					$inputName,
					$value,
					$options
				);
		}
	}

	public function getBoolInput($key, $inputName, $config, $value) {
		if(!array_key_exists("type_configuration", $config)) $config["type_configuration"] = array();

		$yesVal = array_key_exists("true_val", $config["type_configuration"]) ? 
			$config["type_configuration"]["true_val"] : "Yes";
		$noVal = array_key_exists("false_val", $config["type_configuration"]) ? 
			$config["type_configuration"]["false_val"] : "No";

		$config["type_configuration"]["options"] = array($yesVal, $noVal);

		return $this->getOptionsInput($key, $inputName, $config, $value, "radio");

	}

	public function getCheckboxesInput($key, $inputName, $config, $value) {
		return $this->getOptionsInput($key, $inputName, $config, $value, "checkbox");
	}

	public function getRadioInput($key, $inputName, $config, $value) {
		return $this->getOptionsInput($key, $inputName, $config, $value, "radio");
	}

	private function getOptionsInput($key, $inputName, $config, $value, $type="radio") {
		
		// 1) key
		// 2) inputName
		// 3) value
		// 4) extra
		// 5) type
		$template = '<div class="%5$s showWhenEdit input-%1$s">';
		$template .= '	<label>';
		$template .= '		<input type="%5$s" name="%2$s"%4$s value="%3$s"/>';
		$template .= '		%3$s';
		$template .= '	</label>';
		$template .= '</div>';

		$input = '';

		foreach($config["type_configuration"]["options"] as $option) {
			$input .= sprintf(
				$template,
				$key,
				$type == "checkbox" ? $inputName . '[]' : $inputName,
				$option,
				((is_string($value) && $value == $option)
					|| ((is_array($value) && 
						in_array($option, $value)))) 
					? ' checked="checked"' : 
					'',
				$type
			);
		};

		return $input;
	}

	public function getUserlistInput($key, $inputName, $config, $value) {
		$template = '<input name="%2$s" type="hidden"';
		$template .= ' value="%3$s"';
		$template .= ' class="showWhenEdit select-user-from-group input-%1$s"';
		$template .= ' data-placeholder--id="%3$s"';
		$template .= ' data-placeholder--text="%3$s"';
		$template .= ' %4$s';
		$template .= '/>';

		$displayroles = 'data-displayroles--admins="%1$b"';
		$displayroles .= ' data-displayroles--users="%2$b"';
		$displayroles .= ' data-displayroles--readonly="%3$b"';
		$displayroles .= ' data-allowcreate="%4$b"';

		$extra = sprintf(
			$displayroles,
			$config["type_configuration"]["show_admins"],
			$config["type_configuration"]["show_users"],
			$config["type_configuration"]["show_readonly"],
			$config["type_configuration"]["allow_create"]
		);

		return sprintf(
			$template, 
			$key, 
			$inputName,
			$value,
			$extra
		);
	}

	public function getStudielistInput($key, $inputName, $config, $value) {
		$template = '<input name="%2$s" type="hidden"';
		$template .= ' value="%3$s"';
		$template .= ' class="showWhenEdit select-dir-from-group input-%1$s"';
		$template .= ' %4$s/>';

		$extra = 'data-typeconfiguration="' . htmlentities(json_encode($config["type_configuration"])) . '"';

		return sprintf(
			$template,
			$key,
			$inputName,
			$value,
			$extra
		);
	}

	public function getEditButtons() {
		$template = <<<EOT
	<div class="container-fluid metadata_form_buttons">
		<div class="row">
			<button class="btn btn-default showWhenEdit col-md-4 metadata-btn-editMetaSubmit"
				disabled="disabled" type="submit">
				<span class="glyphicon glyphicon-save"></span>
				Submit
			</button>
			<button type="button" class="btn btn-default hideWhenEdit col-md-4 metadata-btn-editAll" 
				action="" onclick="enableAllForEdit()">
				<span class="glyphicon glyphicon-pencil"></span>
				Edit all
			</button>
			<button type="button" 
				class="btn btn-default showWhenEdit col-md-4 metadata-btn-cancelAll"
				disabled="disabled" action="" onclick="disableAllForEdit()">
				<span class="glyphicon glyphicon-remove"></span>
				Cancel edit
			</button>
		</div>
	</div>
EOT;
		return $template;
	}

}