# Meta Data Scheme Editor
Meta data schemes can be created dynamicaly, by creating a simple PHP array. The creation of the presentor and editor is handled automatically by the application, provided the PHP array describing the meta data is valid and contains all the necessary fields.

The PHP array is associative, meaning each value has a unique key, describing all the fields in the meta data scheme. The key of each field is also the key the value will be saved as in iRods. The value for each field contains the configuration for the field. 

The array would look something like this:

```php
$fields = array(
    "id" => $config_for_id,
    "name" => $config_for_name,
    "description" => $config_for_description
);
```
Each of the $config... variables contains the configuration for the corresponding field, as described below

# Types
At the moment of writing, there are 7 different field types available:

- Text
- Selectbox
- Date/Time
- Checkboxes
- Radio buttons
- User list (selectbox)
- Studies list (selectbox)

For each of the types, the configuration is another associative array. Each configuration array contains at least these fields:

- **label**: The label that is showed in front of the field, to indicate the name of the value.
- **help**: A short text explaining the meaning of the field. This help text is shown when the user hovers over the label
- **type**: The field type (as listed above)
- **custom_type**: Type of custom type fields. This field is only required if *type* is *custom*
- **type_configuration**: Another array, containing configuration specific to used type
- **required**: A boolean, indicating wether or not the field is required (at a certain stage)
- **depends**: If the field should only be shown when another field is filled in, or has a certain value, the value for this key should be another array, as explained below. If the field should always be visible, the value should be *false*

The sections below describe the required keys for the *type_configuration* key in the field array
## Text fields
The custom configuration for the text field requires three keys:

- **length**: An integer indicating the maximum allowed length for the field, or *false* if any length is allowed
- **pattern**: A PHP regex that matches allowed inputs, or false if any value is allowed. See [http://www.phpliveregex.com/] for help on creating regexes
- **longtext**: A boolean indicating wether the field should be a default input field, or a text area

## Selectbox
The custom configuration for the selectbox requires 5 keys, and has an optional 6th one:

- **restricted**: Boolean indicating wether the value is restricted. If the value is restricted, only values that have been used before are suggested.
- **allow_create**: Boolean indicating wether the user can add values that have not been used before. If true, the user can add new values. This option is only used if the *restricted* option is used.
- **begin**: Beginning of range (see below)
- **end**: End of range (see below)
- **step**: Step in range (see below)
- **options** (optional): Non-associative array giving all possible options. Users can only select from the options listed here. This only works if *restricted* isn't used

### Range
If no options are provided and *restricted* is *false*, a range of numbers can be used. This is done by filling in the *begin*, *end* and *step* parameters. The *begin* paramter describes the first option in the selectbox, the *end* parameter describes the last option in the selectbox and the *step* parameter describes how many numbers are skipped between each option. So to show a list of years since 1980 to 2016 with step 4, [1980, 1984, 1988, 1992, 1996, 2000, 2004, 2008, 2012, 2016], the following parameters should be used:
```php
"begin" => 1980,
"end" => 2016,
"step" => 4
```
To show the list counting backwards, the *step* parameter can be negative. The *begin* value has to be greater than the *end* value as well. So with the *begin* and *end* value from the example above swapped, and the *step* parameter being *-4*, the list will show the same years, but with 2016 at the top and 1980 at the bottom.

### Example
```php
...
"year" => array(
    "label" => "year",
    "help" => "Select a year from the list",
    "type" => "select",
    "type_configuration" => array(
        "restricted" => false,
        "allow_create" => false,
        "begin" => 2016,
        "end" => 1980,
        "step" => -4
    ),
    "required" => true,
    "depens" => false
),
...
```
## Date/Time
The date/time input allows the user to enter a date and/or time in a text field, or select one from a date/time picker. The custom configuration for this field requires 4 keys and provides two optional ones:
 * **show_years**: Boolean indicating wether or not the field accepts years
 * **show_months** Boolean indicating wether or not the field accepts months
 * **show_days** Boolean indicating wether or not the field accepts days
 * **show_time** Boolean indicating wether or not to accept time
 * **min_date_time** (optional): Object providing a minimum date and/or time
 * **max_date_time** (optional) Object proividing a maximum date and/or time

The first view parameters can be toggled independantly, but is is usually a good idea to not display any smaller units if a larger unit is not shown. So if you say *show_year* but not *show_month*, it's good practice to also not to *show_days* and *show_time*.

The date/time picker automatically adepts its view on what precision is used for selecting a moment. The format is 'YYYY-MM-DD HH:ii' (2016-06-23 09:00), where parts that are disabled in the type configuration are omitted.

### Minimum and maximum date time
The minimum and maximum date time both are optional objects (or could have the value `false` which will do the same as not instantiating them), which have either of two keys (not both):
 * **fixed**: A fixed date-time in the above format
 * **linked**: A key name of another date time field

In the case of *fixed*, a user cannot select a date and/or time earlier (in the case of *min_date_time*) or greater (in the case of *max_date_time*) than the provided time. In the case of *linked* a user cannot select a date and/or time earlier (in the case of *min_date_time*) or later (in the case of *max_date_time*) than the current value of the linked field. If the value in that field is changed, this restriction changes along. The *linked* option is great when two fields are used to specify a range

### Example
```php
...
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
        ),
        "max_date_time" => false
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
            "linked" => "date_time_example"
        ),
        "max_date_time" => false
    )
)
...
```
In the above example, two date/time input fields are generated. In both fields, the complete YYYY-MM-DD HH:ii format is used. The first field does not accept any dates earlier than 25 July 2016, while the second field accepts no date/time inputs earlier than the date/time selected in the first field.

## User list 
The user list is a specific type of selectbox, that allows selecting an existing user in the Yoda environment. The type configuration for the user list should be '*custom*' and the custom_type should be *'userlist'*. The type configuration requires the following keys:

- **allow_create**: If false, only existing users can be selected. If this parameter is true, however, a new name can be entered. **Note:** No new user is created. This just allows picking a value that does not correspond with an existing user
- **show_admins**: Boolean indicating wether administrators for the current study should be listed.
- **show_users**: Boolean indicating wether users with read/write access on the study, who are not administrators, should be listed.
- **show_readonly**: Boolean indicating wether users with read-only access on the study should be listed

### Example

```php
...
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
    "depends" => false
)
...
```
## Conditional Logic
The form supports conditional logic, meaning you can show or hide fields depending on the value entered on other fields. 
To use conditional logic, you can use the *depends* key of the field object. The value for the *depends* key is an object, consisting of 3 fields: 
 * **action**: either `hide` or `show`
 * **if**: one of `none`, `all`, `any` (meaning at least one)
 * **fields**: A list of field names the current field depends on.

### *Fields* object
The *fields* object takes another three keys:
* **field_name**: The field key of the field the current field depends on
* **operator**: One of `==` (equals), `!=` (does not equal), `>` (is larger than), `>=` (is larger than or equal to), `<` (is less than) or `<=` (is less than or equal to)
* **value**: An object containing the value that is checked against

### *Value* object
The value object takes one of three keys: **fixed**, **regex** or **like**. 

In the case of the key **fixed**, you give a value that should be matched entirely. This can be a string, an integer or any other value. All operators can be used. 

In the case of the key **like** you give a substring. For example, *vascr* is like *javascript* but not like *java*. In this case, again only the `==` and `!=` operators can be used in the **operator** key in the object described above, for the same reason.

In the case of the key **regex** the value is a regex that is matched against. In the **operator** key in the object described above, only `==` or `!=` can be used, because a regex works on a string and it either matches, or it does not.

If a combination of any of these three keys is used inside the *value* object, only one is checked. This happens in the order as they appear above.

## How to use these objects
Each of the keys described in this section take exactly one value. You should read the keys as follows (replace the key names with their corresponding value):

`action` this field, if `if` of the following are true:\
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;*(for each field):*\
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The value of `field_name` `operator`s `value`
    
Where value is either matched against a regular expression, a substring or a fixed value, depening on what key s chosen on the **value** object.

## Example
For example:
```php
...
$fields = array(
    "start_year" => array(
        ...
    ),
    "end_year" => array(
        ...
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
                    "operator" => ">=",
                    "value" => array(
                        "fixed" => 2000
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
    )
);  
...
```
This example only shows the field **depends_example** if the start_year is equal to or larger than 2000 and the end year is (strictly) less than 2016.