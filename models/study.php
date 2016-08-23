<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Study extends CI_Model {
    // Permissions - study dependant
    public $PERM_GroupDataManager = 'grp-datamanager-';

    public $ROLE_Assistant = ""; //$this->config->item('role:contributor'); // 'assistant';
    public $ROLE_Manager = 'datamanager';

    // validateIntakeStudyPermissions constants
    // This is merely symbolic for now. Nothing is actually done with this.
    const PERMISSION_ERROR_Invalid_Study = 1;
    const PERMISSION_ERROR_No_Permission = 2;

    public function __construct()
    {
        parent::__construct();
        $this->ROLE_Assistant = $this->config->item('role:contributor');
        $this->load->model('dataset');
        $this->load->model('rodsuser');
    }

    static public function validateStudy($studies, &$studyID)
    {
        if(!$studyID){
            foreach($studies as $study){ // determination of default study
                $studyID = $study;
                return TRUE;
            }
        }
        else{
            foreach($studies as $study){
                if($studyID === $study){ // passed study has to match an existing one
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * @param $studyID
     *
     * @return object
     */
    public function getIntakeStudyPermissions($studyID){
        $rodsaccount = $this->rodsuser->getRodsAccount();
        return array(
            $this->config->item('role:contributor') => ($studyID !== "") ? get_instance()->dataset->isGroupMember(
                $rodsaccount, 
                $this->config->item('intake-prefix') . $studyID ,  
                get_instance()->rodsuser->getUsername()
            ) : false,
            $this->config->item('role:administrator') => ($studyID !== "") ? get_instance()->dataset->isGroupManager(
                $rodsaccount,
                $this->config->item('intake-prefix') . $studyID,
                get_instance()->rodsuser->getUsername()
            ) : false,
            'datamanager' => ($studyID !== "") ? get_instance()->dataset->isGroupMember(
                $rodsaccount, 
                $this->PERM_GroupDataManager . $studyID, 
                get_instance()->rodsuser->getUsername()
            ) : false
        );
    }

    public function getPermissionsForLevel($depth, $studyID) {

        if($studyID !== "") {
            $level = 
                    sizeof($this->config->item('level-hierarchy')) > $depth ?
                    $this->config->item('level-hierarchy')[$depth] : 
                    $this->config->item('default-level');
        } else {
            $level = $this->config->item('base-level');
        }

        $permissions = $this->getIntakeStudyPermissions($studyID);

        $levelPermissions = array();

        $levelPermissions["canEditMeta"] = $level["metadata"] !== false && is_array($level["metadata"]) && array_key_exists("form", $level["metadata"]) && 
            (array_key_exists("canEdit", $level["metadata"]) && $level["metadata"]["canEdit"] !== false
                && array_key_exists($level["metadata"]["canEdit"], $permissions) && $permissions[$level["metadata"]["canEdit"]]
            );

        $levelPermissions["canViewMeta"] = $level["metadata"] !== false && is_array($level["metadata"]) && array_key_exists("form", $level["metadata"]) && 
            (array_key_exists("canView", $level["metadata"]) && $level["metadata"]["canView"] !== false 
                && array_key_exists($level["metadata"]["canView"], $permissions) && $permissions[$level["metadata"]["canView"]]
            );

        $levelPermissions["canSnapshot"] = 
            $level["canSnapshot"] !== false && 
            array_key_exists($level["canSnapshot"], $permissions) && 
            $permissions[$level['canSnapshot']];

        $levelPermissions["canArchive"] = 
            $level["canArchive"] !== false && 
            array_key_exists($level["canArchive"], $permissions) && 
            $permissions[$level['canArchive']];

        return (object)$levelPermissions;
    }

    public function getUsernameValid($iRodsAccount, $username) {
        $ruleBody = "
        myRule {
            *valid = uuUserNameIsValid(*name);
        }";

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array("*username" => $username),
                array("*valid")
            );

            $result = $rule->execute();
            return $result["*valid"] == true;
        } catch(RODSException $e) {
            return false;
        }
        return false;
    }

    public function getGroupMembers($iRodsAccount, $groupName, $showAdmins, $showUsers, $showReadonly) {
        $ruleBody = 
        'myRule {
            if(*showadmins == "1") {
                *showAdmins = true;
            } else {
                *showAdmins = false;
            }

            if(*showusers == "1") {
                *showUsers = true;
            } else {
                *showUsers = false;
            }

            if(*showreadonly == "1") {
                *showReadonly = true;
            } else {
                *showReadonly = false;
            }

            uuIiGetFilteredMembers(*groupName, *showAdmins, *showUsers, *showReadonly, *memberList)

            uuJoin(";", *memberList, *memberList);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*groupName" => $groupName,
                    "*showadmins" => $showAdmins,
                    "*showusers" => $showUsers,
                    "*showreadonly" => $showReadonly
                ),
                array("*memberList")
            );

            $result = $rule->execute();

            return explode(";", $result["*memberList"]);
        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
    }

    public function getDirectories(
        $iRodsAccount, 
        $showProjects, 
        $showStudies, 
        $showDatasets, 
        $requiresContribute, 
        $requiresManager
    ) {
        $ruleBody = 
        'myRule {
            if(*showprojects == "1") {
                *showProjects = true;
            } else {
                *showProjects = false;
            }

            if(*showstudies == "1") {
                *showStudies = true;
            } else {
                *showStudies = false;
            }

            if(*showdatasets == "1") {
                *showDatasets = true;
            } else {
                *showDatasets = false;
            }

            if(*requirescontribute == "1") {
                *requiresContribute = true;
            } else {
                *requiresContribute = false;
            }

            if(*requiresmanager == "1") {
                *requiresManager = true;
            } else {
                *requiresManager = false;
            }
            
            uuIiGetDirectories(
                *showProjects, 
                *showStudies, 
                *showDatasets, 
                *requiresContribute, 
                *requiresManager,
                *directoryList
            );

            uuJoin(";", *directoryList, *directoryList);
            #*directoryList = "lol;jolo";
            
        }';

        

        

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*showprojects" => $showProjects,
                    "*showstudies" => $showStudies,
                    "*showdatasets" => $showDatasets,
                    "*requirescontribute" => $requiresContribute,
                    "*requiresmanager" => $requiresManager
                ),
                array("*directoryList")
            );

            $result = $rule->execute();

            return explode(";", $result["*directoryList"]);
        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
    }
}