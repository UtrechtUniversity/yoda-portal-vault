<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MetaData extends MY_Controller
{

   
    public function __construct()
    {
        parent::__construct();
        $this->load->model('metadatamodel');
        $this->load->model('rodsuser');
        $this->load->helper('intake');
    }

 	public function update() {
 		$metDat = $this->input->post('metadata');

 		if(sizeof($metDat) == 0) return $this->noChange();

 		foreach($metDat as $metKey => $metVal) {
 			echo "Meta data key: " . $metKey . "<br/>";
 			echo "Meta data val: " . $metVal . "<br/><br/>";
 		}
    }

    private function nosweat() {
    	$rodsuser = $this->rodsuser->getRodsAccount();
    	$newOwner = $this->input->post('owner');
    	$collection = $this->input->post('studyRoot') . "/" . $this->input->post('dataset');
    	if($this->metadatamodel->setForKey($rodsuser, "dataset_owner", $newOwner, $collection, true)) {
    		$message = "Meta data was successfuly updated";
			$error = false;
			$type = "info";
    	} else {
    		$message = "Updating of meta data failed";
			$error = true;
			$type = "error";
    	}
    	displayMessage($this, $message, $error, $type);
  //   	if(isset($_SERVER['HTTP_REFERER'])) {
		// 	redirect($_SERVER['HTTP_REFERER'], 'refresh');
		// } else {
		// 	$redir = $this->modulelibrary->getRedirect($this->input->post('studyId'), $this->input->post('dataset'));
		// 	redirect($redir, 'refresh');
		// }
    }

    private function noChange() {
    	$message = "No changes made";
    	$error = false;
    	displayMessage($this, $message, $error, $type);
    	if(isset($_SERVER['HTTP_REFERER'])) {
			redirect($_SERVER['HTTP_REFERER'], 'refresh');
		} else {
			$redir = $this->modulelibrary->getRedirect($this->input->post('studyId'), $this->input->post('dataset'));
			redirect($redir, 'refresh');
		}
    }

    public function test() {
    	$num = 3;
    	$location = "forest";
    	$format = 'The %2$s contains %1$04d monkeys<br/>';
		echo sprintf($format, $num, $location);

		$format = 'The %2$s contains %1$d monkeys.
           That\'s a nice %2$s full of %1$d monkeys.<br/>';
        echo sprintf($format, $num, $location);

    	$format = '%2$s first and then %1$s and then %2$s again';
    	echo sprintf($format, "'first arg'", "'second arg'");
    }

}