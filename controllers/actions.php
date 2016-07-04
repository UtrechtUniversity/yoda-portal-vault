<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Actions extends MY_Controller
{
   
    public function __construct()
    {
        parent::__construct();

        $this->load->model("dataset");
        $this->load->model("rodsuser");
        $this->load->helper("intake");
    }

    public function snapshot()
    {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$snapOf = $this->input->post("dataset") ? 
    		array(0 => $this->input->post("dataset")) : $this->input->post("checked_studies");

    	$success = true;
    	$datasets = array();
    	if(sizeof($snapOf) > 0) {
	    	foreach($snapOf as $dataset) {
	    		$result = $this->dataset->prepareDatasetForSnapshot($rodsuser, $this->input->post("studyRoot"), $dataset);
	    		if($result) {
	    			array_push($datasets, $dataset);
	    		} else {
	    			$success = false;
	    		}
	    	}
	    }

    	if($success) {
			if(sizeof($datasets) > 1) {
    			$message = "Snapshots of the studies " . human_implode($datasets, ", ", " and ") . " are currently being created. This might take a while";
			} else if (sizeof($datasets) == 0) {
				$message = "No dataset selected";
			} else {
				$message = $datasets[0] . " is currently being snapshotted";
			}
			$error = false;
			$type = "info";
		} else {
			$message = "Some datasets could not be prepared to be moved to the vault. Try again later.";
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