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

    public function getGroupMembers($iRodsAccount, $groupName) {
        $ruleBody = "
        myRule {
            uuGroupGetMembers(*groupName, *members);
            uuJoin(',', *members, *groupUsers);
        }";

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array("*groupName" => $groupName),
                array("*groupUsers")
            );

            $result = $rule->execute();

            return explode(",", $result["*groupUsers"]);
        } catch(RODSException $e) {
            return false;
        }

        return false;
    }
}