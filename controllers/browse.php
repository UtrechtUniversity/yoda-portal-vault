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
        $this->load->view('browse', $this->data);
        $this->load->view('common-end');
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

        $path = $pathStart;
        if (!empty($dirPath)) {
            $path .= $dirPath;
        }

        $result = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

        $output = array('draw' => $draw, 'recordsTotal' => $result['summary']['total'], 'recordsFiltered' => 0, 'data' => array());

        if ($result['summary']['returned'] > 0) {
            foreach ($result['rows'] as $row) {
                $path = str_replace($pathStart, '', $row['path']);
                $output['data'][] = array(
                    '<span class="browse" data-path="'. $path .'">' . trim($row['basename'], '/') . '</span>',
                    date('Y-m-d H:i:s', $row['modify_time'])
                );
            }
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



        $result = $this->filesystem->browse($rodsaccount, $path, "Collection", "COLL_NAME", "desc", 25, 0);

        print_r($result);
    }
}