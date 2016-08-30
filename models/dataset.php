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
     * Method that queries irods for the current locked status of a collection or 
     * dataobject
     * 
     * @param iRodsAccount      Reference to the rods account object of the user
     * @param path              The path to the collection or dataobject
     * @param isCollection      Boolean, true iff the path point to a collection,
     *                          false if it points to a data object
     * @return array            Key "locked" indicates the path is locked, 
     *                          "frozen" indicates it is also frozen and cannot be
     *                          unlocked anymore by the user
     */
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
     * Method that queries irods for the snapshot/version history of a certain collection
     * @param iRodsAccount      Reference to the rods account object of the user
     * @param dataset           The path to the collection to get the history from
     * @return array            Array of versions, sorted from old to new, where each
     *                          version is an object with the keys vaultPath, version,
     *                          createdDateTime (unix timestamp), createdUser (the username
     *                          of the user who requested the version creation),
     *                          createdUserZone (the zone of the user who created the version),
     *                          dependsID (the collection ID the version was created from),
     *                          dependsPath (the path to the collection corresponding to the id) and
     *                          dependsVersion (the version this version was created from)
     */
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

            $keys = array("vaultPath", "version", "createdDatetime", "createdUser", "createdUserzone", "dependsID", "dependsPath", "dependsVersion");
            $history = array();
            foreach(explode(",", $result["*str"]) as $snapshot) {
                $snapshotElems = explode("#", $snapshot);
                if(sizeof($snapshotElems) === sizeof($keys)) {
                    $history[] = (object) array_combine($keys, $snapshotElems);
                }
            }

            return $history;
            
        } catch(RODSException $e) {
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
                uuIiIntakerStudies(*studies);
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
}