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
     * Creates an compound structure
     * returns the entire object, either without or without changes
     *
     * @param $xml
     * @param $xml_metadata
     * @param $mainElement - name of element holding the actual compound elements
     * @param $structObjectProperties
     * @param $formData
     * @return mixed
     */
    private function _addCompoundToXml($xml,
                                       $xmlCompoundParent,
                                       $compoundMainElement,
                                       $structObjectProperties,
                                       $formData)
    {
        $xmlMainElement = $xml->createElement($compoundMainElement);
        $anyValueFound = false;

        foreach ($structObjectProperties as $compoundElementKey => $compoundElementInfo) {
            if (isset($formData[$compoundElementKey]) && strlen($formData[$compoundElementKey])) {
                $anyValueFound = true;
                $xmlCompoundElement = $this->_createXmlElementWithText($xml, $compoundElementKey, $formData[$compoundElementKey]);
                $xmlMainElement->appendChild($xmlCompoundElement);
            }
        }

        if ($anyValueFound) {
            $xmlCompoundParent->appendChild($xmlMainElement);
        }

        return $anyValueFound;
    }


    /**
     * Handles the posted information of a yoda form and puts the values, after escaping, in .yoda-metadata.xml
     * The config holds the correct paths to form definitions and .yoda-metadata.xml
     *
     * NO VALIDATION OF DATA IS PERFORMED IN ANY WAY
     *
     * @param $rodsaccount
     * @param $config
     */
    public function processPost($rodsaccount, $config)
    {
        $arrayPost = $this->CI->input->post();
        $formReceivedData = json_decode($arrayPost['formData'], true);

        // formData now contains info of descriptive groups.
        // These must be excluded first for ease of use within code
        $formData = array();
        foreach($formReceivedData as $group=>$realFormData) {
            // first level to be skipped as is descriptive
            foreach($realFormData as $key => $val  ) {
                $formData[$key] = $val;
            }
        }

        $folder = $config['metadataXmlPath'];
        $jsonsElements = json_decode($this->loadJSONS($rodsaccount, $folder), true);

        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;

        $xml_metadata = $xml->createElement("metadata");

        $attributeXSI = $xml->createAttribute('xmlns:xsi');
        $attributeXSI->value = 'http://www.w3.org/2001/XMLSchema-instance';

        $attributeXML = $xml->createAttribute('xmlns');
        $attributeXML->value = $this->_getSchemaLocation($folder);  // to be determined dynamically via iRODS

        $attributeXSD = $xml->createAttribute('xsi:schemaLocation');
        $attributeXSD->value = $this->_getSchemaLocation($folder) . ' ' . $this->_getSchemaSpace($folder);  // to be determined dynamically via iRODS

        $xml_metadata->appendChild($attributeXSI);
        $xml_metadata->appendChild($attributeXML);
        $xml_metadata->appendChild($attributeXSD);

        foreach ($jsonsElements['properties'] as $groupName => $formElements) {
            foreach ($formElements['properties'] as $mainElement => $element) {

                if (isset($formData[$mainElement])) {
                    if (!isset($element['type'])
                        || $element['type']=='integer'
                        || $element['type']=='string' // string in situations like date on top level (embargo end date)
                    ) {  //No structure single element

                        // Numerical fields are set to 0 when get assigned a non numeric value by the user.
                        // Only done for toplevel elements at this moment
                        $elementData = $formData[$mainElement];
                        //if (isset($element['type']) && $element['type']=='integer' && !is_numeric($elementData)) {
                        //    $elementData = 0; // Set to 0 when numerical value contains non numerical value
                        //}
                        if (strlen($elementData)) {
                            $xmlMainElement = $this->_createXmlElementWithText($xml, $mainElement, $elementData);
                            $xml_metadata->appendChild($xmlMainElement);
                        }
                    }
                    else {
                        $structObject = array();

                        if ($element['type'] == 'object') {   // SINGLGE STRUCT ON HIGHEST LEVEL
                            $structObject = $element;
                            if ($structObject['yoda:structure'] == 'compound') { // heeft altijd een compound signifying element nodig
                                $this->_addCompoundToXml($xml,
                                                        $xml_metadata,
                                                        $mainElement,
                                                        $structObject['properties'],
                                                        $formData[$mainElement]);
                            }
                            elseif ($structObject['yoda:structure'] == 'subproperties') {
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
                                    foreach ($formData[$mainElement] as $subPropertyStructData) {  // loop through data for the lead/subproperty structure
                                        $hasLeadValue = false; // Lead value is required for saving to XML fole
                                        $hasSubPropertyValues = false; // Properties element only added to main element if actually holds data
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
                                            else {
                                                // SUBPROPERTY PART OF STRUCTURE
                                                if($index==1) { // Start of subproperty part. Create subproperty structure element here.
                                                    $xmlProperties = $xml->createElement('Properties');
                                                }

                                                $values = array();
                                                // Single simple field (i.e. no structure)
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    $values[0] = isset($subPropertyStructData[$subPropertyElementKey])? $subPropertyStructData[$subPropertyElementKey] : '';
                                                    foreach($values as $value) {
                                                        if(strlen($value)) {
                                                            $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                            $xmlProperties->appendChild($xmlSubElement);
                                                            $hasSubPropertyValues = true;
                                                        }
                                                    }
                                                }
                                                // Single compound as part of a subproperty
                                                elseif ($subPropertyElementInfo['yoda:structure']=='compound') {
                                                    if ($this->_addCompoundToXml($xml,
                                                                                $xmlProperties,
                                                                                $subPropertyElementKey,
                                                                                $subPropertyElementInfo['properties'],
                                                                                $subPropertyStructData[$subPropertyElementKey])) {
                                                        $hasSubPropertyValues = true;
                                                    }
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
                                                        // Multiple compounds as part of a subproperty structure
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $data) {
                                                            if ($this->_addCompoundToXml($xml,
                                                                                        $xmlProperties,
                                                                                        $subPropertyElementKey,
                                                                                        $subPropertyElementInfo['items']['properties'],
                                                                                        $data)) {
                                                                $hasSubPropertyValues = true;
                                                            }
                                                        }
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
     * Load the yoda-metadata.xml file ($path) in an array structure
     *
     * Reorganise this this in such a way that hierarchy is lost but indexing is possible by eg 'Author_Property_Role'
     *
     * @param $rodsaccount
     * @param $path
     * @return array|bool

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

        $formData = json_decode($json, TRUE);

        return $formData;
    }

    /**
     * Combines XML data with the JSON schema and returns array as a basis for react form.
     *
     * @param $jsonSchema
     * @param $xmlFormData
     * @return array
     */
    public function prepareJSONSFormData($jsonSchema, $xmlFormData)
    {
        $jsonSchema = json_decode($jsonSchema, true);
        $formData = array();

        foreach ($jsonSchema['properties'] as $groupKey => $group) {
            // Group
            foreach ($group['properties'] as $fieldKey => $field) {
                // Field
                if (array_key_exists('type', $field) && isset($xmlFormData[$fieldKey])) {
                    if ($field['type'] == 'string') {
                        // String
                        $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                    } elseif ($field['type'] == 'integer') {
                        // Integer
                        if (is_numeric($xmlFormData[$fieldKey])) {
                            $formData[$groupKey][$fieldKey] = intval($xmlFormData[$fieldKey]);
                        } else {
                            $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                        }
                    } elseif ($field['type'] == 'array') {
                        // Array
                        if (!isset($field['items']['type']) || $field['items']['type'] == 'string') {
                            // String array
                            if (!is_array($xmlFormData[$fieldKey])) {
                                $formData[$groupKey][$fieldKey] = array($xmlFormData[$fieldKey]);
                            } else {
                                $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                            }
                        } elseif ($field['items']['type'] == 'object') {
                            // Object array
                            $emptyObjectField = array();

                            $xmlDataArray = array();
                            foreach ($xmlFormData[$fieldKey] as $key => $value) {
                                if (is_numeric($key)) {
                                    $xmlDataArray = $xmlFormData[$fieldKey];
                                } else {
                                    $xmlDataArray[] = $xmlFormData[$fieldKey];
                                }
                                break;
                            }

                            // Loop through object data
                            foreach($xmlDataArray as $xmlData) {
                                $mainProp = true;
                                $emptyObjectField = array();

                                // Loop through the elements constituing the structure:
                                foreach ($field['items']['properties'] as $objectKey => $objectField) {
                                    // Start of sub property structure
                                    if ($field['items']['yoda:structure'] == 'subproperties') {
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
                                            }
                                            $mainProp = false;
                                        } else {   // sub part of sub property handling.
                                                if (isset($objectField['type']) && $objectField['type'] == 'array') {  // multiple - can be compound or single field
                                                    if (isset($objectField['items']['yoda:structure'])) { // collect each compound and assess whether is valid
                                                        if (isset($xmlData['Properties'][$objectKey])) {
                                                            // prepare the data
                                                            $baseData = array();
                                                            foreach ($xmlData['Properties'][$objectKey] as $key => $val) {
                                                                if (!is_numeric($key)) {
                                                                    $baseData[] = $xmlData['Properties'][$objectKey];
                                                                } else {
                                                                    $baseData = $xmlData['Properties'][$objectKey];
                                                                }
                                                                break;
                                                            }
                                                            foreach ($baseData as $data) {
                                                                $arCompoundFields = array();
                                                                foreach ($objectField['items']['properties'] as $compoundElementKey => $info) {
                                                                    // only take the data when not an array
                                                                    if (isset($data[$compoundElementKey]) && !is_array($data[$compoundElementKey])) {
                                                                        $arCompoundFields[$compoundElementKey] = $data[$compoundElementKey];
                                                                    }
                                                                }
                                                                $emptyObjectField[$objectKey][] = $arCompoundFields;
                                                            }
                                                        } else {
                                                            // Handle empty structure?
                                                        }
                                                    } else {
                                                        if (!isset($xmlData['Properties'][$objectKey])) {
                                                            $emptyObjectField[$objectKey][0] = '';
                                                        } else {
                                                            $affValuesArray = $xmlData['Properties'][$objectKey];
                                                            if (!is_array($affValuesArray)) {
                                                                $affValuesArray = array($xmlData['Properties'][$objectKey]);
                                                            }
                                                            $emptyObjectField[$objectKey] = $affValuesArray; //$xmlData['Properties'][$objectKey];
                                                        }
                                                    }
                                                } elseif (isset($objectField['type']) && $objectField['type'] == 'object') {  // compound single structure
                                                    $arCompoundFields = array();

                                                    $data = $xmlData['Properties'][$objectKey];
                                                    foreach ($objectField['properties'] as $compoundElementKey => $info) {
                                                        // only take the data when not an array
                                                        if (isset($data[$compoundElementKey]) && !is_array($data[$compoundElementKey])) {
                                                            $arCompoundFields[$compoundElementKey] = $data[$compoundElementKey];
                                                        }
                                                    }
                                                    $emptyObjectField[$objectKey] = $arCompoundFields;
                                                } else { // can only be single field as this is a subproperty
                                                    $subValue = '';
                                                    if (isset($xmlData['Properties'][$objectKey]) && !is_array($xmlData['Properties'][$objectKey])) {
                                                        $subValue = $xmlData['Properties'][$objectKey];
                                                    }
                                                    $emptyObjectField[$objectKey] = $subValue;
                                                }
                                        }
                                    } else {
                                        if ($objectField['type'] == 'string') {
                                            $emptyObjectField[$objectKey] = $objectKey;
                                        } elseif ($objectField['type'] == 'object') { //subproperties (OLD)
                                            foreach ($objectField['properties'] as $subObjectKey => $subObjectField) {
                                                if ($subObjectField['type'] == 'string') {
                                                    $emptyObjectField[$objectKey][$subObjectKey] = $objectKey;
                                                } elseif ($subObjectField['type'] == 'object') {// Composite
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
                        }
                    } elseif ($field['type'] == 'object') {
                        // Object
                        $structure = $field['yoda:structure'];
                        if (isset($structure) && $structure == 'subproperties') {
                            // Object subproperties
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
                } elseif (isset($xmlFormData[$fieldKey])) {
                    // Unkown type
                    $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                }
            }
        }

        return $formData;
    }

    /**
     * Request for location of current XSD based on location of file
     * @param $folder
     * @return schemaLocation
     */
    private function _getSchemaLocation($folder)
    {
        $outputParams = array('*schemaLocation', '*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFrontGetSchemaLocation', $inputParams, $outputParams);
        $result = $rule->execute();

        return $result['*schemaLocation'];
    }

    /**
     * Request for space of current XSD based on location of file
     * @param $folder
     * @return schemaSpace
     */
    private function _getSchemaSpace($folder)
    {
        $outputParams = array('*schemaSpace', '*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFrontGetSchemaSpace', $inputParams, $outputParams);
        $result = $rule->execute();

        return $result['*schemaSpace'];
    }
}
