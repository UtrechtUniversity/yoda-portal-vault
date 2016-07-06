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

    public static function processResults($iRodsAccount, $object, $deleteArr, $addArr) {
        $alphabet = "abcdefghijklmnopqrstuvwxyz";
        $ruleBody = "myRule{\n\t*error1a = 0\n\t; *error2a = 0;\n\tmsiGetObjType(*objectPath, *t);\n\t";
        $kvp = "*";

        if(!empty($deleteArr[0])){
            for($i = 0; $i < sizeof($deleteArr); $i++){
                $kvp .= $alphabet[$i % 25];
                foreach($deleteArr[$i] as $kv) {
                    $ruleBody .= "msiAddKeyVal(" . $kvp . ", '" . $kv->key . "', '" . $kv->value . "');\n\t";
                    $ruleBody .= "writeLine(\"serverLog\", \"Added call to remove key value pair ";
                    $ruleBody .= $kv->key . ":" . $kv->value . ", resulting in " . $kvp . "\");\n\t";
                }
                $ruleBody .= "*error1a = *error1a + errorcode(msiRemoveKeyValuePairsFromObj(" . $kvp . ", *objectPath, *t));\n\t";
            }
        }


        if(!empty($addArr[0])) {
            for($i = 0; $i < sizeof($addArr); $i++){
                $kvp .= $alphabet[$i % 25];
                foreach($addArr[$i] as $kv) {
                    $ruleBody .= "msiAddKeyVal(" . $kvp . ", '" . $kv->key . "', '" . $kv->value . "');\n\t";
                    $ruleBody .= "writeLine(\"serverLog\", \"Added call to add key value pair ";
                    $ruleBody .= $kv->key . ":" . $kv->value . "\");\n\t";
                }
                $ruleBody .= "*error2a = *error2a + errorcode(msiAssociateKeyValuePairsToObj(" . $kvp . ", *objectPath, *t));\n\t";
            }
        }
        
        $ruleBody .= "*error1 = str(*error1a);\n\t*error2 = str(*error2a);\n}";

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*objectPath" => $object
                ),
                array(
                    "*error1", "*error2"
                )
            );

            $result = $rule->execute();
            var_dump($result);
            $deleteStatus = $result["*error1"] == "0" ? 0 : -1;
            $addStatus = $result["*error2"] == "0" ? 0 : -2;

            return $deleteStatus + $addStatus;
        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return UPDATE_FAILED;
        }

        return UPDATE_FAILED;
    }

    public static function getValuesForKeys2($iRodsAccount, $keyList, $object) {
        try{
            $prodsdir = new ProdsDir($iRodsAccount, $object);
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
            foreach($keyList as $key) {
                $keyValuePairs[$key] = array_key_exists($key, $rodsKVPairs) ? $rodsKVPairs[$key] : array();
            }
            return $keyValuePairs;

        } catch(RODSException $e) {
            echo $e->showStacktrace();
            return false;
        }
    }

    public static function getValueForKey($iRodsAccount, $key, $object, $isCollection) {
    	$ruleBody = '
    		myRule {
    			if(*isCollection == "1") {
                    *isColl = true;
                } else {
                    *isColl = false;
                }

    			uuIiGetValueForKey(*key, *object, *isColl, *value);
    			*value = str(*value);
    		}';

    	try {
    		$rule = new ProdsRule(
                $iRodsAccount,
    			$ruleBody,
    			array(
    				"*key" => $key,
    				"*object" => $object,
    				"*isCollection" => $isCollection
    			),
    			array(
    				'*value'
    			)
    		);

    		$result = $rule->execute();

    		return $result["*value"];
    	} catch(RODSException $e) {
    		echo $e->showStacktrace();
            return false;
        }
        return false;
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
        }';

        try {
            $rule = new ProdsRule(
                $iRodsAccount,
                $ruleBody,
                array(
                    "*key" => $key,
                    "*searchString" => $searchString
                    ),
                array("*values")
            );

            $result = $rule->execute();

            if($result && array_key_exists("*values", $result)) {
                $like = explode("#;#", $result["*values"]);
                return array_slice($like, 1);
            }

        } catch(RODSException $e) {
            return false;
        }
        return false;
    }


}