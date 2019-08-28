<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Metadata controller
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
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
        $showForm = true;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $isDatamanager = $formConfig['isDatamanager'];

        $userType = $formConfig['userType'];

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

        // Transformations
        // TODO: Vault & Datamanager checks!!
        $metadataExists = $formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataJson'] == 'true';
        $transformation = false;
        $transformationText = null;
        $transformationButtons = false;
        if ($metadataExists) {
            // Transformation exists (irods?)?
            $transformation = $formConfig['transformation'] == 'true' ;
            if ($transformation) {
                $showForm = false;
                if ($writePermission) {
                    $transformationText = $formConfig['transformationText'];
                    $transformationButtons = true;
                } else {
                    $transformationText = '<p>It is not possible to load this form as the metadata xml file is not in accordance with the form definition.</p>';
                    $transformationButtons = false;
                }
            }
        }

        $viewParams = array(
            'styleIncludes' => array(
                'lib/font-awesome/css/font-awesome.css',
                'lib/sweetalert/sweetalert.css',
                'css/metadata/form.css',
                'css/metadata/leaflet.css',
            ),
            'scriptIncludes' => array(
                'lib/sweetalert/sweetalert.min.js',
                'js/metadata/delete.js'
            ),
            'path'             => $path,
            'tokenName'        => $tokenName,
            'tokenHash'        => $tokenHash,
            'userType'         => $userType,
            'flashMessage'     => $flashMessage,
            'flashMessageType' => $flashMessageType,
            'metadataExists'   => $metadataExists,
            'writePermission'  => $writePermission,
            'showForm'         => $showForm,

            // Transformation vars
            'transformation' => $transformation,
            'transformationText' => $transformationText,
            'transformationButtons' => $transformationButtons,

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
        $jsonSchema = $this->Metadata_form_model->getJsonSchema($rodsaccount, $fullPath);
        $uiSchema = $this->Metadata_form_model->getJsonUiSchema($rodsaccount, $fullPath);

        // Check if yoda-metadata.json or yoda-metadata.xml exists.
        $jsonMetadataExists = $formConfig['hasMetadataJson'] == 'true';
        $xmlMetadataExists  = $formConfig['hasMetadataXml']  == 'true';
        $xmlFormData = null;

        if ($jsonMetadataExists) {
            $formData = $this->Metadata_form_model->getJsonMetadata($rodsaccount, $formConfig['metadataJsonPath']);
        } else if ($xmlMetadataExists) {
            $xmlFormData = $this->Metadata_form_model->loadFormData($rodsaccount, $formConfig['metadataXmlPath']);
            $formData = $this->Metadata_form_model->prepareJSONSFormData($jsonSchema, $xmlFormData);
        }

        // Validation
        $errors = array();
        if (empty($formData) && ($jsonMetadataExists || $xmlMetadataExists)) {
            $errors[] = 'Please check the structure of this file.';
        } else if (empty($formData)) {
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
                    // Continue if required or if there is a dependency
                    if ($error['constraint'] == 'required' || $error['constraint'] == 'dependencies') {
                        continue;
                    }
                    $errors[] = $error['property'];
                }
            }
        }

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $isDatamanager     = $formConfig['isDatamanager']  == 'yes';
        $isVaultPackage    = $formConfig['isVaultPackage'] == 'yes';
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
            $isLocked = $formConfig['lockFound'] == "here" || $formConfig['lockFound'] == "ancestor";
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

        $uiSchema = json_decode($uiSchema, true);
        if ($isLocked
            || (!$isVaultPackage && $isDatamanager && !$writePermission)
            || ($isVaultPackage && !$isDatamanager)
            || ($isVaultPackage && $isDatamanager && $updateButton)
            || (!$writePermission && !$isDatamanager)) {
            $uiSchema["ui:readonly"] = "true";
        }

        $parentHasMetadata = false;
        if (!$isVaultPackage) {
            $parentHasMetadata = $formConfig['parentHasMetadata'] == 'true';
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
        $output['metadataExists']    = ($jsonMetadataExists || $xmlMetadataExists);
        $output['locked']            = $isLocked;
        $output['writePermission']   = $writePermission;
        $output['submitButton']      = $submitButton;
        $output['unsubmitButton']    = $unsubmitButton;

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

        if (!($userType=='normal' || $userType=='manager')) { // superseeds userType!= reader - which comes too late for permissions for vault submission
            $this->session->set_flashdata('flashMessage', 'Insufficient rights to perform this action.'); // wat is een locking error?
            $this->session->set_flashdata('flashMessageType', 'danger');
            return redirect('research/browse?dir=' . rawurlencode($path), 'refresh');
        }

        if ($this->input->post('vault_submission') || $this->input->post('vault_unsubmission')) {
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));
            if ($this->input->post('vault_submission')) { // HdR er wordt nog niet gecheckt dat juiste persoon dit mag

                $metadataExists = ($formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes');
                $loadedFormData = $this->Metadata_form_model->loadFormData($rodsaccount, $formConfig['metadataXmlPath'], $metadataExists, $pathStart);
                if ($loadedFormData['schemaIdMetadata'] != $loadedFormData['schemaIdCurrent']) {
                    setMessage('error', 'No submission allowed as your data is not up to date with the lastest YoDa schema for this community.');
                    return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
                }

                if(!$this->vaultsubmission->checkLock()) {
                    setMessage('error', 'There was a locking error encountered while submitting this folder.');
                }
                else {
                    // first perform a save action of the latest posted data - only if there is no lock!
                    if ($formConfig['folderStatus']!='LOCKED') {
                        $result = $this->Metadata_form_model->saveJsonMetadata($rodsaccount, $fullPath);
                        if ($result['status'] != "Success") {
                            setMessage('error', 'Saving the metadata form failed: ' . $result['statusInfo']);
                        }
                    }
                    // Do vault submission
                    $result = $this->vaultsubmission->validate();
                    if ($result === true) {
                        $submitResult = $this->vaultsubmission->setSubmitFlag();
                        if ($submitResult) {
                            setMessage('success', 'The folder is successfully submitted.');
                        } else {
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
                if ($result['*status']== 'Success') {
                    setMessage('success', 'This folder was successfully unsubmitted from the vault.');
                }
                else {
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
                $result = $this->Metadata_form_model->saveJsonMetadata($rodsaccount, $fullPath);
                if ($result['status'] != "Success") {
                    setMessage('error', 'Saving the metadata form failed: ' . $result['statusInfo']);
                }
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
        if ($formConfig['parentHasMetadata'] == 'true') {
            $result = $this->filesystem->cloneMetadata($rodsaccount, $fullPath);
        }

        return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
    }

    public function transformation()
    {
        $this->load->model('Metadata_model');

        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Metadata_model->transform($fullPath);

        if ($result['*status'] == 'Success') {
            return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
        } else {
            setMessage('error', $result['*statusInfo']);
            return redirect('research/browse?dir=' . rawurlencode($path), 'refresh');
        }
    }
}
