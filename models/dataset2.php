<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dataset2 extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    static public function countSubFiles($iRodsAccount, $path) {
        $ruleBody = '
            myRule {
                iiFileCount(*path);
            }
        ';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array("*path" => $path),
                array("*dircount", "*filecount", "*totalSize")
            );

            $result = $rule->execute();

            return array(
                    "dircount" => intval($result["*dircount"]),
                    "filecount" => intval($result["*filecount"]),
                    "totalSize" => intval($result["*totalSize"]),
                );

        } catch(RODSException $e) {
            return false;
        }

        return false;
    }

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