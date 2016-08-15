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
        $this->load->library('modulelibrary');
        $this->load->library('pathlibrary');
    }

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
            $message = sprintf("ntl: %s path does not seem to be a valid directory. No metadata updated", $directory);
            $error = true;
            $type = "error";
        } else {
            $this->pathlibrary->getCurrentLevelAndDepth($this->config, $segments, $currentLevel, $currentDepth);
            $levelPermissions = $this->study->getPermissionsForLevel($currentDepth, $studyID);
            if($levelPermissions->canEditMeta === false) {
                $message = sprintf("ntl: You do not have permission to edit meta data for the folder %s", $folder);
                $error = true;
                $type = "error";
            } else {
                $formdata = $this->input->post('metadata');
                $shadowData = $this->input->post('metadata-shadow');
                $fields = $this->metadatafields->getFields($directory, true);

                $this->checkDependencyProperties($fields, $formdata);

                $wrongFields = $this->veryInput($formdata, $fields);

                if(sizeof($wrongFields) == 0) {

                    // Process results (depricated)
                    // $deletedValues = $this->findDeletedItems($formdata, $shadowData, $fields);
                    // $addedValues = $this->findChangedItems($formdata, $shadowData, $fields);

                    // $this->dumpKeyVals($deletedValues, "These items will be deleted:");
                    // $this->dumpKeyVals($addedValues, "These items will be (re)added");

                    // $status = $this->metadatamodel->processResults($rodsaccount, $directory, $deletedValues, $addedValues); 

                    // Process results
                    $changes = $this->findChanges($formdata, $shadowData, $fields);
                    // $this->dumpChanges($changes);

                    // Update results
                    $rodsaccount = $this->rodsuser->getRodsAccount();
                    $status = $this->metadatamodel->processResults($rodsaccount, $directory, $changes);

                    if($status["success"] === true) {
                        $message = "ntl:The metadata was updated successfully";
                    } else {
                        $message = $this->buildError($status, $fields);
                        $error = true;
                        $type = "warning";   
                    }
                } else {
                    $message = "ntl: Incorrect input";
                    $error = true;
                    $type = "warning";

                    $this->session->set_flashdata('incorrect_fields', $wrongFields);
                    $this->session->set_flashdata('form_data', $formdata);
                }
            }
        }

        $referUrl = site_url(array($this->modulelibrary->name(), "intake", "metadata")) . "?dir=" . urlencode($directory);
        displayMessage($this, $message, $error, $type);
        redirect($referUrl, 'refresh');
    }

    private function buildError($status, $fields) {
        $message = "<p><b>ntl: Something went wrong updating the meta data:</b></p>";
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

    private function buildSingleError($errorFields, $fields, $action) {
        $object = $this->input->post('directory');
        $prfx = "";
        if($this->config->item("metadata_prefix") && $this->config->item("metadata_prefix") !== false) {
            $prfx .= $this->config->item("metadata_prefix");
        }
        $meta = $this->metadatafields->getMetaForLevel($object);
        if(array_key_exists("prefix", $meta) && $meta["prefix"] !== false) {
            $prfx .= $meta["prefix"];
        }

        $errors = array();
        foreach($errorFields as $f) {
            if(strpos($f, $prfx) === 0) $f = substr($f, strlen($prfx));
            if(array_key_exists($f, $fields) && array_key_exists("label", $fields[$f])) {
                $errors[] = sprintf('"%1$s"', $fields[$f]["label"]);
            } else {
                $errors[] = sprintf('"%1$s"', $f);
            }
        }
        return sprintf('<p>nt:Could not %1$s the values for %2$s', $action, human_implode($errors, ", ", " ntl:and "));
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
                    $this->metadatafields->evaluateRowDependencies($field["depends"], $formdata);
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
                $errors = $this->metadatafields->verifyKey($inputValues, $fields[$inputKey], $formdata, false);
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
    private function findDeletedItems($formdata, $shadowdata, $fields) {
        $deletedValues = array(array());

        foreach($shadowdata as $key => $value) {
            if($fields[$key]["dependencyMet"] === false)
                continue;
            if(array_key_exists("multiple", $fields[$key]) || $fields[$key]["type"] == "checkbox") {
                if(is_array($value)) {
                    $i = 0;
                    foreach($value as $index => $value_part) {
                        if(
                            $value_part != "" && (
                                !array_key_exists($key, $formdata) ||
                                !in_array($value_part, $formdata[$key])
                            )
                        ) {
                            if(!array_key_exists($i, $deletedValues)) {
                                $deletedValues[$i] = array();
                            }
                            $deletedValues[$i][] = (object)array("key" => $key, "value" => $value_part);
                            $i++;
                        }
                    }
                } else {
                    // TODO, this shouldn't happen, but it's a nice check
                    var_dump($value);
                    echo "Value of multiple-value-key $key is not of type array, but of type " . 
                        gettype($value) . "<br/>";
                }
            } else {
                if(array_key_exists($key, $formdata) && $formdata[$key] != $value && $value != "") {
                    $deletedValues[0][] = (object)array("key" => $key, "value" => $value);
                }
            }
        }

        return $deletedValues;
    }

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
     * Function that generates an object list of key-value pairs that should be added
     * to the object that contains the meta data. These are values that have either
     * changed to a non-zero value, or have been added in the case of multi-value keys
     * 
     * @param formdata      array of which the keys are meta data keys 
     *                      and the values are the values from the editable fields, 
     *                      edited by the user
     * @param shadowdata    Array of reference data, having same format as the
     *                      formdata, but which contains the original values
     * @param fields        Array containing the original fields definition
     * @return array        Object with two keys: "added" and "changed", which contain
     *                      an array of objects, which have a "key" key and a
     *                      "value" key, which together form the key-value
     *                      pair which follows the description above
     */
    private function findChangedItems($formdata, $shadowdata, $fields) {
        $addedValues = array(array());

        foreach($formdata as $key => $value) {
            if($fields[$key]["dependencyMet"] === false)
                continue;
            if(array_key_exists("multiple", $fields[$key]) || $fields[$key]["type"] == "checkbox") {
                if(is_array($value)) {
                    $i = 0;
                    foreach($value as $index => $value_part) {
                        if(
                            $value_part != "" && (
                                !in_array($value_part, $shadowdata[$key])
                            )
                        ) {
                            if(!array_key_exists($i, $addedValues)) {
                                $addedValues[$i] = array();
                            }
                            $addedObject = (object)array("key" => $key, "value" => $value_part); echo "<br/><br/>";
                            if(!in_array($addedObject, $addedValues[$i])){
                                $addedValues[$i][] = $addedObject;
                                $i++;
                            }
                        }
                    }
                } else {
                    var_dump($value);
                    // TODO, this shouldn't happen, but it's a nice check
                    echo "Value of multiple-value-key $key is not of type array, but of type " 
                        . gettype($value) . "<br/>";
                }
            } else {
                if($value != "" && $value != $shadowdata[$key]) {
                    $addedValues[0][] = (object)array("key" => $key, "value" => $value);
                }
            }
        }

        return $addedValues;
    }

    /** 
     * Function that generates a json list of previously
     * used values for a certain key
     * @param key   The key to perform search on
     */
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