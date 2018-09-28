<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model
{

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();

        $this->CI->load->model('filesystem');
    }


    private function _createXmlElementWithText($xml, $elementName, $text)
    {
        $xmlElement = $xml->createElement($elementName);
        $xmlElement->appendChild($xml->createTextNode($text));

        return $xmlElement;
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
    public function processPost($rodsaccount, $config)
    {
        $arrayPost = $this->CI->input->post();
        $formReceivedData = json_decode($arrayPost['formData'], true);

        // formData now contains info of descriptive groups.
        // These must be excluded first for ease of use within code
        $formData = array();
        foreach($formReceivedData as $group=>$realFormData) {
            #first level to be skipped as is descriptive
            foreach($realFormData as $key => $val  ) {
                $formData[$key] = $val;
            }
        }

        $folder = $config['metadataXmlPath'];
        $jsonsElements = $this->loadJSONS($rodsaccount, $folder);

        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;

        $xml_metadata = $xml->createElement("metadata");

        foreach ($jsonsElements['properties'] as $groupName => $formElements) {
            foreach ($formElements['properties'] as $mainElement => $element) {

                if (isset($formData[$mainElement])) {
                    if (!isset($element['type'])
                        || $element['type']=='integer'
                        || $element['type']=='string' // string in situations like date on top level (embargo end date)
                    ) {  //No structure single element

                        $xmlMainElement = $this->_createXmlElementWithText($xml, $mainElement, $formData[$mainElement]);
                        $xml_metadata->appendChild($xmlMainElement);
                    }
                    else {
                        $structObject = array();

                        if ($element['type'] == 'object') {   // SINGLGE STRUCT ON HIGHEST LEVEL
                            $structObject = $element;

                            if ($structObject['yoda:structure'] == 'compound') { // heeft altijd een compound signifying element nodig
                                $xmlMainElement = $xml->createElement($mainElement);
                                $anyValueFound = false;
                                foreach ($structObject['properties'] as $compoundElementKey => $compoundElementInfo) {
                                    if (isset($formData[$mainElement][$compoundElementKey]) && strlen($formData[$mainElement][$compoundElementKey])) {
                                        $anyValueFound = true;

                                        $xmlCompoundElement = $this->_createXmlElementWithText($xml, $compoundElementKey, $formData[$mainElement][$compoundElementKey]);
                                        $xmlMainElement->appendChild($xmlCompoundElement);
                                    }
                                }
                                if ($anyValueFound) {
                                    $xml_metadata->appendChild($xmlMainElement);
                                }
                            }
                            elseif($structObject['yoda:structure'] == 'subproperties'){
                                // Single subproperty struct is not present at the moment in the schema
                                // Not handled at this moment
                            }
                        }
                        // Multiple
                        elseif ($element['type'] == 'array') {
                            if (!(isset($element['items']['type']) and $element['items']['type'] == 'object')) {
                                // multiple non structured element
                                // So loop through data now
                                foreach($formData[$mainElement] as $value) {
                                    if ($value) {
                                        $xmlMainElement = $this->_createXmlElementWithText($xml, $mainElement, $value);
                                        $xml_metadata->appendChild($xmlMainElement);
                                    }
                                }
                            }
                            // multiple structures
                            else {
                                $structObject = $element['items'];
                                if ($structObject['yoda:structure'] == 'subproperties') {

                                    foreach ($formData[$mainElement] as $subPropertyStructData) {  // loop through all subproperty structures
                                        $hasLeadValue = false;
                                        $hasSubPropertyValues = false;
                                        $xmlMainElement = $xml->createElement($mainElement);
                                        $index = 0; // to distinguish between lead and sub
                                        foreach ($structObject['properties'] as $subPropertyElementKey => $subPropertyElementInfo) {

                                            // Step through object structure
                                            if ($index==0) { // Lead part of structure - ALWAYS SINGLE VALUE!!
                                                $leadData = isset($subPropertyStructData[$subPropertyElementKey])? $subPropertyStructData[$subPropertyElementKey] : '';
                                                $xmlLeadElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $leadData);
                                                $xmlMainElement->appendChild($xmlLeadElement);

                                                if (strlen($leadData)) {
                                                    $hasLeadValue = true;
                                                }
                                            }
                                            elseif($index==1) { // Start of subproperty part. Create subproperty structure element here.
                                                $xmlProperties = $xml->createElement('Properties');
                                                // Subproperty part of structure --
                                                // This is the first line
                                                // NEVER compound on first subprop line so take shortcut here.
                                                // Prepwork
                                                $values = array();
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    $values[0] = isset($subPropertyStructData[$subPropertyElementKey])? $subPropertyStructData[$subPropertyElementKey] : '';
                                                }
                                                else if ($subPropertyElementInfo['type']=='array') {
                                                    $values = $subPropertyStructData[$subPropertyElementKey];
                                                }

                                                foreach($values as $value) {
                                                    if(strlen($value)) {
                                                        $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                        $xmlProperties->appendChild($xmlSubElement);

                                                        $hasSubPropertyValues = true;
                                                    }
                                                }
                                            }
                                            else {  // next lines after first in subproperty part
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    //echo 'SINGLE ITEM';  ///KOMT  NU NIET VOOR VOOR SUBPROPERTIES
                                                }
                                                elseif ($subPropertyElementInfo['type']=='array') {
                                                    if (!isset($subPropertyElementInfo['items']['type'])) {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $value) {
                                                            if (strlen($value)) {
                                                                $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                                $xmlProperties->appendChild($xmlSubElement);

                                                                $hasSubPropertyValues = true;
                                                            }
                                                        }
                                                    }
                                                    else {
                                                        // Multiple compounds
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $data) {
                                                            $subCompoundHasAnyValue = false;
                                                            $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                            foreach ($subPropertyElementInfo['items']['properties'] as $subCompoundKey => $subVal) {
                                                                if(isset($data[$subCompoundKey])) {
                                                                    if (strlen($data[$subCompoundKey])) {
                                                                        $subData = $data[$subCompoundKey];
                                                                        $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);
                                                                        $xmlSubElement->appendChild($xmlSubCompound);

                                                                        $subCompoundHasAnyValue = true;
                                                                    }
                                                                }
//                                                                else {
//                                                                    $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, '');
//                                                                    $xmlSubElement->appendChild($xmlSubCompound);
//                                                                    //$subCompoundHasAnyValue = true;
//                                                                }
                                                            }
                                                            // Only add compound struct if actually data is present within compound
                                                            if ($subCompoundHasAnyValue) {
                                                                $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap

                                                                $hasSubPropertyValues = true;
                                                            }
                                                        }
                                                    }
                                                } // Single compound
                                                elseif ($subPropertyElementInfo['yoda:structure']=='compound') {
                                                    $subCompoundHasAnyValue = false;
                                                    $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                    foreach($subPropertyElementInfo['properties'] as $subCompoundKey => $subVal) {
                                                        if (isset($subPropertyStructData[$subPropertyElementKey][$subCompoundKey])) {
                                                            $subData = $subPropertyStructData[$subPropertyElementKey][$subCompoundKey];
                                                            if (strlen($subData)) {
                                                                $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);
                                                                $xmlSubElement->appendChild($xmlSubCompound);

                                                                $subCompoundHasAnyValue = true;
                                                            }
                                                        }
                                                    }
                                                    // Only add compound struct if actually data is present within compound
                                                    if ($subCompoundHasAnyValue) {
                                                        $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap

                                                        $hasSubPropertyValues = true;
                                                    }
                                                }
                                            }
                                            $index++;
                                        }

                                        // Extra intelligence to only save when there is relevant data
                                        if ($hasLeadValue) {  // for now overrule this requirement
                                            // add the entire structure to the main element
                                            if ($hasSubPropertyValues) {
                                                $xmlMainElement->appendChild($xmlProperties);
                                            }
                                            $xml_metadata->appendChild($xmlMainElement);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $xml->appendChild($xml_metadata);

        $xmlString = $xml->saveXML();
        print_r($xmlString);
//exit;
        $this->CI->filesystem->writeXml($rodsaccount, $config['metadataXmlPath'], $xmlString);
    }

    /**
     * @param $rodsaccount
     * @param $path - folder of area being worked in. irods will find out which json schema is to be used
     * @return string
     */
    public function loadJSONS($rodsaccount, $path)
    {
        $result = $this->CI->filesystem->getJsonSchema($rodsaccount, $path);

        if ($result['*status'] == 'Success') {
            return $result['*result'];
        } else {
            return '';
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

    /** USER IN NEW SITUATION */
    public function loadFormData($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        libxml_use_internal_errors(true);
        $xmlData = simplexml_load_string($fileContent);
        $errors = libxml_get_errors();

//        print_r($errors);

        libxml_clear_errors();

        if (count($errors)) {
            return false;
        }

        $json = json_encode($xmlData);

        $formData = json_decode($json, TRUE);

        return $formData;
    }

    /**
     * @param $jsonSchema
     * @param $xmlFormData
     * @return array
     *
     * Combines xml data with the json schema and returns array as a basis for react form
     */
    public function prepareJSONSFormData($jsonSchema, $xmlFormData)
    {
        $formData = array();
        //foreach ($result['properties'] as $groupKey => $group) {
        foreach ($jsonSchema['properties'] as $groupKey => $group) {
            //Group
            foreach($group['properties'] as $fieldKey => $field) {
                // Field
                if (array_key_exists('type', $field)) {
                    if ($field['type'] == 'string') { // string
                        if (isset($xmlFormData[$fieldKey])) {
                            $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                        }
                    } else if ($field['type'] == 'integer') { // integer
                        if (isset($xmlFormData[$fieldKey])) {
                            $formData[$groupKey][$fieldKey] = (integer) $xmlFormData[$fieldKey];
                        }
                    } else if ($field['type'] == 'array') { // array

                        if ($field['items']['type'] == 'string' || !isset($field['items']['type'])) {

                            if (isset($xmlFormData[$fieldKey])) {
                                if (count($xmlFormData[$fieldKey]) == 1) {
                                    $formData[$groupKey][$fieldKey] = array($xmlFormData[$fieldKey]);
                                } else {
                                    $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                                }
                            }
                            else { // Add single (empty value) array item so duplicable fields are shown in an opened state with no actual data in em
                                $formData[$groupKey][$fieldKey] = array(0=>'');
                            }
                        } else if ($field['items']['type'] == 'object') {
                            //$formData[$groupKey][$fieldKey] = array();
                            $emptyObjectField = array();

                            //if (true) {
                            // check whether is enumeration or single structure in the data.
                            $xmlDataArray = array();
                            if (isset($xmlFormData[$fieldKey])) {
                                foreach ($xmlFormData[$fieldKey] as $keyTest => $valueTest) {
                                    if (is_numeric($keyTest)) {
                                        $xmlDataArray = $xmlFormData[$fieldKey];
                                    } else {
                                        $xmlDataArray[] = $xmlFormData[$fieldKey];
                                    }
                                    break;
                                }
                            }
                            else {
                                $xmlDataArray = array($fieldKey=>'');  // not present situation
                            }

                            // Loop through data
                            foreach($xmlDataArray as $xmlData) {
                                $mainProp = true;
                                $emptyObjectField = array();

                                // Loop through the elements constituing the structure:
                                foreach ($field['items']['properties'] as $objectKey => $objectField) {
                                                    //echo $fieldKey . '-' . $objectKey;
//                                                    print_r($field);
//                                                    if ($fieldKey == 'Related_Datapackage') {
//                                                        echo 'RDP ---' . $objectKey;
//                                                        print_r($objectField);
//                                                    }
                                    // Start of sub property structure
                                    if ( $field['items']['yoda:structure'] == 'subproperties') {
                                        // Lead property handling
                                        if ($mainProp) {
                                            if (isset($xmlData[$objectKey])) { // DIT KUNNEN DUS OOK LEGE REGELS ZIJN IN HET XML FORM
                                                // Should NOT be an array!
                                                // This is possible when a tag is present in xml but has no data. <Creator><Name></Name></Creator>
                                                $leadData = '';
                                                if (!is_array($xmlData[$objectKey])) {
                                                    $leadData = $xmlData[$objectKey];
                                                }
                                                $emptyObjectField[$objectKey] = $leadData;

                                            } else { // Add empty string so lead property exists => will visibly open the structure
                                                $emptyObjectField[$objectKey] = '';
                                            }
                                            $mainProp = false;
                                        } else {   // sub part of sub property handling.
//                                            if($fieldKey=='Related_Datapackage') {
//                                                print_r($objectKey);
//                                                echo '->';
//                                                print_r($objectField);
//                                                print_r($xmlData['Properties'][$objectKey]);
//                                                echo '------------------------------------------------------------------';
//                                            }

                                            if (isset($xmlData['Properties'][$objectKey])) { // DATA EXISTS FOR $objectKey
                                                if ($objectField['type']=='array') {  // multiple - can be compound or single field

//                                                    if($fieldKey=='Creator') {
//                                                        print_r($objectKey);
//                                                        print_r($objectField);
//
//                                                        print_r($xmlData['Properties'][$objectKey]);
//                                                    }

                                                    $countData = count($xmlData['Properties'][$objectKey]);
                                                    if(!$countData) { //when there are empty tags in the xml file
                                                        if(isset($objectField['items']['yoda:structure'])) {
                                                            // step through compound elements
                                                            $arCompoundFields = array();
                                                            foreach ($objectField['items']['properties'] as $compoundElementKey => $info) {
                                                                $arCompoundFields[$compoundElementKey] = '';
                                                            }
                                                            $emptyObjectField[$objectKey][] = $arCompoundFields;
                                                        }
                                                        else {
                                                            $emptyObjectField[$objectKey][] = '';
                                                        }
                                                    } else {
                                                        if(isset($objectField['items']['yoda:structure'])) { // collect each compound and assess whether is valid
                                                            // each compound field must be assigned a value

                                                            // prepare the data
                                                            foreach($xmlData['Properties'][$objectKey] as $key=>$val) {
                                                                if(!is_numeric($key)) {
                                                                    $baseData[] = $xmlData['Properties'][$objectKey];
                                                                }
                                                                else {
                                                                    $baseData = $xmlData['Properties'][$objectKey];
                                                                }
                                                                break;
                                                            }

                                                            foreach($baseData as $data) {
                                                                //$data = $xmlData['Properties'][$objectKey];
                                                                $arCompoundFields = array();
                                                                foreach ($objectField['items']['properties'] as $compoundElementKey => $info) {
                                                                    $arCompoundFields[$compoundElementKey] = '';

                                                                    // only take the data when not an array
                                                                    if (isset($data[$compoundElementKey]) && !is_array($data[$compoundElementKey])) {
                                                                        $arCompoundFields[$compoundElementKey] = $data[$compoundElementKey];
                                                                    }
                                                                }
                                                                $emptyObjectField[$objectKey][] = $arCompoundFields;
                                                            }
                                                        }
                                                        else {
                                                            //print_r($objectField);
                                                            // AFfiliation gedoe
                                                            //$emptyObjectField[$objectKey][] = $xmlData['Properties'][$objectKey];
                                                            $affValuesArray = $xmlData['Properties'][$objectKey];
                                                            if(!is_array($affValuesArray)) {
                                                                $affValuesArray = array($xmlData['Properties'][$objectKey]);
                                                            }
                                                            $emptyObjectField[$objectKey] = $affValuesArray ; //$xmlData['Properties'][$objectKey];
                                                        }
                                                    }
                                                    //}
                                                } elseif(($objectField['type']=='object')) {  // compound single structure
//                                                    echo $fieldKey . '-' . $objectKey;
//                                                    print_r()
//                                                    if ($fieldKey == 'Related_Datapackage') {
//                                                        echo 'RDP ---' . $objectKey;
//                                                        print_r($objectField);
//                                                    }
//                                                    if($fieldKey=='Related_Datapackage') {
//                                                        print_r($objectKey);
//                                                        echo '->';
//                                                        print_r($objectField);
//                                                        print_r($xmlData['Properties'][$objectKey]);
//                                                        echo '------------------------------------------------------------------';
//                                                    }

                                                    $arCompoundFields = array();

                                                    $data = $xmlData['Properties'][$objectKey];
                                                    foreach ($objectField['properties'] as $compoundElementKey => $info) {
                                                        $arCompoundFields[$compoundElementKey] = '';

                                                        // only take the data when not an array
                                                        if (isset($data[$compoundElementKey]) && !is_array($data[$compoundElementKey])) {
                                                            $arCompoundFields[$compoundElementKey] = $data[$compoundElementKey];
                                                        }
                                                    }
                                                    $emptyObjectField[$objectKey] = $arCompoundFields;

                                                }
                                                else { // can only be single field as this is a subproperty
                                                    $subValue = '';
                                                    if (!is_array($xmlData['Properties'][$objectKey])) {
                                                        $subValue = $xmlData['Properties'][$objectKey];
                                                    }
                                                    $emptyObjectField[$objectKey] = $subValue;
                                                }
                                            }  // DATA DOES NOT EXIST
                                            else {
//                                                echo '--------- BLABLABLABLABLA------';
//                                                print_r($objectKey);
//                                                print_r($objectField);
//                                                echo '---------';

                                                if(isset($objectField['items']['yoda:structure'])) {
                                                    $arCompoundFields = array();
                                                    foreach ($objectField['items']['properties'] as $compoundElementKey => $info) {
                                                        $arCompoundFields[$compoundElementKey] = '';
                                                    }
                                                    $emptyObjectField[$objectKey][] = $arCompoundFields;
                                                }
                                                else {
                                                    if ($objectField['type'] == 'array') {
                                                        $emptyObjectField[$objectKey][] = '';
                                                    }
                                                    else if ($objectField['type'] == 'object') {
                                                        $arCompoundFields = array();
                                                        foreach ($objectField['properties'] as $compoundElementKey => $info) {
                                                            $arCompoundFields[$compoundElementKey] = '';
                                                        }
                                                        $emptyObjectField[$objectKey] = $arCompoundFields;
                                                    }
                                                    else {
                                                        $emptyObjectField[$objectKey] = '';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else {
                                        if ($objectField['type'] == 'string') {
                                            $emptyObjectField[$objectKey] = $objectKey;
                                        } else if ($objectField['type'] == 'object') { //subproperties (OLD)
                                            foreach ($objectField['properties'] as $subObjectKey => $subObjectField) {
                                                if ($subObjectField['type'] == 'string') {
                                                    $emptyObjectField[$objectKey][$subObjectKey] = $objectKey;
                                                } else if ($subObjectField['type'] == 'object') {// Composite
                                                    $compositeField = array();
                                                    foreach ($subObjectField['properties'] as $subCompositeKey => $subCompositeField) {
                                                        $compositeField[$subCompositeKey] = $subCompositeKey;
                                                    }

                                                    $emptyObjectField[$objectKey][$subObjectKey] = $compositeField;
                                                }
                                            }
                                        }
                                    }
                                }
                                if (count($emptyObjectField)) {
                                    $formData[$groupKey][$fieldKey][] = $emptyObjectField;
                                }
                            }

////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                $formData[$groupKey][$fieldKey][] = array('Funder_Name' => 'Fund1',
//                                    'Award_Number' => 'Award1');
//                                $formData[$groupKey][$fieldKey][] = array('Funder_Name' => 'Fund2',
//                                    'Award_Number' => 'Award2');
////////////////////////////////////////////////////////////////////////////////////////////////////////////

                        }
                    } else if ($field['type'] == 'object') {
                        $structure = $field['yoda:structure'];
                        // Subproperties

                        if (isset($structure) && $structure == 'subproperties') {
                            $mainProp = true;
                            foreach ($field['properties'] as $objectKey => $objectField) {
                                if ($mainProp) {
                                    if (isset($xmlFormData[$fieldKey][$objectKey])) {
                                        $formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey][$objectKey];
                                    }
                                    $mainProp = false;
                                } else {
                                    if (isset($xmlFormData[$fieldKey]['Properties'][$objectKey])) {
                                        $formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey]['Properties'][$objectKey];
                                    }
                                }
                            }
                        }

                        foreach ($field['properties'] as $objectKey => $objectField) {
                            if (isset($xmlFormData[$fieldKey][$objectKey])) {
                                $formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey][$objectKey];
                            }
                        }
                    }
                } else {
                    if (isset($xmlFormData[$fieldKey])) {
                        $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                    }
                }
            }
        }

        //print_r(json_encode($jsonSchema)); exit;
//        print_r($xmlFormData);exit;
//
//        print_r($formData);
//        exit;
//        print_r(json_encode($formData));
//        exit;

        return $formData;
    }
}