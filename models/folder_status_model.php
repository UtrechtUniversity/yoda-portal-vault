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
        $outputParams = array('*status', '*statusInfo');
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

}