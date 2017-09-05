<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Folder_Status_model extends CI_Model
{
    var $CI = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    function lock($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderLock', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    function unlock($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderUnlock', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    function submit($folder)
    {
        $outputParams = array('*folderStatus', '*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderSubmit', $inputParams, $outputParams);

        $result = $rule->execute();

        return $result;
    }

    function unsubmit($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderUnsubmit', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    function accept($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderAccept', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    function reject($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiFolderReject', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    function grant($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*path' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiGrantReadAccessToResearchGroup', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    function revoke($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*path' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiRevokeReadAccessToResearchGroup', $inputParams, $outputParams);

        $result = $rule->execute();
        return $result;
    }

    // including version of conditions&terms
    function submit_for_publication($folder,  $confirmationVersion)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder,
            '*confirmationVersion' => $confirmationVersion);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiVaultSubmit', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

    function approve_for_publication($folder)
    {
        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folder);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiVaultApprove', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }

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
