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

        $mode = $this->input->get('mode'); // ?mode=edit_for_vault

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
                'css/metadata/leaflet.css',
            ),
            'scriptIncludes' => array(
                'lib/sweetalert/sweetalert.min.js'
            ),
            'path'             => $path,
            'tokenName'        => $tokenName,
            'tokenHash'        => $tokenHash,
            'userType'         => $userType,
            'mode'             => $mode,
            'flashMessage'     => $flashMessage,
            'flashMessageType' => $flashMessageType,
            'metadataExists'   => true,
            'writePermission'  => $writePermission,
            'showForm'         => $showForm,
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

        $formData = $this->Metadata_form_model->getJsonMetadata($rodsaccount, $formConfig['metadataJsonPath']);

        // Validation
        $errors = array();
        if (empty($formData)) {
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
        $userType          = $formConfig['userType'];

        if ($userType == 'normal' || $userType == 'manager') {
            $writePermission = true;
        } else {
            $writePermission = false;
        }

        // Should submit button be rendered?
        $submitButton = false;
        $isLocked = false;

        // Should unsubmit button be rendered?
        $unsubmitButton = false;
        if (($userType == 'normal' || $userType == 'manager')
            && $folderStatus == 'SUBMITTED') {
            $unsubmitButton = true;
        }

        // Should update button be rendered?
        $mode = $this->input->get('mode');
        $updateButton = $isDatamanager && $mode !== 'edit_in_vault';

        $uiSchema = json_decode($uiSchema, true);
        if ($isLocked
            || (!$isDatamanager)
            || ($isDatamanager && $updateButton)
            || (!$writePermission && !$isDatamanager)) {
            $uiSchema["ui:readonly"] = "true";
        }

        $output = array();
        $output['path']              = $path;
        $output['schema']            = json_decode($jsonSchema);
        $output['uiSchema']          = $uiSchema;
        $output['formData']          = $formData;
        $output['formDataErrors']    = $errors;
        $output['isDatamanager']     = $isDatamanager;
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

         $path = $this->input->get('path');
         $fullPath = $pathStart . $path;

         if ($this->input->server('REQUEST_METHOD') == 'POST') {
             $result = $this->Metadata_form_model->saveJsonMetadata($rodsaccount, $fullPath);
         }

         return redirect('vault/metadata/form?path=' . rawurlencode($path), 'refresh');
     }
}
