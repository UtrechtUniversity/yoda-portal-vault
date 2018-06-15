import React, { Component } from "react";
import { render } from "react-dom";

import Form from "react-jsonschema-form";

const schema = {
    "definitions": {
        "Discipline": {
            "type": "string",
            "enum": [
                "Natural Sciences - Mathematics (1.1)",
                "Natural Sciences - Mathematics (1.2)"
            ]
        },
        "Language": {
            "type": "string",
            "enum": [
                "EN",
                "NL"
            ],
            "enumNames": [
                "English",
                "Dutch"
            ]
        },
        "Related_Datapackage": {
            "type": "string",
            "enum": [
                "DP 1",
                "DP 2",
                "DP 3"
            ]
        },
        "Data_Classification": {
            "type": "string",
            "enum": [
                "Public",
                "Basic",
                "Sensitive",
                "Critical"
            ]
        },
        "Contributor_Type": {
            "type": "string",
            "enum": [
                "ContactPerson",
                "DataCollector",
                "DataCurator",
                "DataManager"
            ]
        },
        "License": {
            "type": "string",
            "enum": [
                "Creative Commons Attribution 4.0 International Public License",
                "Creative Commons Attribution-ShareAlike 4.0 International Public License",
                "Open Data Commons Attribution License (ODC-By) v1.0",
                "Custom"
            ]
        },
        "Data_Package_Access": {
            "type": "string",
            "enum": [
                "Open - freely retrievable",
                "Restricted - available upon request",
                "Closed"
            ]
        }
    },
    "title": "",
    "type": "object",
    "properties": {
        "descriptive": {
            "type": "object",
            "title": "Descriptive",
            "required": ["title", "description", "version", "pldate"],
            "properties": {
                "title": {
                    "type": "string",
                    "title": "Title"
                },
                "description": {
                    "type": "string",
                    "title": "Description"
                },
                "discipline": {
                    "type": "string",
                    "title": "Discipline",
                    "$ref": "#/definitions/Discipline",
                },
                /*
                "geo_location": {
                    "type": "number"
                },
                */
                "version": {
                    "type": "string",
                    "title": "Version"
                },
                "language": {
                    "type": "string",
                    "title": "Language of the Data",
                    "$ref": "#/definitions/Language",
                },
                "Collected": {
                    "type": "array",
                    "title": "Collection process",
                    "items": {
                        "type": "object",
                        "properties": {
                            "Start_Date": {
                                "type": "string",
                                "title": "Start Date"
                            },
                            "End_Date": {
                                "type": "string",
                                "title": "End Date"
                            }
                        }
                    }
                },
                "Covered_Geolocation_Place": {
                    "type": "string",
                    "title": "Location(s) covered",
                },
                "Tags": {
                    "type": "array",
                    "title": "Tags",
                    "items": {
                        "type": "object",
                        "properties": {
                            "tag": {
                                "type": "string",
                                "title": "Tag"
                            }
                        }
                    }
                },
                "Related_Datapackages": {
                    "type": "array",
                    "title": "Related_Datapackages",
                    "items": {
                        "type": "object",
                        "properties": {
                            "Related_Datapackage": {
                                "type": "string",
                                "title": "Related Datapackage",
                                "$ref": "#/definitions/Related_Datapackage",
                            },
                            "SubProperties": {
                                "type": "object",
                                "properties": {
                                    "title": {
                                        "type": "string",
                                        "title": "Title"
                                    }
                                },
                            }
                        }
                    }
                }
            }
        },
        "administrative": {
            "type": "object",
            "title": "Administrative",
            "required": ["retention_period"],
            "properties": {
                "retention_period": {
                    "type": "integer",
                    "title": "Retention Period (years)"
                },
                "Retention_Information": {
                    "type": "string",
                    "title": "Retention Information"
                },
                "Embargo_End_Date": {
                    "type": "string",
                    "format": "date",
                    "title": "Embargo End Date"
                },
                "Data_Classification": {
                    "type": "string",
                    "title": "Data Classification",
                    "$ref": "#/definitions/Data_Classification"
                },
                "Collection_Name": {
                    "type": "string",
                    "title": "Name of Collection"
                },
                "Funding_Reference": {
                    "type": "array",
                    "title": "Funders",
                    "items": {
                        "type": "object",
                        "properties": {
                            "Funder_Name": {
                                "type": "string",
                                "title": "Funder",
                            },
                            "SubProperties": {
                                "type": "object",
                                "properties": {
                                    "Award_Number": {
                                        "type": "string",
                                        "title": "Award Number"
                                    }
                                },
                            }
                        }
                    }
                }
            }
        },
        "rights": {
            "type": "object",
            "title": "Rights",
            "required": [],
            "properties": {
                "Creator": {
                    "type": "array",
                    "items": {
                        "type": "object",
                        "properties": {
                            "Name": {
                                "type": "string",
                                "title": "Creator of Data Package",
                            },
                            "SubProperties": {
                                "type": "object",
                                "properties": {
                                    "Affiliation": {
                                        "type": "string",
                                        "title": "Affiliation"
                                    }
                                },
                            }
                        }
                    }
                },
                "Contributor": {
                    "type": "array",
                    "items": {
                        "type": "object",
                        "properties": {
                            "Name": {
                                "type": "string",
                                "title": "Contributor(s) to Data Package",
                            },
                            "SubProperties": {
                                "type": "object",
                                "properties": {
                                    "Contributor_Type": {
                                        "type": "string",
                                        "title": "Contributor Type",
                                        "$ref": "#/definitions/Contributor_Type"
                                    },
                                    "Affiliation": {
                                        "type": "string",
                                        "title": "Affiliation"
                                    }
                                }
                            }
                        }
                    }
                },
                "License": {
                    "type": "string",
                    "$ref": "#/definitions/License"
                },
                "Data_Package_Access": {
                    "type": "string",
                    "title": "Data Package Access",
                    "$ref": "#/definitions/Data_Package_Access"
                }
            }
        }
    }
};

// Define a custom component for handling the root position object
class GeoPosition extends React.Component {
    constructor(props) {
        super(props);

        this.state = {...props.formData};
    }

    onChange(name) {
        console.log(name);

        return (event) => {
            this.setState({
                [name]: event.target.value
            }, () => this.props.onChange(this.state));
        };
    }

    render() {
        const {geo_location} = this.state;

        return (
            <div>
                <input type="string" value={geo_location} onChange={this.onChange(this.props.name)} />
            </div>
        );
    }
}

const DatepickerWidget = (props) => {
    return (
        <input type="text"
               className="form-control datepicker"
               value={props.value}
               required={props.required}
               onChange={(event) => props.onChange(event.target.value)} />
    );
};

const FlexdateWidget = (props) => {
    return (
        <input type="text"
               className="form-control"
               placeholder="yyyy(-mm(-dd))"
               value={props.value}
               required={props.required}
               onChange={(event) => props.onChange(event.target.value)} />
    );
};

// Custom widgets
const widgets = {
    DatepickerWidget: DatepickerWidget,
    FlexdateWidget: FlexdateWidget
};

// Custom fields
const fields = {geo: GeoPosition};

const uiSchema = {
    "descriptive": {
        "description": {
            "ui:widget": "textarea",
            //"ui:readonly": true
        },
        "date": {
            "ui:widget": "DatepickerWidget"
        },
        "flexdate": {
            "ui:widget": "FlexdateWidget"
        },
        "discipline": {
            "ui:widget": "select"
        },
        "geo_location": {
            "ui:field": "geo"
        },
        "Related_Datapackages": {
            items: {
                "SubProperties": {
                    "ui:subproperties": true
                }
            }
        },
        "Collected": {
            "ui:compound": true,
            items: {
                "ui:compound-items": true
            }
        }
    },
    "administrative": {
        "retention_period": {
            "ui:widget": "updown"
        },
        "Funding_Reference": {
            items: {
                "SubProperties": {
                    "ui:subproperties": true
                }
            }
        }
    },
    "rights": {
        "Creator": {
            items: {
                "SubProperties": {
                    "ui:subproperties": true
                }
            }
        },
        "Contributor": {
            items: {
                "SubProperties": {
                    "ui:subproperties": true
                }
            }
        }
    }
};

const formData = {
    "descriptive": {
        "title": "Datapackage Title",
        "Tags": [
            {}
        ],
        "Related_Datapackages": [
            {
                "Properties": {

                }
            }
        ]
    },
    "administrative": {
        "Funding_Reference": [
            {
                "Properties": {

                }
            }
        ]
    },
    "rights": {
        "Creator": [
            {
                "Properties": {

                }
            }
        ],
        "Contributor": [
            {
                "Properties": {

                }
            }
        ]
    }
};

const log = (type) => console.log.bind(console, type);
const onSubmit = ({formData}) => submitData(formData)
const onChange = ({formData}) => console.log("Data changed: ",  formData);


class YodaForm extends Form {
    constructor(props) {
        super(props);
        const superOnSubmit = this.onSubmit;
        this.onSubmit = (event) => {
            event.preventDefault();

            {this.props.formContext.env == 'research' ? (
                this.props.onSubmit(this.state, { status: "submitted" })
            ) : (
                this.setState(this.state, ()=>superOnSubmit(event))
            )}
        }


    }
}


render((
    <YodaForm className="form form-horizontal metadata-form"
              schema={schema}
              idPrefix={"yoda"}
              uiSchema={uiSchema}
              formData={formData}
              formContext={{env: 'research'}}
              fields={fields}
              widgets={widgets}
              ArrayFieldTemplate={ArrayFieldTemplate}
              ObjectFieldTemplate={ObjectFieldTemplate}
              FieldTemplate={CustomFieldTemplate}
              liveValidate={true}
              noValidate={false}
              noHtml5Validate={true}
              showErrorList={false}
              onChange={onChange}
              onSubmit={onSubmit}
              onError={log("errors")} />
), document.getElementById("form"));

function submitData(data)
{
    console.log(data);

    $.ajax({
        type: "POST",
        url: "server.php",
        data: {
            'data': data
        },
        dataType: "json",
        success: function(data) {
            alert('SAVED');
        },
        failure: function(errMsg) {
            alert(errMsg);
        }
    });
}

function CustomFieldTemplate(props) {
    const {id, classNames, label, help, hidden, required, description, errors, rawErrors, children, displayLabel} = props;

    if (hidden || !displayLabel) {
        return children;
    }

    const hasErrors = Array.isArray(errors.props.errors) ? true : false;

    return (
        <div className={classNames}>
            <label className={'col-sm-2 control-label'}>
                <span data-toggle="tooltip" title="" data-original-title="">{label}</span>
            </label>

            {required ? (
                <span className={'fa-stack col-sm-1'}>
        <i className={'fa fa-lock safe fa-stack-1x'} aria-hidden="true" data-toggle="tooltip" title="" data-original-title="Required for the vault"></i>

                    {!hasErrors ? (
                        <i className={'fa fa-check fa-stack-1x checkmark-green-top-right'} aria-hidden="true" data-toggle="tooltip" title="" data-original-title="Filled out correctly for the vault"></i>
                    ) : (
                        null
                    )}

      </span>
            ) : (
                <span className={'fa-stack col-sm-1'}></span>
            )}
            <div className={'col-sm-9'}>
                <div className={'row'}>
                    <div className={'col-sm-12'}>
                        {description}
                        {children}
                    </div>
                </div>
                {errors}
                {help}
            </div>
        </div>
    );
}

function ObjectFieldTemplate(props) {
    const { TitleField, DescriptionField } = props;

    const isCompoundItem = props.uiSchema["ui:compound-items"];
    if (isCompoundField) {
        return (
            <fieldset>
                {(props.uiSchema["ui:title"] || props.title) && (
                    <TitleField
                        id={`${props.idSchema.$id}__title`}
                        title={props.title || props.uiSchema["ui:title"]}
                        required={props.required}
                        formContext={props.formContext}
                    />
                )}
                {props.description && (
                    <DescriptionField
                        id={`${props.idSchema.$id}__description`}
                        description={props.description}
                        formContext={props.formContext}
                    />
                )}
                {props.properties.map(prop => prop.content)}
            </fieldset>
        );

    } else {
        return (
            <fieldset className={props.uiSchema["ui:subproperties"] ? 'subproperties' : ''}>
                {(props.uiSchema["ui:title"] || props.title) && (
                    <TitleField
                        id={`${props.idSchema.$id}__title`}
                        title={props.title || props.uiSchema["ui:title"]}
                        required={props.required}
                        formContext={props.formContext}
                    />
                )}
                {props.description && (
                    <DescriptionField
                        id={`${props.idSchema.$id}__description`}
                        description={props.description}
                        formContext={props.formContext}
                    />
                )}
                {props.properties.map(prop => prop.content)}
            </fieldset>
        );
    }
}

function ArrayFieldTemplate(props) {
    // Compound field wrapper
    const isCompoundField = props.uiSchema["ui:compound"];
    if (isCompoundField) {
        return (
            <div>
                <div class="compound-field">
                    {props.canAdd && <button type="button" onClick={props.onAddClick}>+</button>}
                    {props.items.map(element => element.children)}
                </div>
            </div>
        );
    } else {
        return (
            <div>
                {props.canAdd && <button type="button" onClick={props.onAddClick}>+</button>}
                {props.items.map(element => element.children)}
            </div>
        );
    }
}
