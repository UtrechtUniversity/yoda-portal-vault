<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Metadata model
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
class Metadata_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    /**
     * Prepare vault metadata for editing.
     *
     * @param $metadataFile
     * @return metadata
     */
    public function prepareVaultMetadataForEditing($metadataFile)
    {
        $outputParams = array('*tempMetadataJsonPath', '*status', '*statusInfo');
        $inputParams = array('*metadataJsonPath' => $metadataFile);

        $rule = $this->irodsrule->make('iiPrepareVaultMetadataForEditing', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }
}
