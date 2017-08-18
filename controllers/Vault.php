<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vault extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;
        $this->load->model('filesystem');
        $this->load->model('filesystem');
        $this->load->model('rodsuser');
        
        $this->load->library('pathlibrary');
    }

    public function submit()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');

        $this->load->model('Metadata_form_model');
        $this->load->model('Folder_Status_model');

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $message = array();

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        if ($formConfig===false) {
            $message = array('status' => 'error', 'statusInfo' => 'Permission denied for current user.');
        }
        else {

            $userType = $formConfig['userType'];

            // Do vault submission
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));
            $result = $this->vaultsubmission->validate();

            if ($result === true) {
                $submitResult = $this->vaultsubmission->setSubmitFlag();

                if ($submitResult['*status'] == 'Success') {
                    $message = array('status' => 'Success', 'statusInfo' => 'The folder is successfully submitted.', 'folderStatus' => $submitResult['*folderStatus']);
                } else {
                    $message = array('status' => $submitResult['*status'], 'statusInfo' => $submitResult['*statusInfo']);
                }
            } else {
                $message = array('status' => 'error', 'statusInfo' => implode("<br><br>", $result));
            }
        }
        echo json_encode($message);
    }

    public function unsubmit()
    {
        $this->load->model('Folder_Status_model');
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $folderStatus = $formConfig['folderStatus'];
        $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));

        $result = $this->vaultsubmission->clearSubmitFlag();
        $status = $result['*status'];
        $statusInfo = $result['*statusInfo'];

        echo json_encode(array('status' => $status, 'statusInfo' => $statusInfo));
    }

    public function accept()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->accept($fullPath);
        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
    }

    public function reject()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->reject($fullPath);
        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
    }

    public function access()
    {
        $path = $this->input->get('path');
        $action = $this->input->get('action');
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath =  $pathStart . $path;
        if ($action == 'grant') {
            $result = $this->Folder_Status_model->grant($fullPath);
        } else {
            $result = $this->Folder_Status_model->revoke($fullPath);
        }

        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
    }

    public function approve()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->approve($fullPath);
        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
    }
}
