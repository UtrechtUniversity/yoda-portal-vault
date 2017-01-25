<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model {

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();

        $this->CI->load->model('filesystem');
    }


    public function getFormElements($rodsaccount, $config) {
        // load xsd and get all the info regarding restrictions
        $xsdElements = $this->loadXsd($rodsaccount, $config['xsd']); // based on element names

        $formData = $this->loadFormData($rodsaccount, $config['metadata']);

        $formGroupedElements = $this->loadFormElements($rodsaccount, $config['elements']);

        $presentationElements = array();

        $groupName = 'undefined';

        foreach($formGroupedElements['Group'] as $formElements) {
            foreach ($formElements as $key => $element) {
                if($key == '@attributes') {
                    $groupName = $element['name'];
                }
                else {
                    $value = isset($formData[$key]) ? $formData[$key] : '';

                    $elementOptions = array(); // holds the options
                    $elementMaxLength = 0;
                    // Determine restricitions/requirements for this

                    switch ($xsdElements[$key]['type']){
                        case 'xs:date':
                            $type = 'date';
                            break;
                        case 'stringNormal':
                            $type = 'text';
                            $elementMaxLength = $xsdElements[$key]['simpleTypeData']['maxLength'];
                            break;
                        case 'xs:integer':
                            $type = 'text';
                            $elementMaxLength = 100; // zelf verzonnen
                            break;
                        case 'xs:anyURI':
                            $type = 'text';
                            $elementMaxLength = 1024;
                            break;
                        case 'stringLong':
                            $type = 'textarea';
                            $elementMaxLength = $xsdElements[$key]['simpleTypeData']['maxLength'];
                            break;
                        case 'KindOfDataTypeType': // different option types will be a 'select' element (these are yet to be determined)
                        case 'optionsDatasetType':
                        case 'optionsDatasetAccess':
                        case 'optionsYesNo':
                        case 'optionsOther':
                            $elementOptions = $xsdElements[$key]['simpleTypeData']['options'];
                            $type = 'select';
                            break;
                    }

                    $mandatory = false;
                    if($xsdElements[$key]['minOccurs']>=1) {
                        $mandatory = true;
                    }

                    $multipleAllowed = false;
                    if($xsdElements[$key]['maxOccurs']>=1 OR strtolower($xsdElements[$key]['maxOccurs'])=='unbounded') {
                        $multipleAllowed = true;
                    }

                    //'select' has options
                    // 'edit/multiline' has length
                    // 'date' has nothing extra
                    // Handled separately as these specifics might grow.
                    $elementSpecifics = array(); // holds all element specific info
                    if($type == 'text' OR $type == 'textarea') {
                        $elementSpecifics = array('maxLength' => $elementMaxLength);
                    }
                    elseif($type == 'select'){
                        $elementSpecifics = array('options' => $elementOptions);
                    }

                    $presentationElements[$groupName][] = array(
                        'key' => $key,
                        'value' => $value,
                        'label' => $element['label'],
                        'helpText' => $element['help'],
                        'type' => $type,
                        'mandatory' => $mandatory,
                        'multipleAllowed' => $multipleAllowed,
                        'elementSpecifics' => $elementSpecifics
                    );
                }
            }
        }
        return $presentationElements;
    }

    public function loadXsd($rodsaccount, $path)
    {
       //$fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        $fileContent = '<?xml version="1.0" encoding="utf-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
	elementFormDefault="qualified">
	<xs:element name="metadata">
		<xs:complexType>
			<xs:sequence>
				<xs:element name="Project_ID" type="stringNormal" maxOccurs="1" />
				<xs:element name="Project_Title" type="stringNormal" minOccurs="1" maxOccurs="1" />
				<xs:element name="Project_Description" type="stringLong" minOccurs="1" maxOccurs="1" />
				<xs:element name="Prim_Inv" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="PI_Prim_Inv" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Project_URL" type="xs:anyURI" maxOccurs="1" />
				<xs:element name="Discipline" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Research_type" type="KindOfDataTypeType" maxOccurs="1" />
				<xs:element name="Ethical_Approval" type="optionsYesNo" maxOccurs="1" />
				<xs:element name="Approval_By" type="stringNormal" maxOccurs="1" />
				<xs:element name="Approval_added" type="optionsYesNo" maxOccurs="1" />
				<xs:element name="Name_approval_doc" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Funding_Organisation" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Fund_Description" type="stringLong" maxOccurs="unbounded" />
				<xs:element name="Fund_URL" type="xs:anyURI" maxOccurs="unbounded" />
				<xs:element name="Funder_Role" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Study_Title" type="stringNormal" maxOccurs="1" />
				<xs:element name="Study_Description" type="stringLong" maxOccurs="1" />
				<xs:element name="Study_Purpose" type="stringNormal" maxOccurs="1" />
				<xs:element name="Related_Studies" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="URL_Related_Studies" type="xs:anyURI" maxOccurs="unbounded" />
				<xs:element name="Instrument" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Instrument_Description" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Dataset_Title" type="stringNormal" minOccurs="1" maxOccurs="1" />
				<xs:element name="Dataset_Description" type="stringLong" minOccurs="1" maxOccurs="1" />
				<xs:element name="PI_Datapackage" type="stringNormal" maxOccurs="1" />
				<xs:element name="Dataset_Type" type="optionsDatasetType" minOccurs="1" maxOccurs="1" />
				<xs:element name="Version_dataset" type="xs:integer" maxOccurs="1" />
				<xs:element name="Version_Rationale" type="stringNormal" maxOccurs="1" />
				<xs:element name="Version_Responsibility" type="stringNormal" maxOccurs="1" />
				<xs:element name="Underlying_Dataset" type="stringNormal" maxOccurs="1" />
				<xs:element name="Language_dataset" type="stringNormal" maxOccurs="1" />
				<xs:element name="Owner" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Citation" type="stringNormal" maxOccurs="1" />
				<xs:element name="Data_Source" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Start_Collection_Date" type="stringNormal" maxOccurs="1" />
				<xs:element name="End_Collection_Date" type="stringNormal" maxOccurs="1" />
				<xs:element name="Collection_Method" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Collection_Method_Description" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Sample_Size" type="stringNormal" maxOccurs="1" />
				<xs:element name="Collection_Software" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Creator" type="stringNormal" maxOccurs="1" />
				<xs:element name="Contributor" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="PI_Contributor" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Unit_Analysis" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Location_Covered" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Start_Period_Dataset" type="xs:date" maxOccurs="1" />
				<xs:element name="End_Period_Dataset" type="xs:date" maxOccurs="1" />
				<xs:element name="Name_Contactperson" type="stringNormal" maxOccurs="1" />
				<xs:element name="PI_Contactperson" type="stringNormal" maxOccurs="1" />
				<xs:element name="URL_Contactperson" type="xs:anyURI" maxOccurs="1" />
				<xs:element name="Contact_Info" type="stringNormal" maxOccurs="1" />
				<xs:element name="UU_Dept" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Tag" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Codes_used" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Codes_Acronym" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="URL_Code" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Indicator" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Indicator_Acronym" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Indicator_Description" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="URL_Indicator" type="stringNormal" maxOccurs="unbounded" />
				<xs:element name="Number_of_files" type="xs:integer" maxOccurs="1" />
				<xs:element name="Retention_period" type="xs:integer" maxOccurs="1" />
				<xs:element name="License" type="stringNormal" maxOccurs="1" />
				<xs:element name="URL_License" type="xs:anyURI" maxOccurs="1" />
				<xs:element name="Publish_Dataset" type="optionsYesNo" maxOccurs="1" />
				<xs:element name="Publish_Metadata" type="optionsYesNo" maxOccurs="1" />
				<xs:element name="Archive" type="optionsYesNo" maxOccurs="1" />
				<xs:element name="Embargo" type="xs:date" maxOccurs="1" />
				<xs:element name="Anonymized" type="stringNormal" maxOccurs="1" />
				<xs:element name="Dataset_Access" type="optionsDatasetAccess" maxOccurs="1" />
				<xs:element name="Dataset_location" type="stringNormal"  maxOccurs="1" />
			</xs:sequence>
		</xs:complexType>
	</xs:element>	

	<xs:simpleType name="stringNormal">
		<xs:restriction base="xs:string">
			<xs:maxLength value="255"/>
		</xs:restriction>		
	</xs:simpleType>

	<xs:simpleType name="stringLong">
		<xs:restriction base="xs:string">
			<xs:maxLength value="2700"/>
		</xs:restriction>		
	</xs:simpleType>

	<xs:simpleType name="optionsYesNo">
		<xs:restriction base="xs:string">
			<xs:enumeration value="Yes"/>
			<xs:enumeration value="No"/>
			<xs:enumeration value="N.A."/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="optionsDatasetType">
		<xs:restriction base="xs:string">
			<xs:enumeration value="Observational Data" />
			<xs:enumeration value="Experimental Data" />
			<xs:enumeration value="Simulation Data" />
			<xs:enumeration value="Derived Data" />
			<xs:enumeration value="Reference Data" />
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="optionsDatasetAccess">
		<xs:restriction base="xs:string">
			<xs:enumeration value="Open" />
			<xs:enumeration value="Restricted to UU" />
			<xs:enumeration value="Restricted to UU dpt" />
			<xs:enumeration value="Restricted - contact I-lab" />
			<xs:enumeration value="Closed" />
		</xs:restriction>
	</xs:simpleType>

	<!-- Taken from http://www.ddialliance.org/Specification/DDI-Lifecycle/3.2/XMLSchema/FieldLevelDocumentation/schemas/reusable_xsd/simpleTypes/KindOfDataTypeType.html -->
	<xs:simpleType name="KindOfDataTypeType">
		<xs:restriction base="xs:string">
			<xs:enumeration value="Qualitative"/>
			<xs:enumeration value="Quantitative"/>
			<xs:enumeration value="Mixed"/>
		</xs:restriction>
	</xs:simpleType>

</xs:schema>
';

        $xml = simplexml_load_string($fileContent, "SimpleXMLElement", 0,'xs',true);

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

        $xsdElements = array();

        $elements = $xml->element->complexType->sequence->element;

        $supportedSimpleTypes = array_keys($simpleTypeData);
        $supportedSimpleTypes[] = 'xs:date'; // add some standard xsd simpleTypes that should be working as well
        $supportedSimpleTypes[] = 'xs:anyURI';
        $supportedSimpleTypes[] = 'xs:integer';

        foreach($elements as $element) {
            $attributes = $element->attributes();

            $elementName = '';
            $elementType = '';
            $minOccurs = 0;
            $maxOccurs = 1;

            foreach($attributes as $attribute=>$simpleXMLvalue) {
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

            // each relevant attribute has been processed.
            if(in_array($elementType,$supportedSimpleTypes)) {
                $xsdElements[$elementName] = array(
                    'type' => $elementType,
                    'minOccurs' => $minOccurs,
                    'maxOccurs' => $maxOccurs,
                    'simpleTypeData' => isset($simpleTypeData[$elementType]) ? $simpleTypeData[$elementType] : array()
                );
            }
        }

        return $xsdElements;
    }

    public function loadFormData($rodsaccount, $path)
    {
        //$fileContent = $this->CI->filesystem->read($rodsaccount, $path);
        $fileContent = '<?xml version="1.0"?>
<metadata>
		<Project_ID>ILAB1</Project_ID>
                <Project_Title>I-lab</Project_Title>
		<Project_Description>Implementation of i-lab</Project_Description>
		<Prim_Inv>Ton Smeele</Prim_Inv>
		<PI_Prim_Inv>PID1</PI_Prim_Inv>
		<Project_URL>http://i-lab.yoda.uu.nl</Project_URL>
		<Discipline>Research IT</Discipline>
		<Research_type>Qualitative</Research_type>
		<Ethical_Approval>No</Ethical_Approval>
		<Approval_By>N.A.</Approval_By>
		<Approval_added>No</Approval_added>
		<Name_approval_doc>N.A.</Name_approval_doc>
		<Funding_Organisation>institutions</Funding_Organisation>
		<Fund_Description></Fund_Description>
		<Fund_URL>http://fund.me</Fund_URL>
		<Funder_Role>Important</Funder_Role>
		<Study_Title>Study of metadata</Study_Title>
		<Study_Description>Studying metadata in context </Study_Description>
		<Study_Purpose></Study_Purpose>
		<Related_Studies></Related_Studies>
		<URL_Related_Studies></URL_Related_Studies>
		<Instrument></Instrument>
		<Instrument_Description></Instrument_Description>
		<Dataset_Title></Dataset_Title>
		<Dataset_Description></Dataset_Description>
		<PI_Datapackage></PI_Datapackage>
		<Dataset_Type></Dataset_Type>
		<Version_dataset>1</Version_dataset>
		<Version_Rationale></Version_Rationale>
		<Version_Responsibility></Version_Responsibility>
		<Underlying_Dataset></Underlying_Dataset>
		<Language_dataset></Language_dataset>
		<Owner></Owner>
		<Citation></Citation>
		<Data_Source></Data_Source>
		<Start_Collection_Date>2017-01-24</Start_Collection_Date>
		<End_Collection_Date>2017-03-01</End_Collection_Date>
		<Collection_Method></Collection_Method>
		<Collection_Method_Description></Collection_Method_Description>
		<Sample_Size></Sample_Size>
		<Collection_Software></Collection_Software>
		<Creator></Creator>
		<Contributor></Contributor>
		<PI_Contributor></PI_Contributor>
		<Unit_Analysis></Unit_Analysis>
		<Location_Covered></Location_Covered>
		<Start_Period_Dataset>2017-01-01</Start_Period_Dataset>
		<End_Period_Dataset>2017-12-31</End_Period_Dataset>
		<Name_Contactperson></Name_Contactperson>
		<PI_Contactperson></PI_Contactperson>
		<URL_Contactperson></URL_Contactperson>
		<Contact_Info></Contact_Info>
		<UU_Dept></UU_Dept>
		<Tag></Tag>
		<Codes_used></Codes_used>
		<Codes_Acronym></Codes_Acronym>
		<URL_Code></URL_Code>
		<Indicator></Indicator>
		<Indicator_Acronym></Indicator_Acronym>
		<Indicator_Description></Indicator_Description>
		<URL_Indicator></URL_Indicator>
		<Number_of_files>12</Number_of_files>
		<Retention_period>100</Retention_period>
		<License>GPL</License>
		<URL_License>http://www.gnu.org/gpl</URL_License>
		<Publish_Dataset>Yes</Publish_Dataset>
		<Publish_Metadata>No</Publish_Metadata>
		<Archive>No</Archive>
		<Embargo>2017-12-31</Embargo>
		<Anonymized></Anonymized>
		<Dataset_Access>Restricted - contact I-lab</Dataset_Access>
		<Dataset_location></Dataset_location>

</metadata>
';

        $xmlData = simplexml_load_string($fileContent);

        $json = json_encode($xmlData);

        return json_decode($json,TRUE);
    }

    public function loadFormElements($rodsaccount, $path)
    {
        //$fileContent = $this->CI->filesystem->read($rodsaccount, $path);

        $fileContent = '<?xml version="1.0" encoding="utf-8"?>
<formelements>
	<Group name="Project information">
		<Project_ID>
			<label>Project ID</label>
			<help>The ID of the project as provided by the SAP system, i.e. the WSB-element of the financial system (SAP)</help>
		</Project_ID>
		<Project_Title>
			<label>Project Title</label>
			<help>The title of your Research Project</help>
		</Project_Title>
		<Project_Description>
			<label>Project Description</label>
			<help>The description of your Research Project</help>
		</Project_Description>
		<Prim_Inv>
			<label>(Optional) Name Principal Investigator(s)</label>
			<help>The title of your Research Project</help>
		</Prim_Inv>
		<PI_Prim_Inv>
			<label>Persistent Identifier PI (optional)</label>
			<help>Persitent Identifier PI (e.g. an ORCID, DAI, of ScopusID)</help>
		</PI_Prim_Inv>
		<Project_URL>
			<label>Link Project Website</label>
			<help>(Optional) Link to project website</help>
		</Project_URL>
		<Discipline>
			<label>(Sub) Discipline of research</label>
			<help>(Optional) Categorize your research to a (sub) discipline</help>
		</Discipline>
		<Research_type>
			<label>Research type</label>
			<help>(Optional) Categorize your Research Type - e.g. survey study, longitudal panel  study, archival study etc.</help>
		</Research_type>
	</Group>
	<Group name="Project approval information">
		<Ethical_Approval>
			<label>Ethical Approval</label>
			<help>Has there been ethical approval for this research project?</help>
		</Ethical_Approval>
		<Approval_By>
			<label>Approval By</label>
			<help>(Optional) Approving authority, e.g. name of commission</help>
		</Approval_By>
		<Approval_added>
			<label>Approval Added</label>
			<help>Has an approval document been added to the datapackage?</help>
		</Approval_added>
		<Name_approval_doc>
			<label>Name approval document</label>
			<help>(Optional) What is the name of the added document containing the approval</help>
		</Name_approval_doc>
		<Approval_Date>
			<label>Ethical Approval date</label>
			<help>When was ethical approval given for this research project?</help>
		</Approval_Date>
	</Group>
	<Group name="Project funding information">
		<Funding_Organisation>
			<label>Fundinding Organisation(s)</label>
			<help>(Optional) The name of the  organisation funding your Research Project, e.g. NWO or H2020</help>
		</Funding_Organisation>
		<Fund_Description>
			<label>Fund Description</label>
			<help>(Optional) A description of the organization funding your Research Project</help>
		</Fund_Description>
		<Fund_URL>
			<label>Link website fund</label>
			<help>(Optional) A link to the website of the organization funding your Research Project</help>
		</Fund_URL>
		<Funder_Role>
			<label>Role Funder</label>
			<help>(Optional) A descrption of the role of the funder, other than an financial, e.g. in offering controlled vocabularies or demands with regards to publicizing data</help>
		</Funder_Role>
	</Group>
	<Group name="Study information">
		<Study_Title>
			<label>Study Title</label>
			<help>The title of your study</help>
		</Study_Title>

		<Study_Description>
			<label>Study Description</label>
			<help>The description of your study</help>
		</Study_Description>

		<Study_Purpose>
			<label>Study Purpose</label>
			<help>(Optional) Describe the purpose of the study</help>
		</Study_Purpose>

		<Related_Studies>
			<label>Related Studies</label>
			<help>(Optional) Studies related to your study - outside the I-lab</help>
		</Related_Studies>

		<URL_Related_Studies>
			<label>Link Related Studies</label>
			<help>(Optional) A link to related studies, e.g. a project website</help>
		</URL_Related_Studies>

		<Instrument>
			<label>Instrument(s)</label>
			<help>(Optional) Instrument used for data collection or capture</help>
		</Instrument>

		<Instrument_Description>
			<label>Instrument Description</label>
			<help>(Optional) Describe how the instrunent have been used, e.g. settings</help>
		</Instrument_Description>
	</Group>
	<Group name="Dataset information">
		<Dataset_Title>
			<label>Dataset Title</label>
			<help>The title of your dataset</help>
		</Dataset_Title>

		<Dataset_Description>
			<label>Dataset_Description</label>
			<help>A description of your dataset</help>
		</Dataset_Description>

		<PI_Datapackage>
			<label>Persitent Identifier Datapackage</label>
			<help>Persistent Identifier Datapackage; will be added automatically after archiving the datapackage</help>
		</PI_Datapackage>

		<Dataset_Type>
			<label>Dataset Type</label>
			<help>What is the type of data in your dataset?</help>
		</Dataset_Type>
	</Group>
	<Group name="Dataset versioning">
		<Version_dataset>
			<label>Version</label>
			<help>An autogenerated version number for the dataset</help>
		</Version_dataset>

		<Version_Rationale>
			<label>Reason for Version</label>
			<help>(Optional) Reason for this new dataset</help>
		</Version_Rationale>

		<Version_Responsibility>
			<label>Person responsible for version</label>
			<help>(Optional) The name of the (system) user responsible for this version</help>
		</Version_Responsibility>
	</Group>

	<Group name="Provenance of data">
		<Underlying_Dataset>
			<label>Predecessing Dataset</label>
			<help>Autogenerated field; is filled when a new version of a dataset is created</help>
		</Underlying_Dataset>

		<Language_dataset>
			<label>Language of data</label>
			<help>The language the dataset is in</help>
		</Language_dataset>

		<Owner>
			<label>Owner Data</label>
			<help>The person or organisation owning the data</help>
		</Owner>

		<Citation>
			<label>Citation</label>
			<help>(Optional) How should this dataset be referred to?</help>
		</Citation>

		<Data_Source>
			<label>Data Source</label>
			<help>Datasource(s) used for creating the dataset</help>
		</Data_Source>

		<Start_Collection_Date>
			<label>Start Date Collection Process</label>
			<help>Indicate when you\'ve started collecting the data for this dataset</help>
		</Start_Collection_Date>

		<End_Collection_Date>
			<label>End Date Collection Process</label>
			<help>Indicate when you\'ve finished collecting the data for this dataset</help>
		</End_Collection_Date>

		<Collection_Method>
			<label>Collection Method</label>
			<help>The method used for collecting data</help>
		</Collection_Method>

		<Collection_Method_Description>
			<label>Description Collection Method</label>
			<help>A description of the method of collecting data for this dataset</help>
		</Collection_Method_Description>

		<Sample_Size>
			<label>Sample Size</label>
			<help>Size of the sample used in the data collection</help>
		</Sample_Size>

		<Collection_Software>
			<label>Software used</label>
			<help>Software used for collecting data</help>
		</Collection_Software>

		<Creator>
			<label>Creator of Dataset</label>
			<help>The name of the person who created (version of) the dataset</help>
		</Creator>

		<Contributor>
			<label>Contributor(s) to Dataset</label>
			<help>(Optional) The name(s) of the persons who have contributed to this dataset</help>
		</Contributor>

		<PI_Contributor>
			<label>Persistent Identifier Contributor</label>
			<help>(Optional) Persitent Identifier contribitor (e.g. an ORCID, DAI, of ScopusID)</help>
		</PI_Contributor>

		<Unit_Analysis>
			<label>Unit_of Analysis</label>
			<help>(Optional) Unit of analysis of the dataset</help>
		</Unit_Analysis>

		<Location_Covered>
			<label>Location(s) covered</label>
			<help>Indication of the geographical entities, like countries, regions and cities, covered with this dataset</help>
		</Location_Covered>

		<Start_Period_Dataset>
			<label>Start period</label>
			<help>An indication of the start date of the period covered by your dataset (format: YYYY-MM-DD)</help>
		</Start_Period_Dataset>

		<End_Period_Dataset>
			<label>End period</label>
			<help>An indication of the end date of the period covered by your dataset (format: YYYY-MM-DD)</help>
		</End_Period_Dataset>
	</Group>	
	<Group name="Contact information">
		<Name_Contactperson>
			<label>Contact</label>
			<help>Name of person or organization which can provide information on the dataset</help>
		</Name_Contactperson>

		<PI_Contactperson>
			<label>Persistent Identifier Contact</label>
			<help>(Optional) Persitent Identifier Contactperson (e.g. an ORCID, DAI, of ScopusID)</help>
		</PI_Contactperson>

		<URL_Contactperson>
			<label>Link webpage contact</label>
			<help>(Optional) Link to profile page contact person</help>
		</URL_Contactperson>

		<Contact_Info>
			<label>Contact Info</label>
			<help>Contact information of person or organization which can provide information on the dataset, preferably an email address</help>
		</Contact_Info>

		<UU_Dept>
			<label>UU Organisational Unit</label>
			<help>In the context of which organizational unit of the UU is the dataset created, e.g. your research group or departement</help>
		</UU_Dept>
	</Group>

	<Group name="Tags">
		<Tag>
			<label>Tag(s)</label>
			<help>(Optional) Free text field for adding (searchable) key words to your dataset</help>
		</Tag>
	</Group>

	<Group name="Information on reusability">
		<Codes_used>
			<label>Codes used</label>
			<help>(Optional) Does the dataset contain any (international) codes, e.g. SBI-codes of the CBS, ISO country codes etc.</help>
		</Codes_used>

		<Codes_Acronym>
			<label>Codes Acronym</label>
			<help>(Optional) The acronym under which the code(s) is known</help>
		</Codes_Acronym>

		<URL_Code>
			<label>Link webpage Code</label>
			<help>(Optional) A link to a webpage describing the coding system(s)</help>
		</URL_Code>

		<Indicator>
			<label>Indicator</label>
			<help>(Optional) Indicators (variable) used in the dataset. This can be generally acclaimed indicators, e.g. poverty level, or some of the most important variables in your dataset.</help>
		</Indicator>

		<Indicator_Acronym>
			<label>Indicator Acronym</label>
			<help>(Optional) The acronym used for the indicator(s)</help>
		</Indicator_Acronym>

		<Indicator_Description>
			<label>Indicator Description</label>
			<help>(Optional) Description of the indicator(s) used</help>
		</Indicator_Description>

		<URL_Indicator>
			<label>Link webpage Indicator</label>
			<help>(Optional) A link to a webpage describing the Indicator)s</help>
		</URL_Indicator>

		<Number_of_files>
			<label>No. Of Files</label>
			<help>The number of files of the datapackage. The field will be automatically set.</help>
		</Number_of_files>

	</Group>

	<Group name="Datamanagement information">
		<Retention_period>
			<label>Retention Period</label>
			<help>Number of years the data must be kept in the archive</help>
		</Retention_period>
		<License>
			<label>License</label>
			<help>The license under which you offer the datapackage for use by third parties.</help>
		</License>
		<URL_License>
			<label>Link Webpage License</label>
			<help>A link to a webpage describing the license and its conditions</help>
		</URL_License>
		<Publish_Dataset>
			<label>Publish Dataset?</label>
			<help>Do you want to make the dataset available once archived?</help>
		</Publish_Dataset>
		<Publish_Metadata>
			<label>Publish Metadata?</label>
			<help>Do you want to make the metadata available in the Data Catalogue?</help>
		</Publish_Metadata>
		<Archive>
			<label>Archive now?</label>
			<help>Do you want to send the dataset to the Archive?</help>
		</Archive>
		<Embargo>
			<label>Embargo?</label>
			<help>If there is an embargo on the Dataset, until what date?</help>
		</Embargo>
		<Anonymized>
			<label>Anonymized?</label>
			<help>Is your dataset anonymized or pseudo-coded?</help>
		</Anonymized>
		<Dataset_Access>
			<label>Dataset access</label>
			<help>Once archived, how is your dataset accessible to third parties?</help>
		</Dataset_Access>
		<Dataset_location>
			<label>Archival Status</label>
			<help>Where is the datapackage stored?</help>
		</Dataset_location>
	</Group>

</formelements>
';
        $xmlFormElements = simplexml_load_string($fileContent);

        $json = json_encode($xmlFormElements);

        return json_decode($json,TRUE);
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