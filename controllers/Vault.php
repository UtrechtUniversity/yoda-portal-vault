<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Vault controller
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
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
}
