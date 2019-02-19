<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Folder status model
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
class Folder_Status_model extends CI_Model
{
    var $CI = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    /**
     * Lock folder in the research space.
     *
     * @param $folder Folder to lock
     * @return status
     */
    function lock($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderLock', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    /**
     * Unlock folder in the research space.
     *
     * @param $folder Folder to unlock
     * @return status
     */
    function unlock($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderUnlock', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    /**
     * Submit folder in the research space.
     *
     * @param $folder Folder to submit
     * @return status
     */
    function submit($folder)
    {
        $outputParams = array('*folderStatus', '*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderSubmit', $inputParams, $outputParams);

        $result = $rule->execute();

        return $result;
    }

    /**
     * Unsubmit folder in the research space.
     *
     * @param $folder Folder to unsubmit
     * @return status
     */
    function unsubmit($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderUnsubmit', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    /**
     * Accept folder in the research space.
     *
     * @param $folder Folder to accept
     * @return status
     */
    function accept($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderAccept', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    /**
     * Reject folder in the research space.
     *
     * @param $folder Folder to reject
     * @return status
     */
    function reject($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderReject', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    /**
     * Grant read acces to folder in the vault space.
     *
     * @param $folder Folder to grant read access to
     * @return status
     */
    function grant($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*path' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiGrantReadAccessToResearchGroup', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    /**
     * Revoke read acces to folder in the vault space.
     *
     * @param $folder Folder to revoke read access to
     * @return status
     */
    function revoke($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*path' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiRevokeReadAccessToResearchGroup', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    /**
     * Submit folder for publication in the vault space.
     *
     * @param $folder Folder to submit for publication
     * @return status
     */
    function submit_for_publication($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder );

        $this->CI->load->library('irodsrule');

        $rule = $this->irodsrule->make('iiVaultSubmit', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    /**
     * Depublish folder in the vault space.
     *
     * @param $folder Folder to depublish
     * @return status
     */
    function depublish_publication($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder );

        $this->CI->load->library('irodsrule');

        $rule = $this->irodsrule->make('iiVaultDepublish', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    /**
     * Republish folder in the vault space.
     *
     * @param $folder Folder to republish
     * @return status
     */
    function republish_publication($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder );

        $this->CI->load->library('irodsrule');

        $rule = $this->irodsrule->make('iiVaultRepublish', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    /**
     * Retrieve publication terms text from server.
     *
     * @param $folder Folder to approve for publication
     * @return status
     */
    function getTermsText($fullPath)
    {
        $outputParams = array('*result', '*status', '*statusInfo');
        $inputParams = array('*folder' => $fullPath);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiGetPublicationTermsText', $inputParams, $outputParams);
        $result = $rule->execute();

        return $result;
    }

    /**
     * Approve folder for publication in the vault space.
     *
     * @param $folder Folder to approve for publication
     * @return status
     */
    function approve_for_publication($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiVaultApprove', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    /**
     * Cancel publication of folder in the vault space.
     *
     * @param $folder Folder to cancel for publication
     * @return status
     */
    function cancel_publication($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiVaultCancel', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }
}
