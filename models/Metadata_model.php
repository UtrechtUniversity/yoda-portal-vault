<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    public function prepareVaultMetadataForEditing($metadataFile)
    {
        $outputParams = array('*tempMetadataXmlPath', '*status', '*statusInfo');
        $inputParams = array('*metadataXmlPath' => $metadataFile);

        $rule = $this->irodsrule->make('iiPrepareVaultMetadataForEditing', $inputParams, $outputParams);
        $result = $rule->execute();
        return $result;
    }
}

