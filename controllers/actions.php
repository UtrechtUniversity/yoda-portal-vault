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
    	$rodsaccount = $this->rodsuser->getRodsAccount();
    	$directory = $this->input->post('directory');
    	$snapOf = array();

    	$pathStart = $this->pathlibrary->getPathStart($this->config);
    	$segments = $this->pathlibrary->getPathSegments($rodsaccount, $pathStart, $directory, $dir);
    	$studyID = $segments[0];

    	$success = true;

    	if(!is_array($segments)) {
    		// TODO 
    		$success = false;
    		return false;
    	}

    	$this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $currentLevel, $currentDepth);

    	if($this->input->post('checked_studies')) {
    		$nextLevelPermissions = $this->study->getPermissionsForLevel($currentDepth + 1, $studyID);
    		if($nextLevelPermissions->canSnapshot === false) {
    			// TODO
    			$success = false;
    			return false;
    		} else {
	    		foreach($this->input->post('checked_studies') as $study) {
	    			$snapOf[] = sprintf("%s/%s", $directory, $study);
	    		}
	    	}
    	} else {
    		$levelPermissions = $this->study->getPermissionsForLevel($currentDepth, $studyID);
    		if($levelPermissions->canSnapshot === false) {
    			// TODO 
    			$success = false;
    			return false;
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
    	}

    	if($success) {
			if(sizeof($succes_directories) > 1) {
    			$message = "Snapshots of the studies " . human_implode($datasets, ", ", " and ") . " are currently being created. This might take a while";
			} else if (sizeof($succes_directories) == 0) {
				$message = "No dataset selected";
			} else {
				$message = $succes_directories[0] . " is currently being snapshotted";
			}
			$error = false;
			$type = "info";
		} else {
			$message = "Some datasets could not be prepared to be moved to the vault. Try again later.";
			$error = true;
			$type = "error";
		}

		displayMessage($this, $message, $error, $type);
		$redir = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $this->intake->getRedirect($studyID);
		redirect($redir, 'refresh');

    }

    public function unlock()
    {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$dataset = $this->input->post("unlock_study");

    	if($this->dataset->removeSnapshotLockFromDataset($rodsuser, $this->input->post("studyRoot"), $dataset)) {
			$message = "Snapshot cancelled for " . $dataset;
			$error = false;
			$type = "info";
    	} else {
    		$message = "The snapshot for the dataset could not be cancelled. Please wait until the snapshot is finished";
			$error = true;
			$type = "error";
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