<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tree extends MY_Controller
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

        /*
        //$this->load->model('dataset');
        $this->load->model('filesystem');
        //$this->load->model('study');
        $this->load->model('rodsuser');
        //$this->load->model('metadatamodel');
        //$this->load->model('metadataschemareader');
        $this->load->helper('date');
        $this->load->helper('language');
        //$this->load->helper('intake');
        $this->load->helper('form');
        $this->load->language('intake');
        $this->load->language('errors');
        $this->load->language('form_errors');
        $this->load->library('module', array(__DIR__));
        $this->load->library('metadatafields');
        $this->load->library('pathlibrary');
        $this->load->library('SSP');
        $this->studies = $this->dataset->getStudies($this->rodsuser->getRodsAccount());
        sort($this->studies);
        */
    }

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
                'js/research.js',
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));
        $this->load->view('tree', $this->data);
        $this->load->view('common-end');
    }

    public function data()
    {
        $output = array('draw' => $this->input->get('draw'), 'recordsTotal' => 1, 'recordsFiltered' => 0, 'data' => array());
        $output['data'][] = array(
            '<i class="fa fa-folder" aria-hidden="true"></i> Raw social data <span class="label label-success pull-right">In vault</span>',
            '2016-11-28 16:43:21'
        );
        $output['data'][] = array(
            '<i class="fa fa-folder" aria-hidden="true"></i> Analysed social data',
            '2016-11-28 11:23:22'
        );
        $output['data'][] = array(
            //'<i class="fa fa-lock" aria-hidden="true"></i> Datapackage 1',
            '<i class="fa fa-folder-o" aria-hidden="true"></i> Study economy',
            '2016-11-28 14:03:22'
        );

        echo json_encode($output);


    }

    public function test()
    {

    }
}