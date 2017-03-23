<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class RevisionModel extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    static public function searchByString($iRodsAccount, $searchstring, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);

    uuRevisionSearchByOriginalPath(*searchstring, *orderby, *ascdesc, *l, *o, *result);
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
            print_r($e->rodsErrAbbrToCode($e->getCodeAbbr()));
            exit;

            echo $e->showStacktrace();
            return array();
        }

        return array();
    }

    static public function listByPath($iRodsAccount, $path)
    {
        $ruleBody = <<<'RULE'
myRule {
    uuRevisionList(*path, *result);
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
}

