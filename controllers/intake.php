<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Intake extends MY_Controller
{
   
    public function __construct()
    {
        parent::__construct();

        $module = array();
        include __DIR__ . "/../config/module.php";
        $module['path'] = sprintf("%s/intake", $module['name']);
        $module['basepath'] = sprintf("%s%s", base_url(), $module['name']);
        $this->module = $module;

        // initially no rights for any study
        $this->permissions = (object)array(
            'assistant' => FALSE,
            'manager'   => FALSE,
        );

        $this->data['userIsAllowed'] = TRUE;

        // TODO: Auto load doesn't work in module?
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
        $studyID = $this->validateStudyPermission($studyID);

        // study dependant intake path.
        $this->intake_path = sprintf(
                "/%s/home/%s%s", 
                $this->config->item('rodsServerZone'),
                $this->config->item('intake-prefix'),
                $studyID
            );

        $validFolders = $this->validateStudyFolder($studyID, $studyFolder);
        
        // Prepare data for view
        $dataArr = array(
                "hasStudies" => $this->studies[0],
                "rodsaccount" => $this->rodsuser->getRodsAccount(),
                "permissions" => $this->permissions,
                "studies" => $this->studies,
                "studyID" => $studyID,
                "title" => sprintf(
                    "%s %s%s",
                    ucfirst(lang('INTAKE_STUDY')),
                    $studyID,
                    ($studyFolder ? '/'. $studyFolder : '')
                ),
                "studyID" => $studyID,
                "studyFolder" => $studyFolder,
                "intakePath" => $this->intake_path,
                "currentDir" => $this->current_path,
                "content" => $this->module['name'] . '/file_overview',
                "directories" => $this->dir->getChildDirs(),
                "files" => $this->dir->getChildFiles(),
                "selectableScanFolders" => $validFolders,
            );
        $this->data = array_merge($this->data, $dataArr);

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

    private function validateStudyPermission($studyID) {
        // studyID handling from session info
        if(!$studyID){
            if($tempID = $this->session->userdata('studyID') AND $tempID){
                $studyID = $tempID;
            } else if($this->studies[0]){
                $studyID = $this->studies[0];
            }
        }

        // get study dependant rights for current user.
        $this->permissions = $this->study->getIntakeStudyPermissions($studyID);

        if(!$this->studies[0]) {
            displayMessage($this, lang('ERROR_NO_INTAKE_ACCESS'), true);
            return false;
        }
        else if(!$this->study->validateStudy($this->studies, $studyID)){
            $message = sprintf(lang('ERR_STUDY_NO_EXIST'), $studyID, $this->getRedirect(), $this->studies[0]);
            displayMessage($this, $message, true);
            return false;
        } else if(!($this->permissions->assistant OR $this->permissions->manager)){
            // If the user doesn't have acces, this study doesn't appear in $this->studies,
            // so the user won't get through the previous test, right?
            $message = sprintf(lang('ERR_STUDY_NO_ACCESS'), $studyID, $this->getRedirect(), $this->studies[0]);
            displayMessage($this, $message, true);
            return false;
        }

        // study is validated. Put in session.
        $this->session->set_userdata('studyID', $studyID);

        return $studyID;
    }

    private function validateStudyFolder($studyID, $studyFolder) {
        $this->current_path = $studyFolder ? sprintf("%s/%s", $this->intake_path, $studyFolder) : $this->intake_path;

        $rodsaccount = $this->rodsuser->getRodsAccount();
        $this->dir = new ProdsDir($rodsaccount, $this->intake_path);

        $validFolders = array();
        foreach($this->dir->getChildDirs() as $folder){
            array_push($validFolders, $folder->getName());
        }

        if($studyFolder AND !in_array($studyFolder, $validFolders)){
            // invalid folder for this study
            $message = sprintf(
                    lang('ERROR_FOLDER_NOT_IN_STUDY'),
                    $studyFolder,
                    $studyFolder,
                    $studyID,
                    $this->getRedirect($studyID),
                    $studyID
                );
            displayMessage($this, $message, true);
            return false;
        } else {
            $this->dir = new ProdsDir($rodsaccount, $this->current_path);
        }

        return $validFolders;
    }

    public function getModuleConfig()
    {
        return $this->module;
    }

    private function getRedirect($studyID = '') {
        $url = "/" . $this->module["name"] . "/intake/index";
        if(!empty($this->studies)) {
            $url .= "/" . ($studyID ? $studyID : $this->studies[0]);
        }
        return $url;
    }

}