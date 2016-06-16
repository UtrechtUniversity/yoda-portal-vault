<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class metadataFields {

	public function __construct() {
		$this->CI =& get_instance();
	}

	public function getFields($object, $isCollection) {
		$iRodsAccount = $this->CI->rodsuser->getRodsAccount();
		$fields = array();
		if($this->fields != null && sizeof($this->fields)) {
			foreach($this->fields as $key => $arr) {
				$arr["value"] = 
					$this->CI->metadatamodel->getValueForKey(
						$iRodsAccount,
						$key,
						$object,
						$isCollection);
					$fields[$key] = $arr;
			}
		}

		return $fields;
	}

	public function getHtmlForRow($key, $config, $value, $indent = 0, $permissions) {
		

		$indent = "";
		for($i = 0; $i < $indent; $i++) {
			$indent .= "\t";
		}

		/**
		 * Template params
		 * 1) key
		 * 2) value
		 * 3) Label
		 * 4) Help text
		 * 5) input
		 * 6) indent
		 */
		$template =  <<<'EOT'
%6$s<tr>
%6$s	<td>
%6$s		<span data-toggle="tooltip" data-placement="top" title="%4$s">
%6$s			%3$s
%6$s 		</span>
%6$s	</td>
%6$s	<td>
%6$s 		<span class="hideWhenEdit" id="label-%1$s">%2$s</span>
%6$s 		%5$s
%6$s 	</td>
%6$s 	<td width="50">
EOT;
		if($permissions->administrator):
			$template .= <<<'EOT'
%6$s 		<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit button-%1$s" 
%6$s			onclick="edit('%1$s')"></span>
%6$s 		<span type="button"
%6$s			class="btn btn-default glyphicon glyphicon-remove showWhenEdit button-%1$s"
%6$s 			onclick="cancelEdit('%1$s')"></span>
EOT;
		endif;
		$template .= <<<'EOT'
%6$s 	</td>
%6$s</tr>
EOT;


		$input = "";

		if($permissions->administrator){
			switch($config["type"]) {
				case "text" :
					$input = $this->getTextInput($key, $config, $value);
					break;
				case "custom" :
					$input = $this->getCustomInput($key, $config, $value);
					break;
				default:
					$input = "misc type";
					break;
			}
		}

		return sprintf(
			$template,
			$key,
			$value,
			$config["label"],
			$config["help"],
			$input,
			$indent
		);

	}

	private function getCustomInput($key, $config, $value) {
		if(!array_key_exists("custom_type", $config)) {
			return "Field configuration with custom field needs 'custom_type' key";
		}
		$input = "";
		switch($config["custom_type"]) {
			case "userlist" : 
				$input = $this->getUserlistInput($key, $config, $value);
				break;
			default :
				$input = "custom (default)";
				break;
		}

		return $input;
	}

	public function getTextInput($key, $config, $value) {
		/**
		 * Template params:
		 * 1) field key (same as metadata key)
		 * 2) Current value
		 * 3) length="<length>" if not false, "" otherwise
		 */
		if($config["type_configuration"]["longtext"])
			return $this->getLongtextInput($key, $config, $value);

		$template ='<input type="text"';
		$template .= ' id="input-%1$s" name="metadata[%1$s]"';
		$template .= ' data-defaultvalue="%2$s"';
		$template .= ' %3$s class="showWhenEdit" value="%2$s"/>';

		$length = $config["type_configuration"]["length"] ? 
			sprintf(
				"length=[%d]", 
				$config["type_configuration"]["length"]
			)
			: "";

		return sprintf($template, $key, $value, $length);
	}

	public function getLongtextInput($key, $config, $value) {
		$template = '<textarea id="input-%1$s" name="metadata[%1$s]"';
		$template .= ' data-defaultvalue="%2$s"';
		$template .= ' class="showWhenEdit">%2$s</textarea>';

		return sprintf($template, $key, $value);
	}

	public function getTimeInput() {

	}

	public function getSelectInput($key, $config, $value) {
		// TODO work in progress
		$exampleField = array(
				"label" => "Example select",
				"help" => "The select field can be used to provide multiple options",
				"type" => "select",
				"type_configuration" => array (
					"restricted" => true,
					"allow_create" => "*",
					"begin" => 0,
					"end" => 2016,
					"step" => 1,
					"options" => array(
							"option 1",
							"option 2",
							"option 3",
							"option 4",
							"option 5",
							"option 6",
							"option 7"
						)
					),
				"required" => true,
				"allow_empty" => true,
				"depends" => false
			);

		if($config["type_configuration"]["restricted"]) {
			$template = '<input name="metadata[%1$s]" type="hidden"';
			$template .= ' id="input-%1$s" value="%2$s"';
			$template .= ' class="showWhenEdit meta-suggestions-field"';
			$template .= ' data-defaultvalue="%2$s"';
			$template .= ' data-placeholder--id="%2$s"';
			$template .= ' data-placeholder--text="%2$s"';
			$template .= ' %3$s';
			$template .= '/>';

			$displayroles .= 'data-allowcreate="%4$b"';

			$extra = sprintf(
				'data-allowcreate="%1$b"',
				$config["type_configuration"]["allow_create"]
			);

			return sprintf(
				$template, 
				$key, 
				$value,
				$extra
			);
		} else {
			$options = "";
			$optTemplate = '<option value="%1$s"%2$s>%1$2</option>';
			if(!array_key_exist("options", $config["type_configuration"]) || sizeof($config["type_configuration"]["options"]) == 0){
				for($i = $config["type_configuration"]["begin"]; $i <= $config["type_configuration"]["end"]; $i += $config["type_configuration"]["step"]) {
					$options .= sprintf($optTemplate, $i, $i == $value ? ' selected=""' : '');
				}
			} else {
				foreach($config["type_configuration"]["options"] as $option) {
					$options .= sprintf($optTemplate, $option, $option == $value ? ' selected=""' : '');
				}
			}
			$select ='<select';
			$select .= ' id="input-%1$s" name="metadata[%1$s]"';
			$select .= ' data-defaultvalue="%2$s"';
			$select .= ' %3$s class="showWhenEdit">%4$s</select>';

			return sprintf(
					$template,
					$key,
					$value,
					$options
				);
		}
	}

	public function getBoolInput() {
		
	}

	public function getCheckboxesInput() {
		
	}

	public function getRadioInput() {
		
	}

	public function getUserlistInput($key, $config, $value) {
		$template = '<input name="metadata[%1$s]" type="hidden"';
		$template .= ' id="input-%1$s" value="%2$s"';
		$template .= ' class="showWhenEdit select-user-from-group"';
		$template .= ' data-defaultvalue="%2$s"';
		$template .= ' data-placeholder--id="%2$s"';
		$template .= ' data-placeholder--text="%2$s"';
		$template .= ' %3$s';
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
			$value,
			$extra
		);
	}// all? in study? allow create? show admins/edit/readonly

	public function getStudielistInput() {
		
	} // all? in study?

	public $fields = array (
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
				"depends" => false
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
				"depends" => false
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
				"depends" => false
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