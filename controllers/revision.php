<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Revision extends MY_Controller
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
                'js/revision.js',
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));
        $this->load->view('revision', $this->data);
        $this->load->view('common-end');
    }

    public function data()
    {
        $output = array('draw' => $this->input->get('draw'), 'recordsTotal' => 1, 'recordsFiltered' => 0, 'data' => array());
        $output['data'][] = array(
            '<i class="fa fa-file-word-o" aria-hidden="true"></i> Start versie met plaatjes.docx',
            '2016-11-28 16:43:21',
            'grp-test/Project-test'
        );
        $output['data'][] = array(
            '<i class="fa fa-file-powerpoint-o" aria-hidden="true"></i> Analysed social data.pptx',
            '2016-11-28 11:23:22',
            'grp-test/Project-test'
        );
        $output['data'][] = array(
            //'<i class="fa fa-lock" aria-hidden="true"></i> Datapackage 1',
            '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> YoDa is fun.pdf',
            '2016-11-28 14:03:22',
            'grp-test/Project-test'
        );
        $output['data'][] = array(
            //'<i class="fa fa-lock" aria-hidden="true"></i> Datapackage 1',
            '<i class="fa fa-file-excel-o" aria-hidden="true"></i> iLab.xls',
            '2016-11-28 14:03:22',
            'grp-test/Project-test'
        );

        echo json_encode($output);

    }

    public function detail()
    {
        // Is this cohort query yours?
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $revisionFiles = array(
            (object)array(
                'revisionName' => 'Start versie met plaatjes.docx',
                'revisionDate' => '28/11/2016 16:43:21',
                'revisionSize' => '32k',
                'revisionPath' => '//grp-test/Project-test'
            ),
            (object)array(
                'revisionName' => 'Start versie.docx',
                'revisionDate' => '28/11/2016 08:32:12',
                'revisionSize' => '22k',
                'revisionPath' => '//grp-test/Project-test'
            ),
            (object)array(
                'revisionName' => 'Start versie.docx',
                'revisionDate' => '27/11/2016 08:15:44',
                'revisionSize' => '20k',
                'revisionPath' => '//grp-test/Project-test'),
                //'revisionPath' => '//grp-project/longpathname/tocheck/whether/looks/ok'),
        );

        $htmlDetail =  $this->load->view('revisiondetail',
            array('revisionFiles' => $revisionFiles,
            ),
            true);


        echo json_encode(array(
                'hasError' => FALSE,
                'output' => $htmlDetail
            )
        );
    }

    public function test()
    {

    }
}