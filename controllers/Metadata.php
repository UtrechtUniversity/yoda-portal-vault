<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Metadata extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['userIsAllowed'] = TRUE;

        $this->load->model('rodsuser');
        $this->config->load('config');

        $this->load->library('pathlibrary');
    }

    public function form()
    {
        $this->load->model('Metadata_model');
        $this->load->model('Metadata_form_model');
        $this->load->model('Filesystem');


        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $isDatamanager = $formConfig['isDatamanager'];
        $isVaultPackage = $formConfig['isVaultPackage'];

        $metadataCompleteness = 0; // mandatory completeness for the metadata
        $mandatoryTotal = 0;
        $mandatoryFilled = 0;
        $validationResult = true;
        $userType = $formConfig['userType'];

        $mode = $this->input->get('mode'); // ?mode=edit_for_vault
        if ($isDatamanager == 'yes' && $isVaultPackage == 'yes' && $mode == 'edit_in_vault') {
            // .tmp file for XSD validation
            $result = $this->Metadata_model->prepareVaultMetadataForEditing($formConfig['metadataXmlPath']);

            $tmpSavePath = $result['*tempMetadataXmlPath'] . '.tmp';
            $tmpFileExists = $this->Filesystem->read($rodsaccount, $tmpSavePath);
            if ($tmpFileExists !== false) {
                $formConfig['metadataXmlPath'] = $tmpSavePath;
            }
        }


        $elements = $this->Metadata_form_model->getFormElements($rodsaccount, $formConfig);
        if ($elements) {
            $this->load->library('metadataform');

            //$form = $this->metadataform->load($elements, $metadata);
            $form = $this->metadataform->load($elements);
            if ($userType == 'normal' || $userType == 'manager') {  //userTypes {normal, manager} get write -access (dus ook submit etc)
                $form->setPermission('write');
            } else {
                $form->setPermission('read');
            }

            // First perform validation if yoda-metadata is present
            if ($formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') {
                $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath)); // folder is not relevant for the application here

                $validationErrors = $this->vaultsubmission->validateMetaAgainstXsdOnly();
                if (count($validationErrors )) {
                    $validationResult = $validationErrors;
                }
            }

            if( $validationResult===true) { // skip calculation if info is not required in frontend.
                // figure out the number of mandatory fields and how many actually hold data
                $form->calculateMandatoryCompleteness($elements);

                // calculate metadataCompleteness with
                $mandatoryTotal = $form->getCountMandatoryTotal();
                $mandatoryFilled = $form->getCountMandatoryFilled();
                if ($mandatoryTotal == 0) {
                    $metadataCompleteness = 100;
                } else {
                    $metadataCompleteness = ceil(100 * $mandatoryFilled / $mandatoryTotal);
                }
            }

            $metadataExists = false;
            $cloneMetadata = false;
            if ($formConfig['hasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') {
                $metadataExists = true;
            }

            if ($formConfig['parentHasMetadataXml'] == 'true' || $formConfig['hasMetadataXml'] == 'yes') {
                $cloneMetadata = true;
            }
        } else {
            $form = null;
            $metadataExists = false;
            $cloneMetadata = false;
        }
        $realMetadataExists = $metadataExists; // keep it as this is the true state of metadata being present or not.

        // Check locks
        if ($formConfig['lockFound'] == "here" || $formConfig['lockFound'] == "ancestor" || $formConfig['folderStatus']=='SUBMITTED' || $formConfig['folderStatus']=='LOCKED') {
            $form->setPermission('read');
            $cloneMetadata = false;
            $metadataExists = false;
        }


        // Corrupt metadata causes no $form to be created.
        // The following code (before adding 'if ($form) ' crashes ($form->getPermission() ) the application http error 500
        $ShowUnsubmitBtn = false;
        if ($form) {
            // Submit To Vault btn
            $submitToVaultBtn = false;
            $lockStatus = $formConfig['lockFound'];
            $folderStatus = $formConfig['folderStatus'];
            if (($lockStatus == 'here' || $lockStatus == 'no') && ($folderStatus == 'PROTECTED' || $folderStatus == 'LOCKED' || $folderStatus == '')
                && ($userType == 'normal' || $userType == 'manager')) { // written this way as the
                $submitToVaultBtn = true;
            }

            if (($userType == 'normal' OR $userType == 'manager')  AND $folderStatus == 'SUBMITTED') {
                $showUnsubmitBtn = true;
            }
        }


        $flashMessage = $this->session->flashdata('flashMessage');
        $flashMessageType = $this->session->flashdata('flashMessageType');

        // Datamanager Edit metadata in vault btn & write permissions
        $showEditBtn = false;
        $messageDatamanagerAfterSaveInVault = '';  // message to datamanger via central messaging -> javascript setMessage
        if ($isDatamanager == 'yes' && $isVaultPackage == 'yes') {
	    if ($formConfig['hasShadowMetadataXml'] == 'no') {
                if ($mode == 'edit_in_vault') {
                    $form->setPermission('write'); // Set write permissions for editing metadata in the vault.
                } else {
                    $showEditBtn = true; // show edit button
                }
	    }

            if ($formConfig['hasShadowMetadataXml'] == 'yes') {
                $messageDatamanagerAfterSaveInVault = 'Update of metadata is pending.';
            }
        }

        // Load CSRF token
        $tokenName = $this->security->get_csrf_token_name();
        $tokenHash = $this->security->get_csrf_hash();

        $viewParams = array(
            'styleIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.css',
                'lib/font-awesome/css/font-awesome.css',
                'lib/sweetalert/sweetalert.css',
                'lib/select2/css/select2.min.css',
                'lib/leaflet/leaflet.css',
                'lib/leaflet/leaflet.draw.css',
                'css/metadata/form.css',
            ),
            'scriptIncludes' => array(
                'lib/jqueryui-datepicker/jquery-ui-1.12.1.js',
                'lib/sweetalert/sweetalert.min.js',
                'lib/select2/js/select2.min.js',
                'lib/jquery-inputmask/jquery.inputmask.bundle.js',
                // LEAFLET
                'lib/leaflet/leaflet.js',
                'lib/leaflet/Leaflet.draw.js',
                'lib/leaflet/Leaflet.Draw.Event.js',
                'lib/leaflet/Toolbar.js',
                'lib/leaflet/Tooltip.js',
                'lib/leaflet/ext/GeometryUtil.js',
                'lib/leaflet/ext/LatLngUtil.js',
                'lib/leaflet/ext/LineUtil.Intersect.js',
                'lib/leaflet/ext/Polygon.Intersect.js',
                'lib/leaflet/ext/Polyline.Intersect.js',
                'lib/leaflet/ext/TouchEvents.js',
                'lib/leaflet/draw/DrawToolbar.js',
                'lib/leaflet/draw/handler/Draw.Feature.js',
                'lib/leaflet/draw/handler/Draw.SimpleShape.js',
                'lib/leaflet/draw/handler/Draw.Polyline.js',
                'lib/leaflet/draw/handler/Draw.Marker.js',
                'lib/leaflet/draw/handler/Draw.Circle.js',
                'lib/leaflet/draw/handler/Draw.CircleMarker.js',
                'lib/leaflet/draw/handler/Draw.Polygon.js',
                'lib/leaflet/draw/handler/Draw.Rectangle.js',
                'lib/leaflet/edit/EditToolbar.js',
                'lib/leaflet/edit/handler/EditToolbar.Edit.js',
                'lib/leaflet/edit/handler/EditToolbar.Delete.js',
                'lib/leaflet/Control.Draw.js',
                'lib/leaflet/edit/handler/Edit.Poly.js',
                'lib/leaflet/edit/handler/Edit.SimpleShape.js',
                'lib/leaflet/edit/handler/Edit.Rectangle.js',
                'lib/leaflet/edit/handler/Edit.Marker.js',
                'lib/leaflet/edit/handler/Edit.CircleMarker.js',
                'lib/leaflet/edit/handler/Edit.Circle.js',

                'js/metadata/form.js',
                //'js/metadata/bundle.js',
            ),
            'activeModule'   => 'research',
            'form' => $form,
            'path' => $path,
            'fullPath' => $fullPath,
            'tokenName' => $tokenName,
            'tokenHash' => $tokenHash,
            'userType' => $userType,
            'metadataExists' => $metadataExists, // @todo: refactor! only used in front end to have true knowledge of whether metadata exists as $metadataExists is unreliable now
            'cloneMetadata' => $cloneMetadata,
            'isVaultPackage' => $isVaultPackage,
            'showEditBtn' => $showEditBtn,
            'messageDatamanagerAfterSaveInVault' => $messageDatamanagerAfterSaveInVault,

            'mandatoryTotal' => $mandatoryTotal,
            'mandatoryFilled' => $mandatoryFilled,
            'metadataCompleteness' => $metadataCompleteness,

            'submitToVaultBtn' => $submitToVaultBtn,
            'showUnsubmitBtn' => $showUnsubmitBtn,
            'flashMessage' => $flashMessage,
            'flashMessageType' => $flashMessageType,
            'validationResult' => $validationResult,

            'realMetadataExists' => $realMetadataExists, // @todo: refactor! only used in front end to have true knowledge of whether metadata exists as $metadataExists is unreliable now
        );
        loadView('metadata/form', $viewParams);
    }

    public function data()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('Metadata_form_model');
        $this->load->model('Metadata_model');
        $this->load->model('Folder_Status_model');
        $this->load->model('Filesystem');

        $path = $this->input->get('path');
        $fullPath = $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $xmlFormData = $this->Metadata_form_model->loadFormData($rodsaccount, $formConfig['metadataXmlPath']);

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



        $uiSchema = <<<'JSON'
    {
    }
JSON;


        $result = json_decode($jsonSchema, true);
        $formData = array();
        foreach ($result['properties'] as $groupKey => $group) {
            //Group
            foreach($group['properties'] as $fieldKey => $field) {
                // Field
                if (array_key_exists('type', $field)) {
                    if ($field['type'] == 'string') { // string
                        if (isset($xmlFormData[$fieldKey])) {
                            $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                        }
                    } else if ($field['type'] == 'array') { // array
                        if ($field['items']['type'] == 'string') {
                            if (isset($xmlFormData[$fieldKey])) {
                                if (count($xmlFormData[$fieldKey]) == 1) {
                                    $formData[$groupKey][$fieldKey] = array($xmlFormData[$fieldKey]);
                                } else {
                                    $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                                }
                            }
                        } else if ($field['items']['type'] == 'object') {
                            //$formData[$groupKey][$fieldKey] = array();
                            $emptyObjectField = array();
                            foreach ($field['items']['properties'] as $objectKey => $objectField) {
                                if ($objectField['type'] == 'string') {
                                    $emptyObjectField[$objectKey] = $objectKey;
                                } else if ($objectField['type'] == 'object') { //subproperties
                                    foreach ($objectField['properties'] as $subObjectKey => $subObjectField) {
                                        if ($subObjectField['type'] == 'string') {
                                            $emptyObjectField[$objectKey][$subObjectKey] = $objectKey;
                                        } else if ($subObjectField['type'] == 'object') {// Composite
                                            $compositeField = array();
                                            foreach ($subObjectField['properties'] as $subCompositeKey => $subCompositeField) {
                                                $compositeField[$subCompositeKey] = $subCompositeKey;
                                            }

                                            $emptyObjectField[$objectKey][$subObjectKey] = $compositeField;
                                        }
                                    }
                                }
                            }
                            //$formData[$groupKey][$fieldKey][] = $emptyObjectField;
                        }
                    } else if ($field['type'] == 'object') {
                        $structure = $field['yoda:structure'];
                        // Subproperties
                        if (isset($structure) && $structure == 'subproperties') {
                            $mainProp = true;
                            foreach ($field['properties'] as $objectKey => $objectField) {
                                if ($mainProp) {
                                    if (isset($xmlFormData[$fieldKey][$objectKey])) {
                                        $formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey][$objectKey];
                                    }
                                    $mainProp = false;
                                } else {
                                    if (isset($xmlFormData[$fieldKey]['Properties'][$objectKey])) {
                                        $formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey]['Properties'][$objectKey];
                                    }
                                }
                            }
                        }

                        foreach ($field['properties'] as $objectKey => $objectField) {
                            if (isset($xmlFormData[$fieldKey][$objectKey])) {
                                $formData[$groupKey][$fieldKey][$objectKey] = $xmlFormData[$fieldKey][$objectKey];
                            }
                        }
                    }
                } else {
                    if (isset($xmlFormData[$fieldKey])) {
                        $formData[$groupKey][$fieldKey] = $xmlFormData[$fieldKey];
                    }
                }
            }
        }

        $output = array();
        $output['path'] = $path;
        $output['schema'] = json_decode($jsonSchema);
        $output['uiSchema'] = json_decode($uiSchema);
        $output['formData'] = $formData;

        $this->output->set_content_type('application/json')->set_output(json_encode($output));
    }

    /**
     * Serves storing of:
     *
     * 1) SUBMIT FOR VAULT
     * 2) UNSUBMIT FOR VAULT
     * 3) save changes to metadata
     *
     * Permitted only for userType in {normal, manager}
     *
     */
    function store()
    {

        // OLD!
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $arrayPost = $this->input->post();

        $this->load->model('Metadata_form_model');
        $this->load->model('Metadata_model');
        $this->load->model('Folder_Status_model');
        $this->load->model('Filesystem');

        $path = $this->input->get('path');
        $fullPath = $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $userType = $formConfig['userType'];
        $lockStatus = $formConfig['lockFound'];
        $folderStatus = $formConfig['folderStatus'];
        $isDatamanager = $formConfig['isDatamanager'];
        $isVaultPackage = $formConfig['isVaultPackage'];

        // Datamanager save metadata in vault package
        if ($isDatamanager == 'yes' && $isVaultPackage == 'yes') {
            $result = $this->Metadata_model->prepareVaultMetadataForEditing($formConfig['metadataXmlPath']);
            $tempPath = $result['*tempMetadataXmlPath'];
            $tmpSavePath = $tempPath . '.tmp';
            $formConfig['metadataXmlPath'] = $tmpSavePath;
            $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));

            $result = $this->vaultsubmission->validate();
            if ($result === true) {
                $tmpFileContent = $this->Filesystem->read($rodsaccount, $tmpSavePath);
                $writeResult = $this->Filesystem->write($rodsaccount, $tempPath, $tmpFileContent);
                if ($writeResult) {
                    //setMessage('success', 'Update of metadata is pending.'); // No message - this is, for this sitatuion dealt with by loading page
                    $this->Filesystem->delete($rodsaccount, $tmpSavePath);
                } else {
                    setMessage('error', 'Unexpected metadata xml write error.');
                }
            } else {
                // result contains all collected messages as an array
                setMessage('error', implode('<br>', $result));
            }

            return redirect('research/metadata/form?path=' . urlencode($path) . '&mode=edit_in_vault', 'refresh');
        }

        if (!($userType=='normal' || $userType=='manager')) { // superseeds userType!= reader - which comes too late for permissions for vault submission
            $this->session->set_flashdata('flashMessage', 'Insufficient rights to perform this action.'); // wat is een locking error?
            $this->session->set_flashdata('flashMessageType', 'danger');
            return redirect('research/browse?dir=' . urlencode($path), 'refresh');
        }

        $status = '';
        $statusInfo = '';

        if ($this->input->post('vault_submission') || $this->input->post('vault_unsubmission')) {
            $this->load->library('vaultsubmission', array('formConfig' => $formConfig, 'folder' => $fullPath));
            if ($this->input->post('vault_submission')) { // HdR er wordt nog niet gecheckt dat juiste persoon dit mag

                if(!$this->vaultsubmission->checkLock()) {
                    setMessage('error', 'There was a locking error encountered while submitting this folder.');
                }
                else {
                    // first perform a save action of the latest posted data - only if there is no lock!
                    if ($formConfig['folderStatus']!='LOCKED') {
                        $result = $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
                    }
                    // Do vault submission
                    $result = $this->vaultsubmission->validate();
                    if ($result === true) {
                        $submitResult = $this->vaultsubmission->setSubmitFlag();
                        if ($submitResult) {
                            setMessage('success', 'The folder is successfully submitted.');
                        } else {
                            setMessage('error', $result['*statusInfo']);
                        }
                    } else {
                        // result contains all collected messages as an array
                        setMessage('error', implode('<br>', $result));

                    }
                }
            }
            elseif ($this->input->post('vault_unsubmission')) {
                $result = $this->vaultsubmission->clearSubmitFlag();
                if ($result['*status']== 'Success') {
                    setMessage('success', 'This folder was successfully unsubmitted from the vault.');
                }
                else {
                    setMessage('error', $result['*statusInfo']);
                }
            }
        }
        else {
            // save metadata xml.  Check for correct conditions
            if ($folderStatus == 'SUBMITTED') {
                setMessage('error', 'The form has already been submitted');
                return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
            }
            if ($folderStatus == 'LOCKED' || $lockStatus == 'ancestor') {
                setMessage('error', 'The metadata form is locked possibly by the action of another user.');
                return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
            }

            if ($this->input->server('REQUEST_METHOD') == 'POST') {
                $result = $this->Metadata_form_model->processPost($rodsaccount, $formConfig);
            }
        }

        return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
    }

    function delete()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('filesystem');
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);

        $userType = $formConfig['userType'];

        if($userType != 'reader') {
            $result = $this->filesystem->removeAllMetadata($rodsaccount, $fullPath);
            if ($result) {
                return redirect('research/browse?dir=' . urlencode($path), 'refresh');
            } else {
                return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
            }
        }
        else {
            //get away from the form, user is (no longer) entitled to view it
            return redirect('research/browse?dir=' . urlencode($path), 'refresh');
        }
    }

    function clone_metadata()
    {
        $pathStart = $this->pathlibrary->getPathStart($this->config);
        $rodsaccount = $this->rodsuser->getRodsAccount();

        $this->load->model('filesystem');
        $path = $this->input->get('path');
        $fullPath =  $pathStart . $path;

        $formConfig = $this->filesystem->metadataFormPaths($rodsaccount, $fullPath);
        if ($formConfig['parentHasMetadataXml'] == 'true') {
            $xmlPath = $formConfig['metadataXmlPath'];
            $xmlParentPath = $formConfig['parentMetadataXmlPath'];

            $result = $this->filesystem->cloneMetadata($rodsaccount, $xmlPath, $xmlParentPath);
        }

        return redirect('research/metadata/form?path=' . urlencode($path), 'refresh');
    }

    /*
    public function index()
    {
        $this->load->view('common-start', array(
            'styleIncludes' => array(
                'css/research.css',
                'lib/datatables/css/dataTables.bootstrap.min.css',
                //'lib/materialdesignicons/css/materialdesignicons.min.css'
                'lib/font-awesome/css/font-awesome.css'
            ),
            'scriptIncludes' => array(
                'lib/datatables/js/jquery.dataTables.min.js',
                'lib/datatables/js/dataTables.bootstrap.min.js',
                'js/research.js',
            ),
            'activeModule'   => $this->module->name(),
            'user' => array(
                'username' => $this->rodsuser->getUsername(),
            ),
        ));

        $this->data['items'] = $this->config->item('browser-items-per-page');

        $this->load->view('browse', $this->data);
        $this->load->view('common-end');
    }
    */


}
