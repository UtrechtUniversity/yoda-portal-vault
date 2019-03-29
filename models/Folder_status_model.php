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
}
