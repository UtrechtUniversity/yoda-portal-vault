<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Debug extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    
    /** 
     * Method calls all iRods rules to unlock UU-locks, to_vault_freezes, to_vault_locks,
     * to_snapshot_freezes and to_snapshot_locks on all collections in the current zone
     * @param $iRodsAccount 	Instance of current users iRods account
     * @return Bool 			Indicating success
     */
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

    /**
     * Function can be adapted to what rules need testing
     */
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
}