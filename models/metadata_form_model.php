<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();

        $this->CI->load->model('filesystem');
    }

    /**
     * @param $rodsaccount
     * @param $config
     * @return array
     *
     * returns all groupnames in an array for the requested for form (in $config['elements']
     */
    public function getFormGroupNamesAsArray($rodsaccount, $config) {
        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);

        $groupNames = array();
        foreach($formGroupedElements['Group'] as $formElements) {
            foreach ($formElements as $key => $element) {
                if ($key == '@attributes') {
                    $groupNames[] = $element['name'];
                }
            }
        }
        return $groupNames;
    }


    /**
     * @param $rodsaccount
     * @param $config
     * @return array
     *
     * returns all present form items in an array for the requested for form (in $config['elements'])
     */
    public function getFormElementsAsArray($rodsaccount, $config) {
        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);


        // If there are multiple groups, the first should contain the number '0' as array-index .
        // Otherwise, this is the direct descriptive information (i.e. not an array form.
        // The software expects an array, so in the latter case should be an array)
        $formAllGroupedElements = array();
        foreach($formGroupedElements['Group'] as $index => $array) {
            if($index=='0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            }
            else {
                $formAllGroupedElements[] = $formGroupedElements['Group']; // rewrite it as an indexable array as input for coming foreach
            }
            break;
        }

        $allElements = array();
        foreach($formAllGroupedElements as $formElements) {
            foreach ($formElements as $key => $element) {
                if ($key != '@attributes') {
                    $allElements[] = $key;
                }
            }
        }
        return $allElements;
    }

    /**
     * @param $rodsaccount
     * @param $config
     *
     * Handles the posted information of a yoda form and puts the values, after escaping, in .yoda-metadata.xml
     * The config holds the correct paths to form definitions and .yoda-metadata.xml
     *
     * NO VALIDATION OF DATA IS PERFORMED IN ANY WAY
     */
    public function processPost($rodsaccount, $config) {
        $metadata = array();

        $allElements = $this->getFormElementsAsArray($rodsaccount, $config);

        // Step through all elements of the form
        foreach($allElements as $element) {
            $valueArray = array();

            $postValue = $this->CI->input->post($element);

            if(!is_array($postValue)) {
                $valueArray[] = $postValue;
            }
            else {
                $valueArray = $postValue;
            }

            //work through all the values for the perticular element
            foreach($valueArray as $value) {

                // no empty lines are allowed
                if(!(empty($value) OR $value=='')) {
                    $metadata[] = array($element => $value);
                }
            }
        }

        $this->writeMetaDataAsXml($rodsaccount, $config['metadataXmlPath'], $metadata);
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @param $metadata
     *
     * Function that writes a key value pair to an xml file
     *
     */
    public function writeMetaDataAsXml($rodsaccount, $path, $metadata) {
        $this->CI->filesystem->writeXml($rodsaccount, $path, $metadata);
    }


    public function getFormElements($rodsaccount, $config)
    {
        // load xsd and get all the info regarding restrictions
        $xsdElements = $this->loadXsd($rodsaccount, $config['xsdPath']); // based on element names

        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
        }

        $metadataPresent = false;

        $formData = array();
        if ($config['hasMetadataXml'] == 'true') {
            $metadataPresent = true;
            $formData = $this->loadFormData($rodsaccount, $config['metadataXmlPath']);

            if ($formData === false) {
                return false;
            }
        }

        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);
        if ($formGroupedElements === false) {
            return false;
        }

        $presentationElements = array();

        $groupName = 'undefined';

        // If there are multiple groups, the first should contain the number '0' as array-index .
        // Otherwise, this is the direct descriptive information (i.e. not an array form.
        // The software expects an array, so in the latter case should be an array)
        $formAllGroupedElements = array();
        foreach($formGroupedElements['Group'] as $index => $array) {
            if($index=='0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            }
            else {
                $formAllGroupedElements[] = $formGroupedElements['Group']; // rewrite it as an indexable array as input for coming foreach
            }
            break;
        }

        foreach($formAllGroupedElements as $formElements) {
            foreach ($formElements as $key => $element) {
                if($key == '@attributes') {
                    $groupName = $element['name'];
                }
                else {
                    $value = isset($formData[$key]) ? $formData[$key] : '';

                    // The number of values determine the number of elements to be created
                    if(!is_array($value) ) {
                        $valueArray = array();
                        $valueArray[] = $value;
                    }
                    else {
                        $valueArray = $value;
                        if(count($valueArray)==0 ) {
                            $valueArray = array('');
                        }
                    }

                    // Mandatory no longer based on XSD but taken from formelements.xml
                    $mandatory = false;
                    if(isset($element['mandatory']) AND strtolower($element['mandatory'])=='true') {
                        $mandatory = true;
                    }

                    $multipleAllowed = false;
                    if($xsdElements[$key]['maxOccurs']!='1') {
                        $multipleAllowed = true;
                    }

                    if(!$multipleAllowed) {
                        if(count($valueArray)>1) {
                            return false;
                        }
                    }

                    foreach($valueArray as $keyValue) { // create an element for each of the values
                        $elementOptions = array(); // holds the options
                        $elementMaxLength = 0;
                        // Determine restricitions/requirements for this
                        switch ($xsdElements[$key]['type']){
                            case 'xs:date':
                                $type = 'date';
                                break;
                            case 'stringNormal':
                                $type = 'text';
                                $elementMaxLength = $xsdElements[$key]['simpleTypeData']['maxLength'];
                                break;
                            case 'xs:integer':
                                $type = 'text';
                                $elementMaxLength = 1024;
                                break;
                            case 'xs:anyURI':
                                $type = 'text';
                                $elementMaxLength = 1024;
                                break;
                            case 'stringLong':
                                $type = 'textarea';
                                $elementMaxLength = $xsdElements[$key]['simpleTypeData']['maxLength'];
                                break;
                            case 'KindOfDataTypeType': // different option types will be a 'select' element (these are yet to be determined)
                            case 'optionsDatasetType':
                            case 'optionsDatasetAccess':
                            case 'optionsYesNo':
                            case 'optionsOther':
                                $elementOptions = $xsdElements[$key]['simpleTypeData']['options'];
                                $type = 'select';
                                break;
                        }


                        //'select' has options
                        // 'edit/multiline' has length
                        // 'date' has nothing extra
                        // Handled separately as these specifics might grow.
                        $elementSpecifics = array(); // holds all element specific info
                        if ($type == 'text' OR $type == 'textarea') {
                            $elementSpecifics = array('maxLength' => $elementMaxLength);
                        } elseif ($type == 'select') {
                            $elementSpecifics = array('options' => $elementOptions);
                        }

                        // frontend value is the value that will be presented in the data field
                        // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                        $frontendValue = (isset($element['default']) AND $writeMode) ? $element['default'] : null;

                        if($config['hasMetadataXml'] == 'true') { // the value in the file supersedes default
                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');
                            $frontendValue = $keyValue;
                        }

/* Possibly for future use
                        $messagesForUser = array();

                        if (($type == 'text' OR $type == 'textarea')
                            AND strlen($frontendValue)>$elementMaxLength) {

                            $messagesForUser[] = array('messageNumber' => -200,
                                'messageText' => 'Value in file is too long, truncated at position ' . $elementMaxLength . '<br>Original value: \'<i>' . $frontendValue . '</i>\'');

                            $frontendValue = substr($frontendValue, 0, $elementMaxLength);
                        }

                        if (($type == 'select' AND $frontendValue)) {
                            if(!in_array($frontendValue, $elementOptions)) {

                                $messagesForUser[] = array('messageNumber' => -300,
                                    'messageText' => 'Value in file is not a valid option: \'<i>' .$frontendValue . '</i>\'');

                                $frontendValue = '';
                            }
                        }

                        if (($type == 'date' AND $frontendValue)) {
                            $date = DateTime::createFromFormat('Y-m-d', $frontendValue);
                            $date_errors = DateTime::getLastErrors();

                            if ($date_errors['warning_count'] + $date_errors['error_count'] > 0) {
                                $messagesForUser[] = array('messageNumber' => -400,
                                    'messageText' => 'Value in file is not a valid date: \'<i>' .$frontendValue . '</i>\'');

                                $frontendValue = '';
                            }

                        }

                        // mandatory signaling at the last as values could be erroneous and taken out
                        if($mandatory AND !$frontendValue) {
                            $messagesForUser[] = array('messageNumber' => -100,
                                'messageText' => 'Mandatory value missing');
                        }
*/

                        $presentationElements[$groupName][] = array(
                            'key' => $key,
                            'value' => $frontendValue,
                            'label' => $element['label'],
                            'helpText' => $element['help'],
                            'type' => $type,
                            'mandatory' => $mandatory,
                            'multipleAllowed' => $multipleAllowed,
                            'elementSpecifics' => $elementSpecifics,
                            //'messagesForUser' => $messagesForUser  //possibly for future use
                        );
                    }
                }
            }
        }
        return $presentationElements;
    }

    public function loadXsd($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);
        $xml = simplexml_load_string($fileContent, "SimpleXMLElement", 0,'xs',true);

        if (empty($xml)) {
            return false;
        }

        // At first simpleType handling - gathering limitations/restrictions/requirements
        $simpleTypeData = array();

        foreach($xml->simpleType as $key => $stype) {
            // simpleTye names
            $simpleTypeAttributes = (array)$stype->attributes();

            $simpleTypeName = $simpleTypeAttributes['@attributes']['name'];

            $restriction = (array)$stype->restriction;

            // typical handling here
            if(isset($restriction['maxLength'])) {
                $lengthArray = (array)$stype->restriction->maxLength->attributes();
                $length = $lengthArray['@attributes']['value'];
                $simpleTypeData[$simpleTypeName]['maxLength'] = $length;
            }
            if(isset($restriction['enumeration'])) {
                $options = array();
                foreach($stype->restriction->enumeration as $enum) {
                    $optionsArray = (array)$enum->attributes();
                    $options[] = $optionsArray['@attributes']['value'];
                }
                $simpleTypeData[$simpleTypeName]['options'] = $options;
            }
        }

        $xsdElements = array();

        $elements = $xml->element->complexType->sequence->element;

        $supportedSimpleTypes = array_keys($simpleTypeData);
        $supportedSimpleTypes[] = 'xs:date'; // add some standard xsd simpleTypes that should be working as well
        $supportedSimpleTypes[] = 'xs:anyURI';
        $supportedSimpleTypes[] = 'xs:integer';

        foreach($elements as $element) {
            $attributes = $element->attributes();

            $elementName = '';
            $elementType = '';
            $minOccurs = 0;
            $maxOccurs = 1;

            foreach($attributes as $attribute=>$simpleXMLvalue) {
                $arrayValue = (array)$simpleXMLvalue;
                $value = $arrayValue[0];
                switch ($attribute) {
                    case 'name':
                        $elementName = $value;
                        break;
                    case 'type':
                        $elementType = $value;
                        break;
                    case 'minOccurs':
                        $minOccurs = $value;
                        break;
                    case 'maxOccurs':
                        $maxOccurs = $value;
                        break;
                }
            }

            // each relevant attribute has been processed.
            if(in_array($elementType,$supportedSimpleTypes)) {
                $xsdElements[$elementName] = array(
                    'type' => $elementType,
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array()
                );
            }
        }

        return $xsdElements;
    }

    public function loadFormData($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        libxml_use_internal_errors(true);
        $xmlData = simplexml_load_string($fileContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if(count($errors)) {
            return false;
        }

        $json = json_encode($xmlData);

        return json_decode($json,TRUE);
    }

    public function loadFormElements($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        if (empty($fileContent)) {
            return false;
        }
        $xmlFormElements = simplexml_load_string($fileContent);

        $json = json_encode($xmlFormElements);

        return json_decode($json,TRUE);
    }
}
