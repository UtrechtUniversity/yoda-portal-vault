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
        $this->load->model('metadataschemareader');
        $this->load->library('module', array(__DIR__));
        $this->load->library('pathlibrary');
        $this->load->library('metadataverification');
        $this->load->helper('language');
        $this->load->helper('metadata_prefix_helper');
        $this->load->language('intake');
        $this->load->language('errors');
        $this->load->language('form_errors');
    }

    /**
     * Method that prepares a posted metadata form for updating to iRODS
     */
    public function update() {
        $rodsaccount = $this->rodsuser->getRodsAccount();
        $directory = $this->input->post('directory');
        $folder = substr(strrchr($directory, "/"), 1);

        $message = "";
        $error = false;
        $type = "info";

        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $segments = $this->pathlibrary->getPathSegments($rodsaccount, $pathStart, $directory, $dir);
        $studyID = $segments[0];

        if(!is_array($segments)) {
            $message = sprintf(lang('intake_metadata_dir_invalid'), $directory);
            $error = true;
            $type = "error";
        } else {
            $this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $currentLevel, $currentDepth);
            $levelPermissions = $this->study->getPermissionsForLevel($currentDepth, $studyID);
            if($levelPermissions->canEditMeta === false) {
                $message = sprintf(lang('intake_metadata_no_permission'), $folder);
                $error = true;
                $type = "error";
            } else {
                $formdata = $this->input->post('metadata');
                $shadowData = $this->input->post('metadata-shadow');
                $fields = $this->metadataschemareader->getFields($directory, true);

                $this->checkDependencyProperties($fields, $formdata);

                $wrongFields = $this->veryInput($formdata, $fields);

                if(sizeof($wrongFields) == 0) {

                    // Process results
                    $changes = $this->findChanges($formdata, $shadowData, $fields);
                    // $this->dumpChanges($changes); // Debug purposes only

                    // Update results
                    $rodsaccount = $this->rodsuser->getRodsAccount();
                    $status = $this->metadatamodel->processResults($rodsaccount, $directory, $changes);

                    if($status["success"] === true) {
                        $message = lang('intake_metadata_update_success');
                    } else {
                        $message = $this->buildError($status, $fields);
                        $error = true;
                        $type = "warning";   
                    }
                } else {
                    $message = lang('intake_metadata_input_invalid');
                    $error = true;
                    $type = "warning";

                    $this->session->set_flashdata('incorrect_fields', $wrongFields);
                    $this->session->set_flashdata('form_data', $formdata);
                }
            }
        }

        $referUrl = site_url(array($this->module->name(), "intake", "metadata")) . "?dir=" . urlencode($directory);
        displayMessage($this, $message, $error, $type);
        redirect($referUrl, 'refresh');
    }

    /**
     * Method that builds error messages indicating which fields failed in updating
     * to iRODS.
     * @param   status      The array containing all field keys that have an error
     * @param   $fields     The field definitions array
     * @return  string      List of all fields that could not be updated, grouped
     *                      by action
     */
    private function buildError($status, $fields) {
        $message = lang('intake_metadata_update_failed_general');
        if($status["delete"]) {
            $message .= $this->buildSingleError($status["delete"], $fields, "delete");
        }
        if($status["add"]) {
            $message .= $this->buildSingleError($status["add"], $fields, "add");
        }
        if($status["update"]) {
            $message .= $this->buildSingleError($status["update"], $fields, "update");
        }
        return $message;
    }

    /**
     * Method that generates an error list for a single action that failed
     * @param errorFields   List of fields that failed for the given action
     * @param fields        The field definitions array
     * @param action        The action the fields failed for
     * @return string       Human readible list of fields that failed for the
     *                      given action
     */
    private function buildSingleError($errorFields, $fields, $action) {
        $object = $this->input->post('directory');
        $errors = array();
        foreach($errorFields as $f) {
            $f = unprefixKey($f, $object);
            if(array_key_exists($f, $fields) && array_key_exists("label", $fields[$f])) {
                $errors[] = sprintf('"%1$s"', $fields[$f]["label"]);
            } else {
                $errors[] = sprintf('"%1$s"', $f);
            }
        }
        return sprintf(
            lang('intake_metadata_update_failed_details'), 
            $action, 
            human_implode(
                $errors, 
                ", ", 
                sprintf(' %s ', lang('intake_and'))
            )
        );
    }

    /**
     * Adds a new key "dependencyMet" to the fields definition array,
     * which indicates if the field was visible according to the dependencies.
     * The form data should only be processed if this is the case.
     * If no dependencies are specified, the requirements are automatically
     * met
     * @param $formdata         Posted form data
     * @param $fields           Array of field definitions
     */
    private function checkDependencyProperties(&$fields, $formdata ) {
        foreach($fields as $key => $field) {
            if(array_key_exists("depends", $field) && $field["depends"] !== false) {
                $field["dependencyMet"] = 
                    $this->metadataverification->evaluateRowDependencies($field["depends"], $formdata);
            } else {
                $field["dependencyMet"] = true;
            }
            $fields[$key] = $field;
        }
    }

    /**
     * Method checks for each meta data key wether the input satisfies the constraints
     * @param $formData         The posted data from the form
     * @param $fields           The field definitions defined in the meta data schema
     * @return array            Containing all keys for which the values do not satisfy
     *                          all the contstraints
     */
    private function veryInput($formdata, $fields) {
        $wrongFields = array();
        foreach($formdata as $inputKey => $inputValues) {
            if($fields[$inputKey]["dependencyMet"]) {
                $errors = $this->metadataverification->verifyKey($inputValues, $fields[$inputKey], $formdata, false);
                if(sizeof($errors) > 0) {
                    // array_push($wrongFields, var)
                    $wrongFields[$inputKey] = $errors;
                }
                // array_push($wrongFields, $inputKey);
            }
        }
        return $wrongFields;
    }

    /**
     * Dumps an array of key-value pairs in the findDeletedItems or findAddedItems
     * format in a readible manner
     * @param $keyValueList     List of key value pairs
     * @param $header           H1 tag to be shown above the dump
     */
    private function dumpKeyVals($keyValList, $header) {
        echo "<h1>" . $header . "</h1>";
        echo "<ul>";
        foreach($keyValList as $value) {
            foreach($value as $val){
                echo "<li><b>" . $val->key . "</b>: " . $val->value . "</li>";
            }
        }
        echo "</ul>";
    }

    /**
     * Dumps the array of changed items, so it can be checked which update actions
     * are going to be performed on iRODS
     * For debugging purposes only
     * 
     * @param changes       Array generated by findChanges()
     */
    private function dumpChanges($changes) {
        echo "<h1>Changes</h1>";
        echo "<ul>";
        foreach($changes as $changed_key => $changed_val) {
            $lis = "";
            foreach($changed_val as $val) {
                if($val->delete && $val->add) {
                    $lis .= sprintf('<li><b>%1$s</b> will be deleted and <b>%2$s</b> will be added</li>', $val->delete, $val->add);
                } else if($val->delete) {
                    $lis .= sprintf('<li><b>%1$s</b> will be deleted (nothing is added)</li>', $val->delete);
                } else {
                    $lis .= sprintf('<li>nothing is deleted but <b>%1$s</b> will be added</li>', $val->add);
                }
            }
            echo sprintf(
                '<li><b>%1$s</b><ul>%2$s</ul></li>',
                $changed_key,
                $lis
            );
        }
        echo "</ul>";
    }
   
    /**
     * Function that generates an object list of key-value pairs that should be
     * deleted from the object that contains the meta data.
     * 
     * @param formdata      array of which the keys are meta data keys 
     *                      and the values are the values from the editable fields, 
     *                      edited by the user
     * @param shadowdata    Array of reference data, having same format as the
     *                      formdata, but which contains the original values
     * @param fields        Array containing the original fields definition
     * @return array        Array of objects, which have a "key" key and a
     *                      "value" key, which together form a key-value
     *                      pair which should be deleted.
     */
    private function findChanges($formdata, $shadowdata, $fields) {
        $changes = array();
        foreach($shadowdata as $shadow_key => $shadow_value) {
            if($fields[$shadow_key]["dependencyMet"] === false) continue; 

            if(
                (
                    array_key_exists("multiple", $fields[$shadow_key]) && 
                    $fields[$shadow_key]["multiple"]
                ) || $fields[$shadow_key]["type"] == "checkbox"
            ) {
                if(is_array($shadow_value)) {
                    foreach($shadow_value as $index => $shadow_value_part) {
                        if($shadow_value_part != "" && (
                                !array_key_exists($shadow_key, $formdata) || 
                                !in_array($shadow_value_part, $formdata[$shadow_key])
                            )
                        ) {
                            if(!array_key_exists($shadow_key, $changes)) $changes[$shadow_key] = array();
                            $deleteItem = (object) array("delete" => $shadow_value_part, "add" => null);
                            // array("value" => $shadow_value_part, "action" => "delete");
                            if(!in_array($deleteItem, $changes[$shadow_key])) $changes[$shadow_key][] = $deleteItem;
                        }
                    }
                } else {
                    // TODO, this shouldn't happen, but it's a nice check
                    var_dump($shadow_value);
                    echo "Value of multiple-value-key $shadow_key is not of type array, but of type " . 
                        gettype($shadow_value) . "<br/>";
                }
            } else {
                if( $shadow_value != "" &&
                    (
                        !array_key_exists($shadow_key, $formdata) ||
                        $shadow_value != $formdata[$shadow_key]
                    )
                ) {
                    if(!array_key_exists($shadow_key, $changes)) $changes[$shadow_key] = array();
                    $deleteItem = array("delete" => $shadow_value, "add" => null);
                    if(array_key_exists($shadow_key, $formdata) && $formdata[$shadow_key] != "") {
                        $deleteItem["add"] = $formdata[$shadow_key];
                    }
                    $changes[$shadow_key][] = (object) $deleteItem;
                }
            }
        }

        foreach($formdata as $form_key => $form_value) {
            if($fields[$form_key]["dependencyMet"] === false) continue;

            if(
                (
                    array_key_exists("multiple", $fields[$form_key]) &&
                    $fields[$form_key]["multiple"]
                ) || $fields[$form_key]["type"] === "checkbox"
            ) {
                if(is_array($form_value)) {
                    foreach($form_value as $index => $form_value_part) {
                        if($form_value_part != "" && 
                            (
                                !array_key_exists($form_key, $shadowdata) ||
                                !in_array($form_value_part, $shadowdata[$form_key])
                            )
                        ) {
                            if(!array_key_exists($form_key, $changes)) $changes[$form_key] = array();
                            $addItem = (object) array("add" => $form_value_part, "delete" => null);
                            if(!in_array($addItem, $changes[$form_key])) $changes[$form_key][] = $addItem;
                        }
                    }
                } else {
                    // TODO, this shouldn't happen, but it's a nice check
                    var_dump($form_value);
                    echo "Value of multiple-value-key $form_key is not of type array, but of type " . 
                        gettype($form_value) . "<br/>";
                }
            } else {
                if( $form_value != "" && 
                    (
                        !array_key_exists($form_key, $shadowdata) || 
                        $shadowdata[$form_key] == ""
                    )
                ) {
                    if(!array_key_exists($form_key, $changes)) $changes[$form_key] = array();
                    $changes[$form_key][] = (object) array("add" => $form_value, "delete" => null);
                }
            }
        }

        return $changes;
    }

    /** 
     * Function that generates a json list of previously
     * used values for a certain key
     * @param key   The key to perform search on
     */
    public function metasuggestions($key = null) {
        $query = $this->input->get('query');
        $rodsAccount = $this->rodsuser->getRodsAccount();
        $key = sprintf('%s%%%s', $this->config->item('metadata_prefix'), $key);
        $options = $this->metadatamodel->getMetadataForKeyLike($rodsAccount, $key, $query);
        $this->output
            ->set_content_type('application/json')
            ->set_output(
                json_encode(
                    $options
                )
            );
    }


    /**
     * Method that can respond to an ajax request to get all users in a group, 
     * or a subset thereof that match certain criteria
     *
     * @param $study        The group from which to show the members
     * @param showAdmins    (through GET) Boolean indicating wether or not to
     *                      include group admins in the returned list of group
     *                      users
     * @param showUsers     (through GET) Boolean indicating wether or not to
     *                      include contributors in the returned list of group
     *                      users
     * @param showReadonly  (through GET) Boolean indicating wether or not to
     *                      include users with the readonly role in the returned
     *                      list of group users
     * @return string       Json encoded list of users that are a member of the
     *                      given group and have the required roles
     */
    public function getGroupUsers($study) {
        $query = $this->input->get('query');
        $showAdmin = $this->input->get("showAdmins");
        $showUsers = $this->input->get("showUsers");
        $showReadonly = $this->input->get("showReadonly");

        $showAdmin = (is_null($showAdmin) || $showAdmin == "0") ? false : true;
        $showUsers = (is_null($showUsers) || $showUsers == "0") ? false : true;
        $showReadonly = (is_null($showReadonly) || $showReadonly == "0") ? false : true;


        $group = sprintf(
                "%s%s", 
                $this->config->item('intake-prefix'),
                $study
            );

        $rodsaccount = $this->rodsuser->getRodsAccount();

        $results = 
            array_values(array_filter(
                $this->study->getGroupMembers($rodsaccount, $group, $showAdmin, $showUsers, $showReadonly),
                function($val) use($query) {
                    return !(!empty($query) && strstr($val, $query) === FALSE);
                }
            ));

        $this->output
            ->set_content_type('application/json')
            ->set_output(
                json_encode(
                    $results
                )
            );
    }

    /**
     * Method that can respond to an ajax request to get a list of collections
     * that exist in iRODS
     *
     * @param showProjects      (through GET) Boolean indicating wether to include
     *                          collections in the first level under home in the
     *                          returned list of collections
     * @param showStudies       (through GET) Boolean indicating wether to include
     *                          collections in the second level under home in the
     *                          returned list of collections
     * @param showDatasets      (through GET) Boolean indicating wether to include
     *                          collections in the third level under home in the
     *                          returned list of collections
     * @param requireContribute (through GET) Boolean indicating wether to show all
     *                          collections inside groups the user has access to and
     *                          match the earlier defined criteria (if false) or only
     *                          those the user has at least contribute access to (true)
     * @param requireManager    Boolean indicating wether to show all
     *                          collections inside groups the user has access to and
     *                          match the earlier defined criteria (if false) or only
     *                          those the user has at least manager access to (true)
     *                          If set to true, the value of requireContribute is
     *                          ignored
     * @return                  JSON encoded list of directories that match the
     *                          criteria
     */
    public function getDirectories() {
        $query = $this->input->get('query');
        $showProjects = $this->input->get('showProjects');
        $showStudies = $this->input->get('showStudies');
        $showDatasets = $this->input->get('showDatasets');
        $requireContribute = $this->input->get('requireContribute');
        $requireManager = $this->input->get('requireManager');

        $showProjects = (is_null($showProjects) || $showProjects == "0" || strtolower($showProjects) !== "true") ? false : true;
        $showStudies = (is_null($showStudies) || $showStudies == "0" || strtolower($showStudies) !== "true") ? false : true;
        $showDatasets = (is_null($showDatasets) || $showDatasets == "0" || strtolower($showDatasets) !== "true") ? false : true;
        $requireContribute = (is_null($requireContribute) || $requireContribute == "0" || strtolower($requireContribute) !== "true") ? false : true;
        $requireManager = (is_null($requireManager) || $requireManager == "0" || strtolower($requireManager) !== "true") ? false : true;

        $rodsaccount = $this->rodsuser->getRodsAccount();

        $results = array_values(array_filter(
            $this->study->getDirectories($rodsaccount, $showProjects, $showStudies, $showDatasets, $requireContribute, $requireManager),
            function($val) use ($query) {
                $dirArr = explode("/", $val);
                $dirName = $dirArr[sizeof($dirArr) - 1];

                return !(!empty($query) && strstr($dirName, $query) === FALSE);
            }
        ));

        $this->output
            ->set_content_type('application/json')
            ->set_output(
                json_encode(
                    $results
                )
            );
    }

}