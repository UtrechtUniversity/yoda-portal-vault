<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Data_Request_model extends CI_Model
{
    var $CI = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    function copy_package_from_vault($folderOrigin, $folderTarget)
    {

//
//
//        print_r($folderOrigin);
//        // Synchronized checks
//        $parts = explode('/', $folderOrigin);
//
//        // 1) check rights
//        print_r($parts);
//
//        $parts = pathinfo($folderTarget);
//        // 1) check rights
//        print_r($parts);


//        return array('*status' => 'ERROR',
//            '*statusInfo' => 'Something went very wrong');
//

        $outputParams = array('*status', '*statusInfo');
        $inputParams = array('*folder' => $folderOrigin,
            '*target' => $folderTarget);

        $this->CI->load->library('irodsrule');
        $rule = $this->irodsrule->make('iiRequestCopyVaultPackage', $inputParams, $outputParams);

        $result = $rule->execute();

        return $result;
    }
}
