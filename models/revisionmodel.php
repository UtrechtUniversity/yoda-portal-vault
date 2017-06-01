<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class RevisionModel extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param $iRodsAccount
     * @param $path
     * @return bool|mixed
     *
     *
     */
    static public function collectionExists($iRodsAccount, $path)
    {
        $ruleBody = <<<'RULE'
myRule {
    uuRevisionCollectionExists(*path, *collectionExists);
}


RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path
                ),
                array("*collectionExists")
            );

            $ruleResult = $rule->execute();

            return ($ruleResult['*collectionExists']=='true');

        } catch(RODSException $e) {
            return false;
        }
    }




    static public function searchByString($iRodsAccount, $searchstring, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    iiRevisionSearchByOriginalFilename(*searchstring, *orderby, *ascdesc, *l, *o, *result, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*searchstring" => $searchstring,
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

    /**
     * @param $iRodsAccount
     * @param $path
     * @return bool|mixed
     *
     *
     */
    static public function listByPath($iRodsAccount, $path)
    {
        $ruleBody = <<<'RULE'
myRule {
    iiRevisionList(*path, *result);
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
            $results = json_decode($ruleResult['*result'], true);

            return $results;

        } catch(RODSException $e) {
            return false;
        }
    }

    /**
     * @param $iRodsAccount
     * @param $path
     * @param $revisionId
     * @return bool
     *
     * rule first checks whether file is present already.
     * responses are:
     *
     * Success: restore action was performed correctly
     *
     * AlreadyExists: restore request found that the
     * Action of user requested
     *
     *
     */


    static public function restoreRevision($iRodsAccount, $path, $revisionId, $overwriteFlag = 'restore_no_overwrite', $newFileName='')
    {
        $ruleBody = <<<'RULE'
myRule {
        iiRevisionRestore(*revisionId, *target, *overwrite, *status, *statusInfo);
}
RULE;
        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*revisionId" => $revisionId,
                    "*target" => $path,
                    "*overwrite" => $overwriteFlag,
                    "*newFileName" => $newFileName
                ),
                array("*status",
                    "*statusInfo"
                )
            );

            $ruleResult = $rule->execute();

            $status = $ruleResult['*status'];
            $statusInfo = $ruleResult['*statusInfo'];

            // For the moment it was decided that the rule passes a status and its contextual information back to this function.
            // Status info can hold an errorcode as well as description.
            // Requires further research to come to a unamgiguous solution that is used throughout the application

            return array(
                'status' => $status,
                'statusInfo' => $statusInfo
            );
        }
        catch(RODSException $e) {
            $errorCode = $e->getCodeAbbr();
            $errorDescription = $e->rodsErrAbbrToCode($errorCode);

            return array('status' => 'Unrecoverable',
                         'statusInfo' => "$errorCode - $errorDescription");
        }

    }
}

