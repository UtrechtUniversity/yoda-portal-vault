import React, { Component } from "react";
import axios from 'axios';
import { render } from "react-dom";


import Form from "react-jsonschema-form";

// Custom widgets
const widgets = {};

// Custom fields
const fields = {};

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

var form = document.getElementById('form');
var path = form.dataset.path;

axios.get("/research/metadata/data?path=" + path)
    .then(function (response) {
        // handle success
        const schema = response.data.schema;
        const uiSchema = response.data.uiSchema;
        const formData = response.data.formData;

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
    })
    .catch(function (error) {
        // handle error
        console.log(error);
    });

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