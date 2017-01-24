<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    function read($rodsaccount, $directoryPath)
    {
        $metadataFile = $this->findPath($rodsaccount, $directoryPath);

        if ($metadataFile !== false) {
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

            $xml = new DOMDocument();
            $xmlLoaded = $xml->loadXml($fileContent);

            if ($xmlLoaded) {
                return $xml;
            }
        }

        return false;
    }

    function findPath($rodsaccount, $directoryPath)
    {
        $this->CI->load->model('filesystem');
        $details = $this->CI->filesystem->collectionDetails($rodsaccount, $directoryPath);

        if (isset($details['org_metadata'])) {
            return $details['org_metadata'];
        }

        return false;
    }

    /*
    function write()
    {

    }
    */
}

