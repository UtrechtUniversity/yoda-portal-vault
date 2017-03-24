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

        $this->data['items'] = $this->config->item('revision-items-per-page');
        $this->data['dlgPageItems'] = $this->config->item('revision-dialog-items-per-page');

        $this->data['filter'] = $this->input->get('filter');

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

        $result = $this->revisionmodel->restoreRevision($rodsaccount, $path, $revisionId);

        $hasError = false;
        $reasonError = '';

        if ($result != "Success") {
                        $hasError = true;
            $reasonError = $result;
        }

        $output = array(
            'hasError' => $hasError,
            'reasonError' => $reasonError
        );

        echo json_encode($output);
    }

    public function data()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $order = $this->input->get('order');
        $orderDir = $order[0]['dir'];
        $orderColumn = $order[0]['column'];
        $draw = $this->input->get('draw');

        $orderColumns = array( // ordering columns on the corresponding iRods column names
            0 => 'META_DATA_ATTR_VALUE'
        );

        $searchArgument = $this->input->get('searchArgument');
        // $searchArgument is changed as iRods cannot handle '%' and '_' and \
        $searchArgument = str_replace(array('\\', '%', '_'),
            array('\\\\', '\\%','\\_'),
            $searchArgument);

        $rows = array();
        $result = $this->revisionmodel->searchByString($rodsaccount, $searchArgument, $orderColumns[$orderColumn], $orderDir, $length, $start);
        $totalItems = $result['summary']['total'];

        if (isset($result['summary']) && $result['summary']['returned'] > 0) {
            foreach ($result['rows'] as $row) {
                $filePath = str_replace($pathStart, '', $row['originalPath']);
                $rows[] = array(
                    '<span data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities( trim( $filePath, '/'))) . '</span>',
                    $row['numberOfRevisions']
                );
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $totalItems, 'recordsFiltered' => $totalItems, 'data' => $rows);

        echo json_encode($output);
    }

    /**
     * @param $studyId
     * @param $objectId
     *
     * Present the revisions of the specific objectId if permitted
     */
    public function detail()
    {
        $path = $this->input->get('path');
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath = $pathStart . $path;

        $revisionFiles = $this->revisionmodel->listByPath($rodsaccount, $fullPath);

        $htmlDetail =  $this->load->view('revisiondetail',
            array('revisionFiles' => $revisionFiles,
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