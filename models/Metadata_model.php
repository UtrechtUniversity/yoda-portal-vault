<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    // read yoda-metadata.
    function read($rodsaccount, $metadataFile)
    {
        //$metadataFile = $this->findPath($rodsaccount, $directoryPath);

        // Open metadata file in read mode
        $file = new ProdsFile($rodsaccount, $metadataFile);
        $file->open("r");

        // Grab the file content
        $fileContent = "";
        while ($str = $file->read(4096)) {
            $fileContent .= $str;
        }
        //close the file pointer
        $file->close();

        return $fileContent;
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

