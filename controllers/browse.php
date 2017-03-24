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
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            )
        ));

        $this->data['items'] = $this->config->item('browser-items-per-page');

        $this->data['dir'] = $this->input->get('dir');

        // Remember search results
        $searchTerm = '';
        $searchStatusValue = '';
        $searchType = 'filename';
        $searchStart = 0;
        $searchOrderDir = 'asc';
        $searchOrderColumn = 0;

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

        $this->data['searchTerm'] = $searchTerm;

        $this->data['searchStatusValue'] = $searchStatusValue;
        $this->data['searchType'] = $searchType;
        $this->data['searchStart'] = $searchStart;
        $this->data['searchOrderDir'] = $searchOrderDir;
        $this->data['searchOrderColumn'] = $searchOrderColumn;

        $showStatus = false;
        $showTerm = false;
        if ($searchType == 'status') {
            $showStatus = true;
        } else {
            $showTerm = true;
        }
        $this->data['showStatus'] = $showStatus;
        $this->data['showTerm'] = $showTerm;

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
     * @param string $excludeOnMetadataPresence
     *
     * $restrict offers the possibilty to distinguish collecting folders and / or files
     *
     * $excludeOnMetadataPresence is a string holding keys of metadata that must NOT be present. If present in a row, this data is excluded from presentation
     *
     */
    public function data( $restrict = 0, $excludeOnMetadataPresence = '')
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

        $toBeExcludedOnMetadata = explode(',', $excludeOnMetadataPresence);

        // Collections
        if ($restrict=='collections' OR !$restrict) {
            $icon = 'fa-folder-o';
            $collections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

            $totalItems += $collections['summary']['total'];
            if ($collections['summary']['returned'] > 0) {
                foreach ($collections['rows'] as $row) {

                    $allowed = true;
                    foreach ($toBeExcludedOnMetadata as $md) {
                        if ($md == 'org_lock_protect') { // special case for locking
                            if (isset($row['org_lock_protect'])) {
                                if($row['org_lock_protect'] <= $row['path']) {
                                    $allowed = false;
                                    break;
                                }
                            }
                        }
                        else {
                            if (isset($row[$md])) {
                                $allowed = false;
                                break;
                            }
                        }
                    }
                    if ($allowed) {
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

                    $allowed = true;
                    foreach ($toBeExcludedOnMetadata as $md) {
                        if (isset($row[$md])) {
                            $allowed = false;
                            break;
                        }
                    }
                    if ($allowed) {
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
        $draw = $this->input->get('draw');
        $itemsPerPage = $this->config->item('browser-items-per-page');
        $totalItems = 0;

        $path = $pathStart;
        if (!empty($dirPath)) {
            $path .= $dirPath;
        }
        $rows = array();
        $columns = array();

        // Set basic search params
        $this->session->set_userdata(
            array(
                'research-search-term' => $filter,
                'research-search-start' => $start,
                'research-search-type' => $type,
                'research-search-order-dir' => $orderDir,
                'research-search-order-column' => $orderColumn
            )
        );

        // Unset values
        $this->session->unset_userdata('research-search-term');
        $this->session->unset_userdata('research-search-status-value');

        // Set value for term or status value
        if ($type == 'status') {
            $this->session->set_userdata('research-search-status-value', $filter);
        } else {
            $this->session->set_userdata('research-search-term', $filter);
        }

        // $filter is changed as iRods cannot handle '%' and '_' and \
        $filter = str_replace(array('\\', '%', '_'),
                            array('\\\\', '\\%','\\_'),
                            $filter);

        // Search / filename
        if ($type == 'filename') {
            $orderColumns = array(
                0 => 'DATA_NAME',
                1 => 'COLL_NAME'
            );
            $result = $this->filesystem->searchByName($rodsaccount, $path, $filter, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $start);
            $totalItems += $result['summary']['total'];

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['parent']);
                    $rows[] = array(
                        '<i class="fa fa-file-o" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities( trim( $row['basename']))),
                        '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities( trim( $filePath, '/'))) . '</span>'
                    );
                }
            }
        }

        // Search / folder
        if ($type == 'folder') {
            $orderColumns = array(
                0 => 'COLL_NAME'
            );
            $result = $this->filesystem->searchByName($rodsaccount, $path, $filter, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);
            $totalItems += $result['summary']['total'];

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['path']);

                    //str_replace(' ', '&nbsp;', htmlentities( trim( $row['basename'], '/')))
                    $rows[] = array(
                        '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities( trim( $filePath, '/'))) . '</span>'
                    );
                }
            }
        }

        // Search / metadata
        if ($type == 'metadata') {
            $orderColumns = array(
                0 => 'COLL_NAME'
            );
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
                        '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities( trim( $filePath, '/'))) . '</span>',
                        '<span class="matches" data-toggle="tooltip" title="'. htmlentities( implode(', ', $matchParts)) . ($i == 5 ? '...' : '') .'">' .  count($row['matches']) .' field(s)</span>'
                    );
                }
            }
        }

        // Search / status
        if ($type == 'status') {
            $orderColumns = array(
                0 => 'COLL_NAME'
            );
            $result = $this->filesystem->searchByOrgMetadata($rodsaccount, $path, $filter, "status", $orderColumns[$orderColumn], $orderDir, $length, $start);
            $totalItems += $result['summary']['total'];

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['path']);
                    $rows[] = array(
                        '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities( trim( $filePath, '/'))) . '</span>'
                    );
                }
            }
        }

        $output = array('draw' => $draw, 'recordsTotal' => $totalItems, 'recordsFiltered' => $totalItems, 'data' => $rows);


        echo json_encode($output);

    }

    public function unset_search()
    {
        $this->session->unset_userdata('research-search-term');
        $this->session->unset_userdata('research-search-start');
        $this->session->unset_userdata('research-search-type');
        $this->session->unset_userdata('research-search-order-dir');
        $this->session->unset_userdata('research-search-order-column');
        $this->session->unset_userdata('research-search-status-value');
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