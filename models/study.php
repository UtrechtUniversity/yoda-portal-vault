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
            'assistant' => get_instance()->yodaprods->isGroupMember($this->rodsuser->getRodsAccount(), $this->PERM_GroupAssistant . $studyID ,  get_instance()->rodsuser->getUsername()),
            'manager'   => get_instance()->yodaprods->isGroupMember($this->rodsuser->getRodsAccount(), $this->PERM_GroupDataManager . $studyID, get_instance()->rodsuser->getUsername()),
        );
    }

    /**
     * @param       $studyID
     * @param array $permissionsAllowed
     * @param       $errorMessage
     *
     * @return bool
     *
     * Check if person has permissions for this study.
     * One can pass an array of permissions that will return a valid access.
     */
    public function validateIntakeStudyPermissions($studyID, $permissionsAllowed=array(), &$errorMessage){
        // user allowed to go into this study?
        if(!$this->validateStudy(get_instance()->studies, $studyID)){
            $errorMessage = self::PERMISSION_ERROR_Invalid_Study;
            return FALSE;
        }

        get_instance()->permissions = $this->getIntakeStudyPermissions($studyID);

        // user has required permissions for this study?
        foreach($permissionsAllowed as $permission){
            if(get_instance()->permissions->$permission){
                return TRUE;
            }
        }

        $errorMessage = self::PERMISSION_ERROR_No_Permission;
        return FALSE;
    }
}