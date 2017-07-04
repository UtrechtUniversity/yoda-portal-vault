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
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
                'js/revision.js',
                'js/search.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->data['items'] = $this->config->item('revision-items-per-page');
        $this->data['dlgPageItems'] = $this->config->item('revision-dialog-items-per-page');

        $this->data['filter'] = $this->input->get('filter');

        // Set basic search params
        $this->session->set_userdata(
            array(
                'research-search-term' => $this->input->get('filter'),
                'research-search-start' => 0,
                'research-search-type' => 'revision',
                'research-search-order-dir' => 'ASC',
                'research-search-order-column' => 0
            )
        );
        $searchTerm = $this->input->get('filter');
        $searchType = 'revision';
        $showStatus = false;
        $showTerm = true;
        $searchStart = 0;
        $searchItemsPerPage = $this->config->item('search-items-per-page');
        $searchData = compact('searchTerm', 'searchType', 'searchStart', 'showStatus', 'showTerm', 'searchItemsPerPage');
        $this->data['searchHtml'] = $this->load->view('search', $searchData, true);

        $this->load->view('revision', $this->data);
        $this->load->view('common-end');
    }

    /**
     * @param $revisionId
     * @param string $overwrite - {'no', 'yes'}

     * Restore a revision based upon its revision-id
     *
     * This process starts from the folder selection dialog.
     *
     * Initially it is not allowed to overwrite a file by the same name in the selected restoration collection.
     * If this occurs, the user gets prompted to overwrite of or not (place next to file with an extra timestamp to be able to distinguish the files)
     *
     *
     *
     */
    public function restore($revisionId, $overwriteFlag='restore_no_overwrite')
    {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);

        $this->output->enable_profiler(FALSE);
        $this->output->set_content_type('application/json');

        $targetDir = $this->input->get('targetdir');
        $newFileName = $this->input->get('newFileName'); // new file name as entered by user when option is 'restore_next_to'

        // Characters / and \ will invoke creation of a new folder.
        if (strpos($newFileName,'/')!==FALSE OR strpos($newFileName,'\'')!==FALSE) {
            $output = array(
                'status' => 'PROMPT_NewFolderNotAllowed',
                'statusInfo' => ''
            );

            echo json_encode($output);
            return;
        }


        $path = $pathStart;
        if (!empty($targetDir)) {
            $path .= $targetDir;
        }

        $response = $this->revisionmodel->restoreRevision($rodsaccount, $path, $revisionId, $overwriteFlag, $newFileName);

        // default front end state
        $frontEndState = 'UNRECOVERABLE';
        $statusInfo = '(999) Unknown error - no defined state';

        switch ($response['status']) {
            case 'Unrecoverable':
                $statusInfo = $response['statusInfo'];
                $frontEndState = 'UNRECOVERABLE';
                break;
            case 'RevisionNotFound':
                $statusInfo = '(100) The selected revision was not found could not be found. Please select another.';
                $frontEndState = 'UNRECOVERABLE';
                break;
            case 'TargetPathDoesNotExist':
                //$statusInfo = '';
                $frontEndState = 'PROMPT_SelectPathAgain';
                break;
            case 'TargetPathLocked':
                $frontEndState = 'PROMPT_TargetPathLocked';
                break;
            case 'FileExistsEnteredByUser':
                $frontEndState = 'PROMPT_FileExistsEnteredByUser';
                break;
            case 'VaultNotAllowed':
                $frontEndState = 'PROMPT_VaultNotAllowed';
                break;
            case 'FileExists':
                $frontEndState = 'PROMPT_Overwrite';
                break;
            case 'PermissionDenied':
                $frontEndState = 'PROMPT_PermissionDenied';
                break;

            case 'Success':
                $frontEndState = 'SUCCESS';
                break;
        }

        $output = array(
            'status' => $frontEndState,
            'statusInfo' => $statusInfo
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

        // Generic error handling intialisation
        $status = 'Success';
        $statusInfo = '';

        $searchArgument = $this->input->get('searchArgument');
        // $searchArgument is changed as iRods cannot handle '%' and '_' and \
        $searchArgument = str_replace(array('\\', '%', '_'),
            array('\\\\', '\\%','\\_'),
            $searchArgument);

        $totalItems = 0;
        $rows = array();

        $result = $this->revisionmodel->searchByString($rodsaccount, $searchArgument, $orderColumns[$orderColumn], $orderDir, $length, $start);

        $status = $result['status'];
        $statusInfo = $result['statusInfo'];

        if ($status=='Success') {
            $totalItems = $result['summary']['total'];

            if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                foreach ($result['rows'] as $row) {
                    $filePath = str_replace($pathStart, '', $row['originalPath']);

                    $rows[] = array(
                        '<span data-path="' . urlencode($filePath) . '" data-collection-exists="' . $row['collectionExists'] . '">' . str_replace(' ', '&nbsp;', htmlentities(trim($filePath, '/'))) . '</span>',
                        $row['numberOfRevisions']
                    );
                }
            }
        }

        $output = array('status' => $status, 'statusInfo' => $statusInfo,
            'draw' => $draw, 'recordsTotal' => $totalItems, 'recordsFiltered' => $totalItems, 'data' => $rows);

        echo json_encode($output);
    }

    /**
     * Present the revisions of the specific objectId if permitted
     *
     * The table holding all revisions that was searched for holds whether a revision collection exists (at moment of population)
     * This is passed as parameter 'collection_exists' in url
     */
    public function detail()
    {
        $path = $this->input->get('path');
        $collectionExists = ($this->input->get('collection_exists') == 'true');


        $rodsaccount = $this->rodsuser->getRodsAccount();
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $fullPath = $pathStart . $path;

        $revisionFiles = $this->revisionmodel->listByPath($rodsaccount, $fullPath);

        // For the revision restore dialog it has to be known whether this path still exists.
        // If so, browsing will start from that (original) location.
        // If not, browsing will start from HOME
        $pathInfo = pathinfo($path);
        $revisionStartPath = $pathInfo['dirname'];

        $parts = pathinfo($path);
        $orgFileName = $parts['basename'];

        if (!$collectionExists) {
            $revisionStartPath = '';
        }

        $htmlDetail =  $this->load->view('revisiondetail',
            array(
                'orgFileName' => $orgFileName,
                'collectionExists' => $collectionExists,
                'revisionStartPath' => $revisionStartPath,
                'revisionFiles' => $revisionFiles,
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