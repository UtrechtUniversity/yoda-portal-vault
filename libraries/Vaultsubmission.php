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

        // $xsdElements = $this->CI->Metadata_form_model->loadXsd($this->account, $this->formConfig['xsdPath']);

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

        $compoundMode = false; // when stepping through a compound the loop can continue

        $mainLevelProperties = array();

        foreach ($formElements as $group => $elements) {
            foreach ($elements as $name => $properties) {

                $base = $properties['subPropertiesBase'];
                if ($base AND !isset($mainLevelProperties[$base]) AND !in_array($properties['type'], $structs)) {
                    $mainLevelProperties[$base] = $properties;
                }

                if (!$compoundMode) {
                    if ($properties['mandatory'] AND !in_array($properties['type'], $structs)) {
                        // Subproperty element set as mandatory - is only possible if parent level has value
                        if ($properties['subPropertiesRole']=='subProperty') { // is subproperty element
                            // mandatoriness for subproperties only counts if parent level has value
                            $parentValue = $mainLevelProperties[$base]['value'];
                            if ($parentValue) {
                                if (!$properties['value']) {
                                    if (!in_array($properties['key'], $invalidFields)) {
                                        $invalidFields[] = $properties['key'];
                                    }
                                }
                            }
                        }
                        else { // normal element - that must be present
                            if (!$properties['value']) {
                                if (!in_array($properties['key'], $invalidFields)) {
                                    $invalidFields[] = $properties['key'];
                                }
                            }
                        }
                    }

                    // add check for empty lead elements in a lead/subproperty structure.
                    // Even if not mandatory, if subprops exist but no main => cancel submission to vault
                    if ($properties['subPropertiesRole'] == 'subPropertyStartStructure'
                        AND $properties['type'] != 'structSubPropertiesOpen'
                        AND !$properties['value']
                    ) {
                        // only add to when there actually is something in the subproperties
                        // Mandatory present in subproperties??
                        // Subproperties

                        if (isset($formData[$properties['subPropertiesBase']])) { // only add to array if main field is present in actual data in yoda-metadata.xml
                            $invalidFields[] = $properties['key'];
                        }
                    }

                    // trap the opening of a compound which requires specific handling
                    if($properties['type']== 'structCombinationOpen') {

                        $compoundMode = 'true';
                        $compoundMissing = array();
                        $compoundCounter = 0;
                        // Next coming items are part of the same compound.
                        // These must all be present (as defined now in user stories).
                    }
                }
                else { // the actual handling regarding compound elements
                    if ($properties['type'] == 'structCombinationClose') { // compound closing tag found => draw conclusions
                        $compoundMode = false; // end of cycling through
                        // Add completeness conclusions to $invalidFields[]

                        $countedMissing = count($compoundMissing);
                        if ($countedMissing AND $compoundCounter != $countedMissing ) { // all missing is allowed as well
                            foreach($compoundMissing as $keyCompound) {
                                if (!in_array($keyCompound, $invalidFields)) {
                                    $invalidFields[] = $keyCompound;
                                }
                            }
                        }
                    }
                    else { // check for completeness of values
                        $compoundCounter++;
                        if(!$properties['value']) {
                            $compoundMissing[] = $properties['key'];
                        }
                    }
                }
            }
        }
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
                if (!in_array($formElementLabels[$fieldID], $fieldLabels)) { // get rid of duplicate elements that differ as their indexes differ-translation is the same
                    $fieldLabels[] = $formElementLabels[$fieldID];
                }
            } else {
                $fieldLabels[] = $field;
            }
        }
        return 'The following fields are invalid for vault submission: ' . implode(', ', $fieldLabels);
    }
}