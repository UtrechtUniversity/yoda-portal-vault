<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
    }

    public function snapshot()
    {
    	$result = $this->performSnapshot();

		displayMessage($this, $result->message, $result->error, $result->type);
		$redir = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $this->intake->getRedirect($studyID);
		redirect($redir, 'refresh');
    }

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
    		$status->message = sprintf("ntl: %s path does not seem to be a valid directory. The collection could not be snapshotted", $directory);
			return $status;
    	}

    	$this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $currentLevel, $currentDepth);

    	if($success && $this->input->post('checked_studies')) {
    		$nextLevelPermissions = $this->study->getPermissionsForLevel($currentDepth + 1, $studyID);
    		if($nextLevelPermissions->canSnapshot === false) {
    			$status->message = "You do not have permission to create snapshots for these folders";
    			return $status;
    		} else {
	    		foreach($this->input->post('checked_studies') as $study) {
	    			$snapOf[] = sprintf("%s/%s", $directory, $study);
	    		}
	    	}
    	} else if($success) {
    		$levelPermissions = $this->study->getPermissionsForLevel($currentDepth, $studyID);
    		if($levelPermissions->canSnapshot === false) {
    			$status->message = "You do not have permission to create snapshots for these folders";
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
    		$status->message = "ntl: No folders selected";
    		return $status;
    	}

    	if($success) {
			if(sizeof($succes_directories) > 1) {
    			$status->message = "Snapshots of the studies " . human_implode($datasets, ", ", " and ") . " are currently being created. This might take a while";
			} else if (sizeof($succes_directories) == 0) {
				$status->message = "No dataset selected";
			} else {
				$status->message = $succes_directories[0] . " is currently being snapshotted";
			}
			$status->error = false;
			$status->type = "info";
			return $status;
		} else {
			$status->message = "Some datasets could not be prepared to be moved to the vault. Try again later.";
			return $status;
		}
    }

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
			$message = sprintf("ntl: %s path does not seem to be a valid directory. The collection could not be unlocked", $directory);
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
					$message = sprintf("ntl: Snapshot cancelled for <i>%s</i>", $folder);
					$error = false;
					$type = "info";
				} else {
					$message = sprintf("ntl: The snapshot for the folder <i>%s</s> could not be cancelled. Please wait until the snapshot is finished", $folder);
					$error = true;
					$type = "error";
				}
			}
		}

		displayMessage($this, $message, $error, $type);
		if(isset($_SERVER['HTTP_REFERER'])) {
			redirect($_SERVER['HTTP_REFERER'], 'refresh');
		} else {
			$redir = $this->intake->getRedirect($this->input->post('studyId'));
			redirect($redir, 'refresh');
		}
		
    }

    public function unlockAll() {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$result = $this->dataset->unlockAll($rodsuser);
    	displayMessage($this, "Result: " . $result);
    	if(isset($_SERVER['HTTP_REFERER'])) {
			redirect($_SERVER['HTTP_REFERER'], 'refresh');
		} else {
			$redir = $this->intake->getRedirect($this->input->post('studyId'));
			redirect($redir, 'refresh');
		}
    }

    public function testFunction() {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$result = $this->dataset->testFunction($rodsuser);
  //   	displayMessage($this, "Result: " . $result);
  //   	if(isset($_SERVER['HTTP_REFERER'])) {
		// 	redirect($_SERVER['HTTP_REFERER'], 'refresh');
		// } else {
		// 	$redir = $this->intake->getRedirect($this->input->post('studyId'));
		// 	redirect($redir, 'refresh');
		// }
    }
}