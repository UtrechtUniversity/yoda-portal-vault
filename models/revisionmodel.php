<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class RevisionModel extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    static public function search($iRodsAccount, $searchArgument, $path, $orderBy, $orderSort, $limit, $offset = 0)
    {
        $output = array();

        $ruleBody = <<<'RULE'
myRule {
    *l = int(*limit);
    *o = int(*offset);
    iiBrowse(*path, *orderby, *ascdesc, *l, *o, *result);
}
RULE;
        try {
            if (true) { // return faked rows and meta information
                $rows = array();

                $totalRows = 95;
                for ($i = $offset; ($i < $totalRows AND ($i - $offset < $limit)); $i++) {
                    $rows[] = array('study' => 'test',
                        'object' => 'object' . $i,
                        'name' => 'name-' . $i,
                        'date' => 'date-' . $i,
                        'path' => 'path-' . $i,
                    );
                }

                $output = array('summary' => array(
                    'total' => $totalRows,
                    'returned' => sizeof($rows)
                ),
                    'rows' => $rows);
                return $output;
            } else {
                $rule = new ProdsRule(
                    $iRodsAccount,
                    $ruleBody,
                    array(
                        "*path" => $path,
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
            }
            return $output;
        } catch (RODSException $e) {
            print_r($e->rodsErrAbbrToCode($e->getCodeAbbr()));
            exit;
            echo $e->showStacktrace();
            return array();
        }
        return array();
    }
}

