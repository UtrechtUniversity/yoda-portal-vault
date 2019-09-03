import React, { Component } from "react";
import axios from 'axios';
import { render } from "react-dom";
import Form from "react-jsonschema-form";
import Select from 'react-select';
import Geolocation from "./Geolocation"

var globalGeoBoxCounter = 0; // Additions for being able to manually add geoBoxes
var globalThis = null;

var schema = {};
var uiSchema = {};
var yodaFormData = {};

var isDatamanager     = false;
var isVaultPackage    = false;
var updateButton      = false;
var save              = false;
var formDataErrors    = [];

var form = document.getElementById('form');
var path = form.dataset.path;

const numberWidget = (props) => {
    return (
        <input type="number"
               className="number-field form-control"
               min="0"
               max="9999"
               value={props.value}
               required={props.required}
               disabled={props.readonly}
               onChange={(event) => props.onChange(event.target.value)} />
    );
};

const customStyles = {
    control: styles => ({...styles, borderRadius: '0px', minHeight: '15px', height: '33.5px'}),
    placeholder: () => ({color: '#555'})
};

const enumWidget = (props) => {
	var enumArray = props["schema"]["enum"];
	var enumNames = props["schema"]["enumNames"];

	if (enumNames == null) {
        enumNames = enumArray;
    }

	var i = enumArray.indexOf(props["value"]);
	var placeholder = enumNames[i] == null ? " " : enumNames[i];

	return (
		<Select
		className={"select-box"}
		placeholder={placeholder}
		required={props.required}
		isDisabled={props.readonly}
		onChange={(event) => props.onChange(event.value)}
		options={props["options"]["enumOptions"]}
		styles={customStyles} />
	);
};

const widgets = {
    numberWidget: numberWidget,
    SelectWidget: enumWidget
};

const fields = {
    geo: Geolocation
};

const onSubmit = ({formData}) => submitData(formData);

class YodaForm extends React.Component {
    constructor(props) {
        super(props);

        const formContext = {
            save: false
        };
        this.state = {
            formData: yodaFormData,
            formContext: formContext
        };
    }

    onChange(form) {
        formCompleteness();

        // Turn save mode off.
        save = false;
        const formContext = {
            save: false
        };

        this.setState({
            formData: form.formData,
            formContext: formContext
        });
    }

    onError(form) {
        let formContext = {...this.state.formContext};
        formContext.save = save;
        this.setState({
            formContext: formContext
        });
    }

    transformErrors(errors) {
        console.log(errors);
        // Only strip errors when saving.
        if (save) {
            var i = errors.length
            while (i--) {
                if (errors[i].name === "required"     ||
                    errors[i].name === "dependencies") {
                    errors.splice(i,1);
                }
            }
        }

        return errors;
    }

    ErrorListTemplate(props) {
        const {errors, formContext} = props;

        if (errors.length === 0) {
            return(<div></div>);
        } else {
            // Show error list only on save or submit.
            if (formContext.save) {
                return (
                  <div className="panel panel-warning errors">
                    <div className="panel-heading">
                      <h3 className="panel-title">Validation warnings</h3>
                    </div>
                    <ul className="list-group">
                      {errors.map((error, i) => {
                        return (
                          <li key={i} className="list-group-item text-warning">
                            {error.stack}
                          </li>
                        );
                      })}
                    </ul>
                  </div>
                );
            } else {
                return(<div></div>);
            }
        }
    }

    render () {
        return (
        <Form className="form form-horizontal metadata-form"
              schema={schema}
              idPrefix={"yoda"}
              uiSchema={uiSchema}
              fields={fields}
              formData={this.state.formData}
              formContext={this.state.formContext}
              ArrayFieldTemplate={ArrayFieldTemplate}
              ObjectFieldTemplate={ObjectFieldTemplate}
              FieldTemplate={CustomFieldTemplate}
              liveValidate={true}
              noValidate={false}
              noHtml5Validate={true}
              showErrorList={true}
              ErrorList={this.ErrorListTemplate}
              onSubmit={onSubmit}
              widgets={widgets}
              onChange={this.onChange.bind(this)}
              onError={this.onError.bind(this)}
              transformErrors={this.transformErrors}>
            <button ref={(btn) => {this.submitButton=btn;}} className="hidden" />
        </Form>
    );
  }
}

class YodaButtons extends React.Component {
    constructor(props) {
        super(props);
    }

    renderSaveButton() {
        return (<button onClick={this.props.saveMetadata} type="submit" className="btn btn-primary">Save</button>);
    }

    renderUpdateButton() {
        return (<button onClick={this.props.updateMetadata} type="button" className="btn btn-primary">Update metadata</button>);
    }

    renderFormCompleteness() {
        return (<span className="form-completeness add-pointer" aria-hidden="true" data-toggle="tooltip" title=""></span>);
    }

    renderButtons() {
        if (isVaultPackage && isDatamanager) {
            // Datamanager in Vault space.
            if (!updateButton && mode === "edit_in_vault") {
                // Show 'Save' button.
                return (<div>{this.renderSaveButton()} {this.renderFormCompleteness()}</div>);
            } else if (updateButton) {
                // Show 'Update' button.
                return (<div>{this.renderUpdateButton()}</div>);
            }
        } else {
            // Show no buttons.
            return (<div></div>);
        }
    }

    render() {
        return (
            <div className="form-group">
                <div className="row yodaButtons">
                    <div className="col-sm-12">
                        {this.renderButtons()}
                    </div>
                </div>
            </div>
        );
    }
}


class Container extends React.Component {
    constructor(props) {
        super(props);
        this.saveMetadata = this.saveMetadata.bind(this);
    }

    saveMetadata() {
        save = true;
        this.form.submitButton.click();
    }

    updateMetadata() {
        window.location.href = '/vault/metadata/form?path=' + path + '&mode=edit_in_vault';
    }

    render() {
        return (
        <div>
          <YodaButtons saveMetadata={this.saveMetadata}
                       updateMetadata={this.updateMetadata} />
          <YodaForm ref={(form) => {this.form=form;}}/>
          <YodaButtons saveMetadata={this.saveMetadata}
                       updateMetadata={this.updateMetadata} />
        </div>
      );
    }
};


var tokenName = form.dataset.csrf_token_name;
var tokenHash = form.dataset.csrf_token_hash;
axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN' : tokenHash
};
axios.defaults.xsrfCookieName = tokenName;
axios.defaults.xsrfHeaderName = tokenHash;

axios.get("/vault/metadata/data?path=" + path + "&mode=" + mode)
    .then(function (response) {
        schema            = response.data.schema;
        uiSchema          = response.data.uiSchema;
        yodaFormData      = response.data.formData;
        isDatamanager     = response.data.isDatamanager;
        isVaultPackage    = response.data.isVaultPackage;
        updateButton      = response.data.updateButton;
        formDataErrors    = response.data.formDataErrors;

        // Check form data errors
        if (formDataErrors.length > 0) {
            var text = '';
            $.each(formDataErrors, function( key, field ) {
                text +='- ' + field + '<br />';
            });
            $('.form-data-errors .error-fields').html(text);

            $('.form-data-errors').removeClass('hide');
            $('.metadata-form').addClass('hide');
        } else {
            render(<Container/>,
                document.getElementById("form")
            );

            formCompleteness();
        }
    })
    .catch(function (error) {
        console.log(error);
    }
);

function submitData(data)
{
    var path = form.dataset.path;
    var tokenName = form.dataset.csrf_token_name;
    var tokenHash = form.dataset.csrf_token_hash;

    // Disable buttons.
    $('.yodaButtons button').attr("disabled", true);

    // Create form data.
    var bodyFormData = new FormData();
    bodyFormData.set(tokenName, tokenHash);
    bodyFormData.set('formData', JSON.stringify(data));

    // Store.
    axios({
        method: 'post',
        url: "/vault/metadata/store?path=" + path,
        data: bodyFormData,
        config: { headers: {'Content-Type': 'multipart/form-data' }}
        })
        .then(function (response) {
            window.location.href = "/vault/metadata/form?path=" + path;
        })
        .catch(function (error) {
            //handle error
            console.log('ERROR:');
            console.log(error);
        });
}

function CustomFieldTemplate(props) {
    const {id, classNames, label, help, hidden, required, description, errors, rawErrors, children, displayLabel, formContext, readonly} = props;

    if (hidden || !displayLabel) {
        return children;
    }

    const hasErrors = Array.isArray(errors.props.errors) ? true : false;

    // Only show error messages after save.
    if (formContext.save) {
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
          <div className={'col-sm-9 field-wrapper'}>
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
    } else {
       return (
        <div className={classNames}>
          <label className={'col-sm-2 control-label'}>
            <span data-toggle="tooltip" title="" data-original-title="">{label}</span>
          </label>

          {required && !readonly ? (
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
          <div className={'col-sm-9 field-wrapper'}>
            <div className={'row'}>
              <div className={'col-sm-12'}>
                {description}
                {children}
              </div>
            </div>
            {help}
          </div>
         </div>
      );
    }
}

function ObjectFieldTemplate(props) {
    const { TitleField, DescriptionField } = props;

    var structureClass;
    var structure;
    if ('yoda:structure' in props.schema) {
        var structureClass = 'yoda-structure ' + props.schema['yoda:structure'];
        var structure = props.schema['yoda:structure'];
    }

    if (structure === 'compound') {
        let array = props.properties;
        let output = props.properties.map((prop, i, array) => {
            return (
                <div key={i} className="col-sm-6 field compound-field">
                    {prop.content}
                </div>
            );
        });

        return (
            <div className={"form-group " + structureClass}>
                <label className="col-sm-2 combined-main-label control-label">
                    <span>{props.title}</span>
                </label>
                <span className="fa-stack col-sm-1"></span>
                <div className="col-sm-9">
                    <div className="form-group row">
                        {output}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <fieldset className={structureClass}>
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
    let array = props.items;
    let canRemove = true;
    if (array.length === 1) {
        canRemove = false;
    }
    let output = props.items.map((element, i, array) => {
        // Read only view
        if (props.readonly) {
            return element.children;
        }

        let item = props.items[i];
        if (array.length - 1 === i) {
            let btnCount = 1;
            if (canRemove) {
                btnCount = 2;
            }

            return (
                <div key={i} className="has-btn">
                    {element.children}
                    <div className={"btn-controls btn-group btn-count-" + btnCount} role="group">
                        {canRemove &&
                        <button type="button" className="clone-btn btn btn-default" onClick={item.onDropIndexClick(item.index)}>
                            <i className="fa fa-minus" aria-hidden="true"></i>
                        </button>}
                        <button type="button" className="clone-btn btn btn-default" onClick={props.onAddClick}>
                            <i className="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            );
        } else {
            if (canRemove) {
                return (
                    <div key={i} className="has-btn">
                        {element.children}
                        <div className="btn-controls">
                            <button type="button" className="clone-btn btn btn-default" onClick={item.onDropIndexClick(item.index)}>
                                <i className="fa fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                )
            }

            return element.children;
        }
    });

    return (
        <div>
            {output}
        </div>
    );
}


function formCompleteness()
{
    var mandatoryTotal = $('.fa-lock.safe:visible').length;
    var mandatoryFilled = $('.fa-stack .checkmark-green-top-right:visible').length;

    if (mandatoryTotal == 0) {
        var metadataCompleteness = 100;
    } else {
        var metadataCompleteness = Math.ceil(100 * mandatoryFilled / mandatoryTotal);
    }

    var html = '<i class="fa fa-check ' + (metadataCompleteness > 19 ? "form-required-present" : "form-required-missing") + '"</i>' +
    '<i class="fa fa-check ' + (metadataCompleteness > 19 ? "form-required-present" : "form-required-missing") + '"></i>' +
    '<i class="fa fa-check ' + (metadataCompleteness > 59 ? "form-required-present" : "form-required-missing") + '"></i>' +
    '<i class="fa fa-check ' + (metadataCompleteness > 79 ? "form-required-present" : "form-required-missing") + '"></i>' +
    '<i class="fa fa-check ' + (metadataCompleteness > 99 ? "form-required-present" : "form-required-missing") + '"></i>';

    $('.form-completeness').attr('title', 'Required for the vault: '+mandatoryTotal+', currently filled required fields: ' + mandatoryFilled);
    $('.form-completeness').html(html);

    if (mandatoryTotal == mandatoryFilled) {
        return true;
    }

    return false;
}
