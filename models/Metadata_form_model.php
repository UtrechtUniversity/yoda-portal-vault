<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model
{

    var $CI = NULL;
    var $presentationElements = array();
    var $xsdElements = array();

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();

        $this->CI->load->model('filesystem');
    }

    /**
     * @param $rodsaccount
     * @param $config
     * @return array
     *
     * returns all groupnames in an array for the requested for form (in $config['elements']
     */
    public function getFormGroupNamesAsArray($rodsaccount, $config)
    {
        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);

        $groupNames = array();
        foreach ($formGroupedElements['Group'] as $formElements) {
            foreach ($formElements as $key => $element) {
                if ($key == '@attributes') {
                    $groupNames[] = $element['name'];
                }
            }
        }
        return $groupNames;
    }

    /**
     * @param $rodsaccount
     * @param $config
     * @return array
     *
     * creates an array to be indexed by fully qualified element name.  eg 'creation_date'
     * This especially for subproperties eg 'creator_properties_pi' which is in fact a construct
     */
    public function getFormElementLabels($rodsaccount, $config)
    {
        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);

        $groupNames = array();
        $elementLabels = array();
        foreach ($formGroupedElements['Group'] as $formElements) {
            foreach ($formElements as $key => $element) {
                if ($key == '@attributes') {
                    $groupNames[] = $element['name'];
                } else {
                    $this->iterateElements($element, $key, $elementLabels, $key);
                }
            }
        }
        return $elementLabels;
    }

    /**
     * @param $element
     * @param $key
     * @param $elementLabels
     *
     * supporting function for getFormElementLabels
     * Adjusted so the leadproperty label is taken into account ($leadPropertyBase is passed throughout all iteration levels)
     */
    public function iterateElements($element, $key, &$elementLabels, $leadPropertyBase, $level = 0)
    {
        if (isset($element['label'])) {
            $elementLabels[$key] = $element['label'];
            if ($level == 1) {
                $elementLabels[$leadPropertyBase] = $element['label'];
            } elseif ($level > 1) {
                $elementLabels[$key] = $elementLabels[$leadPropertyBase] . '-' . $element['label'];

            }
        } else {
            $level++;
            foreach ($element as $key2 => $element2) {
                $this->iterateElements($element2, $key . '_' . $key2, $elementLabels, $leadPropertyBase, $level);
            }
        }
    }

// @todo: Obsolete nu met subroperties
    /**
     * @param $rodsaccount
     * @param $config
     * @return array
     *
     * returns all present form items in an array for the requested for form (in $config['elements'])
     */
    public function getFormElementsAsArray($rodsaccount, $config)
    {
        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);

        // If there are multiple groups, the first should contain the number '0' as array-index .
        // Otherwise, this is the direct descriptive information (i.e. not an array form.
        // The software expects an array, so in the latter case should be an array)
        $formAllGroupedElements = array();
        foreach ($formGroupedElements['Group'] as $index => $array) {
            if ($index == '0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            } else {
                $formAllGroupedElements[] = $formGroupedElements['Group']; // rewrite it as an indexable array as input for coming foreach
            }
            break;
        }

        $allElements = array();
        foreach ($formAllGroupedElements as $formElements) {
            foreach ($formElements as $key => $element) {
                if ($key != '@attributes') {
                    $allElements[] = $key;
                }
            }
        }
        return $allElements;
    }

    /**
     * @param $array
     * @return bool
     * structure like this:
     * [Name] =>
     * [Properties] => Array
     * (
     * [PI] =>
     * [PI_Type] =>
     * [Affiliation] =>
     * )
     */
    public function arrayHoldsAnyData($array)
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                if ($value) {
                    return true;
                }
            } else {
                foreach ($value as $key1 => $value1) {
                    if (!is_array($value1)) {
                        if ($value1) {
                            return true;
                        }
                    } else {
                        foreach ($value1 as $key2 => $value2) {
                            if ($value2) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Posted data is not coming in in the correct structure due to the way the frontend had to be prepared to be able to clone elements with subproperties
     *
     * Therefore, reorganisation must take place
     *
     * Step through each element and given the type create the correct output so it can be converted to XML later on
     */
    public function reorganisePostedData($rodsaccount, $xsdPath)
    {
        $metadataElements = array();
        $elementCounter = 0;

        $xsdElements = $this->loadXsd($rodsaccount, $xsdPath);

        $arrayPost = $this->CI->input->post();

        if (isset($arrayPost['vault_submission'])) { // clean up: this is extra input in the posted data that should not be handled as being metadata
            unset($arrayPost['vault_submission']);
        }


//        $arrayPost = array(
//            'Title' => $arrayPost['Title'],
//            'Description' => $arrayPost['Description'],
//            'Discipline' => array(0 => 'hallo',
//                1=>''),
//            'Person' => $arrayPost['Person']
//            //'Related_Datapackage' => $arrayPost['Related_Datapackage']
//        );
//

        // First reorganise in such a way that data coming back from front end
        foreach ($arrayPost as $key => $value) {
            if (is_array($value)) { // multiplicity && subproperty handling
                $sublevelCounter = 0;
                $keepArray = array();
                $structType = '';

                foreach ($value as $subKey => $subValue) {
                    if (is_numeric($subKey)) {
                        if ($subValue) {
                            if (!isset($metadataElements[$elementCounter])) {
                                $newValueCollection = array();
                                foreach ($value as $index => $localVal) { // rebuild the array only holding values
                                    if ($localVal) {
                                        $newValueCollection[] = $localVal;
                                    }
                                }
                                $metadataElements[$elementCounter] = array($key => $newValueCollection);
                            }
                        }
                    } else { // subproperty handling - can either be a single struct or n-structs
                        // A struct typically has 1 element as a hierarchical top element.
                        // The other element holds properties

                        if ($sublevelCounter == 0) {
                            if (count($subValue) == 1) { // single instance of a struct
                                $structType = 'SINGLE';
                            } else { // multiple instances of struct
                                $structType = 'MULTIPLE';
                            }
                            $keepArray[$sublevelCounter] = array($subKey => $subValue);
                        } else {
                            $keepArray[$sublevelCounter] = array($subKey => $subValue);
                        }
                    }
                    $sublevelCounter++;
                }

                $multipleAllowed = false;
                if ($xsdElements[$key]['maxOccurs'] != '1') {
                    $multipleAllowed = true;
                }
                $isStruct = false;
                if ($xsdElements[$key]['type'] == 'openTag') {
                    $isStruct = true;
                }

                // after looping through an entire struct it becomes
                if ($structType == 'SINGLE' AND !$multipleAllowed) {
//                    echo '<br>KEY1: ' . $key;
//                    echo '<pre>';
//                        print_r($value);
//                    echo '</pre>';
                    // check values within entire structure to prevent from adding fully empty structure

                    if ($this->arrayHoldsAnyData($value)) { // only add if actually holds any data
                        $metadataElements[$elementCounter] = array($key => $value);
                    }
                } elseif ($structType == 'MULTIPLE' OR ($multipleAllowed AND $isStruct)) { // Multi situation
                    $enumeratedData = array();
                    foreach ($keepArray[0] as $keyData => $valData) { // step through the lead properties => then find corresponding subproperties
                        foreach ($valData as $referenceID => $leadPropertyVal) { // referenceID is actually the counter in the array that coincides for lead and subproperties

                            // Within keepArray[1] handle the corresponding subproperties
                            $subpropArray = array();
                            $structHoldsData = strlen($leadPropertyVal) > 0 ? true : false;
                            foreach ($keepArray[1] as $subpropKey => $propValue) {
                                foreach ($propValue as $subPropertyName => $subData) {
                                    //echo '<br>' . $subPropertyName . ' - ' .$subData[$referenceID];
                                    $subpropArray[$subPropertyName] = $subData[$referenceID];
                                    if ($subData[$referenceID] AND !$structHoldsData) {
                                        $structHoldsData = true;
                                    }
                                }
                            }

                            if ($structHoldsData) {
                                $enumeratedData[] = array($keyData => $leadPropertyVal,
                                    $subpropKey => $subpropArray
                                );
                            }

                        }
                    }
                    if (count($enumeratedData)) {
                        $metadataElements[$elementCounter] = array($key => $enumeratedData); // can contain multiple items - is an enumerated list
                    }
                }
            } else {
                // only add if actually containing a value
                if ($value) {
                    $metadataElements[$elementCounter] = array($key => $value);
                }
            }
            $elementCounter++;
        }

        return $metadataElements;
    }

    public function formElementsMainFieldList($formGroupedElements)
    {
        $elements = array();

        $formAllGroupedElements = array();
        foreach ($formGroupedElements['Group'] as $index => $array) {
            if ($index == '0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            } else {
                $formAllGroupedElements[] = $formGroupedElements['Group']; // rewrite it as an indexable array as input for coming foreach
            }
            break;
        }

        // Form elements is hierarchical too.
        // Bring it back to one level with
        // 1) parent indication
        // 2) fully qualified name (unique!)
        // 3) Add start end tags for combination fields

        // HIER ALLEEN DE HOOFD ELEMENTEN
        foreach ($formAllGroupedElements as $formElements) {

            foreach ($formElements as $key => $element) {
                // Find group definition
                // Find hierarchies of elements regarding subproperties.
                if ($key != '@attributes') { // GROUP handling
//                    echo '<pre>';
//                    echo 'Key = ' . $key;
//                    echo '<br>';
//                    print_r($element);
//                    echo '</pre>';

                    $elements[$key] = $element;
                }
            }
        }
        return $elements;
    }

    /**
     * @param $allFormMetadata - posted data from form
     * @return string
     *
     * Per form element that holds data create xml structure
     */
    public function metadataToXmlString($allFormMetadata, $rodsaccount, $config)
    {
        $xsdPath = $config['xsdPath'];
        $this->xsdElements = $this->loadXsd($rodsaccount, $xsdPath);

//        echo '<pre>';
//            print_r($allFormMetadata);
//        echo '</pre>';

        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);
        if ($formGroupedElements === false) {
            return false;
        }

        // Get the mainFields only - filter out the grouping
        $formMainFields  = $this->formElementsMainFieldList($formGroupedElements);

        // XML initialization
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;

        $xml_metadata = $xml->createElement("metadata");


        // Step through all fields and
        foreach($formMainFields as $mainField=>$mainFieldData) {

            $isSubPropertyStructure = false;
            if (!$this->isCompoundElement($mainFieldData) AND $this->isSubpropertyStructure($mainFieldData)) {
                $isSubPropertyStructure = true;
            }

            // ElementData is posted data for the mainField at hand
            if (!isset($allFormMetadata[$mainField])) {
                return false;
            }
            $elementData = ($allFormMetadata[$mainField]);

            // Prepwork required for removal of entire empty compounds where necessary
            $arEmptyCompoundTestNames = array();
            $isCompoundInStruct = false;
            if (isset($mainFieldData['Properties'])) {
                $Properties = $mainFieldData['Properties'];
                foreach ($Properties as $pkey => $pval) { // For this moment think of only one compound present in subproperty-struct
                    $tempArray = array();
                    foreach ($pval as $itemName => $itemVal) {
                        if (substr($itemName, 0, 5) == '@attr') {
                            $isCompoundInStruct = true;
                        } elseif ($isCompoundInStruct) {
                            $tempArray[] = $itemName;
                        }
                    }
                    if ($isCompoundInStruct) {
                        $arEmptyCompoundTestNames[$pkey] = $tempArray;
                    }
                }
            }

            // $allFormMetadata[$mainField]
            $topLevelData = array();
            if (!is_numeric(key($elementData))) {
                $topLevelData[] = $elementData;
            } else {
                $topLevelData = $elementData;
            }

        //    echo '<br>Element name: ' . $mainField;
            foreach ($topLevelData as $key => $val) { // per $val => entire structure to be added to $elementName
                $temp = array();
                if (!(is_array($val) AND is_numeric(key($val)))) {
                    // enumerated structures where we only need the structure itself.
                    $temp[] = $val;
                } else {
                    $temp = $val;
                }

                foreach ($temp as $elementInfo) { // step through each instance of each element
                    // Test for and remove fully empty structures as they intervene with saving the xml structure
                    if ($isSubPropertyStructure AND $arEmptyCompoundTestNames) { // can we test compound values in the subproperty-struct?
                        foreach ($arEmptyCompoundTestNames as $compoundKeyName => $compoundFieldNames) { // shoould be either 0 or 1 loop

                            $isEnumerated = is_numeric(key($elementInfo['Properties'][$compoundKeyName]));

                            if ($isEnumerated) {
                                $testArray = $elementInfo['Properties'][$compoundKeyName];  // base to test whether all compound elements are are empty

                                foreach ($testArray as $idTest => $testValues) {
                                    $isFilledCompound = false;

                                    foreach ($compoundFieldNames as $cfName) {
                                        //echo '<br>' . $compoundKeyName . '-' . $cfName;
                                        if ($testArray[$idTest][$cfName]) {
                                            $isFilledCompound = true; // at least one value
                                            break;
                                        }
                                    }

                                    if (!$isFilledCompound) {
                                        unset($elementInfo['Properties'][$compoundKeyName][$idTest]);
                                    }
                                }
                            }
                        }
                    }

                    if ($isSubPropertyStructure) {
                        // first element of lead-subproperty struct must have a value.
                        // Per posted data structure for this element, check whether the lead element holds a value.
                        // If not, don't write the data (which only consists of subproperty data) to the xml

                        if (reset($elementInfo) == '') {
                            break;
                        }
                    }

                    $xml_item = $xml->createElement($mainField); // base element
//                    echo '<pre>';
//                    print_r($elementInfo);
//                    echo '</pre>';

                    $level = 0; // for deletion handling - deeper levels (>0)can always be deleted (i.e. not written to file when empty value)
                    $metaStructureXML = $this->xmlMetaStructure($xml, $elementInfo, $xml_item, $level);

                    if ($metaStructureXML['anyDataPresent']) {
                        $xml_metadata->appendChild($metaStructureXML['xmlParentElement']);
                    }
                }
            }
        }
//        exit;

        $xml->appendChild($xml_metadata);

        return $xml->saveXML();
    }


    //$topLevelName - for deletion purpose (Sub info can always be deleted (i.e. not added)
    /**
     * @param $xmlMain
     * @param $arMetadata
     * @param $xmlParentElement
     * @param $level
     * @param bool $anyDataPresent
     * @param string $totalTagName - for indexing in $xsdElements to find out whether a field is op type xs:date
     * @return array
     */
    public function xmlMetaStructure($xmlMain, $arMetadata, $xmlParentElement, $level, $anyDataPresent = false, $totalTagName='')
    {
        $doAddThisLevel = true;

        $arrayMeta = array();
        if (!is_array($arMetadata)) {
            $arrayMeta[] = $arMetadata;
        } else {
            $arrayMeta = $arMetadata;
        }

        if (!$totalTagName) {
            $totalTagName = $xmlParentElement->tagName;
        }
        else {
            $totalTagName = $totalTagName . '_' . $xmlParentElement->tagName;
        }

        foreach ($arrayMeta as $key => $val) {
            if (!is_array($val) AND is_numeric($key)) {
                //return $xmlParentElement;
                ///een val is altijd het eind ding van een element.
                // Als er geen waarde is, moet dus ook het element worden verwijderd
                if ($val != '') {
                    if ($this->xsdElements[$totalTagName]['type'] == 'xs:date') {
                        // the date requires years to be added YYYY
                        // 2017-10-12 -> length = 10
                        // years before 1000 can be added by the datepicker but are formatted like: 900-12-25
                        $val = str_repeat('0', 10-strlen($val)) . $val; // add preceiding 0's -> 0900-12-25
//                        echo '<br>Val: ' . $val . ' - ' . $this->xsdElements[$totalTagName]['type'];// . $totalPath;
                    }
                    $xmlParentElement->appendChild($xmlMain->createTextNode($val));
                    //echo '<pre>';
                    //print_r($xmlParentElement);
                    //echo '</pre>';
                    $anyDataPresent = true;
                } else {
                    $doAddThisLevel = false;
                }
            } else {
                $level++;
                // $val kan hier multi zijn
                $arraySubLevels = array();
                if (!is_numeric(key($val))) { // if not enumerated
                    $arraySubLevels[] = $val;
                } else {
                    $arraySubLevels = $val;
                }
                foreach ($arraySubLevels as $key2 => $val2) {
                    $xmlElement = $xmlMain->createElement($key);

                    // Deze aanroep gebeurt in het kader van $xmlElement / $key.
                    $structInfo = $this->xmlMetaStructure($xmlMain, $val2, $xmlElement, $level, $anyDataPresent, $totalTagName);

                    // Add (entire) srtructure or 1 value
                    // Het element wordt hier al geappend zonder dat zeker is of dit moet (en dan komt ie als lege tag in yoda-metadata.xml)
                    if ($structInfo['doAddThisLevel'] AND is_object($structInfo['xmlParentElement']->firstChild)) {
                        $xmlParentElement->appendChild($structInfo['xmlParentElement']);
                    }
                    $anyDataPresent = $structInfo['anyDataPresent'];
                }
            }
        }
        return array('xmlParentElement' => $xmlParentElement,
            'anyDataPresent' => $anyDataPresent,
            'doAddThisLevel' => $doAddThisLevel
        );
    }


    /**
     * @param $rodsaccount
     * @param $config
     *
     * Handles the posted information of a yoda form and puts the values, after escaping, in .yoda-metadata.xml
     * The config holds the correct paths to form definitions and .yoda-metadata.xml
     *
     * NO VALIDATION OF DATA IS PERFORMED IN ANY WAY
     */
    public function processPost($rodsaccount, $config)
    {

        $allFormMetadata = $this->CI->input->post();

        if (isset($allFormMetadata['vault_submission'])) { // clean up: this is extra input in the posted data that should not be handled as being metadata
            unset($allFormMetadata['vault_submission']);
        }

        $xmlString = $this->metadataToXmlString($allFormMetadata, $rodsaccount, $config);

        exit;

        $this->CI->filesystem->writeXml($rodsaccount, $config['metadataXmlPath'], $xmlString);
    }

    /**
     * @param array $array
     * @return array
     *
     * Created unemarated array of a possibly associative array
     */
    public function enumerateArray(array $array)
    {
        if (!is_numeric(key($array))) {
            $enumeratedArray = array();
            $enumeratedArray[] = $array;
            return $enumeratedArray;
        }
        return $array;
    }


    /**
     * @param $element
     * @return bool
     *
     * Determine whether given element actually is a compound element and consists of several element in itself
     */
    public function isCompoundElement($element)
    {
        return (isset($element['@attributes']['class']) AND strtoupper($element['@attributes']['class']) == 'COMPOUND');
    }

    /**
     * @param $compoundElement
     * @return int
     *
     * Get the count of form elements within compoound field represented in $element
     * label, help and @attributes are not allowed to be counted as are not elements!
     */
    public function getCountOfElementsInCompound($compoundElement)
    {
        $exclude = array('label', 'help', '@attributes');
        $count=0;
        foreach($compoundElement as $elementName=>$elementData) {
            if (!in_array($elementName, $exclude)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @param $element
     * @return bool
     *
     * Determine whether given element is a subroperty structure - i.e. lead element and N subproperties
     */
    public function isSubpropertyStructure($element)
    {
        return (!isset($element['label']));
    }


// Nieuwe met inachtneming van subproperties structuur
    /*
         Differentiation Combined, subproperty and normal
        1) isset($element['combined']) => Combined element
        2) !isset($element['label']) => Subproperty (weak distinction! to be refactored???)
        3) rest is  normal item

    */
    public function getFormElements($rodsaccount, $config)
    {
        // load xsd and get all the info regarding restrictions
        $xsdElements = $this->loadXsd($rodsaccount, $config['xsdPath']); // based on element names

//        echo '<pre>';
//        print_r($xsdElements);
//        echo '</pre>';
//
//        echo '<hr><hr><hr>';

        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
        }

        $metadataPresent = false;

        $formData = array();
        if ($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') {
            $metadataPresent = true;
            $formData = $this->loadFormData($rodsaccount, $config['metadataXmlPath']);

            if ($formData === false) {
                return false;
            }
        }

        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);
        if ($formGroupedElements === false) {
            return false;
        }

        $groupName = 'undefined';

        // If there are multiple groups, the first should contain the number '0' as array-index .
        // Otherwise, this is the direct descriptive information (i.e. not an array form.
        // The software expects an array, so in the latter case should be an array)
        $formAllGroupedElements = array();
        foreach ($formGroupedElements['Group'] as $index => $array) {
            if ($index == '0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            } else {
                $formAllGroupedElements[] = $formGroupedElements['Group']; // rewrite it as an indexable array as input for coming foreach
            }
            break;
        }

        // Form elements is hierarchical too.
        // Bring it back to one level with
        // 1) parent indication
        // 2) fully qualified name (unique!)
        // 3) Add start end tags for combination fields

        // HIER ALLEEN DE HOOFD ELEMENTEN
        foreach ($formAllGroupedElements as $formElements) {

            foreach ($formElements as $key => $element) {
                // Find group definition
                // Find hierarchies of elements regarding subproperties.
                if ($key == '@attributes') { // GROUP handling
                    $groupName = $element['name'];

                }
                elseif ($this->isCompoundElement($element)) {
                    // formdata is passed as an enumerated string
                    if (!$this->addCompoundElement($config, $groupName, $key, $element, $this->enumerateArray($formData[$key]), $xsdElements)) {
                        return false;
                    }
                    // echo 'After compound element';
                    //exit;
                } elseif ($this->isSubpropertyStructure($element)) {//(!isset($element['label'])) { // STEPPING INTO DEEPER LEVELS -- we step into a hierarchy => SUPPROPERTIES

                    $structValueArray = $this->enumerateArray($formData[$key]);

                    if (!$this->addLeadAndSubpropertyElements($config, $groupName, $key, $element, $structValueArray, $xsdElements)) {
                        return false;
                    }
                } else // This is the first level only!
                {
                    $value = isset($formData[$key]) ? $formData[$key] : ''; // dit gaat goed, nieuwe wijze

                    // turn it into an array as multiple entries must be presented n times with same element properties but different value
                    // @todo - refactor to enumerateArray!
                    $valueArray = $this->getElementValueAsArray($value);

                    $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                    $multiPostFix = ''; // Addition to element name when mupliple instances can occur
                    if (!$multipleAllowed) {
                        if (count($valueArray) > 1) {
                            return false; // break it off as this does not comply with xml
                        }
                    } else {
                        $multiPostFix = '[]'; // Create array as variable can have multiple values, hier mag [] - volgorde is niet belangrijk
                    }

                    /// Step through all present values (if multiple and create element for each of them)
                    foreach ($valueArray as $keyValue) {
                        $this->presentationElements[$groupName][] =
                            $this->newWayPresentationElement($config, $xsdElements[$key], $element, $key . $multiPostFix, $keyValue);
                    }
                }
            }
        }
//
//        echo '<pre>';
//        print_r($this->presentationElements);
//        echo '</pre>';

// exit;

        return $this->presentationElements;
    }

    // Combination of Lead & subproperties => a combined field can also be a subproperty

    // Pre conditions for lead&subproperties:
    // 1 lead element
    // 1 property element designating properties
    // subproperties can be variable amounts and types (even compound element)

    // General rule: if a subproperty is present, the lead element has to be present as well

    public function addLeadAndSubpropertyElements($config, $groupName, $key, $element, $structValueArray, $xsdElements)
    {
        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
        }

        $elementCounterForFrontEnd = 0; // to be able to distinghuis structures and add to key

        // Per struct (at least once - otherwise N times) create the lead and subproperty elements
        foreach ($structValueArray as $structValues) {

            // MAIN LOOP TO SETUP A COMPLETE SUBPROPERTY STRUCTURE
            foreach ($element as $id => $em) {
                // $elements now holds an array of formelements - these are subproperties
                $subKey = $key . '_' . $id;

                if (isset($em['label'])) {   // LEAD ELEMENT OF A STRUCT

                    //$id can be used straight as lead is always single value
                    $subKeyValue = $structValues[$id];

                    // Use toplevel here as that defines multiplicity for a structure (is in fact not an element
                    $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                    $keyCounterInfix = '';

                    // @todo - zijn er meerdere waarden??? dan is dat fout => afbreken
                    if (!$multipleAllowed) {
//                    if(count($structValueArray)>1) {
//                        return false;
//                    }

                    } else {
                        $keyCounterInfix = '[' . $elementCounterForFrontEnd . ']'; // Add counter to key for controller array creation for frontend
                    }
                    // frontend value is the value that will be presented in the data field
                    // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                    $frontendValue = (isset($em['default']) AND $writeMode) ? $em['default'] : null;

                    if ($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
                        $frontendValue = $subKeyValue;
                    }

                    $subPropArray = array(
                        'subPropertiesRole' => 'subPropertyStartStructure',
                        'subPropertiesBase' => $key,
                        'subPropertiesStructID' => $multipleAllowed ? $elementCounterForFrontEnd : -1
                    );

                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement(
                            $config, $xsdElements[$subKey], $em, $key . $keyCounterInfix . '[' . $id . ']', $frontendValue, $multipleAllowed, $subPropArray);

                    //  Add start tag
                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement(
                            $config, array('type' => 'structSubPropertiesOpen'), $em, $key . $keyCounterInfix . '[' . $id . ']', '', $multipleAllowed, $subPropArray);

                } else { // STEP THROUGH EACH SUB PROPERTY
                    foreach ($em as $propertyKey => $propertyElement) {
                        $subKey = $key . '_' . $id . '_' . $propertyKey;
                        $subPropArray = array(
                            'subPropertiesRole' => 'subProperty',
                            'subPropertiesBase' => $key,
                            'subPropertiesStructID' => $multipleAllowed ? $elementCounterForFrontEnd : -1
                        );

                        if ($this->isCompoundElement($propertyElement)) { //isset($propertyElement['combined'])) {

                            $arRouting = $xsdElements[$subKey]['tagNameRouting'];

                            $subKeyValue = $structValues[$arRouting[1]][$arRouting[2]];

                            $subKeyValue = $this->enumerateArray($subKeyValue);


                            //$this->addCompoundElement($config, $groupName, $key, $element, $formData, $xsdElements);
                            // todo:: check value array -> is this what is required here???

                            $offsetKeyForFrontEnd = $key . $keyCounterInfix . '[' . $id . ']' . '[' . $propertyKey . ']';

                            // trap incorrect formatting
                            if (!$this->addCompoundElement($config, $groupName, $subKey, $propertyElement, $subKeyValue, $xsdElements,
                                $offsetKeyForFrontEnd, $subPropArray)
                            ) {
                                return false;
                            }
                        } else {
                            $arRouting = $xsdElements[$subKey]['tagNameRouting'];

//                            if($subKey=='Creator_Properties_Affiliation2') {
//                                echo 'subKey: ' . $subKey;
//                                echo '<pre>';
//                                    print_r($xsdElements);
//                                echo '</pre>';
//                                exit;
//                                //echo '<hr>';
//                            }

                            // we know this is the 2nd(1 in tagNameRoute) and 3rd(2 in tagNameRoute) level so no real analysing required
                            $subKeyValue = $structValues[$arRouting[1]][$arRouting[2]];

                            $subKeyValue = $this->enumerateArray($subKeyValue);

                            $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$subKey]);

                            // frontend value is the value that will be presented in the data field
                            // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                            $frontendValue = array(0 => (isset($propertyElement['default']) AND $writeMode) ? $propertyElement['default'] : null);

                            // @todo - hoe zit dit precies met die $config
                            if ($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
//                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');  // no purpose as it is superseded by next line
                                $frontendValue = $subKeyValue;
                            }

                            $multiPostFix = '';
                            if ($multipleAllowed) {
                                $multiPostFix = '[]'; // Create array as variable can have multiple values, hier mag [] - volgorde is niet belangrijk
                            }

                            foreach ($frontendValue as $fek => $fev) {
                                $this->presentationElements[$groupName][] =
                                    $this->newWayPresentationElement(
                                        $config, $xsdElements[$subKey], $propertyElement,
                                        $key . $keyCounterInfix . '[' . $id . '][' . $propertyKey . ']' . $multiPostFix,
                                        $fev, $multipleAllowed, $subPropArray);
                            }
                        }
                    }
                }
            }

            $this->presentationElements[$groupName][] =
                $this->newWayPresentationElement(
                    $config, array('type' => 'structSubPropertiesClose'), $em, $key . $keyCounterInfix . '[' . $id . ']', '', $multipleAllowed, $subPropArray);

            $elementCounterForFrontEnd++; // new element (if still present in array)
        }
        return true;
    }



// This fully adds an element that is a combination of all possible formelement-types
// Can also add these as a subproperty

// There are two keys
// one is directed at the frontend, to be used for an element's name.
// the other is used as an index at the internal arrays

// if counts are wrong in single element situation, return FALSE.
// Else return TRUE


    /**
     * @param $config
     * @param $groupName
     * @param $key
     * @param $element
     * @param $formData - enumerated highest level of data for this key
     * @param $xsdElements
     * @param string $elementOffsetFrontEnd
     * @param array $subPropArray
     * @return bool
     */
    public function addCompoundElement($config, $groupName, $key, $element,
                                       $formData, $xsdElements, $elementOffsetFrontEnd = '', $subPropArray = array()) // presentation elements will be extended -> make it class variable
    {
        // A combined element from 2017/10/25 on has a title & help text

        if (isset($xsdElements[$key])) {

            // Extend the passed array with compound specific data for the frontend

            // $element holds the structure under the parent level
            // Count te be decremented by 1 as @attributes is not part of the fields for the frontend
            /**
            [@attributes] => Array
                [class] => compound

            [Persistent_Identifier]
                [label] => Persistent Identifier
                [help] => Persistent identifier PI (e.g. an ORCID, DAI, or ScopusID)

            [Persistent_Identifier_Type]
                [label] => Type of Persistent Identifier
                [help] => What type of persistent person identifier


             */
            $subPropArrayExtended = $subPropArray;

            $subPropArrayExtended['compoundFieldCount'] = $this->getCountOfElementsInCompound($element);
            $subPropArrayExtended['compoundFieldPosition'] = 0;
            $subPropArrayExtended['compoundBackendArrayLevel'] = 0;

            if ($xsdElements[$key]['type'] == 'openTag') {

                if ($elementOffsetFrontEnd) {
                    unset($subPropArrayExtended['compoundBackendArrayLevel']);
                    $subPropArrayExtended['compoundBackendArrayLevel'] = 3;  // @todo - moet nog dynamisch!!!! maar nu klopt het altijd
                }

                if ($elementOffsetFrontEnd) { // is vanuit een subproperty siutatie
                    $baseCombiElementOffsetFrontEnd = $elementOffsetFrontEnd; // . "[$key]";
                } else {
                    $baseCombiElementOffsetFrontEnd = $key;
                }

                $combiElementMultipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                // front end should know whether entire compound is clonable in order to show the clone button
                $subPropArrayExtended['compoundMultipleAllowed'] = $combiElementMultipleAllowed;

                // Step though all values that are within yoda-metadata.xml
                $combiCounter = 0; // To create unique names in array form for frontend

                foreach ($formData as $arValues) { //   $formValues
                    // 1) Add start tag - based upon type = openTag
                    $combiElementName = $baseCombiElementOffsetFrontEnd;

//                    echo '<br>combination: ' . $combiElementName;
//
                    if ($combiElementMultipleAllowed) {
                        $combiElementName .= "[$combiCounter]";
                    }
//                    echo '<br>combination: ' . $combiElementName;

                    // This overall placeholder for multiple fields should indicate whether it is in its total mandatory
                    // 1) Mandatory for vault processing
                    // @todo:: Als er iets in het sublevel verplicht is, moet het top level ook verplicht aangeven????????????
                    // 2) MultipleAllowed [OK]
                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement($config, array('type' => 'structCombinationOpen'), $element, $combiElementName, '', false, $subPropArrayExtended);

                    // 2) step through all elements to complete the combined field

                    $fieldPosition = 0;
                    foreach ($element as $id => $em) {
                        // $elements now holds an array of formelements - these are subproperties
                        $subKey = $key . '_' . $id;

                        if ($id != '@attributes') { // exclude this item added due to class=compound in the tag itself
                            // @todo: een element kan ook weer een multiple variabele zijn !!!!!!!!!!!!!!!!! nog meenemen later
                            $subCombiCounter = 0;
                            // => dus hier lopen if so en keyname for front end aanpassen
                            $subCombiMultipleAllowed = $this->getElementMultipleAllowed($xsdElements[$subKey]);

//                            $allSubCombiValues = array();
//                            $allSubCombiValues[] = $arValues[$subKey];
                            unset($subPropArrayExtended['compoundFieldPosition']);
                            $subPropArrayExtended['compoundFieldPosition'] = $fieldPosition;

                            if (isset($xsdElements[$subKey])) {
                                $this->presentationElements[$groupName][] =
                                    $this->newWayPresentationElement($config, $xsdElements[$subKey], $em,
                                        $combiElementName . '[' . $id . ']' . ($subCombiMultipleAllowed ? '[]' : ''),
                                        $arValues[$id], false, $subPropArrayExtended);
                                $subCombiCounter++;

                                $fieldPosition++;
                            }
                        }
                    }
                    // 3) Add stop tag derived from the start tag
                    //$xsdEndTagElement = $xsdElements[$key];
                    //$xsdEndTagElement['type'] = 'endTag';
                    unset($subPropArrayExtended['fieldPosition']);
                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement($config, array('type' => 'structCombinationClose'), $element, $combiElementName, '', false, $subPropArrayExtended);

                    $combiCounter++;
                }
            }
        }
        return true;
    }


    // incliuding start / stop tags
    //xsdELement => data from XSD
    // $element => data from formelements
    // keyId name of element in frontend
    public function newWayPresentationElement($config, $xsdElement, $element, $keyId, $value, $overrideMultipleAllowed = false, $subpropertyInfo = array())
    {
        // @todo: - inefficient - will be determinded each time and element passes,
        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
        }

        $frontendValue = (isset($element['default']) AND $writeMode) ? $element['default'] : null;

        if ($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') {
            $frontendValue = $value;
        }

        if ($overrideMultipleAllowed) {
            $multipleAllowed = true;
        } else {
            $multipleAllowed = $this->getElementMultipleAllowed($xsdElement);
        }
        // Mandatory no longer based on XSD but taken from formelements.xml
        $mandatory = false;
        if (isset($element['mandatory']) AND strtolower($element['mandatory']) == 'true') {
            $mandatory = true;
        }

        $elementOptions = array(); // holds the options
        $elementMaxLength = 0;
        // Determine restricitions/requirements for this
        switch ($xsdElement['type']) {
            case 'structCombinationOpen':
                $type = 'structCombinationOpen'; // Start combination of elements in 1 element so frontend knows that several passing items are brought to 1 element
                break;
            case 'structCombinationClose':
                $type = 'structCombinationClose'; // combination of elements in 1 element so frontend knows that several passing items are brought to 1 element is to an end
                break;
            case 'structSubPropertiesOpen':
                $type = 'structSubPropertiesOpen';
                break;
            case 'structSubPropertiesClose':
                $type = 'structSubPropertiesClose';
                break;
            case 'xs:date':
                $type = 'date';
                break;
            case 'stringURI':
            case 'stringNormal':
                $type = 'text';
                $elementMaxLength = $xsdElement['simpleTypeData']['maxLength'];
                break;
            case 'xs:integer':
                $type = 'numeric';
                $elementMaxLength = 10;  // arbitrary length for this moment
                break;
            case 'xs:anyURI':
                $type = 'text';
                $elementMaxLength = 1024;
                break;
            case 'stringLong':
                $type = 'textarea';
                $elementMaxLength = $xsdElement['simpleTypeData']['maxLength'];
                break;
            case 'KindOfDataTypeType': // different option types will be a 'select' element (these are yet to be determined)
                /*
                case 'optionsDatasetType':
                case 'optionsDatasetAccess':
                case 'optionsYesNo':
                case 'optionsOther':
                case 'optionsPersonalPersistentIdentifierType':
                */
            case (substr($xsdElement['type'], 0, 7) == 'options'):
                $elementOptions = $xsdElement['simpleTypeData']['options'];
                $type = 'select';
                break;
        }

        //'select' has options
        // 'edit/multiline' has length
        // 'date' has nothing extra
        // Handled separately as these specifics might grow.
        $elementSpecifics = array(); // holds all element specific info
        if ($type == 'text' OR $type == 'textarea' OR $type == 'numeric') {
            $elementSpecifics = array('maxLength' => $elementMaxLength);
        } elseif ($type == 'select') {
            $elementSpecifics = array('options' => $elementOptions);
        }

        $elementData = array(
            'key' => $keyId, // Key to be used by frontend
            'value' => $frontendValue,
            'label' => $element['label'],
            'helpText' => $element['help'],
            'type' => $type,
            'mandatory' => $mandatory,
            'multipleAllowed' => $multipleAllowed,
            'elementSpecifics' => $elementSpecifics,
            'elementRouting' => $xsdElement['tagNameRouting']  // @todo: change tagNameRouting to elementRouting
        );

        if (count($subpropertyInfo)) {
            foreach ($subpropertyInfo as $key => $value) {
                $elementData[$key] = $value;
            }
        }
        return $elementData;
    }


    /**
     * @param $xsdElements
     * @param $element
     * @param $xsdKey
     * @param $keyId
     * @param $frontendValue
     * @param $multipleAllowed
     * @param array $subpropertyInfo
     * @return array
     * construct an array with all required data for frontend presentation in the metadataform
     */
    public function obsolete_newPresentationElement($xsdElements, $element, $xsdKey, $keyId, $frontendValue, $multipleAllowed, $subpropertyInfo = array())
    {
        // Mandatory no longer based on XSD but taken from formelements.xml
        $mandatory = false;
        if (isset($element['mandatory']) AND strtolower($element['mandatory']) == 'true') {
            $mandatory = true;
        }

        $elementOptions = array(); // holds the options
        $elementMaxLength = 0;
        // Determine restricitions/requirements for this
        switch ($xsdElements[$xsdKey]['type']) {
            case 'openTag':
                $type = 'tagstart'; // Start combination of elements in 1 element so frontend knows that several passing items are brought to 1 element
                break;
            case 'endTag':
                $type = 'tagend'; // combination of elements in 1 element so frontend knows that several passing items are brought to 1 element is to an end
                break;
            case 'xs:date':
                $type = 'date';
                break;
            case 'stringURI':
            case 'stringNormal':
                $type = 'text';
                $elementMaxLength = $xsdElements[$xsdKey]['simpleTypeData']['maxLength'];
                break;
            case 'xs:integer':
                $type = 'numeric';
                $elementMaxLength = 10;  // arbitrary length for this moment
                break;
            case 'xs:anyURI':
                $type = 'text';
                $elementMaxLength = 1024;
                break;
            case 'stringLong':
                $type = 'textarea';
                $elementMaxLength = $xsdElements[$xsdKey]['simpleTypeData']['maxLength'];
                break;
            case 'KindOfDataTypeType': // different option types will be a 'select' element (these are yet to be determined)
                /*
                case 'optionsDatasetType':
                case 'optionsDatasetAccess':
                case 'optionsYesNo':
                case 'optionsOther':
                case 'optionsPersonalPersistentIdentifierType':
                */
            case (substr($xsdElements[$xsdKey]['type'], 0, 7) == 'options'):
                $elementOptions = $xsdElements[$xsdKey]['simpleTypeData']['options'];
                $type = 'select';
                break;
        }

        //'select' has options
        // 'edit/multiline' has length
        // 'date' has nothing extra
        // Handled separately as these specifics might grow.
        $elementSpecifics = array(); // holds all element specific info
        if ($type == 'text' OR $type == 'textarea' OR $type == 'numeric') {
            $elementSpecifics = array('maxLength' => $elementMaxLength);
        } elseif ($type == 'select') {
            $elementSpecifics = array('options' => $elementOptions);
        }

        $elementData = array(
            'key' => $keyId, // Key to be used by frontend
            'value' => $frontendValue,
            'label' => $element['label'],
            'helpText' => $element['help'],
            'type' => $type,
            'mandatory' => $mandatory,
            'multipleAllowed' => $multipleAllowed,
            'elementSpecifics' => $elementSpecifics,
        );

        if (count($subpropertyInfo)) {
            foreach ($subpropertyInfo as $key => $value) {
                $elementData[$key] = $value;
            }
        }
        return $elementData;
    }

    /**
     * @param $xsdElement
     * @return bool
     *
     * Supporting function for getFormELements
     * is multiple allowed for the given element from the xsd
     */
    public function getElementMultipleAllowed($xsdElement)
    {
        $multipleAllowed = false;
        if ($xsdElement['maxOccurs'] != '1') {
            $multipleAllowed = true;
        }
        return $multipleAllowed;
    }

    /**
     * @param $value
     * @return array
     *
     * Supporting function for getFormELements
     *
     * return the value as being part of an array
     */
    public function getElementValueAsArray($value)
    {
        // The number of values determine the number of elements to be created
        if (!is_array($value)) {
            $valueArray = array();
            $valueArray[] = $value;
        } else {
            $valueArray = $value;
            if (count($valueArray) == 0) {
                $valueArray = array('');
            }
        }
        return $valueArray;
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @return array|bool
     *
     * Load XSD schema and reorganize in such a way that it coincides with formelements
     *
     * XSD can hold hierarchical complex types now that must match formelements definitions
     */
    public function loadXsd($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        $xml = simplexml_load_string($fileContent, "SimpleXMLElement", 0, 'xs', true);

        if (empty($xml)) {
            return false;
        }

        // At first simpleType handling - gathering limitations/restrictions/requirements
        $simpleTypeData = array();

        foreach ($xml->simpleType as $key => $stype) {
            // simpleTye names
            $simpleTypeAttributes = (array)$stype->attributes();

            $simpleTypeName = $simpleTypeAttributes['@attributes']['name'];

            $restriction = (array)$stype->restriction;

            // typical handling here
            if (isset($restriction['maxLength'])) {
                $lengthArray = (array)$stype->restriction->maxLength->attributes();
                $length = $lengthArray['@attributes']['value'];
                $simpleTypeData[$simpleTypeName]['maxLength'] = $length;
            }
            if (isset($restriction['enumeration'])) {
                $options = array();
                foreach ($stype->restriction->enumeration as $enum) {
                    $optionsArray = (array)$enum->attributes();
                    $options[] = $optionsArray['@attributes']['value'];
                }
                $simpleTypeData[$simpleTypeName]['options'] = $options;
            }
        }

        $supportedSimpleTypes = array_keys($simpleTypeData);
        $supportedSimpleTypes[] = 'xs:date'; // add some standard xsd simpleTypes that should be working as well
        $supportedSimpleTypes[] = 'xs:anyURI';
        $supportedSimpleTypes[] = 'xs:integer';

        // Basic information is complete

        // NOW collect stuff regarding fields

        $xsdElements = array();

        // Hier moet eigenlijk op getest worden of dit info bevat. Dit is de kern van informatie die je bij alle zaken brengt.
        // DIt is zelfs hierarchisch dus zou
        $elements = $xml->element->complexType->sequence->element;

        $this->addElements($xsdElements, $elements, $supportedSimpleTypes, $simpleTypeData);

//        echo '<pre>';
//        print_r($xsdElements);
//        echo '</pre>';
//        exit;

        return $xsdElements;
    }

    /**
     * @param $xsdElements
     * @param $elements
     * @param $supportedSimpleTypes
     * @param $simpleTypeData
     * @param string $prefixHigherLevel

    Handling of hierarchical elements
     */
    public function addElements(&$xsdElements, $elements, $supportedSimpleTypes, $simpleTypeData, $prefixHigherLevel = '')
    {
        foreach ($elements as $element) {
            $attributes = $element->attributes();

            $elementName = '';
            $elementType = '';
            $minOccurs = 0;
            $maxOccurs = 1;

            foreach ($attributes as $attribute => $simpleXMLvalue) {
                $arrayValue = (array)$simpleXMLvalue;
                $value = $arrayValue[0];
                switch ($attribute) {
                    case 'name':
                        $elementName = $value;
                        break;
                    case 'type':
                        $elementType = $value;
                        break;
                    case 'minOccurs':
                        $minOccurs = $value;
                        break;
                    case 'maxOccurs':
                        $maxOccurs = $value;
                        break;
                }
            }

//            echo '<br> PrefixHigherLevel: ' . $prefixHigherLevel;
//            echo '<br> Element name: '.$elementName.'<br>';

            $isDeeperLevel = false;
            if (@property_exists($element->complexType->sequence, 'element')) {  // Starting tag Deeper level
                $isDeeperLevel = true;
            }

            // with parentElement the routing to this element can be set
            // Routing is required to address directly in formData-array or formElements-array when processing
            $parentElementName = substr($prefixHigherLevel, 0, strlen($prefixHigherLevel) - 1);

            $routing = array();
            if (isset($xsdElements[$parentElementName]['tagNameRouting'])) {
                $routing = $xsdElements[$parentElementName]['tagNameRouting'];
                $routing[] = $elementName;
            } else {
                $routing[] = $elementName;
            }
            // each relevant attribute has been processed.
            if (in_array($elementType, $supportedSimpleTypes) OR $isDeeperLevel) {
                $xsdElements[$prefixHigherLevel . $elementName] = array(
                    'type' => ($isDeeperLevel ? 'openTag' : $elementType),
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array(),
                    'tagNameRouting' => $routing
                );
            }

            if ($isDeeperLevel) { // Add new level and extend the prefix-identifier
                $elementSubLevel = $element->complexType->sequence->element;

                //$prefixHigherLevel = $elementName . '_'; // to be used to identify elements

                $prefixHigherLevelKeep = $prefixHigherLevel;
                if (!$prefixHigherLevel) {
                    $prefixHigherLevel = $elementName . '_';
                } else {
                    $prefixHigherLevel = $prefixHigherLevel . $elementName . '_';
                }


                // dieper niveau uitvoeren op basis an de gestelde prefix.
                $this->addElements($xsdElements, $elementSubLevel, $supportedSimpleTypes, $simpleTypeData, $prefixHigherLevel);

                $prefixHigherLevel = $prefixHigherLevelKeep; //''; //reset it again

                // Closing tag - Deeper level
                $xsdElements[$prefixHigherLevel . $elementName . '_close'] = array(
                    'type' => 'closeTag',
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array()
                );
            }
        }
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @return array|bool
     *
     * Load the yoda-metadata.xml file ($path) in an array structure
     *
     * Reorganise this this in such a way that hierarchy is lost but indexing is possible by eg 'Author_Property_Role'
     */
    public function loadFormData($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);
        libxml_use_internal_errors(true);
        $xmlData = simplexml_load_string($fileContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (count($errors)) {
            return false;
        }

        $json = json_encode($xmlData);

        $formData = json_decode($json, TRUE);

        return $formData;
    }

    /**
     * @param $rodsaccount
     * @param $path
     * @return bool|mixed
     *
     * Load the required xml file with formelements description and return as an array
     */
    public function loadFormElements($rodsaccount, $path)
    {
        $fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        if (empty($fileContent)) {
            return false;
        }
        $xmlFormElements = simplexml_load_string($fileContent);

        $json = json_encode($xmlFormElements);

        $data = json_decode($json, TRUE);

        // Single presence of a group requires extra handling
        if (!isset($data['Group'][0])) {
            $keepGroupData = $data['Group'];
            unset($data['Group']);
            $data['Group'][0] = $keepGroupData;
        }

        return $data;
    }

}