<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Actions extends MY_Controller
{
   
    public function __construct()
    {
        parent::__construct();

        $this->load->model("dataset2");
        $this->load->model("metadata");
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
	    		$result = $this->dataset2->prepareDatasetForSnapshot($rodsuser, $this->input->post("studyRoot"), $dataset);
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
			redirect("/intake-ilab/intake/index", 'refresh');
		}
    }

    public function unlock()
    {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$dataset = $this->input->post("unlock_study");

    	if($this->dataset2->removeSnapshotLockFromDataset($rodsuser, $this->input->post("studyRoot"), $dataset)) {
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
			redirect("/intake-ilab/intake/index", 'refresh');
		}
    }

    public function unlockAll() {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$result = $this->dataset2->unlockAll($rodsuser);
    	displayMessage($this, "Result: " . $result);
    	if(isset($_SERVER['HTTP_REFERER'])) {
			redirect($_SERVER['HTTP_REFERER'], 'refresh');
		} else {
			redirect("/intake-ilab/intake/index", 'refresh');
		}
    }

    public function testFunction() 
    {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$result = $this->dataset2->testFunction($rodsuser);
    	displayMessage($this, "Result: " . $result);
    	if(isset($_SERVER['HTTP_REFERER'])) {
			redirect($_SERVER['HTTP_REFERER'], 'refresh');
		} else {
			redirect("/intake-ilab/intake/index", 'refresh');
		}
    }

    public function updateMetadata() {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$newOwner = $this->input->post('owner');
    	$collection = $this->input->post('studyRoot') . "/" . $this->input->post('studyID');
    	if($this->metadata->setForKey($rodsuser, "dataset_owner", $newOwner, $collection, true)) {
    		$message = "Meta data was successfuly updated";
			$error = false;
			$type = "info";
    	} else {
    		$message = "Updating of meta data failed";
			$error = true;
			$type = "error";
    	}
  //   	displayMessage($this, $message, $error, $type);
  //   	if(isset($_SERVER['HTTP_REFERER'])) {
		// 	redirect($_SERVER['HTTP_REFERER'], 'refresh');
		// } else {
		// 	redirect("/intake-ilab/intake/index/", 'refresh');
		// }
    }
}