<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Metadata form model
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
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
     * Get JSON schema for collection.
     *
     * @param $rodsaccount
     * @param $path        Collection of area being worked in
     *
     * @return string      JSON schema
     */
    public function getJsonSchema($rodsaccount, $path)
    {
        $result = $this->CI->filesystem->getJsonSchema($rodsaccount, $path);

        if ($result['*status'] == 'Success') {
            return $result['*result'];
        } else {
            return '';
        }
    }

    /**
     * Get JSON UI schema for collection.
     *
     * @param $rodsaccount
     * @param $path        Collection of area being worked in
     *
     * @return string      JSON UI schema
     */
    public function getJsonUiSchema($rodsaccount, $path)
    {
        $result = $this->CI->filesystem->getJsonUiSchema($rodsaccount, $path);

        if ($result['*status'] == 'Success') {
            return $result['*result'];
        } else {
            return '';
        }
    }

    /**
     * Get the yoda-metadata.json file contents.
     *
     * @param $rodsaccount
     * @param $path
     * @return string
     */
    public function getJsonMetadata($rodsaccount, $path)
    {
        $formData = $this->CI->filesystem->read($rodsaccount, $path);

        return json_decode($formData);
    }

    /**
     * Request for location of current XSD based on location of file.
     *
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
     * Request space (research or vault) of current XSD based on location of file.
     *
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
