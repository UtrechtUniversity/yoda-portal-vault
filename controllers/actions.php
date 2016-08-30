<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| USER ACTIONS CONTROLLER
| -------------------------------------------------------------------------
| This file contains REST functions that are called for certain user actions
|
*/
class Actions extends MY_Controller
{
   
    public function __construct()
    {
        parent::__construct();

        $this->load->model("dataset");
        $this->load->model("rodsuser");
        $this->load->model("study");
        $this->load->helper("intake");
        $this->load->library('pathlibrary');
        $this->load->helper('language');
        $this->load->language('intake');
        $this->load->language('errors');
        $this->load->language('form_errors');
    }

    /**
     * Performs a snapshot (create version) if a user clicks the create version
     * button
     */
    public function snapshot()
    {
    	$result = $this->performSnapshot();

		displayMessage($this, $result->message, $result->error, $result->type);
		$redir = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $this->intake->getRedirect();
		redirect($redir, 'refresh');
    }

    /**
     * Sub function of snapshot() that handles creating the actual version and prepares
     * the redirect method and message
     */
    private function performSnapshot() {
    	$status = (object)array("message" => "", "error" => false, "type" => "info");

    	$rodsaccount = $this->rodsuser->getRodsAccount();
    	$directory = $this->input->post('directory');
    	$folder = substr(strrchr($directory, "/"), 1);
    	$snapOf = array();

    	$pathStart = $this->pathlibrary->getPathStart($this->config);
    	$segments = $this->pathlibrary->getPathSegments($rodsaccount, $pathStart, $directory, $dir);
    	$studyID = $segments[0];

    	$success = true;

    	if(!is_array($segments)) {
    		$status->message = sprintf(lang('intake_actions_snapshot_path_invalid'), $directory);
			return $status;
    	}

    	$this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $currentLevel, $currentDepth);

    	if($success && $this->input->post('checked_studies')) {
    		$nextLevelPermissions = $this->study->getPermissionsForLevel($currentDepth + 1, $studyID);
    		if($nextLevelPermissions->canSnapshot === false) {
    			$status->message = lang('intake_actions_no_permission');
    			return $status;
    		} else {
	    		foreach($this->input->post('checked_studies') as $study) {
	    			$snapOf[] = sprintf("%s/%s", $directory, $study);
	    		}
	    	}
    	} else if($success) {
    		$levelPermissions = $this->study->getPermissionsForLevel($currentDepth, $studyID);
    		if($levelPermissions->canSnapshot === false) {
    			$status->message = lang('intake_actions_no_permission');
    			return $status;
    		} else {
    			$snapOf[] = $directory;
    		}
    	}

    	$succes_directories = array();

    	if(sizeof($snapOf) > 0) {
    		foreach($snapOf as $dir) {
    			$result = $this->dataset->prepareDatasetForSnapshot($rodsaccount, $dir);
    			if($result) {
    				array_push($succes_directories, $dir);
    			} else {
    				$success = false;
    			}
    		}
    	} else {
    		$status->message = lang('intake_actions_no_selected_folder');
    		return $status;
    	}

    	if($success) {
			if(sizeof($succes_directories) > 1) {
    			$status->message = sprintf(
                    lang('intake_actions_snapshots_in_progress'), human_implode($datasets, ", ", " and "));
			} else if (sizeof($succes_directories) == 0) {
				$status->message = lang('intake_actions_no_selected_folder');
			} else {
				$status->message = sprintf(lang('intake_actions_snapshot_in_progress'), $succes_directories[0]);
			}
			$status->error = false;
			$status->type = "info";
			return $status;
		} else {
			$status->message = lang('intake_actions_snapshot_general_error');
			return $status;
		}
    }

    /**
     * Method that tries to unlock a collection, if it is not yet picked up by the filesystem
     */
    public function unlock() {
    	$rodsaccount = $this->rodsuser->getRodsAccount();
		$directory = $this->input->post('directory');
		$folder = substr(strrchr($directory, "/"), 1);
		$snapOf = array();

		$pathStart = $this->pathlibrary->getPathStart($this->config);
		$segments = $this->pathlibrary->getPathSegments($rodsaccount, $pathStart, $directory, $dir);
		$studyID = $segments[0];

		if(!is_array($segments)) {
			// TODO 
			$message = sprintf(lang('intake_actions_unlock_no_valid_directory'), $directory);
			$error = true;
			$type = "error";
		} else {
			$this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $currentLevel, $currentDepth);
			$levelPermissions = $this->study->getPermissionsForLevel($currentDepth, $studyID);

			if($levelPermissions->canSnapshot === false) {
				$message = sprintf("ntl: You do not have permission to unlock the folder <i>%s</i>", $folder);
				$error = true;
				$type = "error";
			} else {
				if($this->dataset->removeSnapshotLockFromDataset($rodsaccount, $directory)) {
					$message = sprintf(lang('intake_actions_snapshot_cancelled'), $folder);
					$error = false;
					$type = "info";
				} else {
					$message = sprintf(lang('intake_actions_snapshot_cancel_fail'), $folder);
					$error = true;
					$type = "error";
				}
			}
		}

		displayMessage($this, $message, $error, $type);
        $referer = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 
            $redir = $this->intake->getRedirect($this->input->post('studyId'));
		redirect($referer, 'refresh');
		
    }

}