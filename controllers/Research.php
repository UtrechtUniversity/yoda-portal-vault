<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Research controller
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
class Research extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;
        $this->load->model('filesystem');
        $this->load->model('rodsuser');

        $this->load->library('pathlibrary');
    }

    /**
     * Submit folder in the research space.
     */
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
        } else {
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

    /**
     * Unsubmit folder in the research space.
     */
    public function unsubmit()
    {
        $this->load->model('Folder_Status_model');
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $path = $this->input->post('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));

        $result = $this->vaultsubmission->clearSubmitFlag();
        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Accept folder in the research space.
     */
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

    /**
     * Reject folder in the research space.
     */
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

    /**
     * Retrieve lists of preservable file formats.
     */
    public function preservableFormatsLists()
    {
        $result = $this->filesystem->getPreservableFormatsLists();

        $this->output
            ->set_content_type('application/json')
            ->set_output($result);
    }

    /**
     * Check whether unpreservable files are used and return their extensions as an array
     */
    public function checkForUnpreservableFiles()
    {
        $path = $this->input->get('path');
        $list = $this->input->get('list');
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath =  $pathStart . $path;

        $result = $this->filesystem->getUnpreservableFileFormats($fullPath, $list);

        $this->output
            ->set_content_type('application/json')
            ->set_output($result);
    }
}
