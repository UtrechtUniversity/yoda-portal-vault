<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Intake extends MY_Controller
{
   
    public function __construct()
    {
        parent::__construct();

        // initially no rights for any study
        $this->permissions = (object)array(
            'assistant' => FALSE,
            'manager'   => FALSE,
        );

        $this->load->model('study');
        $this->load->model('yodaprods');
        $this->load->model('dataset2');
        $this->load->model('rodsuser');
        $this->load->helper('date');
        $this->load->helper('language');
        $this->load->helper('intake');
        $this->load->language('intake');
        $this->load->language('errors');

        $this->studies = $this->yodaprods->getStudies($this->rodsuser->getRodsAccount());
    }

    /*
     * @param string $studyID
     * @param string $studyFolder
     *
     * */
    public function index($studyID='', $studyFolder='')
    {
        // studyID handling from session info
        if(!$studyID){
            if($tempID = $this->session->userdata('studyID') AND $tempID){
                $studyID = $tempID;
            }
        }

        if(!$this->study->validateStudy($this->studies, $studyID)){
            // var_dump("Eerste");
            // return showErrorOnPermissionExceptionByValidUser($this,'ACCESS_INVALID_STUDY','intake/intake/index');
            $link = $module['name'] . '/intake/index/';
            $link .= sizeof($studies) > 0 ? $studies[0] : "";
            $this->session->set_flashdata('error', true);
            $this->session->set_flashdata('alert', 'danger');
            $this->session->set_flashdata('message', sprintf(lang('ERR_STUDY_NO_EXIST'), $studyID, $link));
            $this->data['userIsAllowed'] = false; // will prevent access to the view part with all the relevant data
        }

        // study is validated. Put in session.
        $this->session->set_userdata('studyID', $studyID);

        // get study dependant rights for current user.
        $this->permissions = $this->study->getIntakeStudyPermissions($studyID);

        if(!($this->permissions->assistant OR $this->permissions->manager)){
            // No access rights for this particular module
            var_dump("Tweede");
            $this->session->set_flashdata('danger', true);
            $this->data['userIsAllowed'] = false;
            // return showErrorOnPermissionExceptionByValidUser($this,'ACCESS_NO_ACCESS_ALLOWED', 'intake/intake/index');
        }

        $this->data['permissions']=$this->permissions;
        $this->data['studies']=$this->studies;
        $this->data['studyID']=$studyID;

        // study dependant intake path.
        $this->intake_path = '/' . $this->config->item('rodsServerZone') . '/home/' . $this->config->item('intake-prefix') . $studyID;
        $this->data['intakePath'] = $this->intake_path;

        $this->data['currentDir'] = $this->intake_path;
        if($studyFolder != '') $this->data['currentDir'] .= "/" . $studyFolder;

        $rodsaccount = $this->rodsuser->getRodsAccount();
        $this->data['rodsaccount'] = $rodsaccount;
        $dir = new ProdsDir($rodsaccount, $this->intake_path);

        $validFolders = array();
        foreach($dir->getChildDirs() as $folder){
            array_push($validFolders, $folder->getName());
        }

        if($studyFolder){ // change the actual folder when person selected a different point of reference.
            $dir = new ProdsDir($rodsaccount, $this->intake_path . '/' . $studyFolder);
        }

        $this->data['directories'] = $dir->getChildDirs();
        $this->data['files'] = $dir->getChildFiles();

        $this->data['selectableScanFolders'] = $validFolders;  // folders that can be checked for scanning

        $studyFolder = urldecode($studyFolder);

        if($studyFolder AND !in_array($studyFolder,$validFolders)){
            // invalid folder for this study
            return showErrorOnPermissionExceptionByValidUser($this, 'ACCESS_INVALID_FOLDER', 'intake/intake/index');
        }

       
        $this->data['userIsAllowed'] = TRUE;

        $this->data['content'] = 'intake-ilab/file_overview';

        $this->data['studyID'] = $studyID;
        $this->data['studyFolder'] = $studyFolder;

        $this->data['title'] = 'Study ' . $studyID . ($studyFolder?'/'.$studyFolder:'');

        $this->load->view('common-start', array(
            'styleIncludes' => array('css/datatables.css', 'css/intake.css'),
            'scriptIncludes' => array('js/datatables.js', 'js/intake.js'),
            'activeModule'   => 'intake-ilab',
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->load->view('intake', $this->data);

        $this->load->view('common-end');
    }

}