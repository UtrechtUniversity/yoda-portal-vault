<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Filesystem extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Calls iRods rule that uses uuTreeWalk to recursively count
     * all subdirectories and files in an iRods collection
     * @param $iRodsAccount Instance of iRods account of current user
     * @param $path 		Full iRods collection name
     * @return array (assoc) containing total number of directories and
     * 						files, and the total file size of all files
     * 						in the collection
     */
    static public function countSubFiles($iRodsAccount, $path) {
        $ruleBody = '
            myRule {
                iiFileCount(*path, *totalSize, *dircount, *filecount, *modified);
                
            }
        ';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array("*path" => $path),
                array("*totalSize", "*dircount", "*filecount", "*modified")
            );

            $result = $rule->execute();

            return array(
                    "dircount" => intval($result["*dircount"]),
                    "filecount" => intval($result["*filecount"]),
                    "totalSize" => intval($result["*totalSize"]),
                    "modified" => intval($result["*modified"])
                );

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return false;
        }

        return false;
    }

    /**
     * Gets file size from a file and comment from the meta data of the file
     * @param $iRodsAccount 	Instance of current users iRods account
     * @param $collection 		Full iRods collection name the file resides in
     * @param $file 			The file name that is probed for information
     * @return array (assoc) 	Containing file size and meta data comment for
     * 							the file
     */
    static public function getFileInformation($iRodsAccount, $collection, $file) {
        $ruleBody = '
            myRule {
                iiGetFileAttrs(*collectionName, *fileName, *size, *comment);
            }';

        
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*collectionName" => $collection,
                    "*fileName" => $file,
                ),
                array("*size", "*comment")
            );

            $result = $rule->execute();

            return $result;

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return false;
        }

        return false;
    }
}
