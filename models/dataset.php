<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dataset extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Calls an iRods rule on a dataset, that recursively locks the dataset for
     * copying a snapshot to the vault, and adds meta data for the user who
     * requested the lock
     * @param $iRodsAccount     Rods Account instance of current user
     * @param $collection       Full iRods collection name of intake path the dataset
     *                          resides in
     * @param $dataset          Name of the dataset inside the collection $collection
     *                          that requires locking
     * @return                  Bool, indicating success
     */
    static public function prepareDatasetForSnapshot($iRodsAccount, $collection) {
        $ruleBody = '
        myRule {
            iiDatasetSnapshotLock(*collection, *status);
            *status = str(*status);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => $collection
                    ),
                array("*status")
            );

            $result = $rule->execute();
            return $result["*status"] == "0";
        } catch(RODSException $e) {
            return false;
        }
    }

    /**
     * Calls iRods rule that recursively removes a snapshot lock from a dataset, if it isn't
     * already frozen
     * @param $iRodsAccount     Rods Account instance of current user
     * @param $collection       Full iRods collection name of intake path the dataset
     *                          resides in
     * @param $dataset          Name of the dataset inside the collection $collection
     *                          that requires unlocking
     * @return                  Bool, indicating success
     */
    static public function removeSnapshotLockFromDataset($iRodsAccount, $collection) {
        $ruleBody = '
        myRule {
            iiDatasetSnapshotUnlock(*collection, *status);
            *status = str(*status);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => $collection
                    ),
                array("*status")
            );

            $result = $rule->execute();
            return $result["*status"] == "0";
        } catch(RODSException $e) {
            return false;
        }
    }

    /**
     * Method to make a call to iRods to create a snapshot of the current state of a dataset.
     * @param $iRodsAccount     A rodsAccount object of the current user
     * @param $vaultRoot        The root of the vault area of the study
     * @param $intakeRoot       The root of the intake area of the study
     * @param $datasetName      The name of the dataset, i.e. a directory directly under $intakeRoot
     * @return Boolean          True iff lock could be aquired (check in later to see if it worked)
     */
    static public function copySnapshotToVault2($iRodsAccount, $vaultRoot, $intakeRoot, $datasetName)
    {
        $ruleBody = '
        myRule {
            uuIiCreateSnapshot(*intakeRoot, *vaultRoot, *datasetName, *status);
            *status = str(*status);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*intakeRoot" => $intakeRoot,
                    "*vaultRoot" => $vaultRoot,
                    "*datasetName" => $datasetName,
                ),
                array("*status")
            );

            $result = $rule->execute();
            return $result["*status"];
            return $result["*status"] == "0";
        } catch(RODSException $e) {
            return false;
        }

        return false;
    }

    
    

    static public function getLockedStatus($iRodsAccount, $path, $isCollection=True) {
       
        $ruleBody = '
            myRule {
                if(*isCollection == "1") {
                    *isColl = true;
                } else {
                    *isColl = false;
                }

                iiObjectIsSnapshotLocked(*path, *isColl, *locked, *frozen);
                *locked = str(*locked);
                *frozen = str(*frozen);
            }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*path" => $path, 
                    "*isCollection" => $isCollection
                ),
                array("*locked", "*frozen")
            );

            $result = $rule->execute();

            return array(
                    "locked" => $result["*locked"] == "true" ? true : false,
                    "frozen" => $result["*frozen"] == "true" ? true : false
                );

        } catch(RODSException $e) {
            return false;
        }

        return false;
    }

    
    /**
     * Get the Date and Time, username and userzone for the latest 
     * snapshot, if any exist
     * @param $iRodsAccount     Rods account object
     * @param $dataset          Collection name of the dataset
     * @return array
     */
    static public function getLatestSnapshotInfo($iRodsAccount, $dataset) {
        $ruleBody = '
            myRule {
                uuIiGetLatestSnapshotInfo(*collection, *time, *userName, *userZone);
                *time = str(*time);
            }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    '*collection' => $dataset
                ),
                array(
                    '*time',
                    '*userName',
                    '*userZone'
                )
            );

            $result = $rule->execute();

            if($result["*time"] == 0) return false;
            else {
                $dt = new DateTime();
                $dt->setTimestamp((int)$result["*time"]);
                $values = array(
                        "datetime" => $dt,
                        "username" => $result["*userName"],
                        "userzone" => $result["*userZone"]
                    );

                return $values;
            }

        } catch(RODSException $e) {
            return false;
        }

        return false;
    }

    static public function getSnapshotHistory($iRodsAccount, $dataset) {
        $ruleBody = '
            myRule {
                uuIiGetSnapshotHistory(*collection, *history);
                uuJoin(",", *history, *str);
            }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*collection" => $dataset
                    ),
                array(
                    "*str"
                )
            );

            $result = $rule->execute();

            $history = array();

            foreach( explode(",", $result["*str"]) as $hist ) {
                if(strlen($hist) === 0) continue;
                $timeAndUser = explode(":", $hist);
                $userAndZone = explode("#", $timeAndUser[1]);
                $history[] = (object) array(
                    "user" => $userAndZone[0],
                    "time" => $timeAndUser[0],
                    "userZone" => $userAndZone[1]
                );
            }

            return $history;
            
        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return array();
        }

        return array();
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
     * Find whether a user is member of the given group.
     *
     * @param $iRodsAccount
     * @param $groupName
     * @param $userName
     * @return bool
     */
    static public function isGroupMember($iRodsAccount, $groupName, $userName){
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

    static public function isGroupManager($iRodsAccount, $groupName, $userName) {

        $ruleBody = "
            myRule {
                uuGroupUserIsManager(*group, *user, *isManager);
                *isManager = str(*isManager);
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
                    '*isManager'
                )
            );
            $result = $rule->execute();
            return ($result['*isManager'] === "true");
        }
        catch(RODSException $e) {
            // if erroneous => NO permission
            return FALSE;
        }
        return FALSE;
    }

    static public function testFunction($iRodsAccount) {
        $ruleBody = <<<'RULE'
myRule {
    *test = "hello world";
    *result = list("This","is","a","list");
    *item = hd(*result);
    *int = 1;
}
RULE;

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(),
                array("*test", "*item", "*int")
            );

            $result = $rule->execute();
            var_dump($result);
        } catch(RODSException $e) {
            echo $e->showStacktrace();
        }
    }

}