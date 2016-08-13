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

    public function processResults($iRodsAccount, $object, $deleteArr, $addArr) {
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
                    $key = $this->prefixKey($object, $kv->key);
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
                    $key = $this->prefixKey($object, $kv->key);
                    $params = array_merge($params, array("*akey" . $pfx => $key, "*aval" . $pfx => $kv->value));
                    $ruleBody .= sprintf($addKVTemplate, "*kva" . $pfx, "*akey" . $pfx, "*aval" . $pfx) . "\n\t";
                    $ruleBody .= sprintf($logTemplate, "Adding", "*akey" . $pfx, "*aval" . $pfx) . "\n\t";
                    $ruleBody .= sprintf($execTemplate, 2, "msiAssociateKeyValuePairsToObj", "*kva" . $pfx) . "\n\t";
                    $j++;
                }  
            }
        }
        $ruleBody .= "*error1 = str(*error1a);\n\t*error2 = str(*error2a);\n}";

        // echo sprintf("<pre>%s</pre>", $ruleBody);

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                $params,
                array(
                    "*error1", "*error2"
                )
            );

            $result = $rule->execute();

            $deleteStatus = $result["*error1"] == "0" ? 0 : -1;
            $addStatus = $result["*error2"] == "0" ? 0 : -2;

            return $deleteStatus + $addStatus;
        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return UPDATE_FAILED;
        }

        return UPDATE_FAILED;
    }

    private function prefixKey($object, $key) {
        $prefixed_key = "";
        if($this->config->item("metadata_prefix") && $this->config->item("metadata_prefix") !== false) {
            $prefixed_key .= $this->config->item("metadata_prefix");
        }
        $meta = $this->metadatafields->getMetaForLevel($object);
        if(array_key_exists("prefix", $meta) && $meta["prefix"] !== false) {
            $prefixed_key .= $meta["prefix"];
        }
        $prefixed_key .= $key;

        return $prefixed_key;
    }

    private function unPrefixKey($object, $prefixed_key) {
        if($this->config->item("metadata_prefix") && $this->config->item("metadata_prefix") !== false) {
            if(strpos($prefixed_key, $this->config->item("metadata_prefix")) === 0) {
                $prefixed_key = substr($prefixed_key, strlen($this->config->item("metadata_prefix")));
            } else {
                return false;
            }
        }
        $meta = $this->metadatafields->getMetaForLevel($object);
        if(array_key_exists("prefix", $meta) && $meta["prefix"] !== false) {
            if(strpos($prefixed_key, $meta["prefix"]) === 0) {
                $prefixed_key = substr($prefixed_key, strlen($meta["prefix"]));
            } else {
                return false;
            }
        }

        return $prefixed_key;
    }

    public function getValuesForKeys2($rodsaccount, $keyList, $object) {
        $prefixedKeyList = array();
        foreach($keyList as $key) {
            $prefixedKeyList[$this->prefixKey($object, $key)] = $key;
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