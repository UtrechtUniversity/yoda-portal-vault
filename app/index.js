import React, { Component } from "react";
import { render } from "react-dom";

import Form from "react-jsonschema-form";

const schema = {
    "definitions": {
        "stringNormal": {
            "type": "string",
            "maxLength": 255
        },
        "stringLong": {
            "type": "string",
            "maxLength": 2700
        }
    },
    "title": "",
    "type": "object",
    "properties": {
        "Descriptive-group": {
            "type": "object",
            "comment": "group",
            "title": "Descriptive",
            "properties": {
                "title" : {
                    "type" : "string",
                    "title": "Title"
                },
                "description" : {
                    "$ref": "#/definitions/stringLong",
                    "title": "Description"
                },
                "Rdiscipline" : {
                    "type" : "array",
                    "comment" : "repeat",
                    "items": {
                        "type" : "string",
                        "title": "Discipline",
                        "enum" : ["science","humanities","gamma"]
                    }
                },
                "version": {
                    "type": "string",
                    "title": "Version"
                },
                "language": {
                    "type": "string",
                    "title": "Language of the data",
                    "enum": ["NL", "EN", "ES"]
                },
                "collect": {
                    "type": "object",
                    "comment": "composite",
                    "title": "Collection process",
                    "properties": {
                        "start": {
                            "type": "string",
                            "title": "Start date"
                        },
                        "end": {
                            "type": "string",
                            "title": "End date"
                        }
                    },
                    "yoda:structure": "compound"
                },
                "Rlocation": {
                    "type": "array",
                    "comment": "repeat",
                    "items": {
                        "type": "string",
                        "title": "Location(s) covered"
                    }
                },
                "period": {
                    "type": "object",
                    "comment": "composite",
                    "title": "Period covered",
                    "properties": {
                        "start": {
                            "type": "string",
                            "title": "Start date"
                        },
                        "end": {
                            "type": "string",
                            "title": "End date"
                        }
                    }
                },

                "tag": {
                    "type": "array",
                    "comment": "repeat",
                    "items": {
                        "type": "string",
                        "title": "Tag"
                    }
                },

                "Rrelated": {
                    "type": "array",
                    "comment": "repeat",
                    "items": {
                        "type": "object",
                        "comment": "subprops",
                        "properties": {
                            "main": {
                                "type": "string",
                                "title": "Related data package"
                            },
                            "sub": {
                                "type": "object",
                                "comment": "sub",
                                "properties": {
                                    "title": {
                                        "type": "string",
                                        "title": "Title"
                                    },
                                    "Rid": {
                                        "type": "object",
                                        "comment": "composite",
                                        "properties": {
                                            "pers": {
                                                "type": "string",
                                                "title": "Persistent identifier"
                                            },
                                            "identifier": {
                                                "type": "string",
                                                "title": "Identifier",
                                                "enum": ["DOI", "EPIC"]
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        },
        "Rights" : {
            "type": "object",
            "comment": "group",
            "title": "Rights group",
            "properties": {
                "suppie": {
                    "type" : "object",
                    "comment" : "subprops type 2",
                    "title": "my suppie",
                    "properties" : {
                        "main" : {
                            "type" : "string",
                            "title": "Main prop"
                        },
                        "sub1" : {
                            "type" : "string",
                            "title": "Sub prop1"
                        },
                        "sub2" : {
                            "type" : "string",
                            "title": "Sub prop2"
                        }
                    },
                    "required": ["main"],
                    "yoda:structure": "subproperties"
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
const widgets = {};

// Custom fields
const fields = {};

const uiSchema = {
    "Descriptive-group": {
        "description": {
            "ui:widget": "textarea"
        }
    }
};

const formData = {};

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
              idPrefix={"yoda2"}
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
    //console.log('Field');
    //console.log(props);

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
    // Sub properties object?
    //if (props.properties.length > 1 && props.properties[0]['name'] == 'main') {
    var structure;
    if ('yoda:structure' in props.schema) {
        var structure = props.schema['yoda:structure'];
        /*
        var obj = {};
        obj['ui:widget'] = "textarea";
        obj['classNames'] = "sdsddsds";

        props.properties.forEach(function(properties, index) {
            if (index == 0) {
                return;
            }


            var name = properties.content.props.name;
            props.uiSchema[name] = obj;
            props.properties[index].content.props.uiSchema = obj;

        });
        */
    }

    console.log(props.properties);
    console.log('fff')
    console.log(props.properties.map(prop => prop.content));


    /*
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
    */

    return (
        <fieldset className={structure}>
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
    // Compound field wrapper
    /*
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
    */

    return (
        <div>
            {props.canAdd && <button type="button" onClick={props.onAddClick}>+</button>}
            {props.items.map(element => element.children)}
        </div>
    );
}