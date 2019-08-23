<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Metadata form model
 *
 * @package    Yoda
 * @copyright  Copyright (c) 2017-2019, Utrecht University. All rights reserved.
 * @license    GPLv3, see LICENSE.
 */
class Metadata_form_model extends CI_Model
{
    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
        $this->CI->load->model('filesystem');
    }

    /**
     * Save JSON metdata posted from the metadata form.
     *
     * @param $rodsaccount Rods account
     * @param $path        Path of collection being worked in
     */
    public function saveJsonMetadata($rodsaccount, $path)
    {
        $arrayPost = $this->CI->input->post();
        $data = $arrayPost['formData'];
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            'rule { iiSaveFormMetadata(*path, *data); }',
            array('*path' => $path, '*data' => $data),
            array('ruleExecOut')
        );
        $result = json_decode($rule->execute()['ruleExecOut'], true);
        return $result;
    }

    /**
     * Get JSON schema for collection.
     *
     * @param $rodsaccount
     * @param $path        Collection of area being worked in
     *
     * @return string      JSON schema
     */
    public function getJsonSchema($rodsaccount, $path)
    {
        $result = $this->CI->filesystem->getJsonSchema($rodsaccount, $path);

        if ($result['*status'] == 'Success') {
            return $result['*result'];
        } else {
            return '';
        }
    }

    /**
     * Get JSON UI schema for collection.
     *
     * @param $rodsaccount
     * @param $path        Collection of area being worked in
     *
     * @return string      JSON UI schema
     */
    public function getJsonUiSchema($rodsaccount, $path)
    {
        $result = $this->CI->filesystem->getJsonUiSchema($rodsaccount, $path);

        if ($result['*status'] == 'Success') {
            return $result['*result'];
        } else {
            return '';
        }
    }

    /**
     * Get the yoda-metadata.json file contents.
     *
     * @param $rodsaccount
     * @param $path
     * @return string
     */
    public function getJsonMetadata($rodsaccount, $path)
    {
        $formData = $this->CI->filesystem->read($rodsaccount, $path);

        return json_decode($formData);
    }
}
