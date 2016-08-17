<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('DIR_NOT_INTAKE', -10);
define('NO_DIRECTORY', -11);

class PathLibrary {

	public function getPathSegments($rodsaccount, $pathStart, $path, &$dir) {
        $studyIDBegin = strpos(
            $path,
            $pathStart
        );
        
        if($studyIDBegin !== 0) {
            // error
            // echo "Not a valid intake folder";
            return DIR_NOT_INTAKE;
        } else {
            try {
                $dir = new ProdsDir($rodsaccount, $path, true);

                return explode("/", substr($path, strlen($pathStart)));
               

            } catch(RODSException $e) {
                return NO_DIRECTORY;
            }
        }
	}

	public function getPathStart($config) {
		return sprintf(
                "/%s/home/%s", 
                $config->item('rodsServerZone'), 
                $config->item('intake-prefix')
            );
	}

    public function getCurrentLevelAndDepth($config, $segments, &$level, &$depth) {
        $depth = sizeof($segments) - 1;

        if(sizeof($config->item('level-hierarchy')) >= sizeof($segments)) {
            $level = $config->item('level-hierarchy')[$depth];
        } else {
            $level = $config->item('default-level');
        }
    }
}