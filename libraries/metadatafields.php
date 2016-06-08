<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class metadataFields {

	public function getHtmlForRow($key, $config, $value, $indent = 0) {
		/**
		 * Template params
		 * 1) key
		 * 2) value
		 * 3) Label
		 * 4) Help text
		 * 5) input
		 * 6) indent
		 */

		$indent = "";
		for($i = 0; $i < $indent; $i++) {
			$indent .= "\t";
		}

		// $template = '%6$s<tr>' . PHP_EOL . '%6$s' . "\t" . '<td>%3$s</td>';
		// $template .= "\n" . '%6$s' . "\t" . '<td>';
		// $template .= '\n%6$s\t\t<span class="hideWhenEdit"';
		// $template .= ' id="label-%1$s">%2$s</span>';
		// $template .= '\n%6$s\t\t%5$s';
		// $template .= '\n%6$s\t</td>';
		// $template .= '\n%6$s\t<td>';

		// $template .= '\n%6$s\t\t<span type="button"';
		// $template .= ' class="btn btn-default glyphicon';
		// $template .= ' glypicon-pencil hideWhenEdit button-%1$s';
		// $template .= ' onclick="edit(\'%1$s\')"></span>';

		// $template .= '\n%6$s\t\t<span type="button"';
		// $template .= ' class="btn btn-default glyphicon';
		// $template .= ' glypicon-remove showWhenEdit button-%1$s';
		// $template .= ' onclick="cancelEdit(\'%1$s\')"></span>';
		// $template .= '\n%6$s\t</td>\n%6$s</tr>';


		$template =  <<<'EOT'
%6$s<tr>
%6$s	<td>%3$s</td>
%6$s	<td>
%6$s 		<span class="hideWhenEdit" id="label-%1$s">%2$s</span>
%6$s 		%5$s
%6$s 	</td>
%6$s 	<td width="50">
%6$s 		<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit button-%1$s" 
%6$s			onclick="edit('%1$s')"></span>
%6$s 		<span type="button"
%6$s			class="btn btn-default glyphicon glyphicon-remove showWhenEdit button-%1$s"
%6$s 			onclick="cancelEdit('%1$s')"></span>
%6$s 	</td>
%6$s</tr>
EOT;


		$input = "";

		switch($config["type"]) {
			case "text" :
				$input = $this->getTextInput($key, $config, $value);
				break;
			default:
				$input = "";
				break;
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

	public function getTextInput($key, $config, $value) {
		/**
		 * Template params:
		 * 1) field key (same as metadata key)
		 * 2) Current value
		 * 3) length="<length>" if not false, "" otherwise
		 */
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

	public function getLongtextInput($key, $value) {
	
	}

	public function getTimeInput() {

	}

	public function getSelectInput() {

	}

	public function getBoolInput() {
		
	}

	public function getCheckboxesInput() {
		
	}

	public function getRadioInput() {
		
	}

	public function getUserlistInput() {
		
	}// all? in study? allow create? show admins/edit/readonly

	public function getStudielistInput() {
		
	} // all? in study?

	public $fields = array (
		"dataset_owner" => array (
				"label" => "Owner",
				"help" => "Enter the username of the owner or contact person of this dataset",
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
				"label" => "lLocation",
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