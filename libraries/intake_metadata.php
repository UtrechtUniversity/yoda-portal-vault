<?php
public $fields = array (
		"example_dirlist" => array (
				"label" => "Directory",
				"help" => "Select a directory from the list",
				"type" => "custom",
				"custom_type" => "directorylist",
				"type_configuration" => array (
					"showProjects" => true,
					"showStudies" => true,
					"showDatasets" => true,
					"requireContribute" => true,
					"requireManager" => false
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
		"date_time_example" => array(
			"label" => "datetime example",
			"help" => "This is an example of how to use the date time field",
			"type" => "datetime",
			"type_configuration" => array(
				"show_years" => true,
				"show_months" => true,
				"show_days" => true,
				"show_time" => true,
				"min_date_time" => array(
					"fixed" => "2016-07-25"
					// "linked" => date_time_example2
				),
				"max_date_time" => false
			),
			"required" => true,
			"depends" => false,
			"multiple" => array(
				"min" => 3,
				"max" => 100,
				"infinite" => false
			)
		),
		"date_time_example2" => array(
			"label" => "datetime example",
			"help" => "This is an example of how to use the date time field",
			"type" => "datetime",
			"type_configuration" => array(
				"show_years" => true,
				"show_months" => true,
				"show_days" => true,
				"show_time" => true,
				"min_date_time" => array(
					// "fixed" => "2016-07-25"
					"linked" => "date_time_example"
				),
				"max_date_time" => false
			)
		),
		"start_year" => array(
			"label" => "Start year",
			"help" => "Enter the start year of the project",
			"type" => "text",
			"type_configuration" => array(
				"length" => 4,
				"pattern" => "^[0-9]{4}$",
				"longtext" => false
			),
			"required" => true,
			"depends" => false
		),
		"end_year" => array(
			"label" => "End year",
			"help" => "Enter the end year of the project",
			"type" => "text",
			"type_configuration" => array(
				"length" => 4,
				"pattern" => "^[0-9]{4}$",
				"longtext" => false
			),
			"required" => true,
			"depends" => false
		),
	    "depends_example" => array (
	        "label" => "Example",
	        "help" => "This field shows how to use the depends object",
	        "type" => "text",
	        "type_configuration" => array(
	            "length" => 10,
	            "pattern" => "*",
	            "longtext" => false
	        ),
	        "required" => true,
	        "depends" => array(
	            "action" => "show",
	            "if" => "any",
	            "fields" => array(
	                array(
	                    "field_name" => "start_year",
	                    "operator" => "!=",
	                    "value" => array(
	                        // "fixed" => 2000
	                        // "like" => "18"
	                        "regex" => "^[0-9]{2}18$"
	                    )
	                ),
	                array(
	                    "field_name" => "end_year",
	                    "operator" => "<",
	                    "value" => array(
	                        "fixed" => 2016
	                    )
	                )
	            )
	        )
	    ),
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
				"max" => false,
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
					"pattern" => "^[0-9]+",
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
					"pattern" => "^[0-9]+",
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

