<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

        $validationResult = true;
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

        $metadataExists = false;
        $cloneMetadata = false;
        if ($formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') {
            $metadataExists = true;
        }

        if ($formConfig['parentHasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') {
            $cloneMetadata = true;
        }

        $realMetadataExists = $metadataExists; // keep it as this is the true state of metadata being present or not.

        // Check locks
        if ($formConfig['lockFound'] == "here" || $formConfig['lockFound'] == "ancestor" || $formConfig['folderStatus']=='SUBMITTED' || $formConfig['folderStatus']=='LOCKED') {
            //$form->setPermission('read');
            $cloneMetadata = false;
            $metadataExists = false;
        }

        // Corrupt metadata causes no $form to be created.
        // The following code (before adding 'if ($form) ' crashes ($form->getPermission() ) the application http error 500
        $ShowUnsubmitBtn = false;
            // Submit To Vault btn
            $submitToVaultBtn = false;
            $lockStatus = $formConfig['lockFound'];
            $folderStatus = $formConfig['folderStatus'];
            if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'PROTECTED' || $folderStatus == 'LOCKED' || $folderStatus == '')
                && ($userType == 'normal' || $userType == 'manager')) { // written this way as the
                $submitToVaultBtn = true;
            }

            if (($userType == 'normal' OR $userType == 'manager')  AND $folderStatus == 'SUBMITTED') {
                $showUnsubmitBtn = true;
            }

        $flashMessage = $this->session->flashdata('flashMessage');
        $flashMessageType = $this->session->flashdata('flashMessageType');

        // Datamanager Edit metadata in vault btn & write permissions
        $showEditBtn = false;
        $messageDatamanagerAfterSaveInVault = '';  // message to datamanger via central messaging -> javascript setMessage
        if ($isDatamanager == 'yes' && $isVaultPackage == 'yes') {
	    if ($formConfig['hasShadowMetadataXml'] == 'no') {
                if ($mode == 'edit_in_vault') {
                    //$form->setPermission('write'); // Set write permissions for editing metadata in the vault.
                } else {
                    $showEditBtn = true; // show edit button
                }
	    }

            if ($formConfig['hasShadowMetadataXml'] == 'yes') {
                $messageDatamanagerAfterSaveInVault = 'Update of metadata is pending.';
            }
        }

        // Load CSRF token
        $tokenName = $this->security->get_csrf_token_name();
        $tokenHash = $this->security->get_csrf_hash();

        $viewParams = array(
            'styleIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.css',
                'lib/font-awesome/css/font-awesome.css',
                'lib/sweetalert/sweetalert.css',
                'lib/select2/css/select2.min.css',
                'lib/leaflet/leaflet.css',
                'lib/leaflet/leaflet.draw.css',
                'css/metadata/form.css',
            ),
            'scriptIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.js',
                'lib/sweetalert/sweetalert.min.js',
                'lib/select2/js/select2.min.js',
                'lib/jquery-inputmask/jquery.inputmask.bundle.js',
                // LEAFLET
                'lib/leaflet/leaflet.js',
                'lib/leaflet/Leaflet.draw.js',
                'lib/leaflet/Leaflet.Draw.Event.js',
                'lib/leaflet/Toolbar.js',
                'lib/leaflet/Tooltip.js',
                'lib/leaflet/ext/GeometryUtil.js',
                'lib/leaflet/ext/LatLngUtil.js',
                'lib/leaflet/ext/LineUtil.Intersect.js',
                'lib/leaflet/ext/Polygon.Intersect.js',
                'lib/leaflet/ext/Polyline.Intersect.js',
                'lib/leaflet/ext/TouchEvents.js',
                'lib/leaflet/draw/DrawToolbar.js',
                'lib/leaflet/draw/handler/Draw.Feature.js',
                'lib/leaflet/draw/handler/Draw.SimpleShape.js',
                'lib/leaflet/draw/handler/Draw.Polyline.js',
                'lib/leaflet/draw/handler/Draw.Marker.js',
                'lib/leaflet/draw/handler/Draw.Circle.js',
                'lib/leaflet/draw/handler/Draw.CircleMarker.js',
                'lib/leaflet/draw/handler/Draw.Polygon.js',
                'lib/leaflet/draw/handler/Draw.Rectangle.js',
                'lib/leaflet/edit/EditToolbar.js',
                'lib/leaflet/edit/handler/EditToolbar.Edit.js',
                'lib/leaflet/edit/handler/EditToolbar.Delete.js',
                'lib/leaflet/Control.Draw.js',
                'lib/leaflet/edit/handler/Edit.Poly.js',
                'lib/leaflet/edit/handler/Edit.SimpleShape.js',
                'lib/leaflet/edit/handler/Edit.Rectangle.js',
                'lib/leaflet/edit/handler/Edit.Marker.js',
                'lib/leaflet/edit/handler/Edit.CircleMarker.js',
                'lib/leaflet/edit/handler/Edit.Circle.js',

                'js/metadata/form.js',
                //'js/metadata/bundle.js',
            ),
            'activeModule'   => 'research',
            'path' => $path,
            'fullPath' => $fullPath,
            'tokenName' => $tokenName,
            'tokenHash' => $tokenHash,
            'userType' => $userType,
            'metadataExists' => $metadataExists, // @todo: refactor! only used in front end to have true knowledge of whether metadata exists as $metadataExists is unreliable now
            'cloneMetadata' => $cloneMetadata,
            'isVaultPackage' => $isVaultPackage,
            'showEditBtn' => $showEditBtn,
            'messageDatamanagerAfterSaveInVault' => $messageDatamanagerAfterSaveInVault,

            'submitToVaultBtn' => $submitToVaultBtn,
            'showUnsubmitBtn' => $showUnsubmitBtn,
            'flashMessage' => $flashMessage,
            'flashMessageType' => $flashMessageType,
            'validationResult' => $validationResult,

            'realMetadataExists' => $realMetadataExists, // @todo: refactor! only used in front end to have true knowledge of whether metadata exists as $metadataExists is unreliable now
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

        $formData = $this->Metadata_form_model->prepareJSONSFormData($jsonSchema, $xmlFormData);
        if (empty($formData)) {
             $formData = json_decode ("{}");
        }

        $uiSchema = json_decode ("{}");

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $output = array();
        $output['path']                 = $path;
        $output['schema']               = $jsonSchema;
        $output['uiSchema']             = $uiSchema;
        $output['formData']             = $formData;
        $output['isDatamanager']        = ($formConfig['isDatamanager'] == 'yes') ? true: false;
        $output['isVaultPackage']       = ($formConfig['isVaultPackage'] == 'yes') ? true: false;
        $output['parentHasMetadataXml'] = ($formConfig['parentHasMetadataXml'] == 'true') ? true: false;

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
                    //setMessage('success', 'Update of metadata is pending.'); // No message - this is, for this sitatuion dealt with by loading page
                    $this->Filesystem->delete($rodsaccount, $tmpSavePath);
                } else {
                    setMessage('error', 'Unexpected metadata xml write error.');
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
                return redirect('research/browse?dir=' . rawurlencode($path), 'refresh');
            } else {
                return redirect('research/metadata/form?path=' . rawurlencode($path), 'refresh');
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
