<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Browse extends MY_Controller
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
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
                'js/research.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->data['items'] = $this->config->item('browser-items-per-page');

        $this->load->view('browse', $this->data);
        $this->load->view('common-end');
    }

    public function top_data()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $dirPath = $this->input->get('dir');

        $output = $this->filesystem->collectionDetails($rodsaccount, $pathStart . $dirPath);

        echo json_encode($output);
    }

    public function data()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $dirPath = $this->input->get('dir');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $order = $this->input->get('order');
        $orderDir = $order[0]['dir'];
        $orderColumn = $order[0]['column'];
        $orderColumns = array(
            0 => 'COLL_NAME',
            1 => 'COLL_MODIFY_TIME'
        );
        $draw = $this->input->get('draw');
        $itemsPerPage = $this->config->item('browser-items-per-page');

        $path = $pathStart;
        if (!empty($dirPath)) {
            $path .= $dirPath;
        }
        $rows = array();


        // Collections
        $icon = 'fa-folder-o';
        // Home path
        if ($path == $pathStart) {
            //$path = $path . '/grp-';
            $icon = 'fa-users';
        }
        
        $collections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);
        if ($collections['summary']['returned'] > 0) {
            foreach ($collections['rows'] as $row) {
                $filePath = str_replace($pathStart, '', $row['path']);
                $rows[] = array(
                    '<span class="browse" data-path="'. $filePath .'"><i class="fa ' . $icon .'" aria-hidden="true"></i> ' . trim($row['basename'], '/') . '</span>',
                    date('Y-m-d H:i:s', $row['modify_time'])
                );
            }
        }

        // Objects
        $objects = $this->filesystem->browse($rodsaccount, $path, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $start);
        if ($objects['summary']['returned'] > 0) {
            foreach ($objects['rows'] as $row) {
                $filePath = str_replace($pathStart, '', $row['path']);
                $rows[] = array(
                    '<span data-path="'. $filePath .'"><i class="fa fa-file-o" aria-hidden="true"></i> ' . trim($row['basename'], '/') . '</span>',
                    date('Y-m-d H:i:s', $row['modify_time'])
                );
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $collections['summary']['total'], 'recordsFiltered' => 0, 'data' => $rows);



        echo json_encode($output);


    }

    public function search()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $dirPath = $this->input->get('dir');
        $filter = $this->input->get('filter');
        $type = $this->input->get('type');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $order = $this->input->get('order');
        $orderDir = $order[0]['dir'];
        $orderColumn = $order[0]['column'];
        $orderColumns = array(
            0 => 'COLL_NAME',
            1 => 'COLL_MODIFY_TIME'
        );
        $draw = $this->input->get('draw');
        $itemsPerPage = $this->config->item('browser-items-per-page');

        $path = $pathStart;
        if (!empty($dirPath)) {
            $path .= $dirPath;
        }
        $rows = array();
        $columns = array();


        // Search / filename
        if ($type == 'filename') {
            $columns = array('Name', 'Location');
            $result = $this->filesystem->searchByName($rodsaccount, $path, $filter, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $start);

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['parent']);
                    $rows[] = array(
                        '<i class="fa fa-file-o" aria-hidden="true"></i> ' . $row['basename'],
                        '<span class="browse" data-path="' . $filePath . '">' . $filePath . '</span>'
                    );
                }
            }

        }

        // Search / location
        if ($type == 'location') {
            $columns = array('Location');
            $result = $this->filesystem->searchByName($rodsaccount, $path, $filter, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['path']);
                    $rows[] = array(
                        '<span class="browse" data-path="' . $filePath . '">' . trim($filePath, '/') . '</span>'
                    );
                }
            }
        }

        // Search / metadata
        if ($type == 'metadata') {
            $result = $this->filesystem->searchByUserMetadata($rodsaccount, $path, $filter, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['path']);
                    $rows[] = array(
                        '<span class="browse" data-path="' . $filePath . '">' . trim($filePath, '/') . '</span>'
                    );
                }
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $result['summary']['total'], 'recordsFiltered' => 0, 'data' => $rows, 'columns' => $columns);


        echo json_encode($output);

    }

    public function change_directory_type()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $type = $this->input->get('type');
        $path = $this->input->get('path');
        $output = array();

        if ($type == 'datapackage') {
            // create datapackage Datapackage
            $result = $this->filesystem->createDatapackage($rodsaccount, $pathStart . $path);
            $beforeAction = 'folder';
        } else {
            // Demote Datapackage
            $result = $this->filesystem->demoteDatapackage($rodsaccount, $pathStart . $path);
            $beforeAction = 'datapackage';
        }

        if ($result) {
            $output = array('success' => true, 'type' => $type);
        } else {
            $output = array('success' => false, 'type' => $beforeAction);
        }

        echo json_encode($output);
    }

    public function test()
    {

        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $path = $pathStart;

        //print_r($rodsaccount);
        //print_r($pathStart);



        //$result = $this->filesystem->browse($rodsaccount, $path, "Collection", "COLL_NAME", "desc", 25, 0);
        $result = $this->filesystem->browse($rodsaccount, $path, "DataObject", "COLL_NAME", "desc", 25, 0);
        //$result = $this->filesystem->searchByName($rodsaccount, $path, 'test', "DataObject", "COLL_NAME", "desc", 25, 0);

        print_r($result);
    }
}