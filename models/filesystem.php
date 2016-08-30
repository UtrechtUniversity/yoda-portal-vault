<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Filesystem extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    static public function getStudiesInformation($iRodsAccount, $limit = 0, $offset = 0, $search = false) {
        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    uuIiGetStudiesInformation(*l, *o, *searchval, *buffer, *f, *i);

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
                        "*limit" => sprintf("%d",$limit),
                        "*offset" => sprintf("%d", $offset),
                        "*searchval" => $searchval,
                    ),
                array("*buffer", "*total", "*filtered")
            );

            $result = $rule->execute();

            $files = array();
            if(strlen($result["*buffer"]) > 0) {
                foreach(explode("++++====++++", $result["*buffer"]) as $file) {
                    $fexp = explode("+=+", $file);
                    if(sizeof($fexp) === 6)
                        $files[] = array("name" => $fexp[0], "size" => $fexp[1], "ndirectories" => $fexp[2],
                        "nfiles" => $fexp[3], "created" => $fexp[4], "modified" => $fexp[5]);
                }
            }

            return array("total" => $result["*total"], "filtered" => $result["*filtered"], "data" => $files);

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
    }

    static public function getDirsInformation($iRodsAccount, $collection, $limit = 0, $offset = 0, $search = false, $canSnap = false) {
        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);
    writeLine("serverLog", "*canSnap");
    if(*canSnap == "1") {
        *canSnapb = true;
    } else {
        *canSnapb = false;
    }

    uuIiGetDirInformation(*collection, *l, *o, *searchval, *buffer, *f, *i, *canSnapb);

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
                        "*searchval" => $searchval,
                        "*canSnap" => $canSnap ? "1" : "0"
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

