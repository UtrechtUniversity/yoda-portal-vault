<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Filesystem extends CI_Model {

    var $CI = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @param $metadata
     *
     * key value pairs to be written to .yoda-metadata.xml
     *
     */
    function writeXml($rodsaccount, $path, $metadata)
    {
        $metedataFile = new ProdsFile($rodsaccount, $path);

        $metedataFile->open("w+", $rodsaccount->default_resc); //$this->config->item('rodsDefaultResource')

        $xml = new DOMDocument( "1.0", "UTF-8" );
	    $xml->formatOutput = true;

        $xml_metadata = $xml->createElement( "metadata" );

        foreach($metadata as $fields) {
            foreach ($fields as $key => $value) {
                $xml_item = $xml->createElement( $key);
                $xml_item->appendChild($xml->createTextNode($value));
                $xml_metadata->appendChild( $xml_item );
            }
        }

        $xml->appendChild($xml_metadata);

        $xmlString = $xml->saveXML();

        $metedataFile->write($xmlString);

        $metedataFile->close();

        return $metadata;
    }

    function read($rodsaccount, $file)
    {
        $fileContent = '';

        try {
            $file = new ProdsFile($rodsaccount, $file);
            $file->open("r");

            // Grab the file content
            while ($str = $file->read(4096)) {
                $fileContent .= $str;
            }
            //close the file pointer
            $file->close();

            return $fileContent;

        } catch(RODSException $e) {
            return false;
        }
    }

    static public function metadataFormPaths($iRodsAccount, $path) {
        $ruleBody = <<<'RULE'
myRule {
    iiPrepareMetadataForm(*path, *result);
}


RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path
                ),
                array("*result")
            );

            $ruleResult = $rule->execute();
            $output = json_decode($ruleResult['*result'], true);

            return $output;

        } catch(RODSException $e) {
            return false;
        }

        return array();
    }

    static public function removeAllMetadata($iRodsAccount, $path)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    iiRemoveAllMetadata(*path);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path
                ),
                array()
            );

            $rule->execute();
            return true;

        } catch(RODSException $e) {
            #print_r($e);
            exit;
            return false;
        }
    }

    static public function cloneMetadata($iRodsAccount, $path, $parentPath)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    iiCloneMetadataXml(*src, *dst);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*src" => $parentPath,
                    "*dst" => $path,
                ),
                array()
            );

            $rule->execute();
            return true;

        } catch(RODSException $e) {
            #print_r($e);
            exit;
            return false;
        }
    }


    // Obsolete
    static public function searchRevisions($iRodsAccount, $path, $type, $orderBy, $orderSort, $limit, $offset = 0) {
        $output = array();
        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);
    iiBrowse(*path, *collectionOrDataObject, *orderby, *ascdesc, *l, *o, *result);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path,
                    "*collectionOrDataObject" => $type,
                    "*orderby" => $orderBy,
                    "*ascdesc" => $orderSort,
                    "*limit" => $limit,
                    "*offset" => $offset
                ),
                array("*result")
            );
            $ruleResult = $rule->execute();
            $results = json_decode($ruleResult['*result'], true);
            $summary = $results[0];
            unset($results[0]);
            $rows = $results;
            $output = array(
                'summary' => $summary,
                'rows' => $rows
            );
            return $output;
        } catch(RODSException $e) {
            #print_r($e->rodsErrAbbrToCode($e->getCodeAbbr()));
            exit;
            echo $e->showStacktrace();
            return array();
        }
        return array();
    }

    static public function collectionDetails($iRodsAccount, $path)
    {
        $output = array();

        $path = str_replace("`", "\\`", $path);

        $ruleBody = <<<'RULE'
myRule {
    iiCollectionDetails(*path, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path
                ),
                array("*result",
                     "*status",
                     "*statusInfo"
                    )
            );

            $ruleResult = $rule->execute();

            //print_r($ruleResult['*result']);exit;

            $status = $ruleResult['*status'];
            $statusInfo = $ruleResult['*statusInfo'];

            $result = json_decode($ruleResult['*result'], true);

            $output = array(
                'result' => $result,
                'status' => $status,
                'statusInfo' => $statusInfo
            );

            return $output;

        } catch(RODSException $e) {
            $output = array(
                'status' => 'Error',
                'statusInfo' => 'Something unexpected went wrong - ' . $e->rodsErrAbbrToCode($e->getCodeAbbr()). '. Please contact a system administrator'
            );
            return $output;
        }
    }



    static public function browseCollections($iRodsAccount, $path) {
        $ruleBody = <<<'RULE'
myRule {
    iiBrowseSubCollections(*path, *result);
}


RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path
                ),
                array("*result")
            );

            $result = $rule->execute();

            #print_r(json_decode($result, true));
            exit;

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
    }

    static public function browse($iRodsAccount, $path, $type, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    iiBrowse(*path, *collectionOrDataObject, *orderby, *ascdesc, *l, *o, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path,
                    "*collectionOrDataObject" => $type,
                    "*orderby" => $orderBy,
                    "*ascdesc" => $orderSort,
                    "*limit" => $limit,
                    "*offset" => $offset
                ),
                array("*result",
                    "*status",
                    "*statusInfo")
            );

            $ruleResult = $rule->execute();

            $results = json_decode($ruleResult['*result'], true);

            $status = $ruleResult['*status'];
            $statusInfo = $ruleResult['*statusInfo'];

            $summary = $results[0];
            unset($results[0]);

            $rows = $results;
            $output = array(
                'summary' => $summary,
                'rows' => $rows,
                'status' => $status,
                'statusInfo' => $statusInfo
            );

            return $output;

        } catch(RODSException $e) {
            $output = array(
                'status' => 'Error',
                'statusInfo' => 'Something unexpected went wrong - ' . $e->rodsErrAbbrToCode($e->getCodeAbbr()). '. Please contact a system administrator'
            );
            return $output;
        }
    }

    static public function searchByName($iRodsAccount, $path, $string, $type, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    iiSearchByName(*path, *searchstring, *collectionOrDataObject, *orderby, *ascdesc, *l, *o, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path,
                    "*searchstring" => $string,
                    "*collectionOrDataObject" => $type,
                    "*orderby" => $orderBy,
                    "*ascdesc" => $orderSort,
                    "*limit" => $limit,
                    "*offset" => $offset
                ),
                array("*result",
                    "*status",
                    "*statusInfo")
            );

            $ruleResult = $rule->execute();
            $results = json_decode($ruleResult['*result'], true);

            $status = $ruleResult['*status'];
            $statusInfo = $ruleResult['*statusInfo'];

            $summary = $results[0];
            unset($results[0]);

            $rows = $results;
            $output = array(
                'summary' => $summary,
                'rows' => $rows,
                'status' => $status,
                'statusInfo' => $statusInfo
            );

            return $output;

        } catch(RODSException $e) {
            $output = array(
                'status' => 'Error',
                'statusInfo' => 'Something unexpected went wrong - ' . $e->rodsErrAbbrToCode($e->getCodeAbbr()) . '. Please contact a system administrator'
            );
            return $output;
        }
    }

    static public function searchByUserMetadata($iRodsAccount, $path, $string, $type, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    iiSearchByMetadata(*path, *searchstring, *collectionOrDataObject, *orderby, *ascdesc, *l, *o, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path,
                    "*searchstring" => $string,
                    "*collectionOrDataObject" => $type,
                    "*orderby" => $orderBy,
                    "*ascdesc" => $orderSort,
                    "*limit" => $limit,
                    "*offset" => $offset
                ),
                array("*result",
                    "*status",
                    "*statusInfo")
            );

            $ruleResult = $rule->execute();
            $results = json_decode($ruleResult['*result'], true);

            $status = $ruleResult['*status'];
            $statusInfo = $ruleResult['*statusInfo'];

            $summary = $results[0];
            unset($results[0]);

            $rows = $results;
            $output = array(
                'summary' => $summary,
                'rows' => $rows,
                'status' => $status,
                'statusInfo' => $statusInfo
            );

            return $output;

        } catch(RODSException $e) {
            $output = array(
                'status' => 'Error',
                'statusInfo' => 'Something unexpected went wrong - ' . $e->rodsErrAbbrToCode($e->getCodeAbbr()) . '. Please contact a system administrator'
            );
            return $output;
        }
    }

    static public function searchByOrgMetadata($iRodsAccount, $path, $string, $type, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    iiSearchByOrgMetadata(*path, *searchstring, *attrname, *orderby, *ascdesc, *l, *o, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path,
                    "*searchstring" => $string,
                    "*attrname" => $type,
                    "*orderby" => $orderBy,
                    "*ascdesc" => $orderSort,
                    "*limit" => $limit,
                    "*offset" => $offset
                ),
                array("*result",
                    "*status",
                    "*statusInfo")
            );

            $ruleResult = $rule->execute();
            $results = json_decode($ruleResult['*result'], true);

            $status = $ruleResult['*status'];
            $statusInfo = $ruleResult['*statusInfo'];

            $summary = $results[0];
            unset($results[0]);

            $rows = $results;
            $output = array(
                'summary' => $summary,
                'rows' => $rows,
                'status' => $status,
                'statusInfo' => $statusInfo
            );

            return $output;

        } catch(RODSException $e) {
            $output = array(
                'status' => 'Error',
                'statusInfo' => 'Something unexpected went wrong - ' . $e->rodsErrAbbrToCode($e->getCodeAbbr()) . '. Please contact a system administrator'
            );
            return $output;
        }
    }

    function listLocks($iRodsAccount, $folder, $offset = 0, $limit = 10)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    iiListLocks(*path, *o, *l, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $folder,
                    "*offset" => $offset,
                    "*limit" => $limit

                ),
                array("*result", "*status", "*statusInfo")
            );

            $ruleResult = $rule->execute();
            $output['*result'] = json_decode($ruleResult['*result'], true);
            $output['*status'] = $ruleResult['*status'];
            $output['*statusInfo'] = $ruleResult['*statusInfo'];

            return $output;

        } catch(RODSException $e) {
            return array();
        }

        return array();
    }
}

