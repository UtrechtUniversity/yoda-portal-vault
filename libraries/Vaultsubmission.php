<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vaultsubmission
{

    public $CI;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get the CI instance
        $this->CI =& get_instance();
    }

    public function validate($xsdFilePath, $metadataFilePath)
    {
        $this->validateXsd($xsdFilePath, $metadataFilePath);
    }

    public function setSubmitFlag()
    {

    }

    private function validateXsd($xsdFilePath, $metadataFilePath)
    {
        $xsdContent = $this->CI->filesystem->read($rodsaccount, $xsdFilePath);
        $metadataContent = $this->CI->filesystem->read($rodsaccount, $metadataFilePath);

        print_r($xsdContent);
        print_r($metadataContent);
        exit;
    }
}