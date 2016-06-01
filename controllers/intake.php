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
        $this->load->model('dataset');
        $this->load->model('debug');
        $this->load->model('filesystem');
        $this->load->model('study');
        $this->load->model('rodsuser');
        $this->load->model('metadata');
        $this->load->helper('date');
        $this->load->helper('language');
        $this->load->helper('intake');
        $this->load->helper('form');
        $this->load->language('intake');
        $this->load->language('errors');

        $this->studies = $this->dataset->getStudies($this->rodsuser->getRodsAccount());
    }

    /**
     * Method to get the module configuration
     * from the config/module.php file.
     * The $module parameter is set in yoda as well,
     * so loading that data may cause problems. The root
     * controller of this module, however, does not have
     * the yoda portal $module parameter in its scope.
     * @return  Array containing module configuration,
     *          as well as the basepath and path keys
     */
    public function getModuleConfig()
    {
        return $this->module;
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

        $urls = (object) array(
            "site" => site_url(), 
            "module" => $this->getModuleBase()
        );
        
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
                "studyFolder" => $studyFolder,
                "intakePath" => $this->intake_path,
                "currentDir" => $this->current_path,
                "content" => $this->module['name'] . '/file_overview',
                "meta_editor" => $this->module['name'] . '/edit_meta',
                "directories" => $this->dir->getChildDirs(),
                "files" => $this->dir->getChildFiles(),
                "selectableScanFolders" => $validFolders,
                "currentViewLocked" => $this->currentViewLocked,
                "currentViewFrozen" => $this->currentViewFrozen,
                "url" => $urls
            );
        $this->data = array_merge($this->data, $dataArr);

        $this->load->view('common-start', array(
            'styleIncludes' => array('css/datatables.css', 'css/intake.css'),
            'scriptIncludes' => array('js/datatables.js', 'js/intake.js'),
            'activeModule'   => $this->module['name'],
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));
        $this->load->view('intake', $this->data);
        $this->load->view('common-end');
    }

    /**
     * Private method that validates the study permissions for
     * the current user and prepares data in the study
     * @param $studyID      The identifying name of the study
     * @return              false (bool) if the study is not valid,
     *                      or the user doesn't have permission,
     *                      the study ID otherwise
     */
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

    /**
     * Method that validates a directory inside a study folder
     * and prepares the data for the view
     * @param $studyID      The identifying name of the study
     * @param $studyFolder  A name of a directory, which, if valid
     *                      resides in the study root directory
     * @return              false if the folder is not valid,
     *                      an array of valid study folders otherwise
     **/
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
            $currentViewLocked = $this->dataset->getLockedStatus($rodsaccount, $this->current_path);
            $this->currentViewLocked = $currentViewLocked['locked'];
            $this->currentViewFrozen = $currentViewLocked['frozen'];
        }

        return $validFolders;
    }

    /**
     * Method that generates a redirect URL if the user has permissions
     * to view other studies
     * @param $studyID (optional)       The identifying name of the study 
     *                                  that should be redirected to.
     *                                  The first of the valid studies
     *                                  is used if not provided
     * @return  A relative URL that points back to the index of this
     *          module and to a valid study, if one is available
     */
    private function getRedirect($studyID = '') {
        // $url = $this->getModuleBase() . "intake/index";
        $segments = array($this->module['name'], "intake", "index");
        if(!empty($this->studies)) {
            // $url .= "/" . ($studyID ? $studyID : $this->studies[0]);
            array_push($segments, $studyID ? $studyID : $this->studies[0]);
        }
        return site_url($segments);
    }

    private function getModuleBase() {
        return site_url($this->module['name']);
    }

}