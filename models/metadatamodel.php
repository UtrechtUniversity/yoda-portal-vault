<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MetadataModel extends CI_Model {

    public function __construct()
    {
        parent::__construct();
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

    public static function setForKey($iRodsAccount, $key, $value, $object, $isCollection) {
    	$ruleBody = '
    		myRule {
    			if(*isCollection == "1") {
                    *isColl = true;
                } else {
                    *isColl = false;
                }

                *recursive = false;
                
    			uuIiReplaceValueForKey(*key, *value, *object, *isColl, *recursive, *status);
    			*status = str(*status);
    		}';

    	try {
    		$rule = new ProdsRule(
                $iRodsAccount,
    			$ruleBody,
    			array(
    				"*key" => $key,
    				"*value" => $value,
    				"*object" => $object,
    				"*isCollection" => $isCollection
    			),
    			array(
    				'*status'
    			)
    		);

    		$result = $rule->execute();
    		var_dump($result);
    		return $result["*status"] == "0";
    	} catch(RODSException $e) {
    		echo $e->showStacktrace();
            return false;
        }
        return false;
    }

    public static function deleteKeyValuePair($iRodsAccount, $key, $value, $object, $isCollection) {
    	$ruleBody = '
    		myRule {
    			if(*isCollection == "1") {
                    *isColl = true;
                } else {
                    *isColl = false;
                }

                *recursive = false;
                
    			uuIiRemoveKeyValueFromObject(*key, *value, *object, *isColl, *recursive, *status);
    			*status = str(*status);
    		}';

    	try {
    		$rule = new ProdsRule(
                $iRodsAccount,
    			$ruleBody,
    			array(
    				"*key" => $key,
    				"*value" => $value,
    				"*object" => $object,
    				"*isCollection" => $isCollection
    			),
    			array(
    				'*status'
    			)
    		);

    		$result = $rule->execute();

    		return $result["*status"] == "0";
    	} catch(RODSException $e) {
            return false;
        }
        return false;
    }

    public static function deleteAllValuesForKey($iRodsAccount, $key, $object, $isCollection) {
    	$ruleBody = '
    		myRule {
    			if(*isCollection == "1") {
                    *isColl = true;
                } else {
                    *isColl = false;
                }

                *recursive = false;
                
    			uuIiRemoveKeyValueFromObject(*key, *object, *isColl, *recursive, *status);
    			*status = str(*status);
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
    				'*status'
    			)
    		);

    		$result = $rule->execute();

    		return $result["*status"] == "0";
    	} catch(RODSException $e) {
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
}