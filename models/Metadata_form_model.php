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
                        || $element['type']=='integer') {  //No structure single element
                        $xmlMainElement = $this->_createXmlElementWithText($xml, $mainElement, $formData[$mainElement]);
                        $xml_metadata->appendChild($xmlMainElement);
                    }
                    else {
                        $structObject = array();

                        if ($element['type'] == 'object') {   // SINGLGE STRUCT OP FIRST LEVEL
                            $structObject = $element;

                            if ($structObject['yoda:structure'] == 'compound') { // heeft altijd een compound signifying element nodig
                                $xmlMainElement = $xml->createElement($mainElement);
                                $anyValueFound = false;
                                foreach ($structObject['properties'] as $compoundElementKey => $compoundElementInfo) {
                                    $compoundValue = '';
                                    if (isset($formData[$mainElement][$compoundElementKey])) {
                                        $compoundValue = $formData[$mainElement][$compoundElementKey];
                                        $anyValueFound = true;
                                    }
                                    $xmlCompoundElement = $this->_createXmlElementWithText($xml, $compoundElementKey, $compoundValue);
                                    $xmlMainElement->appendChild($xmlCompoundElement);
                                }
                                if ($anyValueFound) {
                                    $xml_metadata->appendChild($xmlMainElement);
                                }
                            }
                            elseif($structObject['yoda:structure'] == 'subproperties'){  // Single subproperty struct is not present at the moment in the schema

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
                                    $hasLeadValue = false;
                                    foreach ($formData[$mainElement] as $subPropertyStructData) {
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
                                                $values = array();
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    $values[0] = isset($subPropertyStructData[$subPropertyElementKey])? $subPropertyStructData[$subPropertyElementKey] : '';
                                                }
                                                else if ($subPropertyElementInfo['type']=='array') {
                                                    $values = $subPropertyStructData[$subPropertyElementKey];
                                                }

                                                foreach($values as $value) {
                                                    $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                    $xmlProperties->appendChild($xmlSubElement);
                                                }
                                            }
                                            else {  // next lines after first in subproperty part
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    //echo 'SINGLE ITEM';  ///KOMT  NU NIET VOOR VOOR SUBPROPERTIES
                                                }
                                                elseif ($subPropertyElementInfo['type']=='array') {
                                                    if (!isset($subPropertyElementInfo['items']['type'])) {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $value) {
                                                            $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                            $xmlProperties->appendChild($xmlSubElement);
                                                        }
                                                    }
                                                    else {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $data) {
                                                            $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                            foreach ($subPropertyElementInfo['items']['properties'] as $subCompoundKey => $subVal) {
                                                                $subData = isset($data[$subCompoundKey]) ? $data[$subCompoundKey] : '';
                                                                $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);
                                                                $xmlSubElement->appendChild($xmlSubCompound);
                                                            }
                                                            $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap
                                                        }
                                                    }
                                                }
                                                elseif ($subPropertyElementInfo['yoda:structure']=='compound'){
                                                    $xmlSubElement = $xml->createElement($subPropertyElementKey);

                                                    foreach($subPropertyElementInfo['properties'] as $subCompoundKey => $subVal) {
                                                        $subData = isset($subPropertyStructData[$subPropertyElementKey][$subCompoundKey])?
                                                                            $subPropertyStructData[$subPropertyElementKey][$subCompoundKey] : '';
                                                        $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);
                                                        $xmlSubElement->appendChild($xmlSubCompound);
                                                    }

                                                    $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap
                                                }
                                            }

                                            $index++;
                                        }

                                        // Extra intelligence to only save when there is relevant data
                                        if ($hasLeadValue OR true) {  // for now overrule this requirement
                                            // add the entire structure to the main element
                                            $xmlMainElement->appendChild($xmlProperties);
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
        //$result = $jsonSchema;

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

                            // $fieldKey=='Funding_Reference' OR $fieldKey=='Creator'  OR $fieldKey=='Contributor'
                            if (true) {
                                // check whether is enumeration or single structure in the data.

                                $xmlDataArray = array();

                                foreach($xmlFormData[$fieldKey] as $keyTest=>$valueTest) {
                                    if(is_numeric($keyTest)) {
                                        $xmlDataArray = $xmlFormData[$fieldKey];
                                    }
                                    else {
                                        $xmlDataArray[] = $xmlFormData[$fieldKey];
                                    }

                                    break;
                                }

                                // Loop through data
                                foreach($xmlDataArray as $xmlData) {
                                   // print_r($xmlData);

                                    $mainProp = true;
                                    $emptyObjectField = array();

                                    // De volgende loop moet mogeljik N keer worden doorlopen omdat er meervoudige data is.
                                    foreach ($field['items']['properties'] as $objectKey => $objectField) {
                                        if ($field['items']['yoda:structure'] == 'subproperties') {
                                            if ($mainProp) {
                                                if (isset($xmlData[$objectKey])) {
                                                    //$formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey][$objectKey];
                                                    $emptyObjectField[$objectKey] = $xmlData[$objectKey];
                                                } else { // Add empty string so lead property exists => will visibly open the structure
                                                    $emptyObjectField[$objectKey] = '';
                                                }
                                                $mainProp = false;
                                            } else {
                                                if (isset($xmlData['Properties'][$objectKey])) {
                                                    //$formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey]['Properties'][$objectKey];
                                                    if ($objectField['type'] == 'array') {
                                                       // $emptyObjectField[$objectKey] =  array($xmlData['Properties'][$objectKey]);
                                                        $propData = $xmlData['Properties'][$objectKey];

                                                        // Prepwork
                                                        $propDataArray = array();
                                                        if (is_array($propData)) {

                                                            foreach ($propData as $propDataItemTest => $propDataItemTestValue) {
                                                                if (!is_numeric($propDataItemTest)) {
                                                                    $propDataArray[] = $propData;
                                                                } else {
                                                                    $propDataArray = $propData;
                                                                }
                                                                break;
                                                            }
                                                        }
                                                        else {
                                                            $propDataArray[] = $propData;
                                                        }

                                                        foreach($propDataArray as $propDataItem) {
                                                            $emptyObjectField[$objectKey][] = $propDataItem;
                                                        }
                                                    } else {
                                                        $emptyObjectField[$objectKey] = $xmlData['Properties'][$objectKey];
                                                    }
                                                } else {
                                                    if ($objectField['type'] == 'array') { // multiple
                                                        if (isset($objectField['items']['yoda:structure'])) { // can only be compound at this moment
                                                            // At this moment we know that fields in a compound are not individually duplicable

                                                            $compoundElements = array();
                                                            foreach ($objectField['items']['properties'] as $compoundElementKey => $info) {
                                                                $compoundElements[$compoundElementKey] = '';
                                                            }

                                                            $emptyObjectField[$objectKey] = array(0 => $compoundElements);
                                                        } else { // single field - multiple values allowed
                                                            $emptyObjectField[$objectKey] = array(0 => '');
                                                        }
                                                    } else {
                                                        // What to do here??? single thing - maybe simply leave out?
                                                    }
                                                }
                                            }
                                        } else {
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

//        print_r($jsonSchema); exit;
//        print_r($xmlFormData);exit;
//
//        print_r($formData);
//        exit;

        return $formData;
    }
}