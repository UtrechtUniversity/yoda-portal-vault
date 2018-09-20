import React, { Component } from "react";
import axios from 'axios';
import { render } from "react-dom";
import Form from "react-jsonschema-form";

var schema = {};
var uiSchema = {};
var yodaFormData = {};

var isDatamanager     = false;
var isVaultPackage    = false;
var parentHasMetadata = false;
var metadataExists    = false;
var submitButton      = false;
var unsubmitButton    = false;
var updateButton      = false;
var locked            = false;
var submit            = false;
var unsubmit          = false;

var form = document.getElementById('form');
var path = form.dataset.path;

const onSubmit = ({formData}) => submitData(formData);

class YodaForm extends React.Component {
    constructor(props) {
        super(props);
        const formContext = {
            env: 'research',
            checkmarks: false
        };
        this.state = {
            formData: yodaFormData,
            formContext: formContext
        };
    }

    onError() {
	console.log(errors);
    }

    transformErrors(errors) {
        // Only strip errors when not submitting.
        if (!submit || mode === "edit_in_vault") {
            console.log("Errors before transform: " + errors.length);

            var i = errors.length
            while (i--) {
                if (errors[i].name === "required") {
                    errors.splice(i,1);
                } else if (errors[i].name === "type") {
                    errors.splice(i,1);
                } else if (errors[i].name === "enum") {
                    errors.splice(i,1);
                }
            }
            console.log("Errors after transform: " + errors.length);
        }

        return errors;
    }

    handleChange(yodaFormData) {
        this.showCheckmarks(false);

        // Set new form data.
        this.setState({
            formData: yodaFormData.formData,
        });
    }

    showCheckmarks(show) {
        let formContext = {...this.state.formContext};
        formContext.checkmarks = show;
        this.setState({
            formContext: formContext
        });
    }

    render () {
        return (
        <Form className="form form-horizontal metadata-form"
              schema={schema}
              idPrefix={"yoda"}
              uiSchema={uiSchema}
              formData={this.state.formData}
              formContext={this.state.formContext}
              ArrayFieldTemplate={ArrayFieldTemplate}
              ObjectFieldTemplate={ObjectFieldTemplate}
              FieldTemplate={CustomFieldTemplate}
              liveValidate={true}
              noValidate={false}
              noHtml5Validate={true}
              showErrorList={false}
              onSubmit={onSubmit}
              onChange={this.handleChange.bind(this)}
              onError={this.onError}
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
        return (
          <button onClick={this.props.saveMetadata} type="submit" className="btn btn-primary">
            Save
          </button>
        );
    }

    renderSubmitButton() {
        return (
          <button onClick={this.props.submitMetadata} type="submit" className="btn btn-primary">
            Submit
          </button>
        );
    }

    renderUnsubmitButton() {
        return (
          <button onClick={this.props.unsubmitMetadata} type="submit" className="btn btn-primary">
            Unsubmit
          </button>
        );
    }

    renderUpdateButton() {
        return (
          <button onClick={this.props.updateMetadata} type="button" className="btn btn-primary">
            Update metadata
          </button>
        );
    }

    renderDeleteButton() {
        return (
          <button onClick={this.props.deleteMetadata} type="button" className="btn btn-danger delete-all-metadata-btn pull-right">
            Delete all metadata
          </button>
        );
    }

    renderCloneButton() {
        return (
          <button onClick={this.props.cloneMetadata} type="button" className="btn btn-primary clone-metadata-btn pull-right">
            Clone from parent folder
          </button>
        );
    }

    renderButtons() {
        if (isVaultPackage) {
            if (isDatamanager && !updateButton && mode === "edit_in_vault") {
                // Show 'Update' button.
                return (
                  <div>
                    {this.renderSaveButton()}
                  </div>
                );
            } else if (isDatamanager && updateButton) {
                // Show 'Save' button.
                return (
                  <div>
                    {this.renderUpdateButton()}
                  </div>
                );
            } else if (!isDatamanager) {
                // Show no buttons.
                return (
		  <div>
		  </div>
                );
            }
        } else {
            if (!isDatamanager && !metadataExists && parentHasMetadata) {
		// Show 'Save' and 'Clone from parent folder' buttons.
		return (
		  <div>
		    {this.renderSaveButton()}
		    {this.renderCloneButton()}
		  </div>
		);
            } else if (!isDatamanager && !locked && submitButton) {
		// Show 'Save', 'Submit' and 'Delete all metadata' buttons.
		return (
	          <div>
	            {this.renderSaveButton()}
		    {this.renderSubmitButton()}
		    {this.renderDeleteButton()}
		  </div>
		);
            } else if (!isDatamanager && locked && submitButton) {
		// Show 'Submit' button.
		return (
	          <div>
		    {this.renderSubmitButton()}
		  </div>
		);
            } else if (!isDatamanager && !locked && !submitButton) {
		// Show 'Save' and 'Delete all metadata' buttons.
		return (
		  <div>
		    {this.renderSaveButton()}
		    {this.renderDeleteButton()}
		  </div>
		);
            } else if (!isDatamanager && unsubmitButton) {
		// Show 'Unsubmit' button.
		return (
		  <div>
		    {this.renderUnsubmitButton()}
		  </div>
		);
            } else {
		// Show no buttons.
		return (
		  <div>
		  </div>
		);
            }
        }
    }

    render() {
        return (
	  <div className="row yodaButtons">
	    <div className="col-sm-12">
	      {this.renderButtons()}
            </div>
	  </div>
        );
    }
}


class Container extends React.Component {
    constructor(props) {
        super(props);
        this.saveMetadata = this.saveMetadata.bind(this);
        this.submitMetadata = this.submitMetadata.bind(this);
        this.unsubmitMetadata = this.unsubmitMetadata.bind(this);
    }

    saveMetadata() {
	    submit = false;
	    unsubmit = false;
        this.form.submitButton.click();
        this.form.showCheckmarks(false);
    }

    submitMetadata() {
        submit = true;
	    unsubmit = false;
        this.form.submitButton.click();
        this.form.showCheckmarks(true);
    }

    unsubmitMetadata() {
	submit = false;
        unsubmit = true;
        this.form.submitButton.click();
    }

    deleteMetadata() {
        swal({
            title: "Are you sure?",
            text: "You will not be able to recover this action!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete all metadata!",
            closeOnConfirm: false,
            animation: false
        },
        function(isConfirm){
            if (isConfirm) {
                window.location.href = '/research/metadata/delete?path=' + path;
            }
        });
    }

    cloneMetadata() {
        swal({
            title: "Are you sure?",
            text: "Entered metadata will be overwritten by cloning.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ffcd00",
            confirmButtonText: "Yes, clone metadata!",
            closeOnConfirm: false,
            animation: false
        },
        function(isConfirm){
            if (isConfirm) {
                window.location.href = '/research/metadata/clone_metadata?path=' + path;
            }
        });
    }

    updateMetadata() {
        window.location.href = '/research/metadata/form?path=' + path + '&mode=edit_in_vault';
    }

    render() {
      return (
        <div>
          <YodaButtons saveMetadata={this.saveMetadata}
                       submitMetadata={this.submitMetadata}
                       unsubmitMetadata={this.unsubmitMetadata}
                       updateMetadata={this.updateMetadata}
                       deleteMetadata={this.deleteMetadata}
                       cloneMetadata={this.cloneMetadata} />
          <YodaForm ref={(form) => {this.form=form;}}/>
          <YodaButtons saveMetadata={this.saveMetadata}
                       submitMetadata={this.submitMetadata}
                       unsubmitMetadata={this.unsubmitMetadata}
                       updateMetadata={this.updateMetadata}
                       deleteMetadata={this.deleteMetadata}
                       cloneMetadata={this.cloneMetadata} />
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

axios.get("/research/metadata/data?path=" + path + "&mode=" + mode)
    .then(function (response) {
        schema            = response.data.schema;
        uiSchema          = response.data.uiSchema;
        yodaFormData          = response.data.formData;
        isDatamanager     = response.data.isDatamanager
        isVaultPackage    = response.data.isVaultPackage
        parentHasMetadata = response.data.parentHasMetadata
        metadataExists    = response.data.metadataExists
        submitButton      = response.data.submitButton
        unsubmitButton    = response.data.unsubmitButton
        updateButton      = response.data.updateButton
        locked            = response.data.locked

        render(<Container />,
            document.getElementById("form")
        );
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
    if (submit) {
        bodyFormData.set('vault_submission', "1");
    } else if (unsubmit) {
        bodyFormData.set('vault_unsubmission', "1");
    }

    // Store.
    axios({
        method: 'post',
        url: "/research/metadata/store?path=" + path,
        data: bodyFormData,
        config: { headers: {'Content-Type': 'multipart/form-data' }}
        })
        .then(function (response) {
            window.location.reload();
        })
        .catch(function (error) {
            //handle error
            console.log('ERROR:');
            console.log(error);
        });
}

function CustomFieldTemplate(props) {
    //console.log('Field');
    //console.log(props);

    const {id, classNames, label, help, hidden, required, description, errors, rawErrors, children, displayLabel, formContext} = props;

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

                    {!hasErrors && formContext.checkmarks ? (
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
}

function ObjectFieldTemplate(props) {
    const { TitleField, DescriptionField } = props;

    var structureClass;
    var structure;
    if ('yoda:structure' in props.schema) {
        var structureClass = 'yoda-structure ' + props.schema['yoda:structure'];
        var structure = props.schema['yoda:structure'];
        //console.log(123);
        //console.log(structure);
    }

    //console.log(structure);

    if (structure == 'compound') {
        let array = props.properties;
        let output = props.properties.map((prop, i, array) => {
            //console.log(prop.content);
            //<div class="col-sm-6 field">
            //{props.properties.map(prop => prop.content)}
            return (
                <div className="col-sm-6 field">
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
        let item = props.items[i];
        if (array.length - 1 === i) {
            let btnCount = 1;
            if (canRemove) {
                btnCount = 2;
            }

            return (
                <div className="has-btn">
                    {element.children}
                    <div className={"btn-controls btn-group btn-count-" + btnCount} role="group">
                        {canRemove && <button type="button" className="clone-btn btn btn-default" onClick={item.onDropIndexClick(item.index)}>-</button>}
                        <button type="button" className="clone-btn btn btn-default" onClick={props.onAddClick}>+</button>
                    </div>
                </div>
            );
        } else {
            if (canRemove) {
                return (
                    <div className="has-btn">
                        {element.children}
                        <div className="btn-controls">
                            <button type="button" className="clone-btn btn btn-default" onClick={item.onDropIndexClick(item.index)}>-</button>
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
