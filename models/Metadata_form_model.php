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

    /**
     * Save JSON metdata posted from the metadata form.
     *
     * @param $rodsaccount Rods account
     * @param $path        Path of collection being worked in
     */
    public function saveJsonMetadata($rodsaccount, $path)
    {
        $arrayPost = $this->CI->input->post();
        $data = $arrayPost['formData'];

        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            'rule { iiSaveFormMetadata(*path, *data); }',
            array('*path' => $path, '*data' => $data),
            array('ruleExecOut')
        );

        $result = json_decode($rule->execute()['ruleExecOut'], true);
        return $result;
    }

    /**
     * Get JSON schema for collection.
     *
     * @param $rodsaccount
     * @param $path        Path of collection being worked in
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
     * @param $path        Path of collection being worked in
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
     * Get the yoda-metadata.json file contents for a collection.
     *
     * @param $rodsaccount
     * @param $path        Path of collection being worked in
     *
     * @return mixed

     */
    public function getJsonMetadata($rodsaccount, $path)
    {
        $formData = $this->CI->filesystem->read($rodsaccount, $path);

        return json_decode($formData);
    }

    /**
     * Load the yoda-metadata.xml file ($path) in an array structure.
     *
     * Reorganise this in such a way that hierarchy is lost but indexing is possible by eg 'Author_Property_Role'.
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
                                                    if (isset($xmlData['Properties'][$objectKey])) {
                                                        $arCompoundFields = array();
                                                        $data = $xmlData['Properties'][$objectKey];
                                                        foreach ($objectField['properties'] as $compoundElementKey => $info) {
                                                            // only take the data when not an array
                                                            if (isset($data[$compoundElementKey]) && !is_array($data[$compoundElementKey])) {
                                                                $arCompoundFields[$compoundElementKey] = $data[$compoundElementKey];
                                                            }
                                                        }
                                                        $emptyObjectField[$objectKey] = $arCompoundFields;
                                                    }
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
}
