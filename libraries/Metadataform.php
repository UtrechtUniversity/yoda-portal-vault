<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Metadataform {

    public $CI;

    public $elements = array();

    public $countMandatoryFilled = 0;

    public $countMandatoryTotal = 0;

    private $permission = 'write';



    /**
     * Constructor
     */
    public function __construct()
    {
        // Get the CI instance
        $this->CI =& get_instance();
        $this->CI->load->helper('form');
    }

    public function getCountMandatoryFilled()
    {
        return $this->countMandatoryFilled;
    }

    public function getCountMandatoryTotal()
    {
        return $this->countMandatoryTotal;
    }

    public function calculateMandatoryCompleteness($formElements)
    {
        $mandatoryCounts = array();

        foreach ($formElements as $group => $elements) {

            foreach ($elements as $name => $properties) {

                // Some fields are multipleAllowed. This should only be counted as 1 field.
                if($properties['mandatory']) {
                    if(!isset($mandatoryCounts[$properties['key']])) {
                        $mandatoryCounts[$properties['key']] = 0;
                    }

                    $markAsPresent = 0;
                    if($properties['value']) {
                        $markAsPresent = 1;
                    }

                    $mandatoryCounts[$properties['key']] = $markAsPresent;
                }
            }
        }

        foreach($mandatoryCounts as $markAsPresent) {
            $this->countMandatoryTotal++;
            if($markAsPresent) {
                $this->countMandatoryFilled++;
            }
        }
    }

    //public function load($formElements, $values)
    public function load($formElements)
    {
        foreach ($formElements as $group => $elements)
        {
           foreach($elements as $name => $properties)
           {
               $properties['group'] = $group;
               //$properties['name'] = $name;

               $this->add_element($properties);
           }
        }

        return $this;
    }

    public function add_element($data)
    {
        // Create a new element
        $element = new Element($this, $data);
        // Assign the new element to the elements array
        $this->elements[] = $element;

        return true;
    }

    public function render($section = null)
    {
        $html = '';
        // Render the entire form

        foreach($this->elements as $element)
        {
            if (empty($section) || $section == $element->group) {
                $html .= $element->render();
            }
        }
        return $html;
    }

    public function show($section = null)
    {
        $html = $this->render($section);
        return $html;
    }

    public function open($action, $class, $method = 'post')
    {
        //return '<form method="' . $method . '" action="' . $action . '" class="' . $class . '">';
        $options = array ('class' => $class);
        return form_open($action, $options);
    }

    public function close()
    {
        return '</form>';
    }

    public function getSections()
    {
        $sections = array();

        foreach($this->elements as $element)
        {
            $group = $element->group;
            if (!in_array($group, $sections)) {
                $sections[] = $group;
            }
        }

        return $sections;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function setPermission($permission)
    {
        $this->permission = $permission;
    }
}

class Element {
    public $form;
    public $key;
    public $label;
    public $helpText;
    public $type;
    public $value = null;
    public $options = array();
    public $selected = array();
    public $mandatory = FALSE;
    public $group = null;
    private $dir = 'metadata/fields/';
    private $multipleAllowed = false;

    /**
     * Constructor
     */
    public function __construct($form, $data)
    {
        $this->form = $form;

        $this->key = $data['key'];
        $this->label = $data['label'];
        $this->helpText = $data['helpText'];
        $this->type = $data['type'];
        $this->mandatory = $data['mandatory'];
        $this->group = $data['group'];

        if (isset($data['value']) && !empty($data['value'])) {
            $this->value = $data['value'];
        }

        if ($this->type == 'select') {
            $this->options = $data['elementSpecifics']['options'];
        }

        if (isset($data['multipleAllowed']) && $data['multipleAllowed']) {
            $this->multipleAllowed = $data['multipleAllowed'];
        }

        // item types will probably be extend in the future
        $fieldsWithMaxLength = array('text',
            'textarea');
        if(in_array($this->type, $fieldsWithMaxLength)) {
            $this->maxLength = 0;
            if(isset($data['elementSpecifics']['maxLength'])) {
                $this->maxLength = $data['elementSpecifics']['maxLength'];
            }
        }

/* Possible functure mesage handling
        // preparation for info to user
        $this->messagesForUser = array();
        if(is_array($data['messagesForUser'])) {
            $this->messagesForUser = $data['messagesForUser'];
        }

        // ADD to metadata/fields/write partials the following
    <div class="col-sm-3">
        <?php foreach($e->messagesForUser as $message): ?>
            <?php echo $message['messageText']; ?>
            <br>
        <?php endforeach; ?>
    </div>

*/
    }

    /**
     * Renders element.
     */
    public function render()
    {
        $form = $this->form;
        $permission = $form->getPermission();

        /*
        // Temp (adding only)
        if ($permission == 'write' && !empty($this->value)) {
            $permission = 'read';
        }
        */

        $data['e'] = $this;

        $html = $this->form->CI->load->view($this->dir . $permission . '/' . $this->type, $data, TRUE);

        return $html;
    }

    public function multipleAllowed()
    {
        return $this->multipleAllowed;
    }
}


/* End of file Metadataform.php */