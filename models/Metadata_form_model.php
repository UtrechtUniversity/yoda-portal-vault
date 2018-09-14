<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Metadata_form_model extends CI_Model
{

    var $CI = NULL;

    function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();

        $this->CI->load->model('filesystem');
    }


    private function _createXmlElementWithText($xml, $elementName, $text)
    {
        $xmlElement = $xml->createElement($elementName);
        $xmlElement->appendChild($xml->createTextNode($text));

        return $xmlElement;
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
        $arrayPost = $this->CI->input->post();
        $formReceivedData = json_decode($arrayPost['formData'], true);

        // formData now contains info of descriptive groups.
        // These must be excluded first for ease of use within code
        $formData = array();
        foreach($formReceivedData as $group=>$realFormData) {
            #first level to be skipped as is descriptive
            foreach($realFormData as $key => $val  ) {
                $formData[$key] = $val;
            }
        }

        # $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
        $xsdPath = $config['xsdPath'];
        $jsonsElements = $this->loadJSONS($rodsaccount, $xsdPath);


        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;

        $xml_metadata = $xml->createElement("metadata");

        foreach ($jsonsElements['properties'] as $groupName => $formElements) {
            foreach ($formElements['properties'] as $mainElement => $element) {
                if (isset($formData[$mainElement])) {
                    if (!isset($element['type'])
                        || $element['type']=='integer') {  //No structure single element
                        $xmlMainElement = $this->_createXmlElementWithText($xml, $mainElement, $formData[$mainElement]);
                        $xml_metadata->appendChild($xmlMainElement);
                    }
                    else {
                        $structObject = array();

                        if ($element['type'] == 'object') {   // SINGLGE STRUCT OP FIRST LEVEL
                            $structObject = $element;

                            if ($structObject['yoda:structure'] == 'compound') { // heeft altijd een compound signifying element nodig
                                $xmlMainElement = $xml->createElement($mainElement);
                                $anyValueFound = false;
                                foreach ($structObject['properties'] as $compoundElementKey => $compoundElementInfo) {
                                    $compoundValue = '';
                                    if (isset($formData[$mainElement][$compoundElementKey])) {
                                        $compoundValue = $formData[$mainElement][$compoundElementKey];
                                        $anyValueFound = true;
                                    }
//                                    $xmlCompoundElement = $xml->createElement($compoundElementKey);
//                                    $xmlCompoundElement->appendChild($xml->createTextNode($compoundValue));
                                    $xmlCompoundElement = $this->_createXmlElementWithText($xml, $compoundElementKey, $compoundValue);
                                    $xmlMainElement->appendChild($xmlCompoundElement);
                                }
                                if ($anyValueFound) {
                                    $xml_metadata->appendChild($xmlMainElement);
                                }
                            }
                            elseif($structObject['yoda:structure'] == 'subproperties'){  // SINGLE subproperty struct is not present at the moment in the schema

                            }
                        }
                        // MULTIPLE
                        elseif ($element['type'] == 'array') {
                            if (!(isset($element['items']['type']) and $element['items']['type'] == 'object')) {
                                // multiple non structured element
                                // So loop through data now
                                foreach($formData[$mainElement] as $value) {
                                    if ($value) {
//                                        $xmlMainElement = $xml->createElement($mainElement);
//                                        $xmlMainElement->appendChild($xml->createTextNode($value));
                                        $xmlMainElement = $this->_createXmlElementWithText($xml, $mainElement, $value);
                                        $xml_metadata->appendChild($xmlMainElement);
                                    }
                                }
                            }
                            // multiple structures
                            else {
                                $structObject = $element['items'];
                                if ($structObject['yoda:structure'] == 'subproperties') {
                                    $hasLeadValue = false;
                                    foreach ($formData[$mainElement] as $subPropertyStructData) {
                                        $xmlMainElement = $xml->createElement($mainElement);
                                        $index = 0; // to distinguish between lead and sub
                                        foreach ($structObject['properties'] as $subPropertyElementKey => $subPropertyElementInfo) {

                                            // Step through object structure
                                            if ($index==0) { // Lead part of structure - ALWAYS SINGLE VALUE!!
                                                $leadData = isset($subPropertyStructData[$subPropertyElementKey])? $subPropertyStructData[$subPropertyElementKey] : '';
                                                //$xmlLeadElement = $xml->createElement($subPropertyElementKey);
                                                //$xmlLeadElement->appendChild($xml->createTextNode($leadData));  // @TODO - get correct lead value

                                                $xmlLeadElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $leadData);

                                                $xmlMainElement->appendChild($xmlLeadElement);

                                                if (strlen($leadData)) {
                                                    $hasLeadValue = true;
                                                }
                                            }
                                            elseif($index==1) { // Start of subproperty part. Create subproperty structure element here.

                                                $xmlProperties = $xml->createElement('Properties');

                                                // Subproperty part of structure --
                                                // This is the first line
                                                // NEVER compound on first subprop line so take shortcut here.

                                                $values = array();
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    $values[0] = isset($subPropertyStructData[$subPropertyElementKey])? $subPropertyStructData[$subPropertyElementKey] : '';
                                                }
                                                else if ($subPropertyElementInfo['type']=='array') {
                                                    $values = $subPropertyStructData[$subPropertyElementKey];
                                                }

                                                foreach($values as $value) {
                                                   // $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                    // $xmlSubElement->appendChild($xml->createTextNode($value));  // @TODO - get correct lead value
                                                    $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);
                                                    $xmlProperties->appendChild($xmlSubElement);
                                                }
                                            }
                                            else {  // next lines after first in subproperty part
                                                if (!isset($subPropertyElementInfo['type'])) {
                                                    echo 'SINGLE ITEM';  ///KOMT  NU NIET VOOR VOOR SUBPROPERTIES
                                                }
                                                elseif ($subPropertyElementInfo['type']=='array') {
                                                    if (!isset($subPropertyElementInfo['items']['type'])) {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $value) {
                                                            //$xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                            //$xmlSubElement->appendChild($xml->createTextNode($value));

                                                            $xmlSubElement = $this->_createXmlElementWithText($xml, $subPropertyElementKey, $value);

                                                            $xmlProperties->appendChild($xmlSubElement);
                                                        }
                                                    }
                                                    else {
                                                        foreach($subPropertyStructData[$subPropertyElementKey] as $data) {
                                                            $xmlSubElement = $xml->createElement($subPropertyElementKey);
                                                            foreach ($subPropertyElementInfo['items']['properties'] as $subCompoundKey => $subVal) {
                                                                $subData = isset($data[$subCompoundKey]) ? $data[$subCompoundKey] : '';

//                                                                $xmlSubCompound = $xml->createElement($subCompoundKey);
//                                                                $xmlSubCompound->appendChild($xml->createTextNode($subData));

                                                                $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);
                                                                $xmlSubElement->appendChild($xmlSubCompound);
                                                            }

                                                            $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap
                                                        }
                                                    }
                                                }
                                                elseif ($subPropertyElementInfo['yoda:structure']=='compound'){
                                                    $xmlSubElement = $xml->createElement($subPropertyElementKey);

                                                    foreach($subPropertyElementInfo['properties'] as $subCompoundKey => $subVal) {
                                                        $subData = isset($subPropertyStructData[$subPropertyElementKey][$subCompoundKey])?
                                                                            $subPropertyStructData[$subPropertyElementKey][$subCompoundKey] : '';

//                                                        $xmlSubCompound = $xml->createElement($subCompoundKey);
//                                                        $xmlSubCompound->appendChild($xml->createTextNode($subData));

                                                        $xmlSubCompound = $this->_createXmlElementWithText($xml, $subCompoundKey, $subData);

                                                        $xmlSubElement->appendChild($xmlSubCompound);
                                                    }

                                                    $xmlProperties->appendChild($xmlSubElement);  // xmlProperties wordt geinitieerd in vorige stap
                                                }
                                            }

                                            $index++;
                                        }

                                        // Extra intelligence to only save when there is relevant data
                                        if ($hasLeadValue) {
                                            // add the entire structure to the main element
                                            $xmlMainElement->appendChild($xmlProperties);
                                            $xml_metadata->appendChild($xmlMainElement);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $xml->appendChild($xml_metadata);

        $xmlString = $xml->saveXML();

        print_r($xmlString);

        $this->CI->filesystem->writeXml($rodsaccount, $config['metadataXmlPath'], $xmlString);
    }

    public function loadJSONS($rodsaccount, $xsdPath)
    {
//        $result = $this->CI->filesystem->getJsonSchema($rodsaccount, $xsdPath);
//
//        if ($result['*status'] == 'SUCCESS') {
//            return $result['*result'];
//        }
//        else {
//            return '';
//        }
//

        //-----------------------------------------------------------------------------------------------------------------------
        // with Related Datapackage - multiple (doesn't work)
        $jsonSchema = <<<'JSON'
{
	"definitions": {
		"stringURI": {
			"type": "uri",
			"maxLength": 1024
		},
		"stringNormal": {
			"type": "string",
			"maxLength": 255
		},
		"stringLong": {
			"type": "string",
			"maxLength": 2700
		},
		"optionsYesNo": {
			"type": "string",
			"enum": ["Yes", "No", "N.A."]
		},
		"optionsDataAccessRestriction": {
			"type": "string",
			"enum": [
				"Open - freely retrievable",
				"Restricted - available upon request",
				"Closed"
			]
		},
		"optionsDataClassification": {
			"type": "string",
			"enum": [
				"Public",
				"Basic",
				"Sensitive",
				"Critical"
			]
		},
		"optionsNameIdentifierScheme": {
			"type": "string",
			"enum": [
				"ORCID",
				"DAI",
				"Author identifier (Scopus)",
				"ResearcherID (Web of Science)",
				"ISNI"
			]
		},
		"optionsPersistentIdentifierScheme": {
			"type": "string",
			"enum": [
				"ARK",
				"arXiv",
				"bibcode",
				"DOI",
				"EAN13",
				"EISSN",
				"Handle",
				"ISBN",
				"ISSN",
				"ISTC",
				"LISSN",
				"LSID",
				"PMID",
				"PURL",
				"UPC",
				"URL",
				"URN"
			]
		},
		"optionsDiscipline": {
			"type": "string",
			"enum": [
				"Natural Sciences - Mathematics (1.1)",
				"Natural Sciences - Computer and information sciences (1.2)",
				"Natural Sciences - Physical sciences (1.3)",
				"Natural Sciences - Chemical sciences (1.4)",
				"Natural Sciences - Earth and related environmental sciences (1.5)",
				"Natural Sciences - Biological sciences (1.6)",
				"Natural Sciences - Other natural sciences (1.7)",
				"Engineering and Technology - Civil engineering (2.1)",
				"Engineering and Technology - Electrical engineering, electronic engineering, information engineering (2.2)",
				"Engineering and Technology - Mechanical engineering (2.3)",
				"Engineering and Technology - Chemical engineering (2.4)",
				"Engineering and Technology - Materials engineering (2.5)",
				"Engineering and Technology - Medical engineering (2.6)",
				"Engineering and Technology - Environmental engineering (2.7)",
				"Engineering and Technology - Environmental biotechnology (2.8)",
				"Engineering and Technology - Industrial Biotechnology (2.9)",
				"Engineering and Technology - Nano-technology (2.10)",
				"Engineering and Technology - Other engineering and technologies (2.11)",
				"Medical and Health Sciences - Basic medicine (3.1)",
				"Medical and Health Sciences - Clinical medicine (3.2)",
				"Medical and Health Sciences - Health sciences (3.3)",
				"Medical and Health Sciences - Health biotechnology (3.4)",
				"Medical and Health Sciences - Other medical sciences (3.5)",
				"Agricultural Sciences - Agriculture, forestry, and fisheries (4.1)",
				"Agricultural Sciences - Animal and dairy science (4.2)",
				"Agricultural Sciences - Veterinary science (4.3)",
				"Agricultural Sciences - Agricultural biotechnology (4.4)",
				"Agricultural Sciences - Other agricultural sciences (4.5)",
				"Social Sciences - Psychology (5.1)",
				"Social Sciences - Economics and business (5.2)",
				"Social Sciences - Educational sciences (5.3)",
				"Social Sciences - Sociology (5.4)",
				"Social Sciences - Law (5.5)",
				"Social Sciences - Political Science (5.6)",
				"Social Sciences - Social and economic geography (5.7)",
				"Social Sciences - Media and communications (5.8)",
				"Social Sciences - Other social sciences (5.9)",
				"Humanities - History and archaeology (6.1)",
				"Humanities - Languages and literature (6.2)",
				"Humanities - Philosophy, ethics and religion (6.3)",
				"Humanities - Art (arts, history of arts, performing arts, music) (6.4)",
				"Humanities - Other humanities (6.5)"
			]
		},
		"optionsContributorType": {
			"type": "string",
			"enum": [
				"ContactPerson",
				"DataCollector",
				"DataCurator",
				"DataManager",
				"Distributor",
				"Editor",
				"HostingInstitution",
				"Producer",
				"ProjectLeader",
				"ProjectManager",
				"ProjectMember",
				"RegistrationAgency",
				"RegistrationAuthority",
				"RelatedPerson",
				"Researcher",
				"ResearchGroup",
				"RightsHolder",
				"Sponsor",
				"Supervisor",
				"WorkPackageLeader"
			]
		},
		"optionsLicense": {
			"type": "string",
			"enum": [
				"Creative Commons Attribution 4.0 International Public License",
				"Creative Commons Attribution-ShareAlike 4.0 International Public License",
				"Open Data Commons Attribution License (ODC-By) v1.0",
				"Custom"
			]
		},
		"optionsRelationType": {
			"type": "string",
			"enum": [
				"IsSupplementTo: Current datapackage is supplement to",
				"IsSupplementedBy: Current datapackage is supplemented by",
				"IsContinuedBy: Current datadatapackage is continued by",
				"Continues: Continues this current dataset",
				"IsNewVersionOf: Current datapackage is new version of",
				"IsPreviousVersionOf: Current datapackage is previous version of",
				"IsPartOf: Current datapackage is part of",
				"HasPart: Is part of current datapackage",
				"IsReferencedBy: Current datapackage is referenced by",
				"References: Current datapackages references",
				"IsVariantFormOf: Current datapackage is variant of",
				"IsOriginalFormOf: Current datapackage is original of",
				"IsSourceOf: Raw data for this current datapackage"
			]
		},
		"optionsISO639-1": {
			"type": "string",
			"enum": [
				"ab - Abkhazian",
				"aa - Afar",
				"af - Afrikaans",
				"ak - Akan",
				"sq - Albanian",
				"am - Amharic",
				"ar - Arabic",
				"an - Aragonese",
				"hy - Armenian",
				"as - Assamese",
				"av - Avaric",
				"ae - Avestan",
				"ay - Aymara",
				"az - Azerbaijani",
				"bm - Bambara",
				"ba - Bashkir",
				"eu - Basque",
				"be - Belarusian",
				"bn - Bengali",
				"bh - Bihari languages",
				"bi - Bislama",
				"nb - Bokmål, Norwegian",
				"bs - Bosnian",
				"br - Breton",
				"bg - Bulgarian",
				"my - Burmese",
				"es - Castilian",
				"ca - Catalan",
				"km - Central Khmer",
				"ch - Chamorro",
				"ce - Chechen",
				"ny - Chewa",
				"ny - Chichewa",
				"zh - Chinese",
				"za - Chuang",
				"cu - Church Slavic",
				"cv - Chuvash",
				"kw - Cornish",
				"co - Corsican",
				"cr - Cree",
				"hr - Croatian",
				"cs - Czech",
				"da - Danish",
				"dv - Dhivehi",
				"dv - Divehi",
				"nl - Dutch",
				"dz - Dzongkha",
				"en - English",
				"eo - Esperanto",
				"et - Estonian",
				"ee - Ewe",
				"fo - Faroese",
				"fj - Fijian",
				"fi - Finnish",
				"nl - Flemish",
				"fr - French",
				"ff - Fulah",
				"gd - Gaelic",
				"gl - Galician",
				"lg - Ganda",
				"ka - Georgian",
				"de - German",
				"ki - Gikuyu",
				"el - Greek, Modern (1453-)",
				"kl - Greenlandic",
				"gn - Guarani",
				"gu - Gujarati",
				"ht - Haitian",
				"ht - Haitian Creole",
				"ha - Hausa",
				"he - Hebrew",
				"hz - Herero",
				"hi - Hindi",
				"ho - Hiri Motu",
				"hu - Hungarian",
				"is - Icelandic",
				"io - Ido",
				"ig - Igbo",
				"id - Indonesian",
				"ia - Interlingua (International Auxiliary Language Association)",
				"ie - Interlingue",
				"iu - Inuktitut",
				"ik - Inupiaq",
				"ga - Irish",
				"it - Italian",
				"ja - Japanese",
				"jv - Javanese",
				"kl - Kalaallisut",
				"kn - Kannada",
				"kr - Kanuri",
				"ks - Kashmiri",
				"kk - Kazakh",
				"ki - Kikuyu",
				"rw - Kinyarwanda",
				"ky - Kirghiz",
				"kv - Komi",
				"kg - Kongo",
				"ko - Korean",
				"kj - Kuanyama",
				"ku - Kurdish",
				"kj - Kwanyama",
				"ky - Kyrgyz",
				"lo - Lao",
				"la - Latin",
				"lv - Latvian",
				"lb - Letzeburgesch",
				"li - Limburgan",
				"li - Limburger",
				"li - Limburgish",
				"ln - Lingala",
				"lt - Lithuanian",
				"lu - Luba-Katanga",
				"lb - Luxembourgish",
				"mk - Macedonian",
				"mg - Malagasy",
				"ms - Malay",
				"ml - Malayalam",
				"dv - Maldivian",
				"mt - Maltese",
				"gv - Manx",
				"mi - Maori",
				"mr - Marathi",
				"mh - Marshallese",
				"ro - Moldavian",
				"ro - Moldovan",
				"mn - Mongolian",
				"na - Nauru",
				"nv - Navaho",
				"nv - Navajo",
				"nd - Ndebele, North",
				"nr - Ndebele, South",
				"ng - Ndonga",
				"ne - Nepali",
				"nd - North Ndebele",
				"se - Northern Sami",
				"no - Norwegian",
				"nb - Norwegian Bokmål",
				"nn - Norwegian Nynorsk",
				"ii - Nuosu",
				"ny - Nyanja",
				"nn - Nynorsk, Norwegian",
				"ie - Occidental",
				"oc - Occitan (post 1500)",
				"oj - Ojibwa",
				"or - Oriya",
				"om - Oromo",
				"os - Ossetian",
				"os - Ossetic",
				"pi - Pali",
				"pa - Panjabi",
				"ps - Pashto",
				"fa - Persian",
				"pl - Polish",
				"pt - Portuguese",
				"pa - Punjabi",
				"ps - Pushto",
				"qu - Quechua",
				"ro - Romanian",
				"rm - Romansh",
				"rn - Rundi",
				"ru - Russian",
				"sm - Samoan",
				"sg - Sango",
				"sa - Sanskrit",
				"sc - Sardinian",
				"gd - Scottish Gaelic",
				"sr - Serbian",
				"sn - Shona",
				"ii - Sichuan Yi",
				"sd - Sindhi",
				"si - Sinhala",
				"si - Sinhalese",
				"sk - Slovak",
				"sl - Slovenian",
				"so - Somali",
				"st - Sotho, Southern",
				"nr - South Ndebele",
				"es - Spanish",
				"su - Sundanese",
				"sw - Swahili",
				"ss - Swati",
				"sv - Swedish",
				"tl - Tagalog",
				"ty - Tahitian",
				"tg - Tajik",
				"ta - Tamil",
				"tt - Tatar",
				"te - Telugu",
				"th - Thai",
				"bo - Tibetan",
				"ti - Tigrinya",
				"to - Tonga (Tonga Islands)",
				"ts - Tsonga",
				"tn - Tswana",
				"tr - Turkish",
				"tk - Turkmen",
				"tw - Twi",
				"ug - Uighur",
				"uk - Ukrainian",
				"ur - Urdu",
				"ug - Uyghur",
				"uz - Uzbek",
				"ca - Valencian",
				"ve - Venda",
				"vi - Vietnamese",
				"vo - Volapük",
				"wa - Walloon",
				"cy - Welsh",
				"fy - Western Frisian",
				"wo - Wolof",
				"xh - Xhosa",
				"yi - Yiddish",
				"yo - Yoruba",
				"za - Zhuang",
				"zu - Zulu"
			]
		}
	},

	"title": "",
	"type": "object",
	"properties": {
		"Descriptive-group": {
			"type": "object",
			"title": "Descriptive",
			"required": [
				"Title",
				"Description",
				"Version"
			],
			"properties": {
				"Title": {
					"$ref": "#/definitions/stringNormal",
					"title": "Title"
				},
				"Description": {
					"$ref": "#/definitions/stringLong",
					"title": "Description"
				},
				"Discipline": {
					"type": "array",
					"items": {
						"$ref": "#/definitions/optionsDiscipline",
						"title": "Discipline"
					}
				},
				"Version": {
					"$ref": "#/definitions/stringNormal",
					"title": "Version"
				},
				"Language": {
					"$ref": "#/definitions/optionsISO639-1",
					"title": "Language of the data"
				},
				"Collected": {
					"type": "object",
					"title": "Collection process",
					"yoda:structure": "compound",
					"properties": {
						"Start_Date": {
							"type": "string",
							"format": "date",
							"title": "Start date"
						},
						"End_Date": {
							"type": "string",
							"format": "date",
							"title": "End date"
						}
					}
				},
				"Covered_Geolocation_Place": {
					"type": "array",
					"items": {
						"$ref": "#/definitions/stringNormal",
						"title": "Location(s) covered"
					}
				},
				"Covered_Period": {
					"type": "object",
					"title": "Period covered",
					"yoda:structure": "compound",
					"properties": {
						"Start_Date": {
							"type": "string",
							"format": "date",
							"title": "Start date"
						},
						"End_Date": {
							"type": "string",
							"format": "date",
							"title": "End date"
						}
					}
				},
				"Tag": {
					"type": "array",
					"items": {
						"$ref": "#/definitions/stringNormal",
						"title": "Tag"
					}
				},
				"Related_Datapackage": {
					"type": "array",
					"minItems": 0,
					"items": {
						"type": "object",
						"yoda:structure": "subproperties",
						"required": [
							"Title"
						],
						"properties": {
							"Relation_Type": {
								"$ref": "#/definitions/optionsRelationType",
								"title": "Related Datapackage"
							},
							"Title": {
								"$ref": "#/definitions/stringNormal",
								"title": "Title"
							},
							"Persistent_Identifier": {
								"type": "object",
								"title": "Persistent Identifier",
								"yoda:structure": "compound",
								"properties": {
									"Identifier_Scheme": {
										"$ref": "#/definitions/optionsPersistentIdentifierScheme",
										"title": "Type"
									},
									"Identifier": {
										"$ref": "#/definitions/stringNormal",
										"title": "Identifier"
									}
								}
							}
						}
					}
				}
			}
		},

		"Administrative-group": {
			"type": "object",
			"title": "Administrative",
			"required": [
				"Retention_Period",
				"Data_Classification"
			],
			"properties": {
				"Retention_Period": {
					"type": "integer",
					"title": "Retention period (years)"
				},
				"Retention_Information": {
					"$ref": "#/definitions/stringNormal",
					"title": "Retention information"
				},
				"Embargo_End_Date": {
					"type": "string",
					"format": "date",
					"title": "Embargo end date"
				},
				"Data_Classification": {
					"$ref": "#/definitions/optionsDataClassification",
					"title": "Data classification"
				},
				"Collection_Name": {
					"$ref": "#/definitions/stringNormal",
					"title": "Name of collection"
				},
				"Funding_Reference": {
					"type": "array",
					"items": {
						"type": "object",
						"title": "Funder",
						"yoda:structure": "subproperties",
						"properties": {
							"Funder_Name": {
								"$ref": "#/definitions/stringNormal",
								"title": "Funder"
							},
							"Award_Number": {
								"$ref": "#/definitions/stringNormal",
								"title": "Award number"
							}
						}
					}
				}
			}
		},

		"Rights-group": {
			"type": "object",
			"title": "Rights",
			"required": [
				"License",
				"Data_Access_Restriction"
			],
			"properties": {
				"Creator": {
					"type": "array",
					"minItems": 0,
					"items": {
						"type": "object",
						"yoda:structure": "subproperties",
						"required": [
							"Name",
							"Affiliation"
						],
						"properties": {
							"Name": {
								"$ref": "#/definitions/stringNormal",
								"title": "Creator of datapackage"
							},
							"Affiliation": {
								"type": "array",
								"items": {
									"$ref": "#/definitions/stringNormal",
									"title": "Affiliation"
								}
							},
							"Person_Identifier": {
								"type": "array",
								"items": {
									"type": "object",
									"title": "Person Identifier",
									"yoda:structure": "compound",
									"properties": {
										"Name_Identifier_Scheme": {
											"$ref": "#/definitions/optionsNameIdentifierScheme",
											"title": "Type"
										},
										"Name_Identifier": {
											"$ref": "#/definitions/stringNormal",
											"title": "Identifier"
										}
									}
								}
							}
						}
					}
				},
				"Contributor": {
					"type": "array",
					"items": {
						"type": "object",
						"yoda:structure": "subproperties",
						"required": [
							"Contributor_Type",
							"Affiliation"
						],
						"properties": {
							"Name": {
								"$ref": "#/definitions/stringNormal",
								"title": "Contributor of datapackage"
							},
							"Contributor_Type": {
								"$ref": "#/definitions/optionsContributorType",
								"title": "Contributor type"
							},
							"Affiliation": {
								"type": "array",
								"items": {
									"$ref": "#/definitions/stringNormal",
									"title": "Affiliation"
								}
							},
							"Person_Identifier": {
								"type": "array",
								"items": {
									"type": "object",
									"title": "Person Identifier",
									"yoda:structure": "compound",
									"properties": {
										"Name_Identifier_Scheme": {
											"$ref": "#/definitions/optionsNameIdentifierScheme",
											"title": "Type"
										},
										"Name_Identifier": {
											"$ref": "#/definitions/stringNormal",
											"title": "Identifier"
										}
									}
								}
							}
						}
					}
				},
				"License": {
					"$ref": "#/definitions/optionsLicense",
					"title": "License"
				},
				"Data_Access_Restriction": {
					"$ref": "#/definitions/optionsDataAccessRestriction",
					"title": "Data package access"
				}
			}
		}
	}
}
JSON;

        return json_decode($jsonSchema, true);
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

    /** USER IN NEW SITUATION */
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
}