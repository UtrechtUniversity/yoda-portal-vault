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
        $this->data['dir'] = $this->input->get('dir');

        // Remember search results
        $searchTerm = '';
        $searchType = 'filename';
        $searchStart = 0;

        if ($this->session->userdata('research-search-term')) {
            $searchTerm = $this->session->userdata('research-search-term');
            $searchType = $this->session->userdata('research-search-type');
            $searchStart = $this->session->userdata('research-search-start');
        }


        $this->data['searchTerm'] = $searchTerm;
        $this->data['searchType'] = $searchType;
        $this->data['searchStart'] = $searchStart;

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
        $totalItems = 0;

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
        $totalItems += $collections['summary']['total'];
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
        $totalItems += $objects['summary']['total'];
        if ($objects['summary']['returned'] > 0) {
            foreach ($objects['rows'] as $row) {
                $filePath = str_replace($pathStart, '', $row['path']);
                $rows[] = array(
                    '<span data-path="'. $filePath .'"><i class="fa fa-file-o" aria-hidden="true"></i> ' . trim($row['basename'], '/') . '</span>',
                    date('Y-m-d H:i:s', $row['modify_time'])
                );
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $totalItems, 'recordsFiltered' => $totalItems, 'data' => $rows);



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
        $totalItems = 0;

        $path = $pathStart;
        if (!empty($dirPath)) {
            $path .= $dirPath;
        }
        $rows = array();
        $columns = array();


        $this->session->set_userdata(
            array(
                'research-search-term' => $filter,
                'research-search-start' => $start,
                'research-search-type' => $type
            )
        );


        // Search / filename
        if ($type == 'filename') {
            $columns = array('Name', 'Location');
            $result = $this->filesystem->searchByName($rodsaccount, $path, $filter, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $start);
            $totalItems += $result['summary']['total'];

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

        // Search / folder
        if ($type == 'folder') {
            $columns = array('Location');
            $result = $this->filesystem->searchByName($rodsaccount, $path, $filter, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);
            $totalItems += $result['summary']['total'];

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
            $totalItems += $result['summary']['total'];

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['path']);
                    $matchParts = array();
                    $i = 1;
                    foreach ($row['matches'] as $match) {
                        foreach ($match as $k => $value) {
                            $matchParts[] = $k . ': ' . $value;
                            if ($i == 5) {
                                break 2;
                            }
                            $i++;
                        }
                    }

                    $rows[] = array(
                        '<span class="browse" data-path="' . $filePath . '">' . trim($filePath, '/') . '</span>',
                        '<span class="matches" data-toggle="tooltip" title="'. implode(', ', $matchParts) . ($i == 5 ? '...' : '') .'">' .  count($row['matches']) .' field(s)</span>'
                    );
                }
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $totalItems, 'recordsFiltered' => $totalItems, 'data' => $rows, 'columns' => $columns);


        echo json_encode($output);

    }

    public function unset_search()
    {
        $this->session->unset_userdata('research-search-term');
        $this->session->unset_userdata('research-search-start');
        $this->session->unset_userdata('research-search-type');
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