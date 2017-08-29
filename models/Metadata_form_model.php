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


// @todo: Obsolete nu met subroperties
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
     * Posted data is not coming in in the correct structure due to the way the frontend had to be prepared to be able to clone elements with subproperties
     *
     * Therefore, reorganisation must take place
     *
     * Step through each element and given the type create the correct output so it can be converted to XML later on
     */
    public function reorganisePostedData($rodsaccount, $xsdPath)
    {
        $metadataElements = array();
        $elementCounter = 0;

        $xsdElements = $this->loadXsd($rodsaccount, $xsdPath);

//        echo '<pre>';
//        print_r($xsdElements);
//        echo '</pre>';

        $arrayPost = $this->CI->input->post();

//        echo '<pre>';
//        echo 'PRE POST<br>';
//           print_r($arrayPost);
//        echo '</pre>';

        // First reorganise in such a way that data coming back from front end
        foreach($arrayPost as $key=>$value) {
            if (is_array($value)) { // multiplicity && subproperty handling
                $sublevelCounter = 0;
                $keepArray = array();
                $structType = '';

                foreach($value as $subKey=>$subValue) {
                    if(is_numeric($subKey)) {
                        if($subValue) {
                            $metadataElements[$elementCounter] = array($key => $value);
                        }
                    }
                    else { // subproperty handling - can either be a single struct or n-structs
                        // A struct typically has 1 element as a hierarchical top element.
                        // The other element holds properties

                        if ($sublevelCounter==0) {
                            if (count($subValue)==1) { // single instance of a struct
                                $structType = 'SINGLE';
                            }
                            else { // multiple instances of struct
                                $structType = 'MULTIPLE';
                            }
                            $keepArray[$sublevelCounter] = array($subKey=>$subValue);
                        }
                        else {
                            $keepArray[$sublevelCounter] = array($subKey=>$subValue);
                        }
                    }
                    $sublevelCounter++;
                }

                $multipleAllowed = false;
                if($xsdElements[$key]['maxOccurs']!='1') {
                    $multipleAllowed = true;
                }
                $isStruct = false;
                if ($xsdElements[$key]['type']=='openTag') {
                    $isStruct = true;
                }

                // after looping through an entire struct it becomes
                if ($structType == 'SINGLE' AND !$multipleAllowed) {
                    $metadataElements[$elementCounter] = array($key => $value);
                }
                elseif ($structType == 'MULTIPLE' OR ($multipleAllowed AND $isStruct)) { // Multi situation
                    $enumeratedData = array();
                    foreach($keepArray[0] as $keyData=>$valData) { // step through the lead properties => then find corresponding subproperties
                        foreach($valData as $referenceID=>$leadPropertyVal ) { // referenceID is actually the counter in the array that coincides for lead and subproperties

                            // Within keepArray[1] handle the corresponding subproperties
                            $subpropArray = array();
                            foreach($keepArray[1] as $subpropKey=>$propValue) {
                                foreach($propValue as $subPropertyName=>$subData){
                                    $subpropArray[$subPropertyName] = $subData[$referenceID];
                                }
                            }
                            $enumeratedData[] = array($keyData => $leadPropertyVal,
                                $subpropKey => $subpropArray
                            );
                        }
                    }
                    $metadataElements[$elementCounter] = array($key => $enumeratedData);
                }
            }
            else {
                // only add if actually containing a value
                if ($value) {
                    $metadataElements[$elementCounter] = array($key => $value);
                }
            }
            $elementCounter++;
        }
        return $metadataElements;
    }

    /**
     * @param $allFormMetadata
     * @return string
     *
     * Per form element that holds data create xml structure
     */
    public function metadataToXmlString($allFormMetadata)
    {

        $xml = new DOMDocument( "1.0", "UTF-8" );
        $xml->formatOutput = true;

        $xml_metadata = $xml->createElement( "metadata" );

        foreach ($allFormMetadata as $elements) {

            foreach ($elements as $elementName=>$elementData) {
                $xml_item = $xml->createElement($elementName);  //@todo - moet deze hier

                if(is_array($elementData)) { // Mutiple situation

                    foreach ($elementData as $key => $value) {

                        if (is_numeric($key)) { // enumeration - multiple

                            if (!is_array($value)) { // multiple - no structure
                                $xml_item = $xml->createElement($elementName); // Per round add 1 new item with same node name to toplevel
                                $xml_item->appendChild($xml->createTextNode($value));
                                $xml_metadata->appendChild($xml_item);
                            }
                            else { // enumerated subproperty structure
                                // entire structure comes by n times

                                foreach($value as $key2=>$value2) {
                                    if(!is_array($value2)) {  // $key2 = Name/ Property
                                        $xml_item = $xml->createElement($elementName);
                                        $xml_sub1 = $xml->createElement($key2);
                                        $xml_sub1->appendChild($xml->createTextNode($value2));
                                        $xml_item->appendChild($xml_sub1);
                                        $xml_metadata->appendChild($xml_item);
                                    }
                                    else {
                                        $xml_sub1 = $xml->createElement($key2);
                                        foreach ($value2 as $key3 => $value3) {
                                            $xml_sub2 = $xml->createElement($key3);
                                            $xml_sub2->appendChild($xml->createTextNode($value3));
                                            $xml_sub1->appendChild($xml_sub2);

                                        }
                                        $xml_item->appendChild($xml_sub1);
                                        $xml_metadata->appendChild($xml_item);
                                    }
                                }

                            }
                        }
                        else { // 1-off property structure
                            $xml_sub1 = $xml->createElement($key); //Name of Property

                            if (!is_array($value)) { // eerste niveau - main property level
                                $xml_sub1->appendChild($xml->createTextNode($value));
                                $xml_item->appendChild($xml_sub1);
                            }
                            else { // tweede niveau - subproperties
                                foreach ($value as $key2 => $value2) {
                                    $xml_sub2 = $xml->createElement($key2);
                                    $xml_sub2->appendChild($xml->createTextNode($value2));
                                    $xml_sub1->appendChild($xml_sub2);

                                }
                                $xml_item->appendChild($xml_sub1);
                            }
                            $xml_metadata->appendChild($xml_item);
                        }
                    }
                }
                else { // 1 topnode - 1 value
                    $xml_item = $xml->createElement($elementName);
                    $xml_item->appendChild($xml->createTextNode($elementData));
                    $xml_metadata->appendChild($xml_item);
                }
            }
        }

        $xml->appendChild($xml_metadata);

        return $xml->saveXML();
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
        $allFormMetadata = $this->reorganisePostedData($rodsaccount, $config['xsdPath']);

        $xmlString = $this->metadataToXmlString($allFormMetadata);

        $this->CI->filesystem->writeXml($rodsaccount, $config['metadataXmlPath'], $xmlString);
    }



// Nieuwe met inachtneming van subproperties structuur
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
        if ($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') {
            $metadataPresent = true;
            $formData = $this->loadFormData($rodsaccount, $config['metadataXmlPath']);

            if ($formData === false) {
                return false;
            }
        }

        $formData = $this->loadFormData($rodsaccount, $config['metadataXmlPath']);

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

        // Form elements is hierarchical too.
        // Bring it back to one level with
        // 1) parent indication
        // 2) fully qualified name (unique!)

        foreach($formAllGroupedElements as $formElements) {

            $fqElementID = '';
            foreach ($formElements as $key => $element) {
                // Find group definition
                // Find hierarchies of elements regarding subproperties.

                if($key == '@attributes') { // GROUP handling

                    $groupName = $element['name'];
                }
                elseif(false) {
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
                            case 'stringURI':
                            case 'stringNormal':
                                $type = 'text';
                                $elementMaxLength = $xsdElements[$key]['simpleTypeData']['maxLength'];
                                break;
                            case 'xs:integer':
                                $type = 'numeric';
                                $elementMaxLength = 10;  // arbitrary length for this moment
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
                                /*
                                case 'optionsDatasetType':
                                case 'optionsDatasetAccess':
                                case 'optionsYesNo':
                                case 'optionsOther':
                                case 'optionsPersonalPersistentIdentifierType':
                                */
                            case (substr($xsdElements[$key]['type'], 0, 7) == 'options'):
                                $elementOptions = $xsdElements[$key]['simpleTypeData']['options'];
                                $type = 'select';
                                break;
                        }


                        //'select' has options
                        // 'edit/multiline' has length
                        // 'date' has nothing extra
                        // Handled separately as these specifics might grow.
                        $elementSpecifics = array(); // holds all element specific info
                        if ($type == 'text' OR $type == 'textarea' OR $type=='numeric') {
                            $elementSpecifics = array('maxLength' => $elementMaxLength);
                        } elseif ($type == 'select') {
                            $elementSpecifics = array('options' => $elementOptions);
                        }

                        // frontend value is the value that will be presented in the data field
                        // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                        $frontendValue = (isset($element['default']) AND $writeMode) ? $element['default'] : null;

                        if($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default
                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');
                            $frontendValue = $keyValue;
                        }

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
                elseif (!isset($element['label'])) { // STEPPING INTO DEEPER LEVELS -- we step into a hierarchy => SUPPROPERTIES
                    $structureValues = $formData[$key];

                    if (!is_array($structureValues)) {
                        $structValueArray = array();
                        $structValueArray[] = $formData; //$structureValues;
                    } else {
                        $structValueArray = $structureValues;
                        if (count($structValueArray) == 0) {
                            $structValueArray = array('');
                        }
                    }

                    $counterForFrontEnd = 0; // to be able to distinghuis structures and add to
                    foreach ($structValueArray as $structValues) {
                        $fqElementID .= $key;

                        // MAIN LOOP TO SETUP A COMPLETE SUBPROPERTY STRUCTURE
                        foreach ($element as $id => $em) {
                            // $elements now holds an array of formelements - these are subproperties

                            $subKey = $key . '_' . $id;

                            if (isset($em['label'])) {   // TOP ELEMENT OF A STRUCT
                                $subKeyValue = $structValues[$subKey];

                                // Use toplevel here as that defines multiplicity for a structure (is in fact not an element
                                $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                                if (!$multipleAllowed) {
//                                    if(count($structValueArray)>1) {
//                                        return false;
//                                    }
                                }

                                // frontend value is the value that will be presented in the data field
                                // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                                $frontendValue = (isset($em['default']) AND $writeMode) ? $em['default'] : null;

                                if($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
//                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');  // no purpose as it is superseded by next line
                                    $frontendValue = $subKeyValue;
                                }

                                $subPropArray = array(
                                    'subPropertiesRole' => 'subPropertyStartStructure',
                                    'subPropertiesBase' => $fqElementID,
                                    'subPropertiesStructID' => $multipleAllowed ? $counterForFrontEnd : -1,//$fqElementID . '-0', // volgnummer -> moet nog dyndamisch worden
                                );
                                $presentationElements[$groupName][] =
                                    $this->newPresentationElement($xsdElements, $em, $subKey, $key . '[' . $id . ']',$frontendValue, $multipleAllowed, $subPropArray);

/*
                                $presentationElements[$groupName][] = array(
                                    'key' => $key . '[' . $id . ']',
                                    'subPropertiesRole' => 'subPropertyStartStructure',
                                    'subPropertiesBase' => $fqElementID,
                                    'subPropertiesStructID' => $multipleAllowed ? $counterForFrontEnd : -1,//$fqElementID . '-0', // volgnummer -> moet nog dyndamisch worden
                                    'value' => $frontendValue,
                                    'label' => $em['label'],
                                    'helpText' => $em['help'],
                                    'type' => $type,
                                    'mandatory' => $mandatory,
                                    'multipleAllowed' => $multipleAllowed,
                                    'elementSpecifics' => $elementSpecifics,
                                );
*/
                            } else { // STEP THROUGH EACH SUB PROPERTY
                                foreach ($em as $propertyKey => $propertyElement) {

                                    //$frontendValue = $formData[$fqElementID . '_' . $id . '_' . $propertyKey];
                                    $subKey = $key . '_' . $id . '_' . $propertyKey;
                                    $subKeyValue = $structValues[$subKey];


                                    $multipleAllowed = false;
                                    if ($xsdElements[$key]['maxOccurs'] != '1') {  //look at top of structure
                                        $multipleAllowed = true;
                                    }

                                    // frontend value is the value that will be presented in the data field
                                    // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                                    $frontendValue = (isset($propertyElement['default']) AND $writeMode) ? $propertyElement['default'] : null;

                                    // @todo - hoe zit dit precies met die $config
                                    if(true) { //$config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
//                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');  // no purpose as it is superseded by next line
                                        $frontendValue = $subKeyValue;
                                    }


                                    $subPropArray = array(
                                        'subPropertiesRole' => 'subProperty',
                                        'subPropertiesBase' => $key,
                                        'subPropertiesStructID' => $multipleAllowed ? $counterForFrontEnd : -1,//$fqElementID . '-0', //volgnummer -> moet nog dynamisch worden
                                    );
                                    $presentationElements[$groupName][] =
                                        $this->newPresentationElement($xsdElements, $propertyElement, $subKey,
                                            $key . '[' . $id . '][' . $propertyKey . ']', $frontendValue, $multipleAllowed, $subPropArray);

/*
                                    $presentationElements[$groupName][] = array(
                                        'key' => $key . '[' . $id . '][' . $propertyKey . ']',
                                        'subPropertiesRole' => 'subProperty',
                                        'subPropertiesBase' => $key,
                                        'subPropertiesStructID' => $multipleAllowed ? $counterForFrontEnd : -1,//$fqElementID . '-0', //volgnummer -> moet nog dynamisch worden
                                        'value' => $frontendValue,
                                        'label' => $propertyElement['label'],
                                        'helpText' => $propertyElement['help'],
                                        'type' => $type,
                                        'mandatory' => $mandatory,
                                        'multipleAllowed' => false, // never multipleAllowed for a supproperty at this moment
                                        'elementSpecifics' => $elementSpecifics,
                                    );
*/
                                }
                                $fqElementID = '';
                            }
                        }
                        $counterForFrontEnd++;
                    }
                }
                else // This is the first level only!
                {
                    $value = isset($formData[$key]) ? $formData[$key] : '';

                    $valueArray = $this->getElementValueAsArray($value);

                    $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                    if (!$multipleAllowed) {
                        if (count($valueArray) > 1) {
                            return false; // break it off as this does not comply with xml
                        }
                    }

                    /// Step through all present values (if multiple and create element for each of them)
                    foreach ($valueArray as $keyValue)
                    {
                        // frontend value is the value that will be presented in the data field
                        // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                        $frontendValue = (isset($element['default']) AND $writeMode) ? $element['default'] : null;

                        // @todo - hoe zit dit precies met die $config
                        if(true) { //$config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
//                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');  // no purpose as it is superseded by next line
                            $frontendValue = $keyValue;
                        }

                        $presentationElements[$groupName][] =
                            $this->newPresentationElement($xsdElements, $element, $key, $key, $frontendValue, $multipleAllowed);
                    }
                }
            }
        }

        return $presentationElements;
    }

    /**
     * @param $xsdElements
     * @param $element
     * @param $xsdKey
     * @param $keyId
     * @param $frontendValue
     * @param $multipleAllowed
     * @param array $subpropertyInfo
     * @return array

     * construct an array with all required data for frontend presentation in the metadataform

     */
    public function newPresentationElement($xsdElements, $element, $xsdKey, $keyId, $frontendValue, $multipleAllowed, $subpropertyInfo=array())
    {
        // Mandatory no longer based on XSD but taken from formelements.xml
        $mandatory = false;
        if (isset($element['mandatory']) AND strtolower($element['mandatory']) == 'true') {
            $mandatory = true;
        }

        $elementOptions = array(); // holds the options
        $elementMaxLength = 0;
        // Determine restricitions/requirements for this
        switch ($xsdElements[$xsdKey]['type']){
            case 'xs:date':
                $type = 'date';
                break;
            case 'stringURI':
            case 'stringNormal':
                $type = 'text';
                $elementMaxLength = $xsdElements[$xsdKey]['simpleTypeData']['maxLength'];
                break;
            case 'xs:integer':
                $type = 'numeric';
                $elementMaxLength = 10;  // arbitrary length for this moment
                break;
            case 'xs:anyURI':
                $type = 'text';
                $elementMaxLength = 1024;
                break;
            case 'stringLong':
                $type = 'textarea';
                $elementMaxLength = $xsdElements[$xsdKey]['simpleTypeData']['maxLength'];
                break;
            case 'KindOfDataTypeType': // different option types will be a 'select' element (these are yet to be determined)
                /*
                case 'optionsDatasetType':
                case 'optionsDatasetAccess':
                case 'optionsYesNo':
                case 'optionsOther':
                case 'optionsPersonalPersistentIdentifierType':
                */
            case (substr($xsdElements[$xsdKey]['type'], 0, 7) == 'options'):
                $elementOptions = $xsdElements[$xsdKey]['simpleTypeData']['options'];
                $type = 'select';
                break;
        }

        //'select' has options
        // 'edit/multiline' has length
        // 'date' has nothing extra
        // Handled separately as these specifics might grow.
        $elementSpecifics = array(); // holds all element specific info
        if ($type == 'text' OR $type == 'textarea' OR $type=='numeric') {
            $elementSpecifics = array('maxLength' => $elementMaxLength);
        } elseif ($type == 'select') {
            $elementSpecifics = array('options' => $elementOptions);
        }

        $elementData = array(
            'key' => $keyId, // Key to be used by frontend
            'value' => $frontendValue,
            'label' => $element['label'],
            'helpText' => $element['help'],
            'type' => $type,
            'mandatory' => $mandatory,
            'multipleAllowed' => $multipleAllowed,
            'elementSpecifics' => $elementSpecifics,
        );

        if (count($subpropertyInfo)) {
            foreach ($subpropertyInfo as $key=>$value) {
                $elementData[$key] = $value;
            }
        }
        return $elementData;
    }

    /**
     * @param $xsdElement
     * @return bool
     *
    Supporting function for getFormELements
     * is multiple allowed for the given element from the xsd
     */
    public function getElementMultipleAllowed($xsdElement)
    {
        $multipleAllowed = false;
        if ($xsdElement['maxOccurs'] != '1') {
            $multipleAllowed = true;
        }
        return $multipleAllowed;
    }

    /**
     * @param $value
     * @return array
     *
     * Supporting function for getFormELements
     *
     * return the value as being part of an array
     */
    public function getElementValueAsArray($value)
    {
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
        return $valueArray;
    }


// @TODO: to be refactored conform subproperties - obsolete???

    /**
     * @param $rodsaccount
     * @param $config
     * @return array|bool
     *
     * Same as getFormElements.
     * However, do not involve yoda-metatadata.xml as this can contain user introduced errors
     * resulting in not delivering xsd/formelements.xml information that should however always be possible to gather
     */
/*
    public function getFormElementsExcludeYodaMetaData($rodsaccount, $config)
    {
        // load xsd and get all the info regarding restrictions
        $xsdElements = $this->loadXsd($rodsaccount, $config['xsdPath']); // based on element names

        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
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

                    // Mandatory no longer based on XSD but taken from formelements.xml
                    $mandatory = false;
                    if(isset($element['mandatory']) AND strtolower($element['mandatory'])=='true') {
                        $mandatory = true;
                    }

                    $multipleAllowed = false;
                    if($xsdElements[$key]['maxOccurs']!='1') {
                        $multipleAllowed = true;
                    }

                    if (true) {
                        $elementOptions = array(); // holds the options
                        $elementMaxLength = 0;
                        // Determine restricitions/requirements for this
                        switch ($xsdElements[$key]['type']){
                            case 'xs:date':
                                $type = 'date';
                                break;
                            case 'stringURI':
                            case 'stringNormal':
                                $type = 'text';
                                $elementMaxLength = $xsdElements[$key]['simpleTypeData']['maxLength'];
                                break;
                            case 'xs:integer':
                                $type = 'numeric';
                                $elementMaxLength = 10;  // arbitrary length for this moment
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
                            case (substr($xsdElements[$key]['type'], 0, 7) == 'options'):
                                $elementOptions = $xsdElements[$key]['simpleTypeData']['options'];
                                $type = 'select';
                                break;
                        }


                        //'select' has options
                        // 'edit/multiline' has length
                        // 'date' has nothing extra
                        // Handled separately as these specifics might grow.
                        $elementSpecifics = array(); // holds all element specific info
                        if ($type == 'text' OR $type == 'textarea' OR $type=='numeric') {
                            $elementSpecifics = array('maxLength' => $elementMaxLength);
                        } elseif ($type == 'select') {
                            $elementSpecifics = array('options' => $elementOptions);
                        }

                        $presentationElements[$groupName][] = array(
                            'key' => $key,
                            'value' => '', // irrelevant in this case
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

*/

    /**
     * @param $rodsaccount
     * @param $path
     * @return array|bool
     *
     * Load XSD schema and reorganize in such a way that it coincides with formelements
     *
     * XSD can hold hierarchical complex types now that must match formelements definitions
     */
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

        $supportedSimpleTypes = array_keys($simpleTypeData);
        $supportedSimpleTypes[] = 'xs:date'; // add some standard xsd simpleTypes that should be working as well
        $supportedSimpleTypes[] = 'xs:anyURI';
        $supportedSimpleTypes[] = 'xs:integer';

        // Basic information is complete

        // NOW collect stuff regarding fields

        $xsdElements = array();

        // Hier moet eigenlijk op getest worden of dit info bevat. Dit is de kern van informatie die je bij alle zaken brengt.
        // DIt is zelfs hierarchisch dus zou
        $elements = $xml->element->complexType->sequence->element;

        $this->addElements($xsdElements, $elements, $supportedSimpleTypes, $simpleTypeData);

        return $xsdElements;
    }

    /**
     * @param $xsdElements
     * @param $elements
     * @param $supportedSimpleTypes
     * @param $simpleTypeData
     * @param string $prefixHigherLevel

     Handling of hierarchical elements
     */
    public function addElements(&$xsdElements, $elements, $supportedSimpleTypes, $simpleTypeData, $prefixHigherLevel='')
    {
        foreach ($elements as $element) {
            $attributes = $element->attributes();

            $elementName = '';
            $elementType = '';
            $minOccurs = 0;
            $maxOccurs = 1;

            foreach ($attributes as $attribute=>$simpleXMLvalue) {
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

            $isDeeperLevel = false;
            if (@property_exists($element->complexType->sequence, 'element')) {  // Starting tag Deeper level
                $isDeeperLevel = true;
            }

            // each relevant attribute has been processed.
            if (in_array($elementType,$supportedSimpleTypes) OR $isDeeperLevel) {
                $xsdElements[ $prefixHigherLevel . $elementName] = array(
                    'type' => ($isDeeperLevel ? 'openTag' : $elementType),
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array()
                );
            }

            if ($isDeeperLevel) { // Add new level and extend the prefix-identifier
                $elementSubLevel = $element->complexType->sequence->element;

                //$prefixHigherLevel = $elementName . '_'; // to be used to identify elements
                if (!$prefixHigherLevel) {
                    $prefixHigherLevel = $elementName . '_';
                }
                else {
                    $prefixHigherLevel = $prefixHigherLevel . $elementName . '_';
                }

                // dieper niveau uitvoeren op basis an de gestelde prefix.
                $this->addElements($xsdElements, $elementSubLevel, $supportedSimpleTypes, $simpleTypeData, $prefixHigherLevel);

				$prefixHigherLevel = ''; //reset it again

                // Closing tag - Deeper level
                $xsdElements[$elementName . '_close'] = array(
                    'type' => 'closeTag',
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array()
                );
            }
        }
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @return array|bool
     *
     * Load the yoda-metadata.xml file ($path) in an array structure
     *
     * Reorganise this this in such a way that hierarchy is lost but indexing is possible by eg 'Author_Property_Role'
     */
    public function loadFormData($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        libxml_use_internal_errors(true);
        $xmlData = simplexml_load_string($fileContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (count($errors)) {
            return false;
        }

        $json = json_encode($xmlData);

        $formData = json_decode($json,TRUE);

//        echo '<pre>';
//        print_r($formData);
//        echo '</pre>';

        $newFormData = array();
        foreach ($formData as $key=>$data) {
            if (!is_array($data)) {
                $newFormData[$key] = $data;
            }
            else {
                // First sublevel.
                // Could be an enumeration - i.e multiple instances of same element
                foreach ($data as $key2=>$data2) {
                    if (!is_array($data2)) {  // normal multisituation handling
                        if (is_numeric($key2)) {
                            $newFormData[$key][$key2] = $data2;
                        }
                        else {
                            $newFormData[$key . '_' . $key2] = $data2;
                        }
                    }
                    else {
                        if (is_numeric($key2)) { // SUBPROPERTIES: enumeration and therefore a complete set
                            $subPropertiesArray = array();

                            foreach ($data2 as $key3=>$data3) {
                                if (!is_array($data3)) {
                                    $subPropertiesArray[$key . '_' . $key3] = $data3;
                                } else {
                                    foreach ($data3 as $key4 => $data4) {
                                        $subPropertiesArray[$key . '_' . $key3 . '_' . $key4] = $data4;
                                    }
                                }
                            }
                            // Put array of subproperties under common key for later purposes
                            $newFormData[$key][] = $subPropertiesArray;
                        }
                        else {
                            foreach ($data2 as $key3=>$data3) {
                                if (!is_array($data3)) {
                                    $newFormData[$key . '_' . $key2 . '_' . $key3] = $data3;
                                } else {
                                    foreach ($data3 as $key4 => $data4) {
                                        $newFormData[$key . '_' . $key2 . '_' . $key3 . '_' . $key4] = $data4;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

//        echo '<pre>';
//        print_r($newFormData);
//        echo '</pre>';
//        exit;
        return $newFormData;
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @return bool|mixed
     *
     * Load the required xml file with formelements description and return as an array
     */
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
