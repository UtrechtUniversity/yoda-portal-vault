<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Metadata controller
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */

class Metadata extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        $this->load->model('rodsuser');
        $this->config->load('config');

        $this->load->library('pathlibrary');
        $this->load->library('api');
    }

    public function form()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $path = $this->input->get('path');
        if ($path === null)
            return redirect('research/browse', 'refresh');

        $fullPath =  $pathStart . $path;

        $flashMessage = $this->session->flashdata('flashMessage');
        $flashMessageType = $this->session->flashdata('flashMessageType');

        // Load CSRF token
        $tokenName = $this->security->get_csrf_token_name();
        $tokenHash = $this->security->get_csrf_hash();

        $formProperties = $this->api->call('uu_meta_form_load', ['coll' => $fullPath]);

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
            'flashMessage'     => $flashMessage,
            'flashMessageType' => $flashMessageType,
            'formProperties'   => $formProperties,
        );
        loadView('metadata/form', $viewParams);
    }
}
