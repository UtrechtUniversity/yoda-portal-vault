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

        $mandatoryFields = $this->checkMandatoryFields();

        // Validate update Vault Package
        if ($isVaultPackage == 'yes') {
            $fieldErrors = array_unique(array_merge($invalidFields, $mandatoryFields));
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
                $messages[] = 'A locking error occurred';
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

    private function checkMandatoryFields()
    {
        $invalidFields = array();
        $this->CI->load->model('Metadata_form_model');


        $formElements = $this->CI->Metadata_form_model->getFormElements($this->account, $this->formConfig);


        //Iinvalid yodametadat.xml file caused getFormElements to return false instead of full representation of all elements.
        // There can therefore be no conclusion on all mandatory data being present or not.
        // The cause for this is always invalid structured xml OR entry of multiple data where this is not allowed.
        // This is trapped when an xsd check is executed
        // That is why this check always has to be combined with an XSD check as that will tell where the problem lies
        if ($formElements===false) {
            return array();
        }

        // Get the actual yoda-metadata.xml to be able to do inventory
        $formData = $this->CI->Metadata_form_model->loadFormData($this->account, $this->formConfig['metadataXmlPath']);

        if ($formData === false) {
            return array();
        }


        // @todo: this now steps trhough all visible and indiciation elements.
        // Maybe refactored in looping through the mainfields only
        // And have extra software dig deeper per element depending on the type of element.
        // This loop is caused by the fact that initially there were only mainfields. Now there are subproperties and compound structures

        // For now do a discovery in this loop regarding mandatory or present subproperties
        // formElements has ALL elements, including subs, flattened in an array
        // Therefore, all this needs to be properly investigated as empty lead elements of structs do not have to be reported when NO subprop data is present
        // Only in the next steps this is possible to be determined

        $structs = array('structCombinationOpen', 'structCombinationClose', 'structSubPropertiesOpen','structSubPropertiesClose');

        foreach ($formElements as $group => $elements) {
            foreach ($elements as $name => $properties) {
                if ($properties['mandatory'] AND !in_array($properties['type'], $structs)) {
                    if (!$properties['value']) {
                        if (!in_array($properties['key'], $invalidFields)) {
                            $invalidFields[] = $properties['key'];
                        }
                    }
                }

                // Hij loopt door alle
                // add check for empty lead elements in a lead/subproperty structure.
                if ($properties['subPropertiesRole']=='subPropertyStartStructure'
                    AND $properties['type']!='structSubPropertiesOpen'
                    AND !$properties['value']) {

                    // only add to when there actually is something in the subproperties
                    // Mandatory present in subproperties??
                    // Subproperties

                    if(isset($formData[$properties['subPropertiesBase']])) { // only add to array if main field is present in actual data in yoda-metadata.xml

                        $invalidFields[] = $properties['key'];
                    }
                }
            }
        }

//        echo '<pre>';
//        print_r($invalidFields);
//        echo '</pre>';
//        exit;

        return $invalidFields;
    }

    public function checkLock()
    {
        $lockStatus = $this->formConfig['lockFound'];
        $folderStatus = $this->formConfig['folderStatus'];

        if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'LOCKED' || $folderStatus == '')) {
            return true;
        }

        return false;
    }

    public function checkFolderStatus()
    {
        $folderStatus = $this->formConfig['folderStatus'];
        if ($folderStatus == 'LOCKED' || $folderStatus == '') {
            return true;
        }

        return false;
    }

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
                $fieldLabels[] = $formElementLabels[$fieldID];
            } else {
                $fieldLabels[] = $field;
            }
        }
        return 'The following fields are invalid for vault submission: ' . implode(', ', $fieldLabels);
    }
}