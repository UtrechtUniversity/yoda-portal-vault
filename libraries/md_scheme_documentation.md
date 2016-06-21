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
