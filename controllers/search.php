<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search extends MY_Controller
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

    public function data()
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

    public function unset_session()
    {
        $this->session->unset_userdata('research-search-term');
        $this->session->unset_userdata('research-search-start');
        $this->session->unset_userdata('research-search-type');
        $this->session->unset_userdata('research-search-order-dir');
        $this->session->unset_userdata('research-search-order-column');
        $this->session->unset_userdata('research-search-status-value');
    }

    public function set_session()
    {
        $value = $this->input->get('value');
        $type = $this->input->get('type');
        if ($type == 'status') {
            $this->session->set_userdata('research-search-status-value', $value);
        } else {
            $this->session->set_userdata('research-search-term', $value);
        }


        $this->session->set_userdata('research-search-type', $type);
        $this->session->set_userdata('research-search-start', 0);
    }
}