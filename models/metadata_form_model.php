<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    function load($rodsaccount, $directoryPath)
    {
        // formelements.xml

        /*
        <?xml version="1.0"?>
        <Project>
            <Project_Title>
                <type>text</type>
                <label>Project title</label>
                <mandatory></mandatory>
            </Project_Title>
        </Project>
        */
        /*
        $content = '
            <Form_Elements>
                <Project>	
                    <Project_Title>
                        <type>text</type>
                        <label>Project title</label>
                        <mandatory>false</mandatory>
                    </Project_Title>
                </Project>    
            </Form_Elements>
        ';

        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        */

        $array = array();
        $array['Eerste groep'][] = array(
            'key' => 'Project_Title',
            'value' => 'Een leuk project',
            'label' => 'Project titel',
            'helpText' => 'Help!!',
            'type' => 'text',
            'mandatory' => true, // zit mss in minOccers/maxOccurs
            'multipleAllowed' => false
        );

        $array['Tweede groep'][] = array(
            'key' => 'Project_Titl',
            'value' => 'Een leuk',
            'label' => 'Project',
            'helpText' => 'Help!!',
            'type' => 'text',
            'mandatory' => true, // zit mss in minOccers/maxOccurs
            'multipleAllowed' => false
        );

        $array['Tweede groep'][] = array(
            'key' => 'Project_Titl2',
            'value' => 'Een leuk2',
            'label' => 'Project2',
            'helpText' => 'Help!!',
            'type' => 'text',
            'mandatory' => true, // zit mss in minOccers/maxOccurs
            'multipleAllowed' => false
        );

        $array['Derde groep'][] = array(
            'key' => 'project_field',
            'label' => 'Project Field',
            'helpText' => 'Help!!',
            'type' => 'text',
            'mandatory' => true, // zit mss in minOccers/maxOccurs
            'multipleAllowed' => false
        );

        $array['Eerste groep'][] = array(
            'key' => 'Project_types',
            'label' => 'Project typeringen',
            'helpText' => 'Help mij!!',
            'type' => 'select',
            'mandatory' => true,
            'multipleAllowed' => true,
            'elementSpecifics' => array('options' => array('Type 1', 'Type 2', 'Type 3'))
        );

        $array['Eerste groep'][] = array(
            'key' => 'Project_date',
            'label' => 'Project date',
            'helpText' => 'Help mij!!',
            'type' => 'date',
            'mandatory' => true,
            'multipleAllowed' => true
        );
        /*
        $array['Eerste groep'][] = array(
            'key' => 'Project_types',
            'value' => array('Type 1', 'Type 2'),
            'label' => 'Project typeringen',
            'helpText' => 'Help mij!!',
            'type' => 'select',
            'mandatory' => true, // zit mss in minOccers/maxOccurs
            'options' => array( 1=>'Type 1',
                2 => 'Type 2',
                3 => 'Type 3'),
            'multipleAllowed' => true
        );

        $array['Eerste groep'][] = array(
            'key' => 'Project_types',
            'value' => array('Type 1', 'Type 2'),
            'label' => 'Project typeringen',
            'helpText' => 'Help mij!!',
            'type' => 'select',
            'mandatory' => true,
            'multipleAllowed' => true,
            'elementSpecifics' => array('options' => array('Type 1', 'Type 2', 'Type 3'))
        );
        */

        return $array;
    }
}