<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Browse extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        $this->load->model('filesystem');
        $this->load->model('rodsuser');
        $this->config->load('config');

        $this->load->library('pathlibrary');
    }

    public function index()
    {
        $items = $this->config->item('browser-items-per-page');
        $dir = $this->input->get('dir');

        /// Hdr test purposes
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $fullPath =  $pathStart . $this->input->get('dir');

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

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
        $searchHtml = $this->load->view('search', $searchData, true);

        $viewParams = array(
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
                'js/dlgSelectCollection.js'
            ),
            'activeModule'   => 'research',
            'searchHtml' => $searchHtml,
            'items' => $items,
            'dir' => $dir,
        );
        loadView('browse', $viewParams);
    }

    public function top_data()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $dirPath = $this->input->get('dir');

        $output = $this->filesystem->collectionDetails($rodsaccount, $pathStart . $dirPath);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function list_locks()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $folderPath = $this->input->get('folder');
        $fullPath = $pathStart . $folderPath;

        $result = $this->filesystem->listLocks($rodsaccount, $fullPath);

        // Strip path
        $locks = array();
        if ($result['*status'] == 'Success') {
            $total = $result['*result']['total'];
            if ($total > 0) {
                $locksResult = $result['*result']['locks'];
                foreach ($locksResult as $path) {
                    $locks[] = str_replace($pathStart, '', $path);
                }
            }
        }

        $output = array('result' => $locks,
	                'status' => $result['*status'],
			'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function list_actionLog()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $folderPath = $this->input->get('folder');
        $fullPath = $pathStart . $folderPath;

        $result = $this->filesystem->listActionLog($rodsaccount, $fullPath);

        $logItems = array();
        if ($result['*status'] == 'Success') {

            foreach($result['*result'] as $item){
                $timestamp = date("Y/m/d H:i:s", 1 * $item[0]);

                #user zone handling - users can originate from different zones
                $parts = explode('#',$item[2]);
                $userName = $parts[0]; // take developer name without zone initially
                if (count($parts)==2 &&
                    $parts[1] != $rodsaccount->zone ) { // ONly present zone when user mentioned in log is not from current zone
                        $userName = $item[2];
                }
                $logItems[] = array($userName, ucfirst($item[1]), $timestamp);
            }
        }

        $output = array('result' => $logItems,
	                'status' => $result['*status'],
			'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Retrieve system metadata of vault package.
     */
    public function system_metadata()
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $folderPath = $this->input->get('folder');
        $fullPath = $pathStart . $folderPath;

        $result = $this->filesystem->listSystemMetadata($rodsaccount, $fullPath);

        $systemMetadata = array();
        if ($result['*status'] == 'Success') {

            foreach($result['*result'] as $item){
                $systemMetadata[] = array($item[0], $item[1]);
            }
        }

        $output = array('result' => $systemMetadata,
	                'status' => $result['*status'],
			'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
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
        //return -1; exit;

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

        // Generic error handling intialisation
        $status = 'Success';
        $statusInformation = '';

        $totalItemsLeftInView = $length;

        $totalItems = 0;

        // Collections
        if ($restrict=='collections' OR !$restrict) {
            // Get the actual total for the Collections
            $testCollections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, 0);
            $status = $testCollections['status'];
            if ($status=='Success') {
                $totalItems = $testCollections['summary']['total'];

                $icon = 'fa-folder-o';
                $collections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

                $status = $collections['status'];
                $statusInfo = $collections['statusInfo'];

                if ($status == 'Success') {
                    // @todo: does not always produce a total - more specifically when there is no resulting set of data!
                    //$totalItems += $collections['summary']['total'];
                    if ($collections['summary']['returned'] > 0) {
                        foreach ($collections['rows'] as $row) {
                            if ($this->_allowRowWhenBrowsing($row, $interveningKeys)) {
                                $filePath = str_replace($pathStart, '', $row['path']);
                                $rows[] = array(
                                    '<span class="browse" data-path="' . urlencode($filePath) . '"><i class="fa ' . $icon . '" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename'], '/'))) . '</span>',
                                    date('Y-m-d H:i:s', $row['modify_time'])
                                );

                                $totalItemsLeftInView--;
                            }
                        }
                    }
                }
            }
        }

        $correctedStartForObjects = 0;
        if ($start>$totalItems) {
            // add the shift following the final collection page holding both Collections and Objects.
            // These objects 'lost' on that combination page must be corrected for regarding the starting point in the dataobject list
            $correctedStartForObjects = $start - $totalItems;
        }

        // Objects
        if( $status=='Success' AND ($restrict=='objects' OR !$restrict)) {
            // Get the actual total for the dataObjects
            $testObjects = $this->filesystem->browse($rodsaccount, $path, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, 0);
            $status = $testObjects['status'];

            if ($status=='Success') {
                $totalItems += $testObjects['summary']['total'];

                // Actual selecting of wanted data for the view
                $objects = $this->filesystem->browse($rodsaccount, $path, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $correctedStartForObjects);

                $status = $objects['status'];
                $statusInfo = $objects['statusInfo'];

                if ($status == 'Success') {
//                $totalItems += $objects['summary']['total']; // add the shift that is lost in the total count due to 1 page with both Collections and Objects

                    if ($objects['summary']['returned'] > 0) {
                        foreach ($objects['rows'] as $row) {
                            if ($this->_allowRowWhenBrowsing($row, $interveningKeys) AND $totalItemsLeftInView) {
                                $filePath = str_replace($pathStart, '', $row['path']);
                                $rows[] = array(
                                    '<span data-path="' . urlencode($filePath) . '"><i class="fa fa-file-o" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename'], '/'))) . '</span>',
                                    date('Y-m-d H:i:s', $row['modify_time'])
                                );
                                $totalItemsLeftInView--;
                            }
                        }
                    }
                }
            }
        }

        // Situational error handling within generic context
        if ($status != 'Success') {
            $totalItems = 0;
            $rows = array();
        }

        $output = array('status' => $status,
	                'statusInfo' => $statusInfo,
                        'draw' => $draw,
			'recordsTotal' => $totalItems,
			'recordsFiltered' => $totalItems,
			'data' => $rows);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
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
            if (isset($row[$key])) {  // simply check the presence of this key and it restricts showing of this row
                $allowed = false;
                break;
            }
        }

        return $allowed;
    }

    /**
     *  Data functionality for selection dialog
     *  Main difference is that the row class is no longer 'browse' but 'browse-for-selection'
     *  This to evade collisions of classes when mutliple browsers are on one page
     *
     *  Todo: - filter vault when required
     *
     * @param int $restrict
     * @param string $interveneOnMetadataKeys
     *
     *
     *
     * $restrict offers the possibilty to distinguish collecting folders and / or files
     *
     * $interveneOnMetadataKeys is a string holding keys of metadata that must NOT be present. If present in a row, this data is excluded from presentation
     *
     */
    public function selectData( $restrict = 0, $interveneOnMetadataKeys = '')
    {
        //return -1; exit;

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

        // Generic error handling intialisation
        $status = 'Success';
        $statusInformation = '';

        $totalItemsLeftInView = $length;

        $totalItems = 0;

        // Collections
        if ($restrict=='collections' OR !$restrict) {
            // Get the actual total for the Collections
            $testCollections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, 0);
            $status = $testCollections['status'];
            if ($status=='Success') {
                $totalItems = $testCollections['summary']['total'];

                $icon = 'fa-folder-o';
                $collections = $this->filesystem->browse($rodsaccount, $path, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

                $status = $collections['status'];
                $statusInfo = $collections['statusInfo'];

                if ($status == 'Success') {
                    // @todo: does not always produce a total - more specifically when there is no resulting set of data!
                    //$totalItems += $collections['summary']['total'];
                    if ($collections['summary']['returned'] > 0) {
                        foreach ($collections['rows'] as $row) {
                            if ($this->_allowRowWhenBrowsing($row, $interveningKeys)) {
                                $filePath = str_replace($pathStart, '', $row['path']);
                                if (!strpos(urlencode($filePath),'2Fvault')==1) { // remove vault folders as these are not allowed as destination. To be made optional
                                    $rows[] = array(
                                        '<span class="browse-select" data-path="' . urlencode($filePath) . '"><i class="fa ' . $icon . '" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename'], '/'))) . '</span>',
                                        date('Y-m-d H:i:s', $row['modify_time'])
                                    );

                                    $totalItemsLeftInView--;
                                }
                            }
                        }
                    }
                }
            }
        }

        $correctedStartForObjects = 0;
        if ($start>$totalItems) {
            // add the shift following the final collection page holding both Collections and Objects.
            // These objects 'lost' on that combination page must be corrected for regarding the starting point in the dataobject list
            $correctedStartForObjects = $start - $totalItems;
        }

        // Objects
        if( $status=='Success' AND ($restrict=='objects' OR !$restrict)) {
            // Get the actual total for the dataObjects
            $testObjects = $this->filesystem->browse($rodsaccount, $path, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, 0);
            $status = $testObjects['status'];

            if ($status=='Success') {
                $totalItems += $testObjects['summary']['total'];

                // Actual selecting of wanted data for the view
                $objects = $this->filesystem->browse($rodsaccount, $path, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $correctedStartForObjects);

                $status = $objects['status'];
                $statusInfo = $objects['statusInfo'];

                if ($status == 'Success') {
//                $totalItems += $objects['summary']['total']; // add the shift that is lost in the total count due to 1 page with both Collections and Objects

                    if ($objects['summary']['returned'] > 0) {
                        foreach ($objects['rows'] as $row) {
                            if ($this->_allowRowWhenBrowsing($row, $interveningKeys) AND $totalItemsLeftInView) {
                                $filePath = str_replace($pathStart, '', $row['path']);
                                $rows[] = array(
                                    '<span data-path="' . urlencode($filePath) . '"><i class="fa fa-file-o" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename'], '/'))) . '</span>',
                                    date('Y-m-d H:i:s', $row['modify_time'])
                                );
                                $totalItemsLeftInView--;
                            }
                        }
                    }
                }
            }
        }

        // Situational error handling within generic context
        if ($status != 'Success') {
            $totalItems = 0;
            $rows = array();
        }

        $output = array('status' => $status,
	                'statusInfo' => $statusInfo,
                        'draw' => $draw,
			'recordsTotal' => $totalItems,
			'recordsFiltered' => $totalItems,
			'data' => $rows);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }


    public function change_folder_status()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $this->load->model('Folder_Status_model');
        $status = $this->input->post('status');
        $path = $this->input->post('path');
        $output = array();

        $beforeAction = 'LOCKED';

        if ($status == 'LOCKED') {
            // Lock Folder
            $result = $this->Folder_Status_model->lock($pathStart . $path);
            $beforeAction = 'UNLOCKED';
        } else if ($status == 'UNLOCKED') {
            // Unlock folder
            $result = $this->Folder_Status_model->unlock($pathStart . $path);
        }

        $output = array('status' => $result['*status'],
	                'statusInfo' => $result['*statusInfo']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
}
