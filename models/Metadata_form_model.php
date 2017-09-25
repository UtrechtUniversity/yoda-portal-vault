<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model {

    var $CI = NULL;
    var $presentationElements = array();

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
        foreach($formGroupedElements['Group'] as $formElements) {
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
        foreach($formGroupedElements['Group'] as $formElements) {
            foreach ($formElements as $key => $element) {
                if ($key == '@attributes') {
                    $groupNames[] = $element['name'];
                }
                else {
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
     public function iterateElements($element, $key, &$elementLabels, $leadPropertyBase, $level=0) {
        if (isset($element['label'])) {
            $elementLabels[$key] = $element['label'];
            if ($level == 1) {
                $elementLabels[$leadPropertyBase] =  $element['label'];
            }
            elseif ($level>1) {
                $elementLabels[$key] = $elementLabels[$leadPropertyBase] . '-'. $element['label'];

            }
        }
        else {
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
    public function getFormElementsAsArray($rodsaccount, $config) {
        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['formelementsPath']);

        // If there are multiple groups, the first should contain the number '0' as array-index .
        // Otherwise, this is the direct descriptive information (i.e. not an array form.
        // The software expects an array, so in the latter case should be an array)
        $formAllGroupedElements = array();
        foreach($formGroupedElements['Group'] as $index => $array) {
            if($index=='0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            }
            else {
                $formAllGroupedElements[] = $formGroupedElements['Group']; // rewrite it as an indexable array as input for coming foreach
            }
            break;
        }

        $allElements = array();
        foreach($formAllGroupedElements as $formElements) {
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
    [Name] =>
    [Properties] => Array
    (
        [PI] =>
        [PI_Type] =>
        [Affiliation] =>
    )
     */
    public function arrayHoldsAnyData($array)
    {
        foreach ($array as $key=>$value ) {
            if (!is_array($value)) {
                if ($value) {
                    return true;
                }
            }
            else {
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
            unset($arrayPost['vault_submission']) ;
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
        foreach($arrayPost as $key=>$value) {
            if (is_array($value)) { // multiplicity && subproperty handling
                $sublevelCounter = 0;
                $keepArray = array();
                $structType = '';

                foreach($value as $subKey=>$subValue) {
                    if(is_numeric($subKey)) {
                        if($subValue) {
                            if (!isset($metadataElements[$elementCounter])) {
                                $newValueCollection = array();
                                foreach ($value as $index=>$localVal) { // rebuild the array only holding values
                                    if ($localVal) {
                                        $newValueCollection[] = $localVal;
                                    }
                                }
                                $metadataElements[$elementCounter] = array($key => $newValueCollection);
                            }
                        }
                    }
                    else { // subproperty handling - can either be a single struct or n-structs
                        // A struct typically has 1 element as a hierarchical top element.
                        // The other element holds properties

                        if ($sublevelCounter==0) {
                            if (count($subValue)==1) { // single instance of a struct
                                $structType = 'SINGLE';
                            }
                            else { // multiple instances of struct
                                $structType = 'MULTIPLE';
                            }
                            $keepArray[$sublevelCounter] = array($subKey=>$subValue);
                        }
                        else {
                            $keepArray[$sublevelCounter] = array($subKey=>$subValue);
                        }
                    }
                    $sublevelCounter++;
                }

                $multipleAllowed = false;
                if($xsdElements[$key]['maxOccurs']!='1') {
                    $multipleAllowed = true;
                }
                $isStruct = false;
                if ($xsdElements[$key]['type']=='openTag') {
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
                }
                elseif ($structType == 'MULTIPLE' OR ($multipleAllowed AND $isStruct)) { // Multi situation
                    $enumeratedData = array();
                    foreach($keepArray[0] as $keyData=>$valData) { // step through the lead properties => then find corresponding subproperties
                        foreach($valData as $referenceID=>$leadPropertyVal ) { // referenceID is actually the counter in the array that coincides for lead and subproperties

                            // Within keepArray[1] handle the corresponding subproperties
                            $subpropArray = array();
                            $structHoldsData = strlen($leadPropertyVal)>0 ? true : false;
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
            }
            else {
                // only add if actually containing a value
                if ($value) {
                    $metadataElements[$elementCounter] = array($key => $value);
                }
            }
            $elementCounter++;
        }

        return $metadataElements;
    }

    /**
     * @param $allFormMetadata
     * @return string
     *
     * Per form element that holds data create xml structure
     */
    public function metadataToXmlString($allFormMetadata, $rodsaccount, $config)
    {
        $xsdPath = $config['xsdPath'];
        $xsdElements = $this->loadXsd($rodsaccount, $xsdPath);


        $xml = new DOMDocument( "1.0", "UTF-8" );
        $xml->formatOutput = true;

        $xml_metadata = $xml->createElement( "metadata" );

//        foreach ($allFormMetadata as $elements) {

            foreach ($allFormMetadata as $elementName=>$elementData) {
                //echo 'Main Element' . $elementName;



//                $xml_item = $xml->createElement($elementName);  //@todo - moet deze hier????????????????? voor combi's ziet ie in de loop
//                                                                // Maar als deze niet wordt geappend aan metadata!!, is er geen probleem!!!

                // TOP LEVEL COMBINED FIELD
                if (false) { // check whether combined within formElements info
                    $combiElementsData = array();
                    if (!is_numeric(key($elementData))) {
                        $combiElementsData[] = $elementData; // make it an enumerated array
                    } else {
                        $combiElementsData = $elementData;
                    }

                    foreach ($combiElementsData as $combinedKey => $combinedData) {
                        $xml_item = $xml->createElement($elementName);

                        foreach ($combinedData as $combiName => $combiVal) { // Loop all tags for this instane

                            $xmlCombi = $xml->createElement($combiName);
                            $xmlCombi->appendChild($xml->createTextNode($combiVal));
                            $xml_item->appendChild($xmlCombi);
                        }
                        $xml_metadata->appendChild($xml_item); //$xml->createElement($elementName);
                    }
                }
                elseif (false) { // Subproperty struct
//                    echo '<pre>';
//                    print_r($elementData);
//                    echo '</pre>';
//                    exit;

                    if (!is_numeric(key($elementData))) {
                        $subPropLevelData[] = $elementData;
                    }
                    else {
                        $subPropLevelData = $elementData;
                    }

                    // 1 subproperty struct bestaat uit een leading element en een body aan subproperties met eventueel combi-elementen
                    // --- LEAD ELEMENT
                    // --- PROPERTIES (n??)
                    // -------SUBPROP1 (n)
                    // -------SUBPROP2 (n)
                    // -------COMBI-DESIGNATOR (n)
                    // ------------item1 (n)
                    // ------------item2 (n)
                    // ------SUBPROP3 (n)

                    foreach($subPropLevelData as $subPropStruct) {

                        echo '<pre>';
                            print_r($subPropStruct);
                        echo '</pre>';

                        // when at an actual value
                        // step through the entire struct
                        foreach($subPropStruct as $k=>$v) {
                            echo '<br>elementName: ' . $elementName;
                            $xmlElement = $xml->createElement($elementName); // highest level under <metadata>

                            // step througn an entire structure and see whether it can be added or not
                            if (!is_array($v)) {
                                echo '<br>key: ' . $k;
                                echo '<br>Val' . $v;

                                $xmlLeadElement = $xml->createElement($k);
                                $xmlLeadElement->appendChild($xml->createTextNode($v));
                                // Add element to current level
                            }
                            else { // Add total subproperty level
                                if (!is_numeric(key($v))) {
                                    $propertyData[] = $v;
                                }
                                else {
                                    $propertyData = $v;
                                }

                                $xmlProperty = $xml->createElement($k);

                                foreach ($propertyData as $prop) {

                                    print_r($prop);
                                }



                            }

                            $xmlElement->appendChild($xmlLeadElement);
                            $xmlElement->appendChild($xmlProperty);
                            $xml_metadata->appendChild($xmlElement);
                        }
                    }
                    //exit;
                }

                // SIMPLE ELEMENT - TOP LEVEL
                elseif (true) { // dit moet de laatste keuze worden, de hieraan voorafgaande zijn speciale
                    $topLevelData = array();
                    if (!is_numeric(key($elementData))) {
                        $topLevelData[] = $elementData;
                    }
                    else {
                        $topLevelData = $elementData;
                    }

                    // N items in array => add N elements with name $elementName and to be added to $xml_metadata
                    foreach($topLevelData as $key => $val) { // per $val => entire structure to be added to $elementName

                        $temp = array();
                        if (!(is_array($val) AND is_numeric(key($val)))) {
                            // enumerated structures where we only need the structure itself.
                            $temp[] = $val;
                        } else {
                            $temp = $val;
                        }

                        foreach ($temp as $elementInfo) { // step through each instance of each element

                            $xml_item = $xml->createElement($elementName); // base element
                            //$xml_item->appendChild($xml->createTextNode($val));
                            $metaStructureXML = $this->xmlMetaStructure($xml, $elementInfo, $xml_item);
                            // Only add to actual structure if
                            if ($metaStructureXML['anyDataPresent']  ) {
                                $xml_metadata->appendChild($metaStructureXML['xmlParentElement']);
                            }
                        }
                    }
                }
                elseif (false) {

                    if (is_array($elementData)) { // Mutiple situation

                        foreach ($elementData as $key => $value) {

                            if (is_numeric($key)) { // enumeration - multiple

                                if (!is_array($value)) { // multiple - no structure
                                    $xml_item = $xml->createElement($elementName); // Per round add 1 new item with same node name to toplevel
                                    $xml_item->appendChild($xml->createTextNode($value));
                                    $xml_metadata->appendChild($xml_item);
                                } else { // enumerated subproperty structure
                                    // entire structure comes by n times

                                    foreach ($value as $key2 => $value2) {
                                        if (!is_array($value2)) {  // $key2 = Name/ Property
                                            // strucure is existant - checked by reorganisePostedData. The leadproperty must be added eventhough it could hold no actual data at this moment
                                            // Aanvankelijk werd de leadprop niet opgeslagen ... dit leidde tot een foute xml structuur en werd laden afgebroken hierdoor
                                            $xml_item = $xml->createElement($elementName);
                                            $xml_sub1 = $xml->createElement($key2);
                                            $xml_sub1->appendChild($xml->createTextNode($value2));
                                            $xml_item->appendChild($xml_sub1);
                                            $xml_metadata->appendChild($xml_item);
                                        } else {
                                            $xml_sub1 = $xml->createElement($key2);
                                            foreach ($value2 as $key3 => $value3) {
                                                if ($value3) {
                                                    $xml_sub2 = $xml->createElement($key3);
                                                    $xml_sub2->appendChild($xml->createTextNode($value3));
                                                    $xml_sub1->appendChild($xml_sub2);
                                                }
                                            }
                                            $xml_item->appendChild($xml_sub1);
                                            $xml_metadata->appendChild($xml_item);
                                        }
                                    }
                                }
                            } else { // 1-off property structure
                                $xml_sub1 = $xml->createElement($key); //Name of Property

                                if (!is_array($value)) { // eerste niveau - main property level
                                    $xml_sub1->appendChild($xml->createTextNode($value));
                                    $xml_item->appendChild($xml_sub1);
                                } else { // tweede niveau - subproperties
                                    foreach ($value as $key2 => $value2) {
                                        $xml_sub2 = $xml->createElement($key2);
                                        $xml_sub2->appendChild($xml->createTextNode($value2));
                                        $xml_sub1->appendChild($xml_sub2);

                                    }
                                    $xml_item->appendChild($xml_sub1);
                                }
                                $xml_metadata->appendChild($xml_item);
                            }
                        }
                    } else { // 1 topnode - 1 value
                        $xml_item = $xml->createElement($elementName);
                        $xml_item->appendChild($xml->createTextNode($elementData));
                        $xml_metadata->appendChild($xml_item);
                    }
                }
            }
 //       }

        $xml->appendChild($xml_metadata);

        return $xml->saveXML();
    }


    //
    public function xmlMetaStructure ($xmlMain, $arMetadata, $xmlParentElement, $anyDataPresent = false)
    {
        $arrayMeta = array();
        if (!is_array($arMetadata)) {
            $arrayMeta[] = $arMetadata;
        }
        else {
            $arrayMeta = $arMetadata;
        }

        foreach ($arrayMeta as $key => $val) {
            //$xml_item = $xml->createElement($elementName);

            if (!is_array($val) AND is_numeric($key)) {
                $xmlParentElement->appendChild($xmlMain->createTextNode($val));
                //return $xmlParentElement;
                if ($val) {
                    $anyDataPresent = true;
                }
            }
            else {
                // $val kan hier multi zijn
                $arraySubLevels = array();
                if (!is_numeric(key($val))) { // if not enumerated
                    $arraySubLevels[] = $val;
                }
                else {
                    $arraySubLevels = $val;
                }
                foreach ($arraySubLevels as $key2=>$val2) {
                    $xmlElement = $xmlMain->createElement($key);
                    //$xmlNew
                    $structInfo = $this->xmlMetaStructure($xmlMain, $val2, $xmlElement, $anyDataPresent);
                    $xmlParentElement->appendChild($structInfo['xmlParentElement']);
                    $anyDataPresent = $structInfo['anyDataPresent'];
                }
            }
        }
        return array('xmlParentElement' => $xmlParentElement,
                'anyDataPresent' => $anyDataPresent
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
    public function processPost($rodsaccount, $config) {

//         $allFormMetadata = $this->processPostedData($rodsaccount, $config['xsdPath']);
//
//        exit;

//         $allFormMetadata = $this->reorganisePostedData($rodsaccount, $config['xsdPath']);
//         exit;


        //@todo: VAULT submission moet er nog !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        $arrayPost = $this->CI->input->post();

        if (isset($arrayPost['vault_submission'])) { // clean up: this is extra input in the posted data that should not be handled as being metadata
            unset($arrayPost['vault_submission']);
        }

//        echo '<pre>';
//        print_r($arrayPost);
//        echo '</pre>';

        $allFormMetadata = array(
            'Title' => array(0=>$arrayPost['Title'], 2=>'BLABLA'),
            'Creator' => array(0 => $arrayPost['Creator'],
                1=> $arrayPost['Creator']),
            //'Title' => array(0=>$arrayPost['Title'], 2=>'BLABLA'),
             'Person' => array(0=>$arrayPost['Person'], 1=>$arrayPost['Person']),
            //'Description' => array( 0=>$arrayPost['Description'], 1=>'asd' ),
            'Discipline' => array(0 => 'hallo',
                1=>'bla'),
            //'Person' => array( 0 => $arrayPost['Person'],
            //                1 => $arrayPost['Person']),
            'Related_Datapackage' =>  $arrayPost['Related_Datapackage'],
        );



//        echo '<pre>';
//        print_r($allFormMetadata);
//        echo '</pre>';

        $xmlString = $this->metadataToXmlString($allFormMetadata, $rodsaccount, $config);

        echo $xmlString;
        exit;


        $this->CI->filesystem->writeXml($rodsaccount, $config['metadataXmlPath'], $xmlString);
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
        foreach($formGroupedElements['Group'] as $index => $array) {
            if($index=='0') {
                // is the index of an array. So we have multiple groups.
                $formAllGroupedElements = $formGroupedElements['Group'];
            }
            else {
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
        foreach($formAllGroupedElements as $formElements) {

            foreach ($formElements as $key => $element) {
                // Find group definition
                // Find hierarchies of elements regarding subproperties.

                if($key == '@attributes') { // GROUP handling

                    $groupName = $element['name'];
                }
                elseif (isset($element['combined'])) { // STARTING combination of
                    // Start of a row that combines multiple fields
                    if (!$this->addCombinedElement($config, $groupName, $key, $element, $formData, $xsdElements)) {
                        return false;
                    }
                }
                elseif (!isset($element['label'])) { // STEPPING INTO DEEPER LEVELS -- we step into a hierarchy => SUPPROPERTIES

                    $structureValues = $formData[$key];

                    if (!is_array($structureValues)) {
                        $structValueArray = array();
                        $structValueArray[] = $formData; //$structureValues;
                    } else {
                        $structValueArray = $structureValues;
                        if (count($structValueArray) == 0) {
                            $structValueArray = array('');
                        }
                    }
                    if (!$this->addLeadAndSubpropertyElements($config, $groupName, $key, $element, $structValueArray, $xsdElements)) {
                        return false;
                    }
                }

                else // This is the first level only!
                {
                    $value = isset($formData[$key]) ? $formData[$key] : '';

                    // turn it into an array as multiple entries must be presented n times with same element properties but different value
                    $valueArray = $this->getElementValueAsArray($value);

                    $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                    $multiPostFix = ''; // Addition to element name when mupliple instances can occur
                    if (!$multipleAllowed) {
                        if (count($valueArray) > 1) {
                            return false; // break it off as this does not comply with xml
                        }
                    }
                    else {
                        $multiPostFix = '[]'; // Create array as variable can have multiple values, hier mag [] - volgorde is niet belangrijk
                    }

                    /// Step through all present values (if multiple and create element for each of them)
                    foreach ($valueArray as $keyValue)
                    {
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
//        exit;

        return $this->presentationElements;
    }


    // Combination of Lead & subproperties => a combined field can also be a subproperty
    public function addLeadAndSubpropertyElements($config, $groupName, $key, $element, $structValueArray, $xsdElements)
    {
        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
        }

        $elementCounterForFrontEnd = 0; // to be able to distinghuis structures and add to key
        foreach ($structValueArray as $structValues) {

            // MAIN LOOP TO SETUP A COMPLETE SUBPROPERTY STRUCTURE
            foreach ($element as $id => $em) {
                // $elements now holds an array of formelements - these are subproperties

                $subKey = $key . '_' . $id;

                if (isset($em['label'])) {   // TOP ELEMENT OF A STRUCT
                    $subKeyValue = $structValues[$subKey];

                    // Use toplevel here as that defines multiplicity for a structure (is in fact not an element
                    $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                    $keyCounterSuffix = '';

                    // @todo - zijn er meerdere waarden??? dan is dat fout => afbreken
                    if (!$multipleAllowed) {
//                                    if(count($structValueArray)>1) {
//                                        return false;
//                                    }

                    }
                    else {
                        $keyCounterSuffix = '[' . $elementCounterForFrontEnd . ']'; // Add counter to key for controller array creation for frontend
                    }


                    // frontend value is the value that will be presented in the data field
                    // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                    $frontendValue = (isset($em['default']) AND $writeMode) ? $em['default'] : null;

                    if($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
                        $frontendValue = $subKeyValue;
                    }

                    $subPropArray = array(
                        'subPropertiesRole' => 'subPropertyStartStructure',
                        'subPropertiesBase' => $key,
                        'subPropertiesStructID' => $multipleAllowed ? $elementCounterForFrontEnd : -1
                    );


                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement(
                                $config, $xsdElements[$subKey], $em, $key . $keyCounterSuffix . '[' . $id . ']',$frontendValue, $multipleAllowed, $subPropArray);

                    //  Add start tag
                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement(
                            $config, array('type'=>'structSubPropertiesOpen'), $em, $key . $keyCounterSuffix . '[' . $id . ']','', $multipleAllowed, $subPropArray);

                }
                else { // STEP THROUGH EACH SUB PROPERTY
                    foreach ($em as $propertyKey => $propertyElement) {

                        $subKey = $key . '_' . $id . '_' . $propertyKey;
                        $subPropArray = array(
                            'subPropertiesRole' => 'subProperty',
                            'subPropertiesBase' => $key,
                            'subPropertiesStructID' => $multipleAllowed ? $elementCounterForFrontEnd : -1
                        );

                        if (isset($propertyElement['combined'])) {
                            //$this->addCombinedElement($config, $groupName, $key, $element, $formData, $xsdElements);
                            // todo:: check value array -> is this what is required here???

                            $offsetKeyForFrontEnd = $key . $keyCounterSuffix . '[' . $id . ']' . '[' . $propertyKey . ']';

                            // trap incorrect formatting
                            if (!$this->addCombinedElement($config, $groupName, $subKey, $propertyElement, $structValueArray, $xsdElements,
                                $offsetKeyForFrontEnd, $subPropArray)) {
                                return false;
                            }
                        }
                        else {

                            $subKeyValue = $structValues[$subKey];

                            $multipleAllowed = $this->getElementMultipleAllowed($xsdElements[$subKey]);

                            // frontend value is the value that will be presented in the data field
                            // If no metadata-file present, it will fall back to its default ONLY of in writable mode (i.e NO READER)
                            $frontendValue = (isset($propertyElement['default']) AND $writeMode) ? $propertyElement['default'] : null;

                            // @todo - hoe zit dit precies met die $config
                            if (true) { //$config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') { // the value in the file supersedes default @todo!!!????????? hoe zit dit??
//                            $frontendValue = htmlspecialchars($keyValue, ENT_QUOTES, 'UTF-8');  // no purpose as it is superseded by next line
                                $frontendValue = $subKeyValue;
                            }

//                            $subPropArray = array(
//                                'subPropertiesRole' => 'subProperty',
//                                'subPropertiesBase' => $key,
//                                'subPropertiesStructID' => $multipleAllowed ? $counterForFrontEnd : -1
//                            );
//                            $this->presentationElements[$groupName][] =
//                                $this->newPresentationElement($xsdElements, $propertyElement, $subKey,
//                                    $key . '[' . $id . '][' . $propertyKey . ']', $frontendValue, $multipleAllowed, $subPropArray);
//

                            $multiPostFix = '';
                            if($multipleAllowed) {
                                $multiPostFix = '[]'; // Create array as variable can have multiple values, hier mag [] - volgorde is niet belangrijk
                            }
                            $this->presentationElements[$groupName][] =
                                $this->newWayPresentationElement(
                                    $config, $xsdElements[$subKey], $propertyElement,
                                    $key . $keyCounterSuffix . '[' . $id . '][' . $propertyKey . ']' . $multiPostFix,
                                    $frontendValue, $multipleAllowed, $subPropArray);

                        }
                    }
                }
            }

            $this->presentationElements[$groupName][] =
                $this->newWayPresentationElement(
                    $config, array('type'=>'structSubPropertiesClose'), $em, $key . $keyCounterSuffix . '[' . $id . ']','', $multipleAllowed, $subPropArray);

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
    public function addCombinedElement($config, $groupName, $key, $element,
                                       $formData, $xsdElements, $elementOffsetFrontEnd='', $subPropArray = array()) // presentation elements will be extended -> make it class variable
    {
        if (isset($xsdElements[$key]) ) {

//            echo '<br> COMBINED ELEMENT';
//            echo 'Key: ' . $key;
//            echo '<br>';
//            echo 'Offset->' . $elementOffsetFrontEnd; // is de NIET ge-arrayriseerde zonder de key
            //                                          -> Die Key wordt er later achter geplakt, al dan niet met [teller]

            //            exit;

//            echo '<pre>';
//            echo 'KEY: ' . $key;
//            print_r($formData);
//            echo '</pre>';

            if ($key=='Person' OR $key=='Related_Datapackage_Properties_ORCID_Combination') {
//                echo '<pre>';
//                echo $key;
//                echo '<br>';
//                print_r($formData);
//
                if($elementOffsetFrontEnd) {
                    $formData = $formData[0];
                }
//
//                print_r($formData);
//
//                echo '</pre>';
            }

            if ($xsdElements[$key]['type'] == 'openTag') {

                if ($elementOffsetFrontEnd AND false) { // initiated from being subproperty position
//                    if (!count($formData[0][$key])) {
//                        $formValues[] = $formData[0];
//                    } else {
//                        //@todo::: Chechk of het wel een mutliple field mag zijn?!
//                        $formValues = $formData[0][$key];
//                    }
//                    echo 'hallo';
                    $formValues = $formData; // take it over directly as it is indexed already
                }
                else {
                    // Is single or multiple values in yoda-metadata.xml
                    // Make a numerated array for it like it would be in a multi value situation
                    if (!count($formData[$key])) {
                        $formValues[] = $formData;
                    } else {
                        //@todo::: Chechk of het wel een mutliple field mag zijn?!
                        $formValues = $formData[$key];
                    }
                }
//                echo '<pre>';
//                echo 'KEY: ' . $key;
//                print_r($formValues);
//                echo '</pre>';
                //exit;



                if ($elementOffsetFrontEnd) { // is vanuit een subproperty siutatie
                    $baseCombiElementOffsetFrontEnd = $elementOffsetFrontEnd; // . "[$key]";
                }
                else {
                    $baseCombiElementOffsetFrontEnd = $key;
                }

//                echo '<hr>Key: ' . $key . '<hr>';

                $combiElementMultipleAllowed = $this->getElementMultipleAllowed($xsdElements[$key]);

                // multiple values present where only one value is allowed.
                // Incorrect xml format
                if (!$combiElementMultipleAllowed AND count($formValues)>1) {
                    return false;
                }


                // Step though all values that are within yoda-metadata.xml
                $combiCounter = 0; // To create unique names in array form for frontend

                foreach ($formValues as $arValues) { //$formData[$key]
                    // 1) Add start tag - based upon type = openTag

                    $combiElementName =  $baseCombiElementOffsetFrontEnd;
                    if ($combiElementMultipleAllowed) {
                        $combiElementName .= "[$combiCounter]";
                    }

                    // This overall placeholder for multiple fields should indicate whether it is in its total mandatory
                    // 1) Mandatory for vault processing
                    // @todo:: Als er iets in het sublevel verplicht is, moet het top level ook verplicht aangeven????????????
                    // 2) MultipleAllowed [OK]
                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement($config, array('type'=>'structCombinationOpen'), $element, $combiElementName, '', false, $subPropArray);

                    // 2) step through all elements to complete the combined field
                    foreach ($element as $id => $em) {
                        // $elements now holds an array of formelements - these are subproperties
                        $subKey = $key . '_' . $id;

//                        echo '<br>subKey: ' . $subKey;
//                        echo '<br>formD: ' . $formData[0][$subKey];
                        //print_r($arValues);

                        // @todo: een element kan ook weer een multiple variabele zijn !!!!!!!!!!!!!!!!! nog meenemen
                        $subCombiCounter = 0;
                        // => dus hier lopen if so en keyname for front end aanpassen
                        $subCombiMultipleAllowed = $this->getElementMultipleAllowed($xsdElements[$subKey]);

                        $allSubCombiValues = array();
                        $allSubCombiValues[] = $arValues[$subKey];

                        foreach ($allSubCombiValues as $subCombiValue) {
                            if (isset($xsdElements[$subKey])) {
                                $this->presentationElements[$groupName][] =
                                    $this->newWayPresentationElement($config, $xsdElements[$subKey], $em,
                                        $combiElementName . '[' . $id . ']' . ($subCombiMultipleAllowed ? '[]' : ''),
                                        $subCombiValue, false, $subPropArray);
                                $subCombiCounter++;
                            }
                        }
                    }
                    // 3) Add stop tag derived from the start tag
                    //$xsdEndTagElement = $xsdElements[$key];
                    //$xsdEndTagElement['type'] = 'endTag';
                    $this->presentationElements[$groupName][] =
                        $this->newWayPresentationElement($config, array('type'=>'structCombinationClose'), $element, $combiElementName, '', false, $subPropArray);

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
    public function newWayPresentationElement($config, $xsdElement, $element, $keyId, $value, $overrideMultipleAllowed=false, $subpropertyInfo=array())
    {
        // @todo: - inefficient - will be determinded each time and element passes, b
        $writeMode = true;
        if ($config['userType'] == 'reader' || $config['userType'] == 'none') {
            $writeMode = false; // Distinnction made as readers, in case of no xml-file being present, should  NOT get default values
        }

        $frontendValue = (isset($element['default']) AND $writeMode) ? $element['default'] : null;

        if($config['hasMetadataXml'] == 'true' || $config['hasMetadataXml'] == 'yes') {
            $frontendValue = $value;
        }

        if ($overrideMultipleAllowed) {
            $multipleAllowed = true;
        }
        else {
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
        switch ($xsdElement['type']){
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
        if ($type == 'text' OR $type == 'textarea' OR $type=='numeric') {
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
            foreach ($subpropertyInfo as $key=>$value) {
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
    public function obsolete_newPresentationElement($xsdElements, $element, $xsdKey, $keyId, $frontendValue, $multipleAllowed, $subpropertyInfo=array())
    {
        // Mandatory no longer based on XSD but taken from formelements.xml
        $mandatory = false;
        if (isset($element['mandatory']) AND strtolower($element['mandatory']) == 'true') {
            $mandatory = true;
        }

        $elementOptions = array(); // holds the options
        $elementMaxLength = 0;
        // Determine restricitions/requirements for this
        switch ($xsdElements[$xsdKey]['type']){
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
        if ($type == 'text' OR $type == 'textarea' OR $type=='numeric') {
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
            foreach ($subpropertyInfo as $key=>$value) {
                $elementData[$key] = $value;
            }
        }
        return $elementData;
    }

    /**
     * @param $xsdElement
     * @return bool
     *
    Supporting function for getFormELements
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
        if(!is_array($value) ) {
            $valueArray = array();
            $valueArray[] = $value;
        }
        else {
            $valueArray = $value;
            if(count($valueArray)==0 ) {
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

        $xml = simplexml_load_string($fileContent, "SimpleXMLElement", 0,'xs',true);

        if (empty($xml)) {
            return false;
        }

        // At first simpleType handling - gathering limitations/restrictions/requirements
        $simpleTypeData = array();

        foreach($xml->simpleType as $key => $stype) {
            // simpleTye names
            $simpleTypeAttributes = (array)$stype->attributes();

            $simpleTypeName = $simpleTypeAttributes['@attributes']['name'];

            $restriction = (array)$stype->restriction;

            // typical handling here
            if(isset($restriction['maxLength'])) {
                $lengthArray = (array)$stype->restriction->maxLength->attributes();
                $length = $lengthArray['@attributes']['value'];
                $simpleTypeData[$simpleTypeName]['maxLength'] = $length;
            }
            if(isset($restriction['enumeration'])) {
                $options = array();
                foreach($stype->restriction->enumeration as $enum) {
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
    public function addElements(&$xsdElements, $elements, $supportedSimpleTypes, $simpleTypeData, $prefixHigherLevel='')
    {
        foreach ($elements as $element) {
            $attributes = $element->attributes();

            $elementName = '';
            $elementType = '';
            $minOccurs = 0;
            $maxOccurs = 1;

            foreach ($attributes as $attribute=>$simpleXMLvalue) {
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

            $isDeeperLevel = false;
            if (@property_exists($element->complexType->sequence, 'element')) {  // Starting tag Deeper level
                $isDeeperLevel = true;
            }

            // each relevant attribute has been processed.
            if (in_array($elementType,$supportedSimpleTypes) OR $isDeeperLevel) {
                $xsdElements[ $prefixHigherLevel . $elementName] = array(
                    'type' => ($isDeeperLevel ? 'openTag' : $elementType),
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array()
                );
            }

            if ($isDeeperLevel) { // Add new level and extend the prefix-identifier
                $elementSubLevel = $element->complexType->sequence->element;

                //$prefixHigherLevel = $elementName . '_'; // to be used to identify elements
                if (!$prefixHigherLevel) {
                    $prefixHigherLevel = $elementName . '_';
                }
                else {
                    $prefixHigherLevel = $prefixHigherLevel . $elementName . '_';
                }

                // dieper niveau uitvoeren op basis an de gestelde prefix.
                $this->addElements($xsdElements, $elementSubLevel, $supportedSimpleTypes, $simpleTypeData, $prefixHigherLevel);

				$prefixHigherLevel = ''; //reset it again

                // Closing tag - Deeper level
                $xsdElements[$elementName . '_close'] = array(
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

        $formData = json_decode($json,TRUE);

        $newFormData = array();
        foreach ($formData as $key=>$data) {
            if (!is_array($data)) {
                $newFormData[$key] = $data;
            }
            else {
                // First sublevel.
                // Could be an enumeration - i.e multiple instances of same element
                foreach ($data as $key2=>$data2) {
                    if (!is_array($data2)) {  // normal multisituation handling
                        if (is_numeric($key2)) {
                            $newFormData[$key][$key2] = $data2;
                        }
                        else {
                            $newFormData[$key . '_' . $key2] = $data2;
                        }
                    }
                    else {
                        if (is_numeric($key2)) { // SUBPROPERTIES: enumeration and therefore a complete set
                            $subPropertiesArray = array();

                            foreach ($data2 as $key3=>$data3) {
                                if (!is_array($data3)) {
                                    $subPropertiesArray[$key . '_' . $key3] = $data3;
                                } else {
                                    foreach ($data3 as $key4 => $data4) {
                                        $subPropertiesArray[$key . '_' . $key3 . '_' . $key4] = $data4;
                                    }
                                }
                            }
                            // Put array of subproperties under common key for later purposes
                            $newFormData[$key][] = $subPropertiesArray;
                        }
                        else {
                            foreach ($data2 as $key3=>$data3) {
                                if (!is_array($data3)) {
                                    $newFormData[$key . '_' . $key2 . '_' . $key3] = $data3;
                                } else {
                                    foreach ($data3 as $key4 => $data4) {
                                        if (is_numeric($key4)) { // is array enumeration -> add it like an array for handling purposes in the layers using this data
                                            $arTemp = array();
                                            foreach($data4 as $tag=>$tagVal) {
                                                $arTemp[$key . '_' . $key2 . '_' . $key3 . '_' . $tag] = $tagVal;
                                            }
                                            //print_r($data4);
                                            //exit;
                                            $newFormData[$key . '_' . $key2 . '_' . $key3][$key4] = $arTemp;
                                        }
                                        else {
                                            $newFormData[$key . '_' . $key2 . '_' . $key3 . '_' . $key4] = $data4;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
//
//        echo '<pre>';
//            print_r($newFormData);
//        echo '</pre>';
//        exit;

        return $newFormData;
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

        $data =  json_decode($json,TRUE);

        // Single presence of a group requires extra handling
        if (!isset($data['Group'][0])) {
            $keepGroupData = $data['Group'];
            unset($data['Group']);
            $data['Group'][0] = $keepGroupData;
        }

        return $data;
    }
}
