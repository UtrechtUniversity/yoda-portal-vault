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
}
