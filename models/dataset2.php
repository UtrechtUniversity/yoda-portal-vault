<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dataset2 extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    static public function prepareDatasetForSnapshot($iRodsAccount, $collection, $dataset) {
        $ruleBody = '
        myRule {
            iiDatasetSnapshotLock(*collection, *datasetId, *status);
            *status = str(*status);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => $collection,
                        "*datasetId" => $dataset,
                    ),
                array("*status")
            );

            $result = $rule->execute();
            return $result["*status"] == "0";
        } catch(RODSException $e) {
            return false;
        }
    }

    static public function removeSnapshotLockFromDataset($iRodsAccount, $collection, $dataset) {
        $ruleBody = '
        myRule {
            iiDatasetSnapshotUnlock(*collection, *datasetId, *status);
            *status = str(*status);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => $collection,
                        "*datasetId" => $dataset,
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

//  foreach(*row in SELECT COLL_NAME, META_COLL_ATTR_VALUE WHERE META_COLL_ATTR_NAME = "to_vault_lock") {
                // uuYcDatasetUnlock(*row.COLL_NAME, *row.COLL_NAME, *status2);
            // }
            // *status2 = str(*status2);

    static public function unlockAll($iRodsAccount) {
        $ruleBody = '
        myRule {
            foreach(*row in SELECT COLL_NAME WHERE META_COLL_ATTR_NAME LIKE "uuLock%") {
                uuUnlock(*row.COLL_NAME);
            }

            *result = "";

            foreach(*row in SELECT COLL_NAME WHERE META_COLL_ATTR_NAME = "to_vault_freeze") {
                uuChopPath(*row.COLL_NAME, *parent, *base);
                uuYcDatasetMelt(*parent, *base, *s3);
                *result = "*result (VaultMelt *parent/*base: *s3),  ";
            }

            foreach(*row in SELECT COLL_NAME WHERE META_COLL_ATTR_NAME = "to_snapshot_freeze") {
                uuChopPath(*row.COLL_NAME, *parent, *base);
                iiDatasetSnapshotMelt(*parent, *base, *s3);
                *result = "*result (SnapshopMelt *parent/*base: *s3),  ";
            }

            foreach(*row in SELECT COLL_NAME WHERE META_COLL_ATTR_NAME = "to_vault_lock") {
                uuChopPath(*row.COLL_NAME, *parent, *base);
                uuYcDatasetUnlock(*parent, *base, *s3);
                *result = "*result (VaultUnlock *parent/*base: *s3),  ";
            }

            foreach(*row in SELECT COLL_NAME WHERE META_COLL_ATTR_NAME = "to_snapshot_lock") {
                uuChopPath(*row.COLL_NAME, *parent, *base);
                iiDatasetSnapshotUnlock(*parent, *base, *s3);
                *result = "*result (SnapshotUnlock *parent/*base: *s3),  ";
            }

            *status = str(*result);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(),
                array("*status")
            );

            $result = $rule->execute();

            var_dump($result);

            return true;
        } catch(RODSException $e) {
            return false;
        }

        return false;
    }

    static public function testFunction($iRodsAccount) {
        $ruleBody = '
        myRule {
            uuIiGetSnapshotHistory(*collection, *buffer)
            *size = str(size(*buffer));
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                        "*collection" => "/nluu10ot/home/grp-intake-testgroup/study1",
                    ),
                array("*size")
                );

            $result = $rule->execute();

            var_dump($result);

            return $result["*size"];
        } catch(exception $e) {
            echo $e->showStacktrace();
            return false;
        }
    }

    static public function countSubFiles($iRodsAccount, $path) {
        $ruleBody = '
            myRule {
                iiFileCount(*path, *totalSize, *dircount, *filecount);
                
            }
        ';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array("*path" => $path),
                array("*totalSize", "*dircount", "*filecount")
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

}