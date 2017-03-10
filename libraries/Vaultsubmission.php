<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vaultsubmission
{

    public $CI;
    private $account;
    private $formConfig = array();
    private $folder;

    /**
     * Constructor
     */
    public function __construct($params)
    {
        // Get the CI instance
        $this->CI =& get_instance();
        $this->account = $this->CI->rodsuser->getRodsAccount();
        $this->formConfig = $params['formConfig'];
        $this->folder = $params['folder'];
    }

    public function validate()
    {
        $messages = array();
        $xsdFilePath = $this->formConfig['xsdPath'];
        $metadataFilePath = $this->formConfig['metadataXmlPath'];

        $invalidFields = $this->validateXsd($xsdFilePath, $metadataFilePath);
        $mandatoryFields = $this->checkMandatoryFields();

        // Field errors
        $fieldErrors = array_unique(array_merge($invalidFields, $mandatoryFields));
        if (count($fieldErrors)) {
            $messages[] = $this->formatFieldErrors($fieldErrors);
        }

        // Lock error
        $lockResult = $this->checkLock();
        if (!$lockResult) {
            $messages[] = 'An locking error occurred';
        }

        if (count($messages) > 0) {
            return $messages;
        }

        return true;
    }

    public function setSubmitFlag()
    {
        $result = false;
        if ($this->validate() === true) {
            $result = $this->CI->filesystem->submitFolderToVault($this->account, $this->folder);
        }

        return $result;
    }

    private function validateXsd($xsdFilePath, $metadataFilePath)
    {
        $invalidFields = array();
        $xsdContent = $this->CI->filesystem->read($this->account, $xsdFilePath);
        $metadataContent = $this->CI->filesystem->read($this->account, $metadataFilePath);

        libxml_use_internal_errors(true);
        $xml = new DOMDocument();
        $xml->loadXML($metadataContent);
        $isValid = $xml->schemaValidateSource($xsdContent);

        if (!$isValid) {
            $errors = libxml_get_errors();

            foreach ($errors as $error) {
                preg_match("/Element \'(.*)\':/", $error->message, $matches);
                if (isset($matches[1])) {
                    if (!in_array($matches[1], $invalidFields)) {
                        $invalidFields[] = $matches[1];
                    }
                }
            }

            libxml_clear_errors();
        }

        return $invalidFields;
    }

    private function checkMandatoryFields()
    {
        $invalidFields = array();
        $this->CI->load->model('Metadata_form_model');
        $formElements = $this->CI->Metadata_form_model->getFormElements($this->account, $this->formConfig);

        foreach ($formElements as $group => $elements) {
            foreach ($elements as $name => $properties) {
                if ($properties['mandatory']) {
                    if (!$properties['value']) {
                        if (!in_array($properties['key'], $invalidFields)) {
                            $invalidFields[] = $properties['key'];
                        }
                    }
                }
            }
        }

        return $invalidFields;
    }

    private function checkLock()
    {
        $lockStatus = $this->formConfig['lockFound'];
        $folderStatus = $this->formConfig['folderStatus'];

        if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'PROTECTED' || $folderStatus == 'UNPROTECTED')) {
            return true;
        }

        return false;
    }

    private function formatFieldErrors($fields)
    {
        $fieldLabels = array();
        foreach ($fields as $field) {
            $label = $this->findLabelByKey($field);
            if ($label) {
                $fieldLabels[] = $label;
            } else {
                $fieldLabels[] = $field;
            }
        }

        return 'The following fields are invalid for vault submission: ' . implode(', ', $fieldLabels);
    }

    private function findLabelByKey($key)
    {
        $this->CI->load->model('Metadata_form_model');
        $formElements = $this->CI->Metadata_form_model->getFormElements($this->account, $this->formConfig);

        foreach ($formElements as $group => $elements) {
            foreach ($elements as $name => $properties) {
                if ($properties['key'] == $key) {
                    return $properties['label'];
                }
            }
        }

        return false;
    }
}