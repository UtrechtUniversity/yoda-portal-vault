<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Metadata extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        //$this->load->model('filesystem');
        $this->load->model('rodsuser');

        $this->load->library('module', array(__DIR__));
        $this->load->library('pathlibrary');
    }

    public function form()
    {
        $this->load->model('Metadata_model');
        $this->load->model('Metadata_form_model');
        $this->load->model('filesystem');


        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $path = $this->input->get('path');

        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $metadataCompleteness = 0; // mandatory completeness for the metadata
        $mandatoryTotal = 0;
        $mandatoryFilled = 0;

        $userType = $formConfig['userType'];

        $elements = $this->Metadata_form_model->getFormElements($rodsaccount, $formConfig);

        if ($elements) {
            $this->load->library('metadataform');

            //$form = $this->metadataform->load($elements, $metadata);
            $form = $this->metadataform->load($elements);
            if ($userType == 'normal' || $userType == 'manager') {
                $form->setPermission('write');
            } else {
                $form->setPermission('read');
            }

            // figure out the number of mandatory fields and how many actually hold data
            $form->calculateMandatoryCompleteness($elements);

            // calculate metadataCompleteness with
            $mandatoryTotal = $form->getCountMandatoryTotal();
            $mandatoryFilled = $form->getCountMandatoryFilled();
            if($mandatoryTotal==0) {
                $metadataCompleteness = 100;
            }
            else {
                $metadataCompleteness =  ceil(100 * $mandatoryFilled / $mandatoryTotal);
            }

            $metadataExists = false;
            $cloneMetadata = false;
            if ($formConfig['hasMetadataXml'] == 'true') {
                $metadataExists = true;
            }

            if ($formConfig['parentHasMetadataXml'] == 'true') {
                $cloneMetadata = true;
            }
        } else {
            $form = null;
            $metadataExists = false;
            $cloneMetadata = false;
        }
        $realMetadataExists = $metadataExists; // keep it as this is the true state of metadata being present or not.

        // Check locks
        if ($formConfig['lockFound'] == "here" || $formConfig['lockFound'] == "ancestor") {
            $form->setPermission('read');
            $cloneMetadata = false;
            $metadataExists = false;
        }


        // Corrupt metadata causes no $form to be created.
        // The following code (before adding 'if ($form) ' crashes ($form->getPermission() ) the application http error 500
        if ($form) {
            // Submit To Vault btn
            $submitToVaultBtn = false;
            $lockStatus = $formConfig['lockFound'];
            $folderStatus = $formConfig['folderStatus'];
            if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'PROTECTED' || $folderStatus == 'UNPROTECTED') && $form->getPermission() == 'write') {
                $submitToVaultBtn = true;
            }
        }

        $this->load->view('common-start', array(
            'styleIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.css',
                'lib/font-awesome/css/font-awesome.css',
                'lib/sweetalert/sweetalert.css',
                'lib/select2/css/select2.min.css',
                'css/metadata/form.css',
            ),
            'scriptIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.js',
                'lib/sweetalert/sweetalert.min.js',
                'lib/select2/js/select2.min.js',
                'js/metadata/form.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $flashMessage = $this->session->flashdata('flashMessage');
        $flashMessageType = $this->session->flashdata('flashMessageType');

        $this->data['form'] = $form;
        $this->data['path'] = $path;
        $this->data['fullPath'] = $fullPath;
        $this->data['userType'] = $userType;
        $this->data['metadataExists'] = $metadataExists;
        $this->data['cloneMetadata'] = $cloneMetadata;

        $this->data['mandatoryTotal'] = $mandatoryTotal;
        $this->data['mandatoryFilled'] = $mandatoryFilled;
        $this->data['metadataCompleteness'] = $metadataCompleteness;

        $this->data['submitToVaultBtn'] = $submitToVaultBtn;
        $this->data['flashMessage'] = $flashMessage;
        $this->data['flashMessageType'] = $flashMessageType;

        $this->data['realMetadataExists'] = $realMetadataExists; // @todo: refactor! only used in front end to have true knowledge of whether metadata exists as $metadataExists is unreliable now

        $this->load->view('metadata/form', $this->data);
        $this->load->view('common-end');
    }

    function store()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('Metadata_form_model');

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $userType = $formConfig['userType'];

        if ($this->input->post('vault_submission')) {
            // Do vault submission
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));
            $result = $this->vaultsubmission->validate();

            if ($result === true) {
                $submitResult = $this->vaultsubmission->setSubmitFlag();
                if ($submitResult) {
                    $this->session->set_flashdata('flashMessage', 'The folder is successfully submitted.');
                    $this->session->set_flashdata('flashMessageType', 'success');
                } else {
                    $this->session->set_flashdata('flashMessage', 'There was an locking error encountered while submitting this folder.');
                    $this->session->set_flashdata('flashMessageType', 'danger');
                }
            } else {
                $this->session->set_flashdata('flashMessage', implode('<br>', $result));
                $this->session->set_flashdata('flashMessageType', 'danger');
            }

            return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
        } else {
            // save metadata xml

            if($userType != 'reader') {
                if ($this->input->server('REQUEST_METHOD') == 'POST') {
                    $result = $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
                }

                return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
            }
            else {
                //get away from the form, user is (no longer) entitled to view it
                return redirect('research/browse?dir=' . urlencode($path), 'refresh'); //
            }
        }
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
                return redirect('research/browse?dir=' . urlencode($path), 'refresh');
            } else {
                return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
            }
        }
        else {
            //get away from the form, user is (no longer) entitled to view it
            return redirect('research/browse?dir=' . urlencode($path), 'refresh');
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

        return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
    }

    /*
    public function index()
    {
        $this->load->view('common-start', array(
            'styleIncludes' => array(
                'css/research.css',
                'lib/datatables/css/dataTables.bootstrap.min.css',
                //'lib/materialdesignicons/css/materialdesignicons.min.css'
                'lib/font-awesome/css/font-awesome.css'
            ),
            'scriptIncludes' => array(
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
                'js/research.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->data['items'] = $this->config->item('browser-items-per-page');

        $this->load->view('browse', $this->data);
        $this->load->view('common-end');
    }
    */


}
