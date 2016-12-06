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
        $this->load->model('dataset');

        $this->load->library('module', array(__DIR__));
        $this->load->library('pathlibrary');

        $this->studies = $this->dataset->getStudies($this->rodsuser->getRodsAccount());
        sort($this->studies);

        // Handle permissions of this user
        //  [0]=> string(3) "jjj"
        // [1]=> string(4) "piet"
        // [2]=> string(12) "project-test"
        // [3]=> string(3) "roy"
        // [4]=> string(6) "study2"
        // [5]=> string(4) "test"
        // [6]=> string(12) "vault-study2"
        // [7]=> string(10) "vault-test"
        $studyID = 'test';
        $this->load->model('study');

        /*
        //$this->load->model('dataset');
        $this->load->model('filesystem');
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

    /**
     * Main page containing the table with the actual files
     */
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


    /**
     * gathering data for the main page - Actual files
     */
    public function data()
    {
        $output = array('draw' => $this->input->get('draw'), 'recordsTotal' => 1, 'recordsFiltered' => 0, 'data' => array());
        $output['data'][] = array(
            'test',
            '1',
            '<i class="fa fa-file-word-o" aria-hidden="true"></i> Start versie met plaatjes.docx',
            '2016-11-28 16:43:21',
            'grp-test/Project-test'
        );
        $output['data'][] = array(
            'test',
            '2',
            '<i class="fa fa-file-powerpoint-o" aria-hidden="true"></i> Analysed social data.pptx',
            '2016-11-28 11:23:22',
            'grp-test/Project-test'
        );
        $output['data'][] = array(
            //'<i class="fa fa-lock" aria-hidden="true"></i> Datapackage 1',
            'test',
            '3',
            '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> YoDa is fun.pdf',
            '2016-11-28 14:03:22',
            'grp-test/Project-test'
        );
        $output['data'][] = array(
            //'<i class="fa fa-lock" aria-hidden="true"></i> Datapackage 1',
            'test',
            '4',
            '<i class="fa fa-file-excel-o" aria-hidden="true"></i> iLab.xls',
            '2016-11-28 14:03:22',
            'grp-test/Project-test/SUBFOLDER'
        );

        echo json_encode($output);

    }

    /**
     * @param $studyId
     * @param $objectId
     *
     * Present the revisions of the specific objectId if permitted
     */
    public function detail($studyId, $objectId)
    {
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $this->permissions = $this->study->getIntakeStudyPermissions($studyId);

        if(!($this->permissions[$this->config->item('role:contributor')] OR $this->permissions[$this->config->item('role:reader')])){
            // insufficient rights
            exit;
        }

        // @todo: Validate whether objectId belongs to study


        // @todo: get the revisions via rule
        $fakeFiles = array(
            1 => 'Start versie met plaatjes.docx',
            2 => 'Analysed social data.pptx',
            3 => 'YoDa is fun.pdf',
            4 => 'iLab.xls'
        );

        $revisionFiles = array(
            (object)array(
                'revisionStudyId' => 'test',
                'revisionObjectId'     => $objectId . '-1',
                'revisionName' => $fakeFiles[$objectId],
                'revisionDate' => '28/11/2016 08:32:12',
                'revisionSize' => '22k',
                'revisionPath' => '//grp-test/Project-test'
            ),
            (object)array(
                'revisionStudyId' => 'test',
                'revisionObjectId'     => $objectId . '-2',
                'revisionName' => $fakeFiles[$objectId],
                'revisionDate' => '27/11/2016 08:15:44',
                'revisionSize' => '20k',
                'revisionPath' => '//grp-test/Project-test'),
        );

        $htmlDetail =  $this->load->view('revisiondetail',
            array('revisionFiles' => $revisionFiles,
                'objectId' => $objectId,
                'permissions' => $this->permissions
            ),
            true);


        echo json_encode(array(
                'hasError' => FALSE,
                'output' => $htmlDetail
            )
        );
    }

    /**
     * @param $studyId
     * @param $objectId
     *
     * objectId should match study
     *
     * permissions are arranged on study level
     *
     * If permitted actualize this file
     */
    public function actualise($studyId, $objectId)
    {
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $this->permissions = $this->study->getIntakeStudyPermissions($studyId);

        // validation

        // actual functionality

        $response = array('hasError' => true
        );
        echo json_encode($response);
    }

    /**
     * @param $studyId
     * @param $objectId
     *
     * objectId should match study
     *
     * permissions are arranged on study level
     *
     * If permitted download this file
     */
    public function download($studyId, $objectid)
    {
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $this->permissions = $this->study->getIntakeStudyPermissions($studyId);

        // validation

        // actual functionality

        $response = array('hasError' => true
        );
        echo json_encode($response);
    }

    /**
     * @param $studyId
     * @param $objectId
     *
     * objectId should match study
     *
     * permissions are arranged on study level
     *
     *
     * If permitted delete this file
     */
    public function delete($studyId, $objectid)
    {
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $this->permissions = $this->study->getIntakeStudyPermissions($studyId);

        // validation

        // actual functionality

        $response = array('hasError' => true
        );
        echo json_encode($response);
    }
}