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

    /* When metadata form is loaded first check validity against xsd. No other checks are required */
    public function validateMetaAgainstXsdOnly()
    {
        $xsdFilePath = $this->formConfig['xsdPath'];
        $metadataFilePath = $this->formConfig['metadataXmlPath'];

        return $this->validateXsd($xsdFilePath, $metadataFilePath);
    }

    public function validate()
    {
        $messages = array();
        $xsdFilePath = $this->formConfig['xsdPath'];
        $metadataFilePath = $this->formConfig['metadataXmlPath'];
        $isVaultPackage = $this->formConfig['isVaultPackage'];

        $invalidFields = $this->validateXsd($xsdFilePath, $metadataFilePath);

        // Validate update Vault Package
        if ($isVaultPackage == 'yes') {
            $fieldErrors = array_unique($invalidFields);
            if (count($fieldErrors)) {
                $messages[] = $this->formatFieldErrors($fieldErrors);
            }

            if (count($messages) > 0) {
                return $messages;
            }

            return true;
        }

        // Check folder status
        $folderStatusResult = $this->checkFolderStatus();

        if (!$folderStatusResult) {
            $messages[] = 'Illegal status transition. Current status is '. $this->formConfig['folderStatus'] .'.';
        } else {
            // Folder status OK:
            // Field errors
            $fieldErrors = array_unique(array_merge($invalidFields, $mandatoryFields));
            if (count($fieldErrors)) {
                $messages[] = $this->formatFieldErrors($fieldErrors);
            }

            // Lock error
            $lockResult = $this->checkLock();
            if (!$lockResult) {
                $messages[] = 'Action could not be executed because of a lock. Check locks (lock symbol)  for more information';
            }
        }

        if (count($messages) > 0) {
            return $messages;
        }

        return true;
    }

    public function setSubmitFlag()
    {
        $result = false;
        if ($this->validate() === true) { // Hdr: dit gebeurt in vault-controller ook al
            $result = $this->CI->Folder_Status_model->submit($this->folder);
        }

        return $result;
    }

    public function clearSubmitFlag()
    {

        return $this->CI->Folder_Status_model->unsubmit($this->folder);
    }

    private function validateXsd($xsdFilePath, $metadataFilePath)
    {
        $invalidFields = array();
        $xsdContent = $this->CI->filesystem->read($this->account, $xsdFilePath);
        $metadataContent = $this->CI->filesystem->read($this->account, $metadataFilePath);

        libxml_use_internal_errors(true);
        $xml = new DOMDocument();
        @$xml->loadXML($metadataContent);
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

    public function checkLock()
    {
        $lockStatus = $this->formConfig['lockFound'];
        $folderStatus = $this->formConfig['folderStatus'];

        if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'LOCKED' || $folderStatus == '' || $folderStatus == 'REJECTED' || $folderStatus == "SECURED")) {
            return true;
        }

        return false;
    }

    public function checkFolderStatus()
    {
        $folderStatus = $this->formConfig['folderStatus'];
        if ($folderStatus == 'LOCKED' || $folderStatus == '' || $folderStatus == 'REJECTED' || $folderStatus == "SECURED") {
            return true;
        }

        return false;
    }


// There's two moments validation of yoda-metadata.xml will take place:
// 1) When opening the metadata form
// 2) WHen submitting a folder to the vault

// Na xsd validatie is deze lijst beperkt bruikbaar daar er geen hierarchische info is komend vanaf xsd-validation
// Usable for translation on irods-metadata-keys of attributes
// If not recognised the found key is left unchanged
// Using this for translating errors coming from schemaValidateSource() only will work for fields that

    private function formatFieldErrors($fields)
    {
        $this->CI->load->model('Metadata_form_model');
        $formElementLabels = $this->CI->Metadata_form_model->getFormElementLabels($this->account, $this->formConfig);

        $fieldLabels = array();
        foreach ($fields as $field) {
            // Convert fields as Creator[Name] or Contributor[Name] to Creator_Name or Contributor_Name
            // quick fix be able to handle new key names that incorporate array handling even further like:
            //  Related_Datapackage[0][Title]
            //  Related_Datapackage[0][Properties][PI][]

            $temp = preg_replace('/[0-9]+/', '', $field);
            $temp = str_replace('[]', '', $temp);
            $fieldID = str_replace(array(']', '['), array('', '_'), $temp);

            if (isset($formElementLabels[$fieldID])) {
                if (!in_array($formElementLabels[$fieldID], $fieldLabels)) { // get rid of duplicate elements that differ as their indexes differ-translation is the same
                    $fieldLabels[] = $formElementLabels[$fieldID];
                }
            } else {
                $fieldLabels[] = $field;
            }
        }
        return 'The following fields are invalid: ' . implode(', ', $fieldLabels);
    }
}
