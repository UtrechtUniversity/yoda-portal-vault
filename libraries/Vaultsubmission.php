<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vaultsubmission
{

    public $CI;
    private $account;
    private $formConfig = array();
    private $folder;

    /**
     * Constructor
     */
    public function __construct($params)
    {
        // Get the CI instance
        $this->CI =& get_instance();
        $this->account = $this->CI->rodsuser->getRodsAccount();
        $this->formConfig = $params['formConfig'];
        $this->folder = $params['folder'];
    }

    public function validate()
    {
        $messages = array();
        $isVaultPackage = $this->formConfig['isVaultPackage'];

        // Validate update Vault Package
        if ($isVaultPackage == 'yes') {
            return true;
        }

        // Check folder status
        $folderStatusResult = $this->checkFolderStatus();

        if (!$folderStatusResult) {
            $messages[] = 'Illegal status transition. Current status is '. $this->formConfig['folderStatus'] .'.';
        } else {
            // Lock error
            $lockResult = $this->checkLock();
            if (!$lockResult) {
                $messages[] = 'Action could not be executed because of a lock. Check locks (lock symbol)  for more information';
            }
        }

        if (count($messages) > 0) {
            return $messages;
        }

        return true;
    }

    public function setSubmitFlag()
    {
        $result = false;
        if ($this->validate() === true) { // Hdr: dit gebeurt in vault-controller ook al
            $result = $this->CI->Folder_Status_model->submit($this->folder);
        }

        return $result;
    }

    public function clearSubmitFlag()
    {
        return $this->CI->Folder_Status_model->unsubmit($this->folder);
    }

    public function checkLock()
    {
        $lockStatus = $this->formConfig['lockFound'];
        $folderStatus = $this->formConfig['folderStatus'];

        if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'LOCKED' || $folderStatus == '' || $folderStatus == 'REJECTED' || $folderStatus == "SECURED")) {
            return true;
        }

        return false;
    }

    public function checkFolderStatus()
    {
        $folderStatus = $this->formConfig['folderStatus'];
        if ($folderStatus == 'LOCKED' || $folderStatus == '' || $folderStatus == 'REJECTED' || $folderStatus == "SECURED") {
            return true;
        }

        return false;
    }
}
