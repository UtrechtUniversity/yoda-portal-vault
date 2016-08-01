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
    }

    public function update() {
        $message = "";
        $error = false;
        $type = "info";

        $redirectTemplate = $this->modulelibrary->getModuleBase() . '/intake/%1$s/' . 
            $this->input->post('studyID') . "/" . $this->input->post("dataset");


        $permissions = $this->study->getIntakeStudyPermissions($this->input->post('studyID'));
        if($permissions->administrator) {

            $formdata = $this->input->post('metadata');
            $shadowData = $this->input->post('metadata-shadow');
            $collection = $this->input->post('studyRoot') . "/" . $this->input->post('dataset');
            $fields = $this->metadatafields->getFields($collection, true);

            $this->checkDependencyProperties($fields, $formdata);

            $wrongFields = $this->veryInput($formdata, $fields);

            if(sizeof($wrongFields) == 0) {

                // Process results
                $deletedValues = $this->findDeletedItems($formdata, $shadowData, $fields);
                $addedValues = $this->findChangedItems($formdata, $shadowData, $fields);

                // $this->dumpKeyVals($deletedValues, "These items will be deleted:");
                // $this->dumpKeyVals($addedValues, "These items will be (re)added");

                // Update results
                $rodsaccount = $this->rodsuser->getRodsAccount();
                $status = $this->metadatamodel->processResults($rodsaccount, $collection, $deletedValues, $addedValues); 
                // $status = UPDATE_SUCCESS;

                if($status == UPDATE_SUCCESS) {
                    $referUrl = sprintf($redirectTemplate, "index");
                    $message = "ntl:The metadata was updated successfully";
                } else {
                    $referUrl = sprintf($redirectTemplate, "metadata");
                    $message = "ntl:Something went wrong updating the metadata. Please check all your metadata. The error code was " . 
                        $status . " if it helps";
                    $error = true;
                    $type = "danger";
                }
            } else {
                $referUrl = sprintf($redirectTemplate, "metadata");
                $message = "ntl: Incorrect input";
                $error = true;
                $type = "warning";

                $this->session->set_flashdata('incorrect_fields', $wrongFields);
                $this->session->set_flashdata('form_data', $formdata);
            }
           
        } else {
            $referUrl = sprintf($redirectTemplate, "index");
            $message = "ntl:You do not have admin rights on the " . $this->input->post("studyID") . " study and can therefor not update metadata";
            $error = true;
            $type = "warning";
        }

        displayMessage($this, $message, $error, $type);
        redirect($referUrl, 'refresh');
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