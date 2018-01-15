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

    // Get the text of the terms a researcher has to confirm
    public function terms()
    {
        $path = $this->input->get('path');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath =  $pathStart . $path;

        $this->load->model('Folder_Status_model');
        $result = $this->Folder_Status_model->getTermsText($fullPath);

        // welk model moet license komen??
        echo json_encode(array('status' => $result['*status'],
            'statusInfo' => $result['*statusInfo'],
            'result' => $result['*result']
            ));
    }

    public function submit_for_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->submit_for_publication($fullPath);
        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
    }

    public function approve_for_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->approve_for_publication($fullPath);
        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
    }

    public function cancel_publication()
    {
        $this->load->model('Folder_Status_model');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->Folder_Status_model->cancel_publication($fullPath);
        echo json_encode(array('status' => $result['*status'], 'statusInfo' => $result['*statusInfo']));
     }


     /// Dit moet naar DataRequst Controller???
     //copyVaultPackageToDynamicArea
    public function copyVaultPackageToDynamicArea()
    {
        $this->load->model('Data_Request_model');
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $fullTargetPath = $pathStart . $this->input->get('targetdir');
        $fullOrgPath= $pathStart  . $this->input->get('orgdir');

        // Verifications -> 2b added to iRods rule
        $collectionDetails = $this->filesystem->collectionDetails($rodsaccount, $fullOrgPath);
        if ($collectionDetails['status']=='ErrorPathNotExists') {
            $status = 'ErrorVaultCollectionDoesNotExist';
            $statusInfo = 'The datapackage does not exist';

            echo json_encode(array('status' => $status, 'statusInfo' => $statusInfo));
            exit;
        }

            // Some synchronous testing before starting the ASYNCHRONOUS copying process
        $collectionDetails = $this->filesystem->collectionDetails($rodsaccount, $fullTargetPath);
        //print_r($collectionDetails);

        if ($collectionDetails['result']['userType']=='reader') {
            $status = 'ErrorTargetPermissions';
            $statusInfo = 'You have insufficient permissions to copy the datapackage to this folder. Please select another folder';

            echo json_encode(array('status' => $status, 'statusInfo' => $statusInfo));
            exit;
        }

        if ($collectionDetails['result']['lockCount']!=0) {
            $status = 'ErrorTargetLocked';
            $statusInfo = 'The selected folder is locked. Please unlock this folder first.';

            echo json_encode(array('status' => $status, 'statusInfo' => $statusInfo));
            exit;
        }

        // Check existance of 2b created copy collection. It should not exist already
        $parts = explode('/', $fullOrgPath);
        $newCollectionNameFromOrg = $parts[count($parts)-1];

        $collectionDetails = $this->filesystem->collectionDetails($rodsaccount, $fullTargetPath . '/' . $newCollectionNameFromOrg);
        if ($collectionDetails['status']!='ErrorPathNotExists') {
            $status = 'ErrorDataPackageAlreadyExists';
            $statusInfo = 'This datapackage already exists in the selected folder. Please select another folder';

            echo json_encode(array('status' => $status, 'statusInfo' => $statusInfo));
            exit;
        }

        // All ok ... now initiate the asynchronous copying process
        $result = $this->Data_Request_model->copy_package_from_vault($fullOrgPath, $fullTargetPath);

        $status = $result['*status'];
        $statusInfo = $result['*statusInfo'];

        echo json_encode(array('status' => $status, 'statusInfo' => $statusInfo));
    }
}
