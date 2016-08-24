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
                array("*size", "*comment", "*locked", "*frozen")
            );

            $result = $rule->execute();

            return $result;

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return false;
        }

        return false;
    }

    static public function getDirsInformation($iRodsAccount, $collection, $limit = 0, $offset = 0, $search = false) {
         $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    uuIiGetDirInformation(*collection, *l, *o, *searchval, *buffer, *f, *i, false);

    *total = str(*i);
    *filtered = str(*f);
}


RULE;
        
        $searchval = "";
        $searchregex = "";

        if($search !== false && is_array($search)) {
            if(array_key_exists("value", $search) && $search["value"]) {
                $searchval = $search["value"];
            }
            if(array_key_exists("regex", $search) && $search["regex"]) {
                $searchregex = $search["regex"];
            }
        }

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => $collection,
                        "*limit" => sprintf("%d",$limit),
                        "*offset" => sprintf("%d", $offset),
                        "*searchval" => $searchval
                    ),
                array("*buffer", "*total", "*filtered")
            );

            $result = $rule->execute();

            $files = array();
            if(strlen($result["*buffer"]) > 0) {
                foreach(explode("++++====++++", $result["*buffer"]) as $file) {
                    $fexp = explode("+=+", $file);
                    if(sizeof($fexp) > 7)
                        $files[] = array("name" => $fexp[0], "size" => $fexp[1], "ndirectories" => $fexp[2],
                        "nfiles" => $fexp[3], "created" => $fexp[4], "modified" => $fexp[5],
                        "version" => $fexp[6], "versionUser" => $fexp[7], "versionTime" => $fexp[8]);
                }
            }

            return array("total" => $result["*total"], "filtered" => $result["*filtered"], "data" => $files);

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
    }

    static public function getFilesInformation($iRodsAccount, $collection, $limit = 0, $offset = 0, $search = false) {
        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    uuIiGetFilesInformation(*collection, *l, *o, *searchval, *buffer, *f, *i);

    *total = str(*i);
    *filtered = str(*f);
}


RULE;
        
        $searchval = "";
        $searchregex = "";

        if($search !== false && is_array($search)) {
            if(array_key_exists("value", $search) && $search["value"]) {
                $searchval = $search["value"];
            }
            if(array_key_exists("regex", $search) && $search["regex"]) {
                $searchregex = $search["regex"];
            }
        }

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => $collection,
                        "*limit" => sprintf("%d",$limit),
                        "*offset" => sprintf("%d", $offset),
                        "*searchval" => $searchval
                    ),
                array("*buffer", "*total", "*filtered")
            );

            $result = $rule->execute();

            $files = array();
            if(strlen($result["*buffer"]) > 0) {
                foreach(explode("++++====++++", $result["*buffer"]) as $file) {
                    $fexp = explode("+=+", $file);
                    if(sizeof($fexp) > 1)
                        $files[] = array("file" => $fexp[1], "size" => $fexp[0], "created" => $fexp[2], "modified" => $fexp[3]);
                }
            }

            return array("total" => $result["*total"], "filtered" => $result["*filtered"], "data" => $files);

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
    }
}

