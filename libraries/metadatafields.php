<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class metadataFields {

	/**
	 * Method that generates a single table row based on field definitions
	 *
	 * @param key 		Field key
	 * @param config 	The field configuration for this field
	 * @param value 	The value that was already stored in irods, if available
	 * 					String for single value fields, array for multivalue keys
	 * @param indent 	Tab indentation for this form (dependant on view)
	 * @param canEdit 	Bool true if user can edit the metadata
	 * @param errors 	Array containing error definitions if any errors exist, 
	 * 					false otherwise
	 * @param formdata 	The posted form data, that is used instead of the values if an
	 *					error occured in the input
	 * @return string 	Containing HTML for table row
	 */
	public function getHtmlForRow($key, $config, $value, $indent = 0, $canEdit, $errors = false, $formdata) {
		$idn = "";
		for($i = 0; $i < $indent; $i++) {
			$idn .= "\t";
		}
		$indent = $idn;
		/**
		 * Template params:
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
		 * 12) intake_metadata_button_add_value lang string
		 * 13) intake_metadata_button_edit lang string
		 * 14) intake_metadata_button_cancel lang string
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
		if(keyIsTrue($config, "multiple") && $canEdit):
			$template .= <<<'EOT'
%11$s 		<span class="btn btn-default glyphicon glyphicon-plus showWhenEdit" 
%11$s 			data-template="%7$s" data-nextindex="%8$d" onclick="addValueRow('%1$s')" id="addRow-%1$s">
%11$s 			%12$s
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
%11$s			title="%13$s" data-toggle="tooltip" data-placement="top" 
%11$s			onclick="edit('%1$s')"></span>
%11$s 		<span type="button"
%11$s			title="%14$s" data-toggle="tooltip" data-placement="top" 
%11$s			class="btn btn-default glyphicon glyphicon-pencil-cancel showWhenEdit button-%1$s"
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
			if (!keyIsTrue($config, "multiple") && is_string($currentValue) || $config["type"] == "checkbox") {
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
				if((keyIsTrue($config, "multiple") || $config["type"] == "checkbox") && is_string($currentValue)) {
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
				$index = is_array($currentValue) ? sizeof($currentValue) : 1;
				$input .= sprintf(
					$deleteRowButtonTemplate,
					$key,
					$index,
					$this->findProperInput($key, sprintf($inputArrayName, $key, $index), $config, "")
				);
			}
		}

		if(keyIsTrue($config, "multiple") || $config["type"] == "checkbox") {
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

		if(keyIsTrue($config, "depends")) {
			$rowDepends .= htmlentities(" data-depends=\"" . json_encode($config["depends"]) . "\"");
		}

		$hasError = sizeof($errors) > 0;

		$errorHelperText = $this->buildErrorExplanation($key, $config, $errors);

		$help = $errorHelperText === "" ? $config["help"] : sprintf("<p>%s</p>%s", $config["help"], $errorHelperText);

		return sprintf(
			$template,
			$key,
			keyIsTrue($config, "multiple") || $config["type"] == "checkbox" ? $multiValueList : $currentValue,
			$config["label"],
			$help,
			$input,
			$this->getShadowInput($key, $config, $value),
			$canEdit ? htmlentities($rowInputTemplate) : "",
			is_array($currentValue) && sizeof($currentValue) > 0 ? max(array_keys($currentValue)) + 1 : 1,
			$hasError ? " has-error" : "",
			$rowDepends,
			$indent,
			lang('intake_metadata_button_add_value'),
			lang('intake_metadata_button_edit'),
			lang('intake_metadata_button_cancel')
		);
	}

	/**
	 * Method that builds an explanation of why the input was rejected
	 * for a certain field
	 *
	 * @param key 			The field key for which one or more errors exist
	 * @param definitions	Field definitions for this field
	 * @param errors 		Array of errors for this field
	 * @return string 		Html list, listing all errors in human text
	 */
	private function buildErrorExplanation($key, $definitions, $errors) {
		if(sizeof($errors) === 0) return "";

		$errArr = array();
		if(in_array(ERROR_MIN_ENTRIES, $errors)) {
			if(keyIsTrue($definitions, array("multiple", "min"))) {
				$min = $definitions["multiple"]["min"];
			} else {
				$min = 0;
			}
			$errArr[] = sprintf(lang('intake_formerror_min_entries'), $min);
		}

		if(in_array(ERROR_MAX_ENTRIES, $errors)) {
			if(keyIsTrue($definitions, array("multiple", "max"))) {
				$max = $definitions["multiple"]["max"];
			} else {
				$max = 0;
			}

			$errArr[] = sprintf(lang('intake_formerror_max_entries'), $max);
		}

		if(in_array(ERROR_SINGLE_ENTRY, $errors)) {
			$errArr[] = lang('intake_formerror_single_entry');
		}

		if(in_array(ERROR_REQUIRED, $errors)) {
			$errArr[] = lang('intake_formerror_required');
		}

		if(in_array(ERROR_MAX_LENGTH, $errors)) {
				if(keyIsTrue($definitions, array("type_configuration", "length"))) {
				$length = $definitions["type_configuration"]["length"];
			} else {
				$length = 0;
			}

			$errArr[] = sprintf(lang('intake_formerror_max_length'), $length);
		}

		if(in_array(ERROR_REGEX, $errors)) {
			$errArr[] = lang('intake_formerror_regex');
		}

		if(in_array(ERROR_NOT_IN_RANGE, $errors)) {
			$errArr[] = lang('intake_formerror_not_in_range');
		}

		if(in_array(ERROR_INVALID_DATETIME_FORMAT, $errors)) {
			$errArr[] = lang('intake_formerror_datetime_format');
		}

		if(in_array(ERROR_DATE_LESS_THAN_FIXED, $errors)) {
			if(keyIsTrue($definitions, array("type_configuration", "min_date_time", "fixed"))) {

				$mindatetime = $definitions["type_configuration"]["min_date_time"]["fixed"];
			} else {
				$mindatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('intake_formerror_datetime_less_fixed'), $mindatetime);
		}

		if(in_array(ERROR_DATE_LESS_THAN_LINKED, $errors)) {
			if(keyIsTrue($definitions, array("type_configuration", "min_date_time", "linked"))) {

				$mindatetime = $definitions["type_configuration"]["min_date_time"]["linked"];
			} else {
				$mindatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('intake_formerror_datetime_less_linked'), $mindatetime);
		}


		if(in_array(ERROR_DATE_HIGHER_THAN_FIXED, $errors)) {
			if(keyIsTrue($definitions, array("type_configuration", "max_date_time", "fixed"))) {
				$maxdatetime = $definitions["type_configuration"]["max_date_time"]["fixed"];
			} else {
				$maxdatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('intake_formerror_datetime_higher_fixed'), $maxdatetime);
		}

		if(in_array(ERROR_DATE_HIGHER_THAN_LINKED, $errors)) {
			if(keyIsTrue($definitions, array("type_configuration", "max_date_time", "linked"))) {
				$maxdatetime = $definitions["type_configuration"]["max_date_time"]["linked"];
			} else {
				$maxdatetime = "<INVALID>";
			}

			$errArr[] = sprintf(lang('intake_formerror_datetime_higher_linked'), $maxdatetime);
		}

		$html = sprintf("<b>%s</b><ul>", lang('intake_formerror_heading'));
		foreach($errArr as $ea) {
			$html .= sprintf("<li>%s</li>", $ea);
		}
		$html .= "</ul>";

		return $html;
	}

	/**
	 * Function that calls the proper function for a certain type of field
	 * and returns its output
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */ 
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
				$input = "<p>Invalid type</p>";
				break;
		}
		return $input;
	}

	/**
	 * Function that finds the proper input function for a non-standard
	 * input field type. This function is separate from findProperInput()
	 * to allow extending of input types without breaking too much
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
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
				$input = "<p>Invalid custom type</p>";
				break;
		}

		return $input;
	}

	/**
	 * Function that generates html for a hidden input, containing the value of
	 * the field as it is stored in iRODS. These values are used for reference
	 * after the user submits a form, to check for changes.
	 * This method limits the number of connections to iRODS, because otherwise
	 * iRODS should be queried for its metadata values after each submit again.
	 *
	 * @param key 			Field key of the field name
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
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
		} else if(keyIsTrue($config, "multiple") || $config["type"] == "checkbox") {
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

	/**
	 * Method that generates the input for the input type 'text'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getTextInput($key, $inputName, $config, $value) {
		/**
		 * Template params:
		 * 1) field key (same as metadata key)
		 * 2) Input name
		 * 3) Current value
		 * 4) length="<length>" if not false, "" otherwise
		 */
		if(keyIsTrue($config, array("type_configuration", "longtext")))
			return $this->getLongtextInput($key, $inputName, $config, $value);

		$template ='<input type="text"';
		$template .= ' name="%2$s"';
		$template .= ' %4$s class="showWhenEdit input-%1$s" value="%3$s"/>';

		$length = keyIsTrue($config, array("type_configuration", "length")) ?
			sprintf(
				"maxlength=\"%d\"", 
				$config["type_configuration"]["length"]
			)
			: "maxlength=\"2700\"";

		return sprintf($template, $key, $inputName, $value, $length);
	}

	/**
	 * Method that generates a text area input
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getLongtextInput($key, $inputName, $config, $value) {
		$template = '<textarea name="%2$s"';
		$template .= ' class="showWhenEdit input-%1$s" maxlength="2700">%3$s</textarea>';

		return sprintf($template, $key, $inputName, $value);
	}

	/**
	 * Method that generates the input html for the metadata input type 'datetime'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getTimeInput($key, $inputName, $config, $value) {
		$template = '<div class="input-group date showWhenEdit input-%1$s" id="metadata-datepicker-%1$s">';
		$template .= '<input type="text" class="form-control metadata-datepicker input-%1$s" ';
		$template .= 'value="%3$s" name="%2$s" %4$s/>';
		$template .= '</div>';

		$extra = "data-typeconfiguration=\"" . 
			htmlentities(
				json_encode(
					keyIsTrue($config, "type_configuration") ? 
					$config["type_configuration"] : 
					array()
				)
			) . 
			"\"";

		return sprintf(
			$template, 
			$key,
			$inputName,
			$value,
			$extra
		);

	}

	/**
	 * Method that generates the input html for the metadata input type 'select'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getSelectInput($key, $inputName, $config, $value) {
		$tc = $config["type_configuration"]; // tc = TypeConfiguration;
		if(keyIsTrue($config, array("type_configuration", "restricted")) && $config["type_configuration"]["restricted"] === true) {
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
				keyIsTrue($config, array("type_configuration", "allow_create")) ? true : false
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
			$optTemplate = '<option value="%1$s"%2$s>%1$s</option>';
			if(
				(!keyIsTrue($config, array("type_configuration", "options")) || sizeof($tc["options"]) === 0) &&
				(
					keyIsTrue($config, array("type_configuration", "begin")) && 
					keyIsTrue($config, array("type_configuration", "end"))
				)
			) {
				$begin = $config["type_configuration"]["begin"];
				$end = $config["type_configuration"]["end"];
				$step = keyIsTrue($config, "type_configuration", "step") ? 
					$config["type_configuration"]["step"] : (
						$begin > $end ? -1 : 1
					);
				for(
					$i = $begin; 
					( $begin <= $end && $i <= $end ) ||
					( $begin > $end && $i >= $end );
					$i += $step
				) {
					$options .= sprintf($optTemplate, $i, $i == $value ? ' selected=""' : '');
				}
			} else {
				$avOptions = keyIsTrue($config, "type_configuration", "options") ? 
					$config["type_configuration"]["options"] :
					array();

				foreach($avOptions as $option) {
					if(is_string($option)) {
						$options .= sprintf($optTemplate, $option, $option == $value ? ' selected=""' : '');
					} else if(is_array($option)) {
						foreach($option as $optgroup) {
							if(keyIsTrue($optgroup, "optlabel"))
								$options .= sprintf('<optgroup label="%s">', $optgroup["optlabel"]);

							if(keyIsTrue($optgroup, "option") && is_array($optgroup["option"])) {
								foreach($optgroup["option"] as $o) {
									$options .= sprintf($optTemplate, $o, $o == $value ? ' selected=""' : '');
								}
							}
							if(keyIsTrue($optgroup, "optlabel"))
								$options .= "</optgroup>";
						}
					}
				}
			}
			$options = "<option value=\"\">&nbsp;</option>" . $options;
			
			$select = '<select';
			$select .= ' name="%2$s"';
			$select .= ' class="showWhenEdit chosen-select input-%1$s">%4$s</select>';

			return sprintf(
					$select,
					$key,
					$inputName,
					$value,
					$options
				);
		}
	}

	/**
	 * Method that generates the input html for the metadata input type 'bool'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getBoolInput($key, $inputName, $config, $value) {
		$yesVal = keyIsTrue($config, array("type_configuration", "true_val")) ?
			$config["type_configuration"]["true_val"] : "Yes";
		$noVal = keyIsTrue($config, array("type_configuration", "false_val")) ?
			$config["type_configuration"]["false_val"] : "No";

		$config["type_configuration"]["options"] = array($yesVal, $noVal);

		return $this->getOptionsInput($key, $inputName, $config, $value, "radio");

	}

	/**
	 * Method that generates the input html for the metadata input type 'checkbox'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getCheckboxesInput($key, $inputName, $config, $value) {
		return $this->getOptionsInput($key, $inputName, $config, $value, "checkbox");
	}

	/**
	 * Method that generates the input html for the metadata input type 'radio'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getRadioInput($key, $inputName, $config, $value) {
		return $this->getOptionsInput($key, $inputName, $config, $value, "radio");
	}

	/**
	 * Method that generates html from field definitions that contain an options list
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @param type 			string that is inserted directly in the html input
	 *						'type' tag 
	 * @return string 		Html for a single input field
	 */
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

		if(keyIsTrue($config, array("type_configuration","options"))) {
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
		}

		return $input;
	}

	/**
	 * Method that generates the input html for the metadata input type 'userlist'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getUserlistInput($key, $inputName, $config, $value) {
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
			keyIsTrue($config, array("type_configuration", "show_admins")) ? true : false,
			keyIsTrue($config, array("type_configuration", "show_users")) ? true : false,
			keyIsTrue($config, array("type_configuration", "show_readonly")) ? true : false,
			keyIsTrue($config, array("type_configuration", "allow_create")) ? true : false
		);

		return sprintf(
			$template, 
			$key, 
			$inputName,
			$value,
			$extra
		);
	}

	/**
	 * Method that generates the input html for the metadata input type 'directorylist'
	 *
	 * @param key 			Field key of the field name
	 * @param inputName 	The value of the "name" tag of the input field
	 * 						(this is just the key for single value fields, and
	 * 						the key appended with an index for multi-value fields)
	 * @param config 		The field definitions for this field
	 * @param value 		The existing value for this field
	 * @return string 		Html for a single input field
	 */
	private function getStudielistInput($key, $inputName, $config, $value) {
		$template = '<input name="%2$s" type="hidden"';
		$template .= ' value="%3$s"';
		$template .= ' class="showWhenEdit select-dir-from-group input-%1$s"';
		$template .= ' %4$s/>';

		$extra = 'data-typeconfiguration="' . 
			htmlentities(
				json_encode(
					keyIsTrue($config, "type_configuration") ?
					$config["type_configuration"] : ''
				)
			) . 
			'"';

		return sprintf(
			$template,
			$key,
			$inputName,
			$value,
			$extra
		);
	}

	/**
	 * Method that generates the html for the metadata form edit buttons,
	 * so they can be placed on multiple locations, while ensuring they are
	 * exactly similar
	 *
	 * @return string 	HTML containing form group for edit buttons
	 */
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