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
        }
    },
    "title": "",
    "type": "object",
    "properties": {
        "descriptive": {
            "type": "object",
            "title": "Descriptive",
            "required": ["title", "description", "version"],
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
                "version": {
                    "type": "string",
                    "title": "Version"
                },
                "language": {
                    "type": "string",
                    "title": "Language of the Data",
                    "$ref": "#/definitions/Language",
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
                            "test1": {
                                "type": "string",
                                "title": "test1"
                            },
                            "test2": {
                                "type": "string",
                                "title": "Test2"
                            },
                            "SubProperties": {
                                "type": "object",
                                "properties": {
                                    "test": {
                                        "type": "string",
                                        "title": "Test"
                                    },
                                    "title": {
                                        "type": "string",
                                        "title": "Title"
                                    },
                                    "test3": {
                                        "type": "string",
                                        "title": "Test3"
                                    },
                                    "test4": {
                                        "type": "string",
                                        "title": "Test4"
                                    }
                                },
                                "dependencies": {
                                    "test3": {
                                        "required": ["test4", "title"]
                                    }
                                }
                            }
                        },
                        "dependencies": {
                            "test1": {
                                "required": ["test2"]
                            },
                            "Related_Datapackage": {
                                "BlaProperties": {
                                    "properties": {
                                        "required": ["test4"]
                                    }
                                }
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
                    "title": "Retention Period"
                }
            }
        }
    }
};


const uiSchema = {
    "descriptive": {
        "description": {
            "ui:widget": "textarea",
            //"ui:readonly": true
        },
        "discipline": {
            "ui:widget": "select"
        },
        "Related_Datapackages": {
            items: {
                "SubProperties": {
                    "ui:subproperties": true
                }
            }

        }
    },
    "administrative": {
        "retention_period": {
            "ui:widget": "updown"
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

function ArrayFieldTemplate(props) {
    return (
        <div>
            {props.canAdd && <button type="button" onClick={props.onAddClick}>+</button>}
            {props.items.map(element => element.children)}
        </div>
    );
}
