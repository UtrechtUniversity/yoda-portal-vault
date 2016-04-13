<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class YodaProds  {

    /**
     *
     * Lock the dataset for transportation to the vault
     * @param $iRodsAccount
     * @param $root
     * @param $datasetId
     * @return int (0=ok)
     */
    static public function datasetLock($iRodsAccount, $root, $datasetId)
    {
        $ruleBody = "
            myRule {
                uuYcDatasetLock(*root,*datasetId,*status);
                *status = str(*status);
        }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array('*root' => $root,
                    '*datasetId' => $datasetId,
                ),
                array(
                    '*status'
                )
            );
            $result = $rule->execute();

            return $result['*status'];  // 0=ok
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            //var_dump($e->getCodeAbbr());
            return 999;
        }
        return 999;
    }

    /**
     * Unlock the dataset from transportation to the vault
     *
     * @param $iRodsAccount
     * @param $root
     * @param $datasetId
     * @return int (0=ok)
     */
    static public  function datasetUnlock($iRodsAccount, $root, $datasetId)
    {
        $ruleBody = "
            myRule {
                uuYcDatasetUnlock(*root,*datasetId,*status);
                *status = str(*status);
        }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array('*root' => $root,
                    '*datasetId' => $datasetId,
                ),
                array(
                    '*status'
                )
            );
            $result = $rule->execute();
            return $result['*status'];  // 0=ok
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            //var_dump($e->getCodeAbbr());
            return 999;
        }
        return 999;
    }


    /**
     * Trigger uuYcIntakeScan-rule that scans all files in the path that require to go through the scanning process.
     *
     * @param $iRodsAccount
     * @param $path
     * @return int (0=ok)
     */
    static public function scanIrodsCollection($iRodsAccount,$path)
    {
        $ruleBody = "
            myRule {
                uuYcIntakeScan(*collection1,*status);
                *status=str(*status);
        }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array('*collection1' => $path),
                array('*status')
            );
            $result = $rule->execute();
            return $result['*status'];  // 0=ok
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            //var_dump($e->getCodeAbbr());
            return 999;
        }
        return 999;
    }

    /**
     * Add a comment to the dataset.
     *
     * @param $iRodsAccount
     * @param $root
     * @param $datasetId
     * @param $comment
     * @return bool
     */

    static public function addCommentToDataset($iRodsAccount, $root, $datasetId, $comment)
    {
        $ruleBody = "
            myRule {
                uuYcIntakeCommentAdd(*root,*datasetId,*text);
        }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array('*root' => $root,
                    '*datasetId' => $datasetId,
                    '*text' => $comment,
                ),
                array(
                )
            );
            $result = $rule->execute();

            return TRUE;
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            //var_dump($e->getCodeAbbr());
            return FALSE;
        }
        return FALSE;
    }


    /**
     * Find whether a user is member of the given group.
     *
     * @param $iRodsAccount
     * @param $groupName
     * @param $userName
     * @return bool
     */
    static public function isGroupMember($iRodsAccount,$groupName, $userName){
        $ruleBody = "
            myRule {
                uuGroupUserExists(*group, *user, *member);
                *member = str(*member);
        }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    '*group' => $groupName,
                    '*user'  => $userName
                ),
                array(
                    '*member'
                )
            );
            $result = $rule->execute();
            return ($result['*member'] === "true");
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            return FALSE;
        }
        return FALSE;
    }

    /**
     * Get list of studies a user can view
     *
     * @param $iRodsAccount
     * @return array|bool
     */
    static public function getStudies($iRodsAccount)
    {
        $ruleBody = "
            myRule {
                uuYcIntakerStudies(*studies);
                *studies = str(*studies);
        }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(),
                array(
                    '*studies'
                )
            );
            $result = $rule->execute();

            $studies = explode(',',$result['*studies']);

            return $studies;
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            //var_dump($e->getCodeAbbr());
            return FALSE;
        }
        return FALSE;
    }

    /**
     * Get the metadata required for the dataset-table in the intake module.
     * The uuYcQueryDataset-rule collects all information. So there is no longer the need to, while populating the table, to analyse this yourself
     *
     * @param $iRodsAccount
     * @param $datasetId
     * @param $dataSetInfo
     * @return bool
     */
    static public function getMetaDataForIntakeDataset($iRodsAccount, $datasetId, &$dataSetInfo)
    {
        $ruleBody = "
            myRule {
                uuYcQueryDataset(*datasetId, *wave, *expType, *pseudocode, *version, *datasetStatus, *datasetCreateName, *datasetCreateDate, *datasetErrors, *datasetWarnings, *datasetComments,  *objects, *objectErrors, *objectWarnings);
                *wave = str(*wave);
                *expType = str(*expType);
                *pseudocode = str(*pseudocode);
                *version = str(*version);
                *datasetStatus = str(*datasetStatus);
                *datasetCreateName = str(*datasetCreateName);
                *datasetCreateDate = str(*datasetCreateDate);
                *datasetErrors = str(*datasetErrors);
                *datasetWarnings = str(*datasetWarnings);
                *datasetComments = str(*datasetComments);
                *objects = str(*objects);
                *objectErrors = str(*objectErrors);
                *objectWarnings = str(*objectWarnings);
       }";

        try{
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    '*datasetId' => $datasetId,
                ),
                array(
                    '*wave',
                    '*expType',
                    '*pseudocode',
                    '*version',
                    '*datasetStatus',
                    '*datasetCreateName',
                    '*datasetCreateDate',
                    '*datasetErrors',
                    '*datasetWarnings',
                    '*datasetComments',
                    '*objects',
                    '*objectErrors',
                    '*objectWarnings',
                )
            );
            $result = $rule->execute();

            $dataSetInfo['wave'] = $result['*wave'];
            $dataSetInfo['expType'] = $result['*expType'];
            $dataSetInfo['pseudocode'] = $result['*pseudocode'];
            $dataSetInfo['version'] = $result['*version'];
            $dataSetInfo['datasetStatus'] = $result['*datasetStatus'];
            $dataSetInfo['datasetCreateName'] = $result['*datasetCreateName'];
            $dataSetInfo['datasetCreateDate'] = $result['*datasetCreateDate'];
            $dataSetInfo['datasetErrors'] = $result['*datasetErrors'];
            $dataSetInfo['datasetWarnings'] = $result['*datasetWarnings'];
            $dataSetInfo['datasetComments'] = $result['*datasetComments'];
            $dataSetInfo['objects'] = $result['*objects'];
            $dataSetInfo['objectErrors'] = $result['*objectErrors'];
            $dataSetInfo['objectWarnings'] = $result['*objectWarnings'];

            return TRUE;
        }
        catch(RODSException $e) {
            return FALSE;
        }
        return FALSE;
    }

    /**
     * Completely open query function.
     *
     * @param $iRodsAccount
     * @param RODSGenQueSelFlds $select
     * @param RODSGenQueConds $condition
     * @param array $data
     * @param bool|false $countOnly
     */
    public function queryGeneral($iRodsAccount,RODSGenQueSelFlds $select, RODSGenQueConds $condition, &$data=array(), $countOnly=false)
    {
        $conn = RODSConnManager::getConn($iRodsAccount);

        $results = $conn->query($select, $condition);

        RODSConnManager::releaseConn($conn);

        $recordCount = $results->getNumRow();
        if ($recordCount < 1 OR $countOnly)
            $data = array('recordCount' => $recordCount,
                'recordValues' => array()
            );
        else {
            $data = array('recordCount' => $recordCount,
                'recordValues' => $results->getValues()
            );
        }
    }

    /**
     * Query function already focused on a specific path and 1 conditioned column in particular.
     *
     * @param $iRodsAccount
     * @param $columns
     * @param $referencePath
     * @param string $specificMetaConditionsOnColumn
     * @param array $specificMetaConditions
     * @return array
     */
    public function query($iRodsAccount, $columns, $referencePath, $specificMetaConditionsOnColumn='', $specificMetaConditions=array())
    {
        $select = new RODSGenQueSelFlds(array_keys($columns), array_keys($columns));

        $condition = new RODSGenQueConds();
        $condition->add('COL_COLL_NAME', 'like',$referencePath);

        // Add specific conditions
        if($specificMetaConditionsOnColumn) {
            $countConditions = count($specificMetaConditions);
            if($countConditions == 1) {
                $condition->add($specificMetaConditionsOnColumn, '=', $specificMetaConditions[0]);
            }
            else {
                $conditionsArray = array();
                for($i=1; $i<$countConditions; $i++) {
                    $conditionsArray[] = array('op' => '=', 'val' => $specificMetaConditions[$i] );
                }
                $condition->add($specificMetaConditionsOnColumn, '=', $specificMetaConditions[0], $conditionsArray);
            }
        }

        $conn = RODSConnManager::getConn($iRodsAccount);

        $results = $conn->query($select, $condition);
        RODSConnManager::releaseConn($conn);

        $recordCount = $results->getNumRow();
        if ($recordCount < 1)
            return array('recordCount' => $recordCount,
                'recordValues' => array()
            );
        else {
            //$values = $results->getValues();
            //return $values;
            return array('recordCount' => $recordCount,
                'recordValues' => $results->getValues()
            );
        }
    }
}
