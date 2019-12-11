<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Search controller
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
class Search extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        $this->load->model('filesystem');
        $this->load->model('rodsuser');
        $this->config->load('config');

        $this->load->library('pathlibrary');
    }

    public function unset_session()
    {
        $this->session->unset_userdata('research-search-term');
        $this->session->unset_userdata('research-search-start');
        $this->session->unset_userdata('research-search-type');
        $this->session->unset_userdata('research-search-order-dir');
        $this->session->unset_userdata('research-search-order-column');
        $this->session->unset_userdata('research-search-status-value');
    }

    public function set_session()
    {
        $value = $this->input->get('value');
        $type = $this->input->get('type');
        if ($type == 'status') {
            $this->session->set_userdata('research-search-status-value', $value);
        } else {
            $this->session->set_userdata('research-search-term', $value);
            $this->session->unset_userdata('research-search-status-value');
        }


        $this->session->set_userdata('research-search-type', $type);
        $this->session->set_userdata('research-search-start', 0);
    }
}
