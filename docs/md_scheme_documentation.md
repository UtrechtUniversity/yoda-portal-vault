# Meta Data Scheme Editor
Meta data schemes can be created dynamicaly, by creating a simple XML document. The creation of the presentor and editor is handled automatically by the application, provided the XML structure describing the meta data is valid according to this documentation.

The meta data scheme is enclosed in `schema` tags. The `schema` tag contains other tags, which define the keys for each of the fields. Inside those keys, the field configurations are defined. The key of each field is also the key the value will be saved as in iRods. The value for each field contains the configuration for the field. If defined in `/config/config.php`, all the keys will be prefixed with the defined prefixes before they are stored in iRODS.

The xml schema would look something like this:

```xml
<schema>
    ...
    <project_name>
        <label>Project name</label>
        <help>Enter a project name</help>
        <type>text</type>
        <type_configuration>
            <length>false</length>
            <pattern>*</pattern>
            <longtext>false</longtext>
        </type_configuration>
        <required>true</required>
        <depends>false</depends>
    </project_name>
    ...
</schema>
```

# Types
At the moment of writing, there are 6 different field types available:

- ``text``: A simple text field or a text area (for longer text inputs).
- ``select``: A selectbox that provides a dropdown menu the user can select a value from
- ``datetime``: A date/time picker tool, which helps the user select a date in a specific format
- ``checkbox``: Checkboxes
- ``radio``: Radio buttons
- ``bool``: A boolean input (usually used for yes/no questions) which is shown using radio buttons

In addition, custom types can be added quite easily. Two have been defined which are specific to how YoDa works:
- ``userlist``: A selectbox which dynamically loads users from iRODS, so the user can pick an existing iRODS user
- ``directorylist``: A selectbox which dynamically loads collection names from iRODS, so a user can pick an existing collection

If a custom type is used, the type of the field becomes `custom` and the field requires an extra tag `custom_type`, which has the custom field type as it's value

```xml
    ...
    <type>custom</type>
    <custom_type>userlist</custom_type>
    <type_configuration>
        ...
    </type_configuration>
    ...
```
For each of the types, the configuration is enclosed in the `type_configuration` tag. Each configuration array contains at least these fields:

- **label**: The label that is showed in front of the field, to indicate the name of the value. This does not have to be similar to the key of the field.
- **help**: A short text explaining the meaning of the field. This help text is shown when the user hovers over the label
- **type**: The field type (as listed above)
- **type_configuration**: The enclosing tag that contains the definitions required for the chosen type
- **required**: A boolean, indicating wether or not the field is required (at a certain stage)
- **depends**: If the field should only be shown or hidden when certain conditions based on other fields in the meta data form are met, these conditions can be defined inside the ``depends`` tag. See below for more information
- **multiple**: If a field can have multiple values, this field should be set to true. If a minimum or maximum amount of values is required, see below

The sections below describe the required keys for the *type_configuration* key in the field array
## text
The custom configuration for the text field requires three keys:

- **length**: An integer indicating the maximum allowed length for the field, or *false* if any length is allowed
- **pattern**: A PHP regex that matches allowed inputs, or false if any value is allowed. See [http://www.phpliveregex.com/] for help on creating regexes. Use `*` if any value should be allowed. If this option is used, provide a clear descrition of what (types) of input is allowed, as the meta data form will indicate the field does not match the pattern, but will give no indication as to what the pattern is. This may become confusing without a clear help text
- **longtext**: A boolean indicating wether the field should be a default input field, or a text area

## select
The custom configuration for the selectbox requires 5 keys, and has an optional 6th one:

- **restricted**: Boolean indicating wether the value is restricted. If the value is restricted, only values that have been used before on the same metadata key are suggested. This list is extracted from all objects in iRODS that use the key, not just the current object
- **allow_create**: Boolean indicating wether the user can add values that have not been used before. If true, the user can add new values. This value of this option is only used if the *restricted* option is used, but should be included either way
- **begin**: Beginning of range (see below)
- **end**: End of range (see below)
- **step**: Step in range (see below)
- **options** *(optional)*: List of all options a user can pick from (see below)

### Suggestions from previous metadata
If you set `restricted` to `true`, the select box will appear as a free text field, where suggestions are shown of values that have previously been used for the same key. You can set `allow_create` to true, to allow users to add new values, if their value is not in the suggestions list.

### Range
If no options are provided and *restricted* is *false*, a range of numbers can be used. This is done by filling in the *begin*, *end* and *step* parameters. The *begin* paramter describes the first option in the selectbox, the *end* parameter describes the last option in the selectbox and the *step* parameter describes how many numbers are skipped between each option. So to show a list of years since 1980 to 2016 with step 4, [1980, 1984, 1988, 1992, 1996, 2000, 2004, 2008, 2012, 2016], the following parameters should be used:
```xml
...
    <begin>1980</begin>
    <end>2016</end>
    <step>4></step>
...
```
To show the list counting backwards, the *step* parameter can be negative. The *begin* value has to be greater than the *end* value as well. So with the *begin* and *end* value from the example above swapped, and the *step* parameter being *-4*, the list will show the same years, but with 2016 at the top and 1980 at the bottom.

### Options
Instead of having the meta data form extract the values from iRODS, or have a list of numbers generated in between a certain range, a list of set options can be provided.
These options are only used if ``restricted`` is set to ``false``. Each option should be enclosed in `<option>` tags:
```xml
...
    <options>
        <option>Option 1</option>
        <option>Option 2</option>
        <option>...</option>
        ...
    </options>
...
```

If options should be grouped, each group should be enclosed in `<optgroup>` tags and should containing an `<optlabel>` tag and again a list of options enclosed in `<option>` tags.

```xml
...
    <options>
        <optgroup>
            <optlabel>Option group 1</optlabel>
            <option>Option 1 in group 1</option>
            <option>Option 2 in group 1</option>
            ...
        </optgroup>
        <optgroup>
            <optlabel>Option group 2</optlabel>
            <option>Option 1 in group 2</option>
            <option>Option 2 in group 2</option>
            ...
        </optgroup>
        ...
    </options>
...
```
### Example
```xml
...
<year>
    <label>year</label>
    <help>Select a year from the list</help>
    <type>select</type>
    <type_configuration>
        <restricted>false</restricted>
        <allow_create>false</allow_create>
        <begin>2016</begin>
        <end>1980</end>
        <step>-4</step>
    </type_configuration>
    <required>false</required>
    <depends>false</depends>
</year>
...
```

## datetime
The date/time input allows the user to enter a date and/or time in a text field, or select one from a date/time picker. The custom configuration for this field requires 4 tags and provides two optional ones:
 * **show_years**: Boolean indicating wether or not the field accepts years
 * **show_months** Boolean indicating wether or not the field accepts months
 * **show_days** Boolean indicating wether or not the field accepts days
 * **show_time** Boolean indicating wether or not to accept time
 * **min_date_time** (optional): Object providing a minimum date and/or time
 * **max_date_time** (optional) Object proividing a maximum date and/or time

The first four `show_...` parameters can be toggled independantly, but is is usually a good idea to not display any smaller units if a larger unit is not shown. So if you say `show_year` but not `show_month`, it's good practice to also set `show_days` and `show_time` to `false`

The date/time picker automatically adepts its view on what precision is used for selecting a moment. The format is 'YYYY-MM-DD HH:ii' (2016-06-23 09:00), where parts that are disabled in the type configuration are omitted.

### Minimum and maximum date time
The minimum and maximum date time both are optional objects (or could have the value `false` which will do the same as not instantiating them), which have either of two keys (not both):
 * **fixed**: A fixed date-time in the above format
 * **linked**: A key name of another date time field

In the case of `fixed`, a user cannot select a date and/or time before (in the case of `min_date_time`) or after (in the case of `max_date_time`) than the provided time. In the case of `linked` a user cannot select a date and/or time before (in the case of `min_date_time`) or after (in the case of `max_date_time`) than the current value of the linked field. If the value in that field is changed, this restriction changes along. The `linked` option is great for specifying two fields, which together form a range.

### Example
```xml
...
<datetime_example>
    <label>datetime example</label>
    <help>This is an example of how to use the date/time field</help>
    <type>datetime</type>
    <type_configuration>
        <show_years>true</show_years>
        <show_months>true</show_months>
        <show_days>true</show_days>
        <show_time>true</show_time>
        <min_date_time>
            <fixed>2016-07-25</fixed>
        </min_date_time>
        <max_date_time>false</max_date_time>
    </type_configuration>
   </datetime_example>
   <date_time_example2>
    <label>datetime example</label>
    <help>This is an example of how to use the date/time field, too</help>
    <type>datetime</type>
    <type_configuration>
        <show_years>true</show_years>
        <show_months>true</show_months>
        <show_days>true</show_days>
        <show_time>true</show_time>
        <min_date_time>
            <linked>datetime_example</linked>
        </min_date_time>
        <max_date_time>
            <fixed>2018-01-01</fixed>
        </max_date_time>
    </type_configuration>
   </date_time_example2>
   ...
   ```
In the above example, two date/time input fields are generated. In both fields, the complete YYYY-MM-DD HH:ii format is used. The first field does not accept any dates earlier than 25 July 2016, while the second field accepts no date/time inputs earlier than the date/time selected in the first field and no date/time after the first of january 2018.

## checkbox
The `checkbox` type requires a predefined list of options and does not accept any other way of generating the options. In contrast to the select, no option groups can be used, either.

Checkboxes are useful for providing a small number of options, multiple of which can be selected.

### Example
```xml
...
<example_checkbox>
    <label>Example checkboxes</label>
    <help>The checkbox field can be used to provide multiple options, of which zero or more can be selected. Generally used with only few options</help>
    <type>checkbox</type>
    <type_configuration>
        <options>
            <option>option 1</option>
            <option>option 2</option>
            <option>option 3</option>
            <option>option 4</option>
            <option>option 5</option>
            <option>option 6</option>
            <option>option 7</option>
        </options>
    </type_configuration>
    <required>true</required>
    <allow_empty>true</allow_empty>
    <depends>false</depends>
</example_checkbox>
...
```

## radio
The radio takes the same configuration as the checkbox, but allows the user to select only one option.

## bool
This input is a faster way of defining a radio buttons input with only two options, `yes` and `no`.

This field does not require a ``type_configuration``, but if required, the two default options can be overridden, by adding one, with two tags: `true_val` (default: *yes*) and `false_val` (default: *no*).

### Example
```xml
<example_bool>
    <label>Publish dataset</label>
    <help>Will this dataset be published?</help>
    <type>bool</type>
    <required>false</required>
    <allow_empty>true</allow_empty>
    <depends>false</depends>
</example_bool>
```

## userlist
The user list is a specific type of selectbox, that allows selecting an existing user in the Yoda environment, that is a member of the same group this metadata form is used in. The type configuration requires the following keys:

- **allow_create**: If false, only existing users can be selected. If this parameter is true, however, a new name can be entered. **Note:** No new user is created. This just allows picking a value that does not correspond with an existing user
- **show_admins**: Boolean indicating wether administrators for the current group should be listed.
- **show_users**: Boolean indicating wether users with read/write access on the group, who are not administrators, should be listed.
- **show_readonly**: Boolean indicating wether users with read-only access on the group should be listed

### Example
```xml
...
<creator>
    <label>Creator</label>
    <help>Select the username of the user that created this collection</help>
    <type>custom</type>
    <custom_type>userlist</custom_type>
    <type_configuration>
        <allow_create>false</allow_create>
        <show_admins>true</show_admins>
        <show_users>true</show_users>
        <show_readonly>true</show_readonly>
    </type_configuration>
    <required>true</required>
    <depends>false</depends>
</creator>
...
```

## directorylist
A directorylist input can be used to allow a user to select an existing directory on a certain level. As soon as the user starts typing, suggestions are shown that include the search string.

The type configuration requires the following tags, which should all be set to either `true` or `false`:
* **showProjects**: Show all projects (or: collections directly associated with a group) the user is a member of
* **showStudies**: Show all first-level subcollections of the previously mentioned projects level, providing a user is a member of the group the collection belongs to
* **showDatasets** Show all second-level subcollections of the previously mentioned projects level, providing a user is a member of the group the collection belongs to
* **requireContribute** If set to `true`, collections on the selected levels are only shown if the user is not just a member, but has contribute access to the respective groups as well
* **requireManager** If set to `true`, collections on the selected levels are only shown if the user is not just a member, but has manager access to the respective groups as well

### Example
```xml
...
<example_dirlist>
    <label>Directory</label>
    <help>Select a directory from the list</help>
    <type>custom</type>
    <custom_type>directorylist</custom_type>
    <type_configuration>
        <showProjects>true</showProjects>
        <showStudies>true</showStudies>
        <showDatasets>true</showDatasets>
        <requireContribute>true</requireContribute>
        <requireManager>false</requireManager>
    </type_configuration>
    <required>true</required>
    <allow_empty>false</allow_empty>
    <depends>false</depends>
</example_dirlist>
...
```

# Conditional Logic
The form supports conditional logic, meaning you can show or hide fields depending on the value entered on other fields. 
To use conditional logic, you can use the *depends* key of the field object. The value for the *depends* tag contains itself 3 tags: 
 * **action**: either `hide` or `show`
 * **if**: one of `none`, `all`, `any` (meaning at least one)
 * **fields**: A list of field names the current field depends on, each field enclosed in `<option>` tags

## *Fields* object
The `fields` object takes another three tags:
* **field_name**: The key of the field the condition applies on
* **operator**: One of `==` (equals), `!=` (does not equal), `>` (is larger than), `>=` (is larger than or equal to), `<` (is less than) or `<=` (is less than or equal to)
* **value**: An object containing the value that is checked against

XML documents may have some issues with the `<` sign, as it is part of the XML syntax. If this turns out to be a problem, the following aliasses can be used:
* *`<`*
    * `lt` (**l**ess **t**han)
    * `&lt;` (HTML character)
* `<=`
    * `leq` (**l**ess than or **eq**eal)
    * `&lt;=` (html character, followed by equals sign)
* `>`
    * `gt` (**g**reater **t**han)
    * `&gt;` (HTML character)
* `>=`
    * `geq` (**g**reater than or **eq**ual)
    * `&gt;=` (HTML character, followed by equals sign)

## *Value* object
The `value` object takes one of three keys: `fixed`, `regex` or `like`. 

In the case of `fixed`, you give a value that should be matched entirely. This can be a string, an integer or any other value. All operators can be used. 

In the case of `like` you give a substring. For example, '*vascr*' is like '*javascript*' but not like '*java*'. In the `like` condition, only the `==` and `!=` operators can be used in the object described above.

In the case of `regex` the value is a regex that is matched against. In the `operator` tag in the object described above, only `==` or `!=` can be used, because a regex works on a string and it either matches, or it does not.

If a combination of any of these three keys is used inside the `value` tag, only one is checked. This happens in the order as they appear above (`fixed` first, then `like` and only then `regex`).

## How to use these objects
Each of the tags described in this section take exactly one value. You should read the tags as follows (replace the tag names with their given value):

`action` this field, if `if` of the following are true:\
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;*(for each field):*\
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The value of `field_name` `operator`s `value`
    
Where `value` is either matched against a regular expression, a substring or a fixed value, depening on what tag is chosen under `value`.

## Example
For example:
```xml
...
    <start_year>
        ...
    </start_year>
    <end_year>
        ...
    </end_year>
    <depends_example>
        <label>Example</label>
        <help>This field only shows if start_year is equal to or higher than 2000 or end_year is strictly less than 2016. </help>
    <type>text</type>
    <type_configuration>
        <length>10</length>
        <pattern>*</pattern>
        <longtext>false</longtext>
    </type_configuration>
    <required>false</required>
    <depends>
        <action>show</action>
        <if>any</if>
        <fields>
            <option>
                <field_name>start_year</field_name>
                <operator>>=</operator>
                <value>
                    <fixed>2000</fixed>
                </value>
            </option>
            <option>
                <field_name>end_year</field_name>
                <operator>></operator>
                <value>
                    <fixed>2016</fixed>
                </value>
            </option>
        </fields>
    </depends>
</depends_example>
...
```
This example only shows the field **depends_example** if the start_year is equal to or larger than 2000 or the end year is (strictly) less than 2016.

# Multiple values
By default, each field is given a single input, allowing the user to enter a single value for the field. If a meta data key can have multiple values, multiple inputs can be shown. In this case, by default only one is shown, but a button below the input allows the user to add another input. Next to each input, a delete button allows the user to remove a value from the key.

A field can be made a multi-value-key, by adding the non-compulsory tag `multiple`. If `multiple` only takes the value `true`, the user is allowed to add multiple values, as few or many as they like. If the configuration should be more finetuned, the following tags can be added inside the `multiple` tag:
* **min**: The minimum amount of values required. If the field is not yet given any value, 0 will be allowed as well. As soon as a value is entered, the remaining number of minimum values that is defined here is requested as well. Usually, this should be 0. If a field is required, at least one value will be required as well on the final check
* **max** The maximum number of values allowed
* **infinite** Boolean indicating wether or not to ignore the value for ``max``. If this tag is set to ``true``, no upper limit is set (even if the value for ``max`` *is* set).

## Example
```xml
...
<multiple_example>
    <label>Discipline</label>
    <help>Enter the discipline for this research. If the discipline crosses over multiple fields, enter all those disciplines</help>
    <type>text</type>
    <type_configuration>
        <length>false</length>
        <pattern>*</pattern>
        <longtext>false</longtext>
    </type_configuration>
    <required>false</required>
    <depends>false</depends>
    <multiple>
        <min>0</min>
        <max>10</max>
        <infinite>true</infinite>
    </multiple>
</multiple_example>
...
```