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

        $this->load->model('filesystem'); //@todo: komt te vervallen!!
        $this->load->model('rodsuser');
        $this->load->model('revisionmodel');

        $this->load->library('module', array(__DIR__));
        $this->load->library('pathlibrary');

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

        $this->data['items'] = $this->config->item('browser-items-per-page');
        $this->data['dlgPageItems'] = 5;

        $this->data['dir'] = $this->input->get('dir');

        $this->load->view('revision', $this->data);
        $this->load->view('common-end');
    }

    public function restore($revisionId)
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $targetDir = $this->input->get('targetdir');

        $path = $pathStart;
        if (!empty($targetDir)) {
            $path .= $targetDir;
        }

        $result = $this->filesystem->restoreRevision($rodsaccount, $path, $revisionId);

        $output = array(
            'hasError' => !$result,
            'result' => TRUE
        );

        echo json_encode($output);
    }

    public function data()
    {
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $rodsaccount = $this->rodsuser->getRodsAccount();

        //$pathStart = $this->pathlibrary->getPathStart($this->config);

        $searchArgument = $this->input->get('searchArgument'); // nodig???

        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $order = $this->input->get('order');
        $orderDir = $order[0]['dir'];
        $orderColumn = $order[0]['column'];

        $orderColumns = array( // ordering columns on the corresponding iRods column names
            0 => 'COLL_NAME',
            1 => 'COLL_MODIFY_TIME',
            2 => 'Tobedone',
            3 => 'Tobedone',
            4 => 'Tobedone',
        );

        $draw = $this->input->get('draw'); // wat is dit?? har

        $path = 'blablabla'; //$pathStart;  moet worden gerelateerd aan de revisie-zone

//        if (!empty($dirPath)) {
//            $path .= $dirPath;
//        }


        $searchArgument = 'blabla'; //@todo: to be added from front end later
        $result = $this->revisionmodel->search($rodsaccount, $searchArgument, $path, $orderColumns[$orderColumn], $orderDir, $length, $start);

        // records filtered is 0????
        // dit is als de search box van datatables zelf wordt gebruikt ... dat is nu niet het geval.
        $output = array('draw' => $draw, 'recordsTotal' => $result['summary']['total'], 'recordsFiltered' => 0, 'data' => array());

        if ($result['summary']['returned'] > 0) {
            foreach ($result['rows'] as $row) {
                $output['data'][] = array(
                    $row['study'],
                    $row['object'],
                    $row['name'],
                    $row['date'],
                    $row['path'],
                );
            }
        }
        $output['recordsTotal'] = 95;
        $output['recordsFiltered'] = 95;

        echo json_encode($output);
    }

    /**
     * @param $objectId
     *
     * Present the revisions of the specific objectId if permitted
     */
    public function detail($objectId)
    {
        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');


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
                'revisionPath' => '/nluu1dev/home/research-revisiontest'
            ),
            (object)array(
                'revisionStudyId' => 'test',
                'revisionObjectId'     => $objectId . '-2',
                'revisionName' => $fakeFiles[$objectId],
                'revisionDate' => '27/11/2016 08:15:44',
                'revisionSize' => '20k',
                'revisionPath' => '/nluu1dev/home/research-revisiontest'),
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

}