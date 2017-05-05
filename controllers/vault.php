<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vault extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // initially no rights for any study
        $this->permissions = array(
            $this->config->item('role:contributor') => FALSE,
            $this->config->item('role:manager') => FALSE,
            $this->config->item('role:reader') => FALSE
        );

        $this->data['userIsAllowed'] = TRUE;

        $this->load->model('filesystem');
        $this->load->model('rodsuser');

        $this->load->library('module', array(__DIR__));
        $this->load->library('pathlibrary');
    }

    public function submit()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->get('path');

        $this->load->model('Metadata_form_model');

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $userType = $formConfig['userType'];
        $message = array();

        // Do vault submission
        $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));
        $result = $this->vaultsubmission->validate();

        if ($result === true) {
            $submitResult = $this->vaultsubmission->setSubmitFlag();
            if ($submitResult) {
                $message = array('status' => 'success', 'text' => 'The folder is successfully submitted.');
            } else {
                $message = array('status' => 'error', 'text' => 'There was an locking error encountered while submitting this folder.');
            }
        } else {
            $message = array('status' => 'error', 'text' => implode("\n\n", $result));
        }

        echo json_encode($message);
    }

    public function unsubmit()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $dirPath = $this->input->get('folder');
    }
}