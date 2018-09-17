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

        # $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
        $xsdPath = $config['xsdPath'];
        $jsonsElements = $this->loadJSONS($rodsaccount, $xsdPath);


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
//                                    $xmlCompoundElement = $xml->createElement($compoundElementKey);
//                                    $xmlCompoundElement->appendChild($xml->createTextNode($compoundValue));
                                    $xmlCompoundElement = $this->_createXmlElementWithText($xml, $compoundElementKey, $compoundValue);
                                    $xmlMainElement->appendChild($xmlCompoundElement);
                                }
                                if ($anyValueFound) {
                                    $xml_metadata->appendChild($xmlMainElement);
                                }
                            }
                            elseif($structObject['yoda:structure'] == 'subproperties'){  // SINGLE subproperty struct is not present at the moment in the schema

                            }
                        }
                        // MULTIPLE
                        elseif ($element['type'] == 'array') {
                            if (!(isset($element['items']['type']) and $element['items']['type'] == 'object')) {
                                // multiple non structured element
                                // So loop through data now
                                foreach($formData[$mainElement] as $value) {
                                    if ($value) {
//                                        $xmlMainElement = $xml->createElement($mainElement);
//                                        $xmlMainElement->appendChild($xml->createTextNode($value));
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
                                                //$xmlLeadElement = $xml->createElement($subPropertyElementKey);
                                                //$xmlLeadElement->appendChild($xml->createTextNode($leadData));  // @TODO - get correct lead value

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
                                                   // $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                    // $xmlSubElement->appendChild($xml->createTextNode($value));  // @TODO - get correct lead value
                                                    $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                    $xmlProperties->appendChild($xmlSubElement);
                                                }
                                            }
                                            else {  // next lines after first in subproperty part
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    echo 'SINGLE ITEM';  ///KOMT  NU NIET VOOR VOOR SUBPROPERTIES
                                                }
                                                elseif ($subPropertyElementInfo['type']=='array') {
                                                    if (!isset($subPropertyElementInfo['items']['type'])) {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $value) {
                                                            //$xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                            //$xmlSubElement->appendChild($xml->createTextNode($value));

                                                            $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);

                                                            $xmlProperties->appendChild($xmlSubElement);
                                                        }
                                                    }
                                                    else {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $data) {
                                                            $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                            foreach ($subPropertyElementInfo['items']['properties'] as $subCompoundKey => $subVal) {
                                                                $subData = isset($data[$subCompoundKey]) ? $data[$subCompoundKey] : '';

//                                                                $xmlSubCompound = $xml->createElement($subCompoundKey);
//                                                                $xmlSubCompound->appendChild($xml->createTextNode($subData));

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

//                                                        $xmlSubCompound = $xml->createElement($subCompoundKey);
//                                                        $xmlSubCompound->appendChild($xml->createTextNode($subData));

                                                        $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);

                                                        $xmlSubElement->appendChild($xmlSubCompound);
                                                    }

                                                    $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap
                                                }
                                            }

                                            $index++;
                                        }

                                        // Extra intelligence to only save when there is relevant data
                                        if ($hasLeadValue) {
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

    public function loadJSONS($rodsaccount, $xsdPath)
    {
        $result = $this->CI->filesystem->getJsonSchema($rodsaccount, $xsdPath);

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
        libxml_clear_errors();

        if (count($errors)) {
            return false;
        }

        $json = json_encode($xmlData);

        $formData = json_decode($json, TRUE);

        return $formData;
    }
}