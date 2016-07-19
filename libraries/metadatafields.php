<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class metadataFields {

	public function __construct() {
		$this->CI =& get_instance();
	}

	public function getFields($object, $isCollection) {
		$iRodsAccount = $this->CI->rodsuser->getRodsAccount();
		$fields = array();
		$keys = array_keys($this->fields);
		$values = $this->CI->metadatamodel->getValuesForKeys2($iRodsAccount, $keys, $object);

		if(!$values) return false;

		foreach($this->fields as $key => $arr) {
			if(array_key_exists("multiple", $arr) || $arr["type"] == "checkbox") {
				$arr["value"] = $values[$key];
			} else {
				if(sizeof($values[$key]) > 0) {
					$arr["value"] = $values[$key][0];
				} else {
					$arr["value"] = "";
				}
			}
			$fields[$key] = $arr;
		}

		return $fields;
	}

	public function getHtmlForRow($key, $config, $value, $indent = 0, $permissions) {
		

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
		 * 9) indent
		 */
		$template =  <<<'EOT'
%9$s<tr>
%9$s	<td>
%9$s		<span data-toggle="tooltip" data-placement="top" title="%4$s">
%9$s			%3$s
%9$s 		</span>
%9$s	</td>
%9$s	<td>
%9$s 		<span class="hideWhenEdit" id="label-%1$s">%2$s</span>
%9$s 		%5$s
%9$s 		%6$s
EOT;
		if(array_key_exists("multiple", $config)):
			$template .= <<<'EOT'
%9$s 		<span class="btn btn-default glyphicon glyphicon-plus showWhenEdit" 
%9$s 			data-template="%7$s" data-nextindex="%8$d" onclick="addValueRow('%1$s')" id="addRow-%1$s">
%9$s 			Add value
%9$s 		</span>
EOT;
		endif;
		$template .= <<<'EOT'
%9$s 	</td>
%9$s 	<td width="50">

EOT;
		if($permissions->administrator):
			$template .= <<<'EOT'
%9$s 		<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit button-%1$s" 
%9$s			onclick="edit('%1$s')"></span>
%9$s 		<span type="button"
%9$s			class="btn btn-default glyphicon glyphicon-remove showWhenEdit button-%1$s"
%9$s 			onclick="cancelEdit('%1$s')"></span>

EOT;
		endif;
		$template .= <<<'EOT'
%9$s 	</td>
%9$s</tr>

EOT;

		$input = "";
		$inputName = 'metadata[%1$s]';
		$inputArrayName = $inputName . '[%2$s]';

		if($permissions->administrator){
			if (!array_key_exists("multiple", $config) && is_string($value) || $config["type"] == "checkbox") {
				$input = $this->findProperInput($key, sprintf($inputName, $key), $config, $value);
				$rowInputTemplate = $this->findProperInput($key, sprintf($inputName, $key), $config, "");
			} else {
				// 1) key
				// 2) index
				// 3) input
				$deleteRowButtonTemplate = <<<'EOT'
<div id="row-%1$s-%2$d" class="row showWhenEdit fixed-row-%1$s">
	<span class="col-md-11">
		%3$s
	</span>
	<span class="col-md-1"><span class="btn btn-default glyphicon glyphicon-trash" onclick="removeFixedRow('#row-%1$s-%2$d');"></span></span>
</div>
EOT;



				$rowInputTemplate = $this->findProperInput($key, sprintf($inputArrayName, $key, "__row_input_id__"), $config, "");
				if((array_key_exists("multiple", $config) || $config["type"] == "checkbox") && is_string($value)) {
					$input = sprintf(
						$deleteRowButtonTemplate,
						$key,
						0,
						$this->findProperInput($key, sprintf($inputArrayName, $key, 0), $config, $value)
					);
				} else {
					for($i = 0; $i < sizeof($value); $i++) {
						$input .= sprintf(
							$deleteRowButtonTemplate,
							$key,
							$i,
							$this->findProperInput($key, sprintf($inputArrayName, $key, $i), $config, $value[$i])
						);
					}
				}
			}
		}

		if(array_key_exists("multiple", $config) || $config["type"] == "checkbox") {
			$v = "<ul class=\"multi-value-list\">";
			if(is_string($value)) {
				$v .= "<li>" . $value . "</li>";
			} else {
				foreach($value as $val) {
					$v .= "<li>" . $val . "</li>";
				}
			}
			$v .= "</ul>";
			$multiValueList = $v;
		}

		return sprintf(
			$template,
			$key,
			array_key_exists("multiple", $config) || $config["type"] == "checkbox" ? $multiValueList : $value,
			$config["label"],
			$config["help"],
			$input,
			$this->getShadowInput($key, $config, $value),
			htmlentities($rowInputTemplate),
			is_array($value) ? sizeof($value) : 1,
			$indent
		);

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
			case "custom":
				$input = $this->getCustomInput($key, $inputName, $config, $value);
				break;
			default:
				var_dump($key);
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

	public function getTimeInput() {

	}

	public function getSelectInput($key, $inputName, $config, $value) {
		$tc = $config["type_configuration"]; // tc = TypeConfiguration;
		if($tc["restricted"]) {
			$template = '<input name="%2$s" type="hidden"';
			$template .= ' value="%3$s"';
			$template .= ' class="showWhenEdit meta-suggestions-field input-%1$s"';
			$template .= ' data-placeholder--id="%3$s"';
			$template .= ' data-placeholder--text="%3$s"';
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
				$value == $option 
					|| (is_array($value) && 
						in_array($option, $value)) 
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
	}// all? in study? allow create? show admins/edit/readonly

	public function getStudielistInput() {
		
	} // all? in study?

			

	public $fields = array (
		"example_checkbox" => array(
			"label" => "Example checkboxes",
			"help" => "The checkbox field can be used to provide multiple options, of which zero or more can be selected. Generally used with only few options",
			"type" => "checkbox",
			"type_configuration" => array (
				"options" => array(
						"option 1",
						"option 2",
						"option 3",
						"option 4",
						"option 5",
						"option 6",
						"option 7"
					),
				"min" => false, // TODO min and max values check if enough and not too many are selected
				"max" => false
				),
			"required" => true,
			"allow_empty" => true,
			"depends" => false,
		),
		"example_radios" => array(
			"label" => "Example radio buttons",
			"help" => "The radio field can be used to provide multiple options, of which exactly one can be selected. Generally used with only few options",
			"type" => "radio",
			"type_configuration" => array (
				"options" => array(
						"option 1",
						"option 2",
						"option 3",
						"option 4",
						"option 5",
						"option 6",
						"option 7"
					),
				),
			"required" => true,
			"allow_empty" => true,
			"depends" => false
		),
		"example_bool" => array(
			"label" => "Publish dataset",
			"help" => "Will this dataset be published?",
			"type" => "bool",
			"type_configuration" => array(
				"true_val" => "yes",
				"false_val" => "no"
			),
			"required" => true,
			"allow_empty" => true,
			"depends" => false
		),
		"example_select" => array(
				"label" => "Example select",
				"help" => "The select field can be used to provide multiple options",
				"type" => "select",
				"type_configuration" => array (
					"restricted" => true,
					"allow_create" => false,
					"begin" => 0,
					"end" => 2016,
					"step" => 1,
					// "options" => array(
					// 		"option 1",
					// 		"option 2",
					// 		"option 3",
					// 		"option 4",
					// 		"option 5",
					// 		"option 6",
					// 		"option 7"
					// 	)
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"project_id" => array (
				"label" => "Project ID",
				"help" => "The unique identifier of this project",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false,
				"multiple" => array(
						"min" => 0,
						"max" => 100,
						"infinite" => false
					)
			),


		"project_name" => array(
				"label" => "Project name",
				"help" => "Enter a descriptive name for the project",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"project_description" => array(
				"label" => "Project description",
				"help" => "Enter a short description for the project",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => true
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false,
				"multiple" => array(
						"min" => 0,
						"max" => 100,
						"infinite" => false
					)
			),

		// TODO: allow repeat
		"dataset_owner" => array (
				"label" => "Primary Investigator",
				"help" => "Enter the username of the primary investigator or contact person for this dataset",
				"type" => "custom",
				"custom_type" => "userlist",
				"type_configuration" => array (
					"allow_create" => false,
					"show_admins" => true,
					"show_users" => true,
					"show_readonly" => true
				),
				"required" => true,
				"allow_empty" => false,
				"depends" => false,
				"multiple" => array(
						"min" => 0,
						"max" => 100,
						"infinite" => false
					)
			),

		"discipline" => array(
				"label" => "Discipline",
				"help" => "Enter the discipline for this project",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"study_id" => array(
				"label" => "Study ID",
				"help" => "Enter the unique identifier for this study",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false

			),

		"study_name" => array(
				"label" => "Study name",
				"help" => "Enter a descriptive name for this study",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_id" => array(
				"label" => "Dataset ID",
				"help" => "The unique identifier for this dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false

			),

		"dataset_title" => array(
				"label" => "Dataset title",
				"help" => "Enter a descriptive title for this dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_description" => array(
				"label" => "Dataset description",
				"help" => "Enter a short description of the dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_collectiondate_start" => array(
				"label" => "Start collection date",
				"help" => "Enter the date the collection process started",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_collectiondate_end" => array(
				"label" => "End collection date",
				"help" => "Enter the date the collection process was finished",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_version" => array(
				"label" => "Dataset version",
				"help" => "Enter the version of the dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"predecessor" => array(
				"label" => "Underlying dataset",
				"help" => "Enter the name of the dataset this dataset was derived from",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"creator" => array(
				"label" => "Creator",
				"help" => "Select the user that led the collection process. This person should know all the ins and outs of the dataset",
				"type" => "custom",
				"custom_type" => "userlist",
				"type_configuration" => array (
					"allow_create" => false,
					"show_admins" => true,
					"show_users" => true,
					"show_readonly" => true
				),
				"required" => true,
				"allow_empty" => false,
				"depends" => false
			),

		"unit_analysis" => array(
				"label" => "Unit analysis",
				"help" => "E.g. groups, individuals (select from list",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"region_name" => array(
				"label" => "Region name",
				"help" => "Enter the name of the region this dataset was collected",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_period_start" => array(
				"label" => "Start date",
				"help" => "Enter the year the dataset starts in",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_period_end" => array(
				"label" => "End date",
				"help" => "Enter the year the dataset ends in",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"dataset_contact" => array(
				"label" => "Contact person",
				"help" => "Select the username from the contact person for this dataset",
				"type" => "custom",
				"custom_type" => "userlist",
				"type_configuration" => array (
					"allow_create" => false,
					"show_admins" => true,
					"show_users" => true,
					"show_readonly" => true
				),
				"required" => true,
				"allow_empty" => false,
				"depends" => false
			),

		"dataset_language" => array(
				"label" => "Dataset language",
				"help" => "Enter the language of the dataset contents",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),

		"subject" => array (
				"label" => "Subject",
				"help" => "Enter the subject of the dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),
		"aggregate_level" => array (
				"label" => "Aggragate level",
				"help" => "Enter the aggragate level of the dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),
		"location" => array (
				"label" => "Location",
				"help" => "Enter the location of the dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),
		"time" => array (
				"label" => "Time",
				"help" => "Enter the time this dataset was created",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => false
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			),
		"method" => array (
				"label" => "Method",
				"help" => "Explain the method used to create this dataset",
				"type" => "text",
				"type_configuration" => array (
					"length" => false,
					"pattern" => "*",
					"longtext" => true
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			)
	);

}