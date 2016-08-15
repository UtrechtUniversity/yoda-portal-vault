<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('UPDATE_SUCCESS', 0);
define('DELETE_FAILED', - 1);
define('ADD_FAILED', -2);
define('UPDATE_FAILED', -3);

class MetadataModel extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function processResults($iRodsAccount, $object, $changes) {
        $removeTemplate = <<<'RULE'
    *kverr = errorcode(msiAddKeyVal(%1$s, %2$s, %3$s));
    *rerr = -1;
    if(*kverr == 0) {
        *rerr = errorcode(msiRemoveKeyValuePairsFromObj(%1$s, *objectPath, *t));
    }
    if(*kverr != 0 || *rerr != 0) {
        writeLine("serverLog", "Could not remove '%3$s' from key '%2$s' for *objectPath");
        writeLine("serverLog", "Got error for creating keyval pair *kverr and for removing *rerr");
        *removeFailed = cons(%2$s, *removeFailed);
    }


RULE;

        $addTemplate = <<<'RULE'
    *kverr = errorcode(msiAddKeyVal(%1$s, %2$s, %3$s));
    *aerr = -1;
    if(*kverr == 0) {
        *aerr = errorcode(msiAssociateKeyValuePairsToObj(%1$s, *objectPath, *t));
    }
    if(*kverr != 0 || *aerr != 0) {
        writeLine("serverLog", "Could not add '%3$s' to key '%2$s' for *objectPath");
        writeLine("serverLog", "Got error for creating keyval pair *kverr and for adding *aerr");
        *addFailed = cons(%2$s, *addFailed);
    }


RULE;
        
        // 1) keyValPair to remove
        // 2) key for key val pair to update
        // 3) value to remove from key
        // 4) keyValPair to add
        // 5) value to add to key
        $replaceTemplate = <<<'RULE'
    *kverr = errorcode(msiAddKeyVal(%1$s, %2$s, %3$s));
    *urerr = -1;
    *uaerr = -1; 
    if(*kverr == 0) {
        *urerr = errorcode(msiRemoveKeyValuePairsFromObj(%1$s, *objectPath, *t));
        if(*urerr == 0) {
            *kverr = errorcode(msiAddKeyVal(%4$s, %2$s, %5$s));
            if(*kverr == 0) {
                *uaerr = errorcode(msiAssociateKeyValuePairsToObj(%4$s, *objectPath, *t));
            }
        }
    }
    if(*urerr != 0 || *uaerr != 0) {
        writeLine("serverLog", "Could not update from '%3$s' to '%5$s' on '%2$s' for *objectPath");
        writeLine("serverLog", "Got error removing *urerr and adding *uaerr");
        *updateFailed = cons(%2$s, *updateFailed);
    }


RULE;
        $params = array("*objectPath" => $object);
        $ruleBody = <<<RULE
myRule {
    *removeFailed = list();
    *addFailed = list();
    *updateFailed = list();
    msiGetObjType(*objectPath, *t);


RULE;
        $a = 0;
        $d = 0;
        $k = 0;
        $kv = 0;
        $prfx = $this->metadatafields->getPrefix($object);
        foreach($changes as $key => $valueList) {
            $kvar = "*key" . $k;
            $params[$kvar] = $prfx . $key;
            $k++;
            foreach($valueList as $values) {
                if($values->delete && $values->add) {
                    $dv = "*delVal" . $d;
                    $params[$dv] = $values->delete;
                    $d++;
                    $av = "*addVal" . $a;
                    $params[$av] = $values->add;
                    $a++;
                    $kv1 = "*kv" . $kv;
                    $kv++;
                    $kv2 = "*kv" . $kv;
                    $kv++;

                    $ruleBody .= sprintf($replaceTemplate, $kv1, $kvar, $dv, $kv2, $av);
                } else if($values->delete) {
                    $dv = "*delVal" . $d;
                    $params[$dv] = $values->delete;
                    $d++;
                    $kv1 = "*kv" . $kv;
                    $kv++;

                    $ruleBody .= sprintf($removeTemplate, $kv1, $kvar, $dv);                    
                } else {
                    $av = "*addVal" . $a;
                    $params[$av] = $values->add;
                    $a++;
                    $kv1 = "*kv" . $kv;
                    $kv++;

                    $ruleBody .= sprintf($addTemplate, $kv1, $kvar, $av);
                }
            }
        }

        $ruleBody .= <<<'RULE'

    uuJoin(",", *addFailed, *addErrors);
    uuJoin(",", *removeFailed, *removeErrors);
    uuJoin(",", *updateFailed, *updateErrors);
}
RULE;
        // echo sprintf('<pre>%s</pre>', $ruleBody);
        // var_dump($params);

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                $params,
                array(
                    "*addErrors", "*removeErrors", "*updateErrors"
                )
            );

            $result = $rule->execute();

            $deleteErrors = explode(",", $result["*removeErrors"]);
            $addErrors = explode(",", $result["*addErrors"]);
            $updateErrors = explode(",", $result["*updateErrors"]);

            $status = array(
                "success" => ($this->isEmpty($deleteErrors) && $this->isEmpty($addErrors) && $this->isEmpty($updateErrors)),
                "delete" => $this->isEmpty($deleteErrors) ? false : $deleteErrors,
                "add" => $this->isEmpty($addErrors) ? false : $deleteErrors,
                "update" => $this->isEmpty($updateErrors) ? false : $updateErrors
            );

            return $status;

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return UPDATE_FAILED;
        }

        return UPDATE_FAILED;
    }

    private function isEmpty($val) {
        if(is_array($val)) return !$val || sizeof($val) === 0 || sizeof($val) === 1 && $val[0] === "";
        else if (is_string($val)) return $val === "";
        else return $val === null;
    }

    /**
     * This update function is depricated, as it had a bug where two values could appear on a key
     * that only allowed a single one:
     * If a user x loaded the meta data screen and
     * User y loaded the same meta data screen
     * user y updated a value z
     * user x then updated a value z,
     * The action of user x tried to remove a no longer existing value, but still associated the new
     *      value
     * The result: two values on the same key, where only one would show up in the portal.
     * Multi value keys still have this problem, but as both values show up in the portal, this is better
     *
     * Use the new processResults function above
     * This function is kept in place in case a challenging bug is found after all in the new function
     */
    public function processResults_depricated($iRodsAccount, $object, $deleteArr, $addArr) {
        $params = array("*objectPath" => $object);

        $ruleBody = "myRule{\n\t*error1a = 0\n\t*error2a = 0; \n\tmsiGetObjType(*objectPath, *t);\n\t";

        $addKVTemplate = '*kverr = errorcode(msiAddKeyVal(%1$s, "%2$s", "%3$s"));';
        $execTemplate = 'if(*kverr == 0) {*error%1$da = *error%1$da + errorcode(%2$s(%3$s, "*objectPath", *t)); }';
        $logTemplate = 'writeLine("serverLog", "%1$s key - \'%2$s\' - and value - \'%3$s\' - pair to/from object \'*objectPath\'")';

        if(!empty($deleteArr[0])) {
            for($i = 0; $i < sizeof($deleteArr); $i++) {
                $j = 0;
                foreach($deleteArr[$i] as $kv) {
                    $pfx = sprintf("%d_%d", $i, $j);
                    $key = $this->metadatafields->prefixKey($kv->key, $object);
                    $params = array_merge($params, array("*dkey" . $pfx => $key, "*dval" . $pfx => $kv->value));
                    $ruleBody .= sprintf($addKVTemplate, "*kvd" . $pfx, "*dkey" . $pfx, "*dval" . $pfx) . "\n\t";
                    $ruleBody .= sprintf($logTemplate, "Removing", "*dkey" . $pfx, "*dval" . $pfx) . "\n\t";
                    $ruleBody .= sprintf($execTemplate, 1, "msiRemoveKeyValuePairsFromObj", "*kvd" . $pfx) . "\n\t";
                    $j++;
                }
            }
        }

        if(!empty($addArr[0])) {
            for($i = 0; $i < sizeof($addArr); $i++) {
                $j = 0;
                foreach($addArr[$i] as $kv) {
                    $pfx = sprintf("%d_%d", $i, $j);
                    $key = $this->metadatafields->prefixKey($kv->key, $object);
                    $params = array_merge($params, array("*akey" . $pfx => $key, "*aval" . $pfx => $kv->value));
                    $ruleBody .= sprintf($addKVTemplate, "*kva" . $pfx, "*akey" . $pfx, "*aval" . $pfx) . "\n\t";
                    $ruleBody .= sprintf($logTemplate, "Adding", "*akey" . $pfx, "*aval" . $pfx) . "\n\t";
                    $ruleBody .= sprintf($execTemplate, 2, "msiAssociateKeyValuePairsToObj", "*kva" . $pfx) . "\n\t";
                    $j++;
                }  
            }
        }
        $ruleBody .= "*error1 = str(*error1a);\n\t*error2 = str(*error2a);\n}";

        echo sprintf("<pre>%s</pre>", $ruleBody);

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                $params,
                array(
                    "*error1", "*error2"
                )
            );

            // $result = $rule->execute();

            $deleteStatus = $result["*error1"] == "0" ? 0 : -1;
            $addStatus = $result["*error2"] == "0" ? 0 : -2;

            return $deleteStatus + $addStatus;
        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return UPDATE_FAILED;
        }

        return UPDATE_FAILED;
    }

    public function getValuesForKeys2($rodsaccount, $keyList, $object) {
        $prefixedKeyList = array();
        foreach($keyList as $key) {
            $prefixedKeyList[$this->metadatafields->prefixKey($key, $object)] = $key;
        }

        try {
            $prodsdir = new ProdsDir($rodsaccount, $object);
            $metadatas = $prodsdir->getMeta();

            $rodsKVPairs = array();
            foreach($metadatas as $key => $val) {
                if(array_key_exists($val->name, $rodsKVPairs)) {
                    $rodsKVPairs[$val->name][] = htmlentities($val->value);
                } else {
                    $rodsKVPairs[$val->name] = array(htmlentities($val->value));
                }
            }

            $keyValuePairs = array();
            foreach($prefixedKeyList as $prefixed_key => $key) {
                $keyValuePairs[$key] = array_key_exists($prefixed_key, $rodsKVPairs) ? $rodsKVPairs[$prefixed_key] : array();
            }   

            return $keyValuePairs;

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return false;
        }
    }

    public static function getOwner($iRodsAccount, $collection) {
    	$ruleBody = '
    		myRule {
    			iiGetOwner(*path, *owner);
    		}';

    	try {
    		$rule = new ProdsRule(
                $iRodsAccount,
    			$ruleBody,
    			array(
    				"*path" => $collection,
    			),
    			array(
    				'*owner'
    			)
    		);

    		$result = $rule->execute();

    		return $result["*owner"];
    	} catch(RODSException $e) {
            return false;
        }
        return false;
    }


    public static function getMetadataForKeyLike($iRodsAccount, $key, $searchString) {
        $ruleBody = '
        myRule {
            *isCollection = true;
            uuIiGetAvailableValuesForKeyLike(*key, *searchString, *isCollection, *values);
            uuJoin("#;#", *values, *str);
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*key" => $key,
                    "*searchString" => $searchString
                    ),
                array("*str")
            );

            $result = $rule->execute();

            if($result && array_key_exists("*str", $result)) {
                $like = explode("#;#", $result["*str"]);
                return array_slice($like, 1);
            }

        } catch(RODSException $e) {
            return false;
        }
        return false;
    }


}