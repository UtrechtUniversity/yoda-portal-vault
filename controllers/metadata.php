<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MetaData extends MY_Controller
{
   
    public function __construct()
    {
        parent::__construct();
        $this->load->model('metadatamodel');
        $this->load->model('rodsuser');
        $this->load->helper('intake');
        $this->load->library('metadatafields');
        $this->load->model('study');
    }

 	public function update() {
        $permissions = $this->study->getIntakeStudyPermissions($this->input->post('studyID'));
        if($permissions->administrator) {
     		$metDat = $this->input->post('metadata');
            $rodsuser = $this->rodsuser->getRodsAccount();
            $collection = $this->input->post('studyRoot') . "/" . $this->input->post('dataset');
            $fields = $this->metadatafields->getFields($collection, true);

     		if(sizeof($metDat) == 0) return $this->NotifyNoChange();

            $failedFor = array();

     		foreach($metDat as $metKey => $metVal) {
                $result = false;

                if($metVal == "") {
                    continue; // hack: deleting doesn't work yet.
                    // TODO: should not be allowed in most cases anyway?
                    $result = $this->metadatamodel->deleteAllValuesForKey(
                        $rodsuser,
                        $metKey,
                        $collection,
                        true
                    );
                } else {
                    $result = $this->metadatamodel->setForKey(
                            $rodsuser, 
                            $metKey, 
                            $metVal, 
                            $collection,
                            true
                        );
                }

                if(!$result) {
                    array_push($failedFor, $fields[$metKey]["label"]);
                }
     		}

            if(sizeof($failedFor)) {
                $message = sprintf("Meta data failed to update for %s", human_implode($failedFor, ", ", " and "));
                $error = true;
                $type = "error";
            } else {
                $message = "Metadata updated succesfully";
                $error = false;
                $type = "info";
            }
        } else {
            $message = "You do not have admin rights on the " . $this->input->post("studyID") . " study and can therefor not update metadata";
            $error = true;
            $type = "warning";
        }

        displayMessage($this, $message, $error, $type);
        if(isset($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        } else {
            $redir = $this->modulelibrary->getRedirect($this->input->post('studyId'), $this->input->post('dataset'));
            redirect($redir, 'refresh');
        }
    }

    private function NotifyNoChange() {
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

    public function metasuggestions($key = null) {
        $query = $this->input->get('query');
        $rodsAccount = $this->rodsuser->getRodsAccount();
        $options = $this->metadatamodel->getMetadataForKeyLike($rodsAccount, $key, $query);
        $this->output
            ->set_content_type('application/json')
            ->set_output(
                json_encode(
                    $options
                )
            );
    }

}