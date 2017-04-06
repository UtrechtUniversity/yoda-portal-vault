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
                'lib/font-awesome/css/font-awesome.css'
            ),
            'scriptIncludes' => array(
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
                'js/research.js',
                'js/search.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            )
        ));

        $this->data['items'] = $this->config->item('browser-items-per-page');
        $this->data['dir'] = $this->input->get('dir');

        // Search results data
        $searchTerm = '';
        $searchStatusValue = '';
        $searchType = 'filename';
        $searchStart = 0;
        $searchOrderDir = 'asc';
        $searchOrderColumn = 0;
        $searchItemsPerPage = $this->config->item('search-items-per-page');

        if ($this->session->userdata('research-search-term') || $this->session->userdata('research-search-status-value')) {
            if ($this->session->userdata('research-search-term')) {
                $searchTerm = $this->session->userdata('research-search-term');
            }
            if ($this->session->userdata('research-search-status-value')) {
                $searchStatusValue = $this->session->userdata('research-search-status-value');
            }
            $searchType = $this->session->userdata('research-search-type');
            $searchStart = $this->session->userdata('research-search-start');
            $searchOrderDir = $this->session->userdata('research-search-order-dir');
            $searchOrderColumn = $this->session->userdata('research-search-order-column');
        }
        $showStatus = false;
        $showTerm = false;
        if ($searchType == 'status') {
            $showStatus = true;
        } else {
            $showTerm = true;
        }
        $searchData = compact('searchTerm', 'searchStatusValue', 'searchType', 'searchStart', 'searchOrderDir', 'searchOrderColumn', 'showStatus', 'showTerm', 'searchItemsPerPage');
        $this->data['searchHtml'] = $this->load->view('search', $searchData, true);

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

    /**
     * @param int $restrict
     * @param string $interveneOnMetadataKeys
     *
     * $restrict offers the possibilty to distinguish collecting folders and / or files
     *
     * $interveneOnMetadataKeys is a string holding keys of metadata that must NOT be present. If present in a row, this data is excluded from presentation
     *
     */
    public function data( $restrict = 0, $interveneOnMetadataKeys = '')
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

        $interveningKeys = explode(',', $interveneOnMetadataKeys);

        // Collections
        if ($restrict=='collections' OR !$restrict) {
            $icon = 'fa-folder-o';
            $collections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

            $totalItems += $collections['summary']['total'];
            if ($collections['summary']['returned'] > 0) {
                foreach ($collections['rows'] as $row) {
                    if ($this->_allowRowWhenBrowsing($row, $interveningKeys)) {
                        $filePath = str_replace($pathStart, '', $row['path']);
                        $rows[] = array(
                            '<span class="browse" data-path="' . urlencode($filePath) . '"><i class="fa ' . $icon . '" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename'], '/'))) . '</span>',
                            date('Y-m-d H:i:s', $row['modify_time'])
                        );
                    }
                }
            }
        }

        // Objects
        if($restrict=='objects' OR !$restrict) {
            $objects = $this->filesystem->browse($rodsaccount, $path, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $start);
            $totalItems += $objects['summary']['total'];
            if ($objects['summary']['returned'] > 0) {
                foreach ($objects['rows'] as $row) {
                    if ($this->_allowRowWhenBrowsing($row, $interveningKeys)) {
                        $filePath = str_replace($pathStart, '', $row['path']);
                        $rows[] = array(
                            '<span data-path="' . urlencode($filePath) . '"><i class="fa fa-file-o" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename'], '/'))) . '</span>',
                            date('Y-m-d H:i:s', $row['modify_time'])
                        );
                    }
                }
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $totalItems, 'recordsFiltered' => $totalItems, 'data' => $rows);

        echo json_encode($output);
    }

    /**
     * @param $row
     * @param $restrictingKeys
     * @return bool
     *
     * function that decides whether a row is allowed to be presented based upon information that is in the metadata, or simply the presence of a metadata key
     */
    private function _allowRowWhenBrowsing(&$row, &$restrictingKeys )
    {
        $allowed = true;
        foreach ($restrictingKeys as $key) {
            if ($key == 'org_lock_protect') { // special case for locking
                if (isset($row['org_lock_protect'])) {
                    if($row['org_lock_protect'] <= $row['path']) {
                        $allowed = false;
                        break;
                    }
                }
            }
            else {
                if (isset($row[$key])) {  // simply check the presence of this key and it restricts showing of this row
                    $allowed = false;
                    break;
                }
            }
        }

        return $allowed;
    }

    public function change_folder_status()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $status = $this->input->get('status');
        $path = $this->input->get('path');
        $output = array();

        $beforeAction = 'PROTECTED';

        if ($status == 'PROTECTED') {
            // Protect Folder
            $result = $this->filesystem->protectFolder($rodsaccount, $pathStart . $path);
            $beforeAction = 'UNPROTECTED';
        } else if ($status == 'UNPROTECTED') {
            // Unprotect folder
            $result = $this->filesystem->unprotectFolder($rodsaccount, $pathStart . $path);
        }

        if ($result) {
            $output = array('success' => true, 'status' => $status);
        } else {
            $output = array('success' => false, 'status' => $beforeAction);
        }

        echo json_encode($output);
    }
}