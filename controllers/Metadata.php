<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;

class Metadata extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        $this->load->model('rodsuser');
        $this->config->load('config');

        $this->load->library('pathlibrary');
    }

    public function form()
    {
        $this->load->model('Metadata_model');
        $this->load->model('Metadata_form_model');
        $this->load->model('Filesystem');

        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $isDatamanager = $formConfig['isDatamanager'];
        $isVaultPackage = $formConfig['isVaultPackage'];

        $userType = $formConfig['userType'];

        $mode = $this->input->get('mode'); // ?mode=edit_for_vault
        if ($isDatamanager == 'yes' && $isVaultPackage == 'yes' && $mode == 'edit_in_vault') {
            // .tmp file for XSD validation
            $result = $this->Metadata_model->prepareVaultMetadataForEditing($formConfig['metadataXmlPath']);

            $tmpSavePath = $result['*tempMetadataXmlPath'] . '.tmp';
            $tmpFileExists = $this->Filesystem->read($rodsaccount, $tmpSavePath);
            if ($tmpFileExists !== false) {
                $formConfig['metadataXmlPath'] = $tmpSavePath;
            }
        }

        if ($userType == 'normal' || $userType == 'manager') {
            $writePermission = true;
        } else {
            $writePermission = false;
        }

        $flashMessage = $this->session->flashdata('flashMessage');
        $flashMessageType = $this->session->flashdata('flashMessageType');

        // Load CSRF token
        $tokenName = $this->security->get_csrf_token_name();
        $tokenHash = $this->security->get_csrf_hash();

        $viewParams = array(
            'styleIncludes' => array(
                'lib/font-awesome/css/font-awesome.css',
                'lib/sweetalert/sweetalert.css',
                'css/metadata/form.css',
            ),
            'scriptIncludes' => array(
                'lib/sweetalert/sweetalert.min.js',
                'js/metadata/delete.js',
            ),
            'path'             => $path,
            'tokenName'        => $tokenName,
            'tokenHash'        => $tokenHash,
            'userType'         => $userType,
            'mode'             => $mode,
            'isVaultPackage'   => $isVaultPackage,
            'flashMessage'     => $flashMessage,
            'flashMessageType' => $flashMessageType,
            'metadataExists'   => ($formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') ? true: false,
            'writePermission'  => $writePermission,
        );
        loadView('metadata/form', $viewParams);
    }

    public function data()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('Metadata_form_model');
        $this->load->model('Metadata_model');
        $this->load->model('Folder_Status_model');
        $this->load->model('Filesystem');

        $path = $this->input->get('path');
        $fullPath = $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $xmlFormData = $this->Metadata_form_model->loadFormData($rodsaccount, $formConfig['metadataXmlPath']);

        $jsonSchema = $this->Metadata_form_model->loadJSONS($rodsaccount, $fullPath);

        $uiSchema = array(
            "Descriptive-group" => array(
               "Description" => array(
                   "ui:widget" => "textarea"
               )
            ),
            "Administrative-group" => array(
                "Retention_Period" => array(
                    "ui:widget" => "numberWidget"
                )
            )
        );

        $errors = array();
        $formData = $this->Metadata_form_model->prepareJSONSFormData($jsonSchema, $xmlFormData);
        if (empty($formData)) {
             $formData = json_decode ("{}");
        } else {
            // decode to objects
            $jsonSchemaObject = json_decode($jsonSchema);
            $formDataEncode = json_encode($formData);
            $formDataObject = json_decode($formDataEncode);

            // validate form data.
            $schemaStorage = new SchemaStorage();
            $schemaStorage->addSchema('file://mySchema', $jsonSchemaObject);
            $jsonValidator = new Validator( new Factory($schemaStorage));
            $jsonValidator->validate($formDataObject, $jsonSchemaObject);

            if (!$jsonValidator->isValid()) {
                foreach ($jsonValidator->getErrors() as $error) {
                    // Continue if required
                    if ($error['constraint'] == 'required') {
                        continue;
                    }
                    $errors[] = $error['property'];
                }
            }
        }

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $isDatamanager     = ($formConfig['isDatamanager'] == 'yes') ? true: false;
        $isVaultPackage    = ($formConfig['isVaultPackage'] == 'yes') ? true: false;
        $userType          = $formConfig['userType'];

        if ($userType == 'normal' || $userType == 'manager') {
            $writePermission = true;
        } else {
            $writePermission = false;
        }

        // Should submit button be rendered?
        $submitButton = false;
        $isLocked = false;
        if (!$isVaultPackage) {
            $lockStatus = $formConfig['lockFound'];
            $folderStatus = $formConfig['folderStatus'];
            $isLocked = ($formConfig['lockFound'] == "here" || $formConfig['lockFound'] == "ancestor") ? true: false;
            if (($lockStatus == 'here' || $lockStatus == 'no')
                && ($folderStatus == 'SECURED' || $folderStatus == 'LOCKED' || $folderStatus == '')
                && ($userType == 'normal' || $userType == 'manager')) {
                $submitButton = true;
            }
        }

        // Should unsubmit button be rendered?
        $unsubmitButton = false;
        if (($userType == 'normal' || $userType == 'manager')
            && $folderStatus == 'SUBMITTED') {
            $unsubmitButton = true;
        }

        // Should update button be rendered?
        $mode = $this->input->get('mode');
        $updateButton = false;
        if ($isDatamanager && $isVaultPackage) {
           if ($formConfig['hasShadowMetadataXml'] == 'no') {
                if ($mode != 'edit_in_vault') {
                    $updateButton = true;
                }
            }
        }

        if ($isLocked
            || (!$isVaultPackage && $isDatamanager && !$writePermission)
            || ($isVaultPackage && !$isDatamanager)
            || ($isVaultPackage && $isDatamanager && $updateButton)
            || (!$writePermission && !$isDatamanager)) {
            $uiSchema["ui:readonly"] = "true";
        }

        $parentHasMetadata = false;
        if (!$isVaultPackage) {
            $parentHasMetadata = ($formConfig['parentHasMetadataXml'] == 'true') ? true: false;
        }

        $output = array();
        $output['path']              = $path;
        $output['schema']            = json_decode($jsonSchema);
        $output['uiSchema']          = $uiSchema;
        $output['formData']          = $formData;
        $output['formDataErrors']    = $errors;
        $output['isDatamanager']     = $isDatamanager;
        $output['isVaultPackage']    = $isVaultPackage;
        $output['parentHasMetadata'] = $parentHasMetadata;
        $output['metadataExists']    = ($formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') ? true: false;
        $output['locked']            = $isLocked;
        $output['writePermission']   = $writePermission;
        $output['submitButton']      = $submitButton;
        $output['unsubmitButton']    = $unsubmitButton;
        $output['updateButton']      = $updateButton;

        $this->output->set_content_type('application/json')->set_output(json_encode($output));
    }

    /**
     * Serves storing of:
     *
     * 1) SUBMIT FOR VAULT
     * 2) UNSUBMIT FOR VAULT
     * 3) save changes to metadata
     *
     * Permitted only for userType in {normal, manager}
     *
     */
    function store()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $arrayPost = $this->input->post();

        $this->load->model('Metadata_form_model');
        $this->load->model('Metadata_model');
        $this->load->model('Folder_Status_model');
        $this->load->model('Filesystem');

        $path = $this->input->get('path');
        $fullPath = $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $userType = $formConfig['userType'];
        $lockStatus = $formConfig['lockFound'];
        $folderStatus = $formConfig['folderStatus'];
        $isDatamanager = $formConfig['isDatamanager'];
        $isVaultPackage = $formConfig['isVaultPackage'];

        // Datamanager save metadata in vault package
        if ($isDatamanager == 'yes' && $isVaultPackage == 'yes') {
            $result = $this->Metadata_model->prepareVaultMetadataForEditing($formConfig['metadataXmlPath']);
            $tempPath = $result['*tempMetadataXmlPath'];
            $tmpSavePath = $tempPath . '.tmp';
            $formConfig['metadataXmlPath'] = $tmpSavePath;
            $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));

            $result = $this->vaultsubmission->validate();
            if ($result === true) {
                $tmpFileContent = $this->Filesystem->read($rodsaccount, $tmpSavePath);
                $writeResult = $this->Filesystem->write($rodsaccount, $tempPath, $tmpFileContent);
                if ($writeResult) {
                    setMessage('success', 'Metadata is updated.');
                    $this->Filesystem->delete($rodsaccount, $tmpSavePath);
                } else {
                    setMessage('error', 'Unexpected metadata write error.');
                }
            } else {
                // result contains all collected messages as an array
                setMessage('error', implode('<br>', $result));
            }

            return redirect('research/metadata/form?path=' . rawurlencode($path) . '&mode=edit_in_vault', 'refresh');
        }

        if (!($userType=='normal' || $userType=='manager')) { // superseeds userType!= reader - which comes too late for permissions for vault submission
            $this->session->set_flashdata('flashMessage', 'Insufficient rights to perform this action.'); // wat is een locking error?
            $this->session->set_flashdata('flashMessageType', 'danger');
            return redirect('research/browse?dir=' . rawurlencode($path), 'refresh');
        }

        $status = '';
        $statusInfo = '';

        if ($this->input->post('vault_submission') || $this->input->post('vault_unsubmission')) {
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));
            if ($this->input->post('vault_submission')) { // HdR er wordt nog niet gecheckt dat juiste persoon dit mag

                if(!$this->vaultsubmission->checkLock()) {
                    setMessage('error', 'There was a locking error encountered while submitting this folder.');
                }
                else {
                    // first perform a save action of the latest posted data - only if there is no lock!
                    if ($formConfig['folderStatus']!='LOCKED') {
                        $result = $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
                    }
                    // Do vault submission
                    $result = $this->vaultsubmission->validate();
                    if ($result === true) {
                        $submitResult = $this->vaultsubmission->setSubmitFlag();
                        if (!$submitResult) {
                            setMessage('error', $result['*statusInfo']);
                        }
                    } else {
                        // result contains all collected messages as an array
                        setMessage('error', implode('<br>', $result));

                    }
                }
            }
            elseif ($this->input->post('vault_unsubmission')) {
                $result = $this->vaultsubmission->clearSubmitFlag();
                if ($result['*status'] != 'Success') {
                    setMessage('error', $result['*statusInfo']);
                }

            }
        }
        else {
            // save metadata xml.  Check for correct conditions
            if ($folderStatus == 'SUBMITTED') {
                setMessage('error', 'The form has already been submitted');
                return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
            }
            if ($folderStatus == 'LOCKED' || $lockStatus == 'ancestor') {
                setMessage('error', 'The metadata form is locked possibly by the action of another user.');
                return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
            }

            if ($this->input->server('REQUEST_METHOD') == 'POST') {
                $result = $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
            }
        }

        return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
    }

    function delete()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('filesystem');
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $userType = $formConfig['userType'];

        if($userType != 'reader') {
            $result = $this->filesystem->removeAllMetadata($rodsaccount, $fullPath);
            if ($result) {
                return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
            } else {
                return redirect('research/browse?dir=' . rawurlencode($path), 'refresh');
            }
        }
        else {
            //get away from the form, user is (no longer) entitled to view it
            return redirect('research/browse?dir=' . rawurlencode($path), 'refresh');
        }
    }

    function clone_metadata()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('filesystem');
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        if ($formConfig['parentHasMetadataXml'] == 'true') {
            $xmlPath = $formConfig['metadataXmlPath'];
            $xmlParentPath = $formConfig['parentMetadataXmlPath'];

            $result = $this->filesystem->cloneMetadata($rodsaccount, $xmlPath, $xmlParentPath);
        }

        return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
    }
}
