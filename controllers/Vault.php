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
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $this->load->model('Metadata_form_model');
        $this->load->model('Folder_Status_model');

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

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($message));
    }

    public function unsubmit()
    {
        $this->load->model('Folder_Status_model');
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $folderStatus = $formConfig['folderStatus'];
        $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));

        $result = $this->vaultsubmission->clearSubmitFlag();
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function accept()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->accept($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function reject()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->reject($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function submit_for_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->submit_for_publication($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function approve_for_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->approve_for_publication($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function cancel_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->cancel_publication($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function depublish_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->depublish_publication($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function republish_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->republish_publication($fullPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function access()
    {
        $path = $this->input->post('path');
        $action = $this->input->post('action');

        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath =  $pathStart . $path;
        if ($action == 'grant') {
            $result = $this->Folder_Status_model->grant($fullPath);
        } else {
            $result = $this->Folder_Status_model->revoke($fullPath);
        }

        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    // Get the text of the terms a researcher has to confirm
    public function terms()
    {
        $path = $this->input->get('path');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath =  $pathStart . $path;

        $this->load->model('Folder_Status_model');
        $result = $this->Folder_Status_model->getTermsText($fullPath);

        // welk model moet license komen??
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo'],
                        'result' => $result['*result']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

     /// Dit moet naar DataRequst Controller???
     //copyVaultPackageToDynamicArea
    public function copyVaultPackageToDynamicArea()
    {
        $this->load->model('Data_Request_model');
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $fullTargetPath = $pathStart . $this->input->post('targetdir');
        $fullOrgPath= $pathStart  . $this->input->post('orgdir');

        $result = $this->Data_Request_model->copy_package_from_vault($fullOrgPath, $fullTargetPath);
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    // Check whether unpreservable files are used and return their extensions as an array
    // So frontend can present them to user
    public function checkForUnpreservableFiles()
    {
        $path = $this->input->get('path');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath =  $pathStart . $path;

        $this->load->model('Folder_Status_model');
        $result = $this->Folder_Status_model->getUnpreservableFileFormats($fullPath);

        $extensionsAsText = '';
        if (is_array($result['*result'])) {
            $extensionsAsText = implode(' ,', $result['*result']);
        }
        $output = array('status' => $result['*status'],
            'statusInfo' => $result['*statusInfo'],
            'result' => $extensionsAsText);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
}
