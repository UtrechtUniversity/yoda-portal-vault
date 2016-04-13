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

        $this->load->model('yodaprods');
        $this->load->model('study');
        $this->load->model('rodsuser');
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
            return showErrorOnPermissionExceptionByValidUser($this,'ACCESS_INVALID_STUDY','intake/intake/index');
        }

        // study is validated. Put in session.
        $this->session->set_userdata('studyID',$studyID);

        // get study dependant rights for current user.
        $this->permissions = $this->study->getIntakeStudyPermissions($studyID);

        if(!($this->permissions->assistant OR $this->permissions->manager)){
            // No access rights for this particular module
            return showErrorOnPermissionExceptionByValidUser($this,'ACCESS_NO_ACCESS_ALLOWED', 'intake/intake/index');
        }

        $this->data['permissions']=$this->permissions;
        $this->data['studies']=$this->studies;
        $this->data['studyID']=$studyID;

        // study dependant intake path.
        $this->intake_path = '/' . $this->config->item('rodsServerZone') . '/home/' . $this->config->item('INTAKEPATH_StudyPrefix') . $studyID;
        $this->data['intakePath'] = $this->intake_path;

        $dir = new ProdsDir($this->rodsuser->getRodsAccount(), $this->intake_path);

        $validFolders = array();
        foreach($dir->getChildDirs() as $folder){
            $validFolders[]=$folder->getName();
        }

        $this->data['selectableScanFolders'] = $validFolders;  // folders that can be checked for scanning

        $studyFolder = urldecode($studyFolder);

        if($studyFolder AND !in_array($studyFolder,$validFolders)){
            // invalid folder for this study
            return showErrorOnPermissionExceptionByValidUser($this, 'ACCESS_INVALID_FOLDER', 'intake/intake/index');
        }

        if($studyFolder){ // change the actual folder when person selected a different point of reference.
            $dir = new ProdsDir($this->rodsuser->getRodsAccount(), $this->intake_path . '/' . $studyFolder);
        }

        // $dataSets = array();
        // $this->dataset->getIntakeDataSets($this->intake_path . ($studyFolder?'/'.$studyFolder:''), $dataSets);
        // $this->data['dataSets'] = $dataSets;

        // // get the total of dataset files
        // $totalDatasetFiles = 0;
        // foreach($dataSets as $set){
        //     $totalDatasetFiles += $set->objects;
        // }
        // $this->data['totalDatasetFiles'] = $totalDatasetFiles;

        // $dataErroneousFiles = array();
        // $this->dataset->getErroneousIntakeFiles($this->intake_path . ($studyFolder?'/'.$studyFolder:''), $dataErroneousFiles);
        // $this->data['dataErroneousFiles'] = $dataErroneousFiles;

        // $totalFileCount = $this->dataset->getIntakeFileCount($this->intake_path . ($studyFolder?'/'.$studyFolder:''));
        // $this->data['totalFileCount'] = $totalFileCount;

        // user has the rights to view info. He/she passed the study validation
        // This as opposed to _invalidIntakeParametersByValidUser() - handling
        $this->data['userIsAllowed'] = TRUE;

        $this->data['content'] = 'intake/intake/index';

        $this->data['studyID'] = $studyID;
        $this->data['studyFolder'] = $studyFolder;

        $this->data['title'] = 'Study ' . $studyID . ($studyFolder?'/'.$studyFolder:'');

        $this->load->view('common-start', array(
            'styleIncludes' => array('css/group-manager.css'),
            'scriptIncludes' => array('js/group-manager.js'),
            'activeModule'   => 'intake-ilab',
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->load->view('intake', $this->data);

        $this->load->view('common-end');
    }

}