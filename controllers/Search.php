<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search extends MY_Controller
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
        $searchString = $filter;
        $searchStringEscaped = str_replace(
					array('\\', '%', '_', '`'),
					array('\\\\', '\\%','\\_', '\\`'),
					$filter);

        // Generic error handling intialisation
        $status = 'Success';
        $statusInfo = '';

        // Search / filename
        if ($type == 'filename') {
            $orderColumns = array(
                0 => 'DATA_NAME',
                1 => 'COLL_NAME'
            );
            $result = $this->filesystem->searchByName($rodsaccount, $path, $searchStringEscaped, "DataObject", $orderColumns[$orderColumn], $orderDir, $length, $start);

            $status = $result['status'];
            $statusInfo = $result['statusInfo'];

            if ($status == 'Success') {
                $totalItems += $result['summary']['total'];

                if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                    foreach ($result['rows'] as $row) {
                        $filePath = str_replace($pathStart, '', $row['parent']);
                        $rows[] = array(
                            '<i class="fa fa-file-o" aria-hidden="true"></i> ' . str_replace(' ', '&nbsp;', htmlentities(trim($row['basename']))),
                            '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities(trim($filePath, '/'))) . '</span>'
                        );
                    }
                }
            }
        }

        // Search / folder
        if ($type == 'folder') {
            $orderColumns = array(
                0 => 'COLL_NAME'
            );
            $result = $this->filesystem->searchByName($rodsaccount, $path, $searchStringEscaped, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

            $status = $result['status'];
            $statusInfo = $result['statusInfo'];

            if ($status == 'Success') {
                $totalItems += $result['summary']['total'];

                if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                    foreach ($result['rows'] as $row) {
                        $filePath = str_replace($pathStart, '', $row['path']);

                        //str_replace(' ', '&nbsp;', htmlentities( trim( $row['basename'], '/')))
                        $rows[] = array(
                            '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities(trim($filePath, '/'))) . '</span>'
                        );
                    }
                }
            }
        }

        // Search / metadata
        if ($type == 'metadata') {
            $orderColumns = array(
                0 => 'COLL_NAME'
            );
            $result = $this->filesystem->searchByUserMetadata($rodsaccount, $path, $searchString, $searchStringEscaped, "Collection", $orderColumns[$orderColumn], $orderDir, $length, $start);

            $status = $result['status'];
            $statusInfo = $result['statusInfo'];

            if ($status == 'Success') {
                $totalItems += $result['summary']['total'];
                $this->load->model('Metadata_form_model');
                if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                    $categoryFormLabels = array();
                    foreach ($result['rows'] as $row) {
                        $filePath = str_replace($pathStart, '', $row['path']);
                        $matchParts = array();
                        $i = 1;

                        // Addition to get the correct labels from formelements.xml
                        // And do this in an efficient way for each category only once!
                        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $row['path']);

                        $pathCategory = $formConfig['category'];

                        if (!isset($categoryFormLabels[$pathCategory])) { // forms (and its labels) are category dependant. Fetch it only once for efficiency purposes
                            $formLabels = $this->Metadata_form_model->getFormElementLabels($rodsaccount, $formConfig);
                            $categoryFormLabels[$pathCategory] = $formLabels;
                        }

                        foreach ($row['matches'] as $match) {
                            foreach ($match as $k => $value) {

                                // convert $k to an index as known within formelements.xml
                                $labelIndex = str_replace( array(' '), '_', $k);
                                $labelIndex = preg_replace("/_[0-9]_/", "_", $labelIndex);
                                $label = isset($categoryFormLabels[$pathCategory][$labelIndex]) ? $categoryFormLabels[$pathCategory][$labelIndex] : $k;

                                $matchParts[] = $label . ': ' . $value;
                                if ($i == 5) {
                                    break 2;
                                }
                                $i++;
                            }
                        }

                        $rows[] = array(
                            '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities(trim($filePath, '/'))) . '</span>',
                            '<span class="matches" data-toggle="tooltip" title="' . htmlentities(implode(', ', $matchParts)) . ($i == 5 ? '...' : '') . '">' . count($row['matches']) . ' field(s)</span>'
                        );
                    }
                }
            }
        }

        // Search / status
        if ($type == 'status') {
            $orderColumns = array(
                0 => 'COLL_NAME'
            );

            $filter = explode(":", $filter);
            $statusType = $filter[0];
            $statusName = $filter[1];

            if ($statusType == "vault") {
                $result = $this->filesystem->searchByOrgMetadata($rodsaccount, $path, $statusName, "vault_status", $orderColumns[$orderColumn], $orderDir, $length, $start);
            } else {
                $result = $this->filesystem->searchByOrgMetadata($rodsaccount, $path, $statusName, "status", $orderColumns[$orderColumn], $orderDir, $length, $start);
            }
            $status = $result['status'];
            $statusInfo = $result['statusInfo'];

            if ($status=='Success') {
                $totalItems += $result['summary']['total'];

                if (isset($result['summary']) && $result['summary']['returned'] > 0) {
                    foreach ($result['rows'] as $row) {
                        $filePath = str_replace($pathStart, '', $row['path']);
                        $rows[] = array(
                            '<span class="browse-search" data-path="' . urlencode($filePath) . '">' . str_replace(' ', '&nbsp;', htmlentities(trim($filePath, '/'))) . '</span>'
                        );
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
            $this->session->unset_userdata('research-search-status-value');
        }


        $this->session->set_userdata('research-search-type', $type);
        $this->session->set_userdata('research-search-start', 0);
    }
}
