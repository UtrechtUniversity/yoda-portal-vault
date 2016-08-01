<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Study extends CI_Model {
    // Permissions - study dependant
    public $PERM_GroupAssistant ='grp-intake-'; // prefix of what is to be extended with a study-id
    public $PERM_GroupDataManager = 'grp-datamanager-';

    public $ROLE_Assistant = 'assistant';
    public $ROLE_Manager = 'manager';

    // validateIntakeStudyPermissions constants
    // This is merely symbolic for now. Nothing is actually done with this.
    const PERMISSION_ERROR_Invalid_Study = 1;
    const PERMISSION_ERROR_No_Permission = 2;

    public function __construct()
    {
        parent::__construct();
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
                if($studyID == $study){ // passed study has to match an existing one
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
        return (object)array(
            'assistant' => get_instance()->dataset->isGroupMember(
                $this->rodsuser->getRodsAccount(), 
                $this->PERM_GroupAssistant . $studyID ,  
                get_instance()->rodsuser->getUsername()
            ),
            'administrator' => get_instance()->dataset->isGroupManager(
                $this->rodsuser->getRodsAccount(),
                $this->PERM_GroupAssistant . $studyID,
                get_instance()->rodsuser->getUsername()
            ),
            'manager' => get_instance()->dataset->isGroupMember(
                $this->rodsuser->getRodsAccount(), 
                $this->PERM_GroupDataManager . $studyID, 
                get_instance()->rodsuser->getUsername()
            ),
        );
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