<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Metadata extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        //$this->load->model('filesystem');
        $this->load->model('rodsuser');

        $this->load->library('module', array(__DIR__));
        $this->load->library('pathlibrary');
    }

    public function form()
    {
        $this->load->model('Metadata_model');
        $this->load->model('Metadata_form_model');
        $this->load->model('filesystem');


        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;
        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $userType = $formConfig['userType'];
        $elements = $this->Metadata_form_model->getFormElements($rodsaccount, $formConfig);

        if ($elements) {
            $this->load->library('metadataform');

            //$form = $this->metadataform->load($elements, $metadata);
            $form = $this->metadataform->load($elements);
            if ($userType == 'reader') {
                $form->setPermission('read');
            } else {
                $form->setPermission('write');
            }
            // figure out the number of mandatory fields and how many actually hold data
            $form->calculateMandatoryCompleteness($elements);

            $metadataExists = false;
            $cloneMetadata = false;
            if ($formConfig['hasMetadataXml'] == 'true') {
                $metadataExists = true;
            }

            if ($formConfig['parentHasMetadataXml'] == 'true') {
                $cloneMetadata = true;
            }
        } else {
            $form = null;
            $metadataExists = false;
            $cloneMetadata = false;
        }
        $this->load->view('common-start', array(
            'styleIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.css',
                'lib/font-awesome/css/font-awesome.css',
                'lib/sweetalert/sweetalert.css',
                'lib/select2/css/select2.min.css',
                'css/metadata/form.css',
            ),
            'scriptIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.js',
                'lib/sweetalert/sweetalert.min.js',
                'lib/select2/js/select2.min.js',
                'js/metadata/form.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->data['form'] = $form;
        $this->data['path'] = $path;
        $this->data['fullPath'] = $fullPath;
        $this->data['userType'] = $userType;
        $this->data['metadataExists'] = $metadataExists;
        $this->data['cloneMetadata'] = $cloneMetadata;

        $this->load->view('metadata/form', $this->data);
        $this->load->view('common-end');

    }

    function store()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('Metadata_form_model');

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;
        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        $result = $this->Metadata_form_model->processPost($rodsaccount, $formConfig);


        return redirect('research/metadata/form?path=' . $path, 'refresh');
    }

    function delete()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('filesystem');
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $result = $this->filesystem->removeAllMetadata($rodsaccount, $fullPath);

        if ($result) {
            return redirect('research/browse?dir=' . $path, 'refresh');
        } else {
            return redirect('research/metadata/form?path=' . $path, 'refresh');
        }
    }

    function clone_metadata()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('filesystem');
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        if ($formConfig['parentHasMetadataXml'] == 'true') {
            $xmlPath = $formConfig['metadataXmlPath'];
            $xmlParentPath = $formConfig['parentMetadataXmlPath'];

            $result = $this->filesystem->cloneMetadata($rodsaccount, $xmlPath, $xmlParentPath);
        }

        return redirect('research/metadata/form?path=' . $path, 'refresh');
    }

    /*
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
    */


}