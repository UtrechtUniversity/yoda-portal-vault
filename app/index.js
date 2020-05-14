import React, { Component } from "react";
import { render } from "react-dom";
import Form from "react-jsonschema-form";
import Select from 'react-select';
import Geolocation from "./Geolocation"

const path = $('#form').attr('data-path');


let schema       = {};
let uiSchema     = {};
let yodaFormData = {};

let actual_edit_mode = false; // if can edit, it is not diretly shown.
let formProperties;

let saving = false;

let form = document.getElementById('form');

const customStyles = {
    control: styles => ({...styles, borderRadius: '0px', minHeight: '15px', height: '33.5px'}),
    placeholder: () => ({color: '#555'})
};

const enumWidget = (props) => {
    let enumArray = props['schema']['enum'];
    let enumNames = props['schema']['enumNames'];

    if (enumNames == null)
        enumNames = enumArray;

    let i = enumArray.indexOf(props['value']);
    let placeholder = enumNames[i] == null ? ' ' : enumNames[i];

    return (<Select className={'select-box'}
                    placeholder={placeholder}
                    required={props.required}
                    isDisabled={props.readonly}
                    onChange={(event) => props.onChange(event.value)}
                    options={props['options']['enumOptions']}
                    styles={customStyles} />);
};

const widgets = {
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
            saving: false
        };
        this.state = {
            formData: yodaFormData,
            formContext: formContext
        };
    }

    onChange(form) {
        updateCompleteness();

        // Turn save mode off.
        saving = false;
        const formContext = { saving: false };

        this.setState({
            formData: form.formData,
            formContext: formContext
        });
    }

    onError(form) {
        let formContext = {...this.state.formContext};
        formContext.saving = saving;
        this.setState({ formContext: formContext });
    }

    transformErrors(errors) {
        // Strip errors when saving.
        if (saving)
            return errors.filter((e) => e.name !== 'required' && e.name !== 'dependencies');
        return errors;
    }

    ErrorListTemplate(props) {
        let {errors, formContext} = props;
        errors = errors.filter((e) => e.name !== 'required' && e.name !== 'dependencies');

        if (errors.length === 0) {
            return(<div></div>);
        } else {
            // Show error list only on save.
            if (formContext.saving) {
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
        return (<span className="form-completeness" aria-hidden="true" data-toggle="tooltip" title=""></span>);
    }

    renderButtons() {
        let buttons = [];

        if (formProperties.data.can_edit) {
            if (!actual_edit_mode) {
                buttons.push(this.renderUpdateButton());
            } else {
                buttons.push(this.renderSaveButton());
                buttons.push(this.renderFormCompleteness());
            }
        }
        return (<div>{buttons}</div>);
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
        saving = true;
        this.form.submitButton.click();
    }

    updateMetadata() {
        actual_edit_mode = true;
        $(_ => loadForm(JSON.parse(atob($('#form-properties').text()))));
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

/**
 * Returns to the browse view for the current collection.
 */
function browse() {
    window.location.href = '/vault/browse?dir=' + encodeURIComponent(path);
}


function loadForm(properties) {
    formProperties = properties;

    // Inhibit "loading" text.
    formLoaded = true;

    if (formProperties.data !== null) {
        // These ary only present when there is a form to show (i.e. no
        // validation errors, and no transformation needed).
        schema       = formProperties.data.schema;
        uiSchema     = formProperties.data.uischema;
        yodaFormData = formProperties.data.metadata === null ? undefined : formProperties.data.metadata;
    }

    if (formProperties.status === 'error_transformation_needed') {
        // Transformation is necessary. Show transformation prompt.
        $('#transformation-text').html(formProperties.data.transformation_html);
        if (formProperties.data.can_edit) {
            $('#transformation-buttons').removeClass('hide')
            $('#transformation-text').html(formProperties.data.transformation_html);
        } else {
            $('#transformation .close-button').removeClass('hide')
        }
        $('.transformation-accept').on('click', async () => {
            $('.transformation-accept').attr('disabled', true);

            await Yoda.call('uu_transform_metadata',
                            {coll: Yoda.basePath+path},
                            {errorPrefix: 'Metadata could not be transformed'});

            window.location.reload();
        });
        $('#transformation').removeClass('hide');

    } else if (formProperties.status !== 'ok') {
        // Errors exist - show those instead of loading a form.
        let text = '';
        if (formProperties.status === 'error_validation') {
            // Validation errors? show a list.
            $.each(formProperties.data.errors, (key, field) => {
                text += '<li>' + $('<div>').text(field.replace('->', '→')).html();
            });
        } else {
            // Structural / misc error? Show status info.
            text += '<li>' + $('<div>').text(formProperties.status_info).html();
        }
        // HdR The delete button does not exist in vault
        //$('.delete-all-metadata-btn').on('click', deleteMetadata);
        $('#form-errors .error-fields').html(text);
        $('#form-errors').removeClass('hide');

    } else if (formProperties.data.metadata === null && !formProperties.data.can_edit) {
        // No metadata present and no write access. Do not show a form.
        $('#form').addClass('hide');
        $('#no-metadata').removeClass('hide');

    } else {
        // Metadata present or user has write access, load the form and must be in actual_edit_mode as chosen by user
        if (!formProperties.data.can_edit || !actual_edit_mode) {
            uiSchema['ui:readonly'] = true;
        }
        else
        {
            uiSchema = formProperties.data.uischema; // take over original ui-shema again- not the readonly one
            // As we open metadataform in the vault, the completeness must be 100%
            // This is dealt with in this way as timing issues occur when doing this where it is done like in research area.
            // Due to that,
            const html = ' '
                + '<i class="fa fa-check form-required-present"></i>'.repeat(5);
            $('.form-completeness').html(html);
        }

        render(<Container/>, document.getElementById('form'));

        // Form may already be visible (with "loading" text).
        if ($('#metadata-form').hasClass('hide')) {
            // Avoid flashing things on screen.
            $('#metadata-form').fadeIn(220);
            $('#metadata-form').removeClass('hide');
        }

        updateCompleteness();
    }
}

$(_ => loadForm(JSON.parse(atob($('#form-properties').text()))));

async function submitData(data) {
    // Disable buttons.
    $('.yodaButtons button').attr('disabled', true);

    // Save.
    try {
        await Yoda.call('uu_meta_form_save',
                        {coll: Yoda.basePath+path, metadata: data},
                        {errorPrefix: 'Metadata could not be saved'});

        Yoda.message('success', `Updated metadata of folder <${path}>`);
        browse();
    } catch (e) {
        // Allow retry.
        $('.yodaButtons button').attr('disabled', false);
    }
}

function CustomFieldTemplate(props) {
    const {id, classNames, label, help, hidden, required, description, errors,
           rawErrors, children, displayLabel, formContext, readonly} = props;

    if (hidden || !displayLabel) {
        return children;
    }

    const hasErrors = Array.isArray(errors.props.errors);

    // Only show error messages after submit.
    if (formContext.saving) {
      return (
        <div className={classNames}>
          <label className={'col-sm-2 control-label'}>
            <span data-toggle="tooltip" title={help.props.help}>{label}</span>
          </label>

          {required ? (
            <span className={'fa-stack col-sm-1'}>
              <i className={'fa fa-lock safe fa-stack-1x'} aria-hidden="true" data-toggle="tooltip" title="Required for the vault"></i>
              {!hasErrors ? (
                <i className={'fa fa-check fa-stack-1x checkmark-green-top-right'} aria-hidden="true" data-toggle="tooltip" title="Filled out correctly for the vault"></i>
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
          </div>
        </div>
      );
    } else {
       return (
        <div className={classNames}>
          <label className={'col-sm-2 control-label'}>
            <span data-toggle="tooltip" title={help.props.help}>{label}</span>
          </label>

          {required && !readonly ? (
            <span className={'fa-stack col-sm-1'}>
              <i className={'fa fa-lock safe fa-stack-1x'} aria-hidden="true" data-toggle="tooltip" title="Required for the vault"></i>
              {!hasErrors ? (
                <i className={'fa fa-check fa-stack-1x checkmark-green-top-right'} aria-hidden="true" data-toggle="tooltip" title="Filled out correctly for the vault"></i>
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
          </div>
         </div>
      );
    }
}

function ObjectFieldTemplate(props) {
    const { TitleField, DescriptionField } = props;

    let structureClass;
    let structure;
    if ('yoda:structure' in props.schema) {
        structureClass = `yoda-structure ${props.schema['yoda:structure']}`;
        structure = props.schema['yoda:structure'];
    }

    if (structure === 'compound') {
        var array = props.properties;
        var output = props.properties.map((prop, i, array) => {
            return (
                <div key={i} className="col-sm-6 field compound-field">
                    {prop.content}
                </div>
            );
        });

        return (
            <div className={`form-group ${structureClass}`}>
                <label className="col-sm-2 combined-main-label control-label">
                    <span data-toggle="tooltip" title={props.uiSchema["ui:help"]}>{props.title}</span>
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
    let canRemove = array.length !== 1;
    let output = props.items.map((element, i, array) => {
        // Read only view
        if (props.readonly || props.disabled) {
            return element.children;
        }

        let item = props.items[i];
        if (array.length - 1 === i) {
            // Render "add" button only on the last item.

            let btnCount = 1 + canRemove;

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

    if (props.disabled)
        return (<div className="hide">{output}</div>);
    else
        return (<div>{output}</div>);
}


function updateCompleteness()
{
    const mandatoryTotal  = $('.fa-lock.safe:visible').length;
    const mandatoryFilled = $('.fa-stack .checkmark-green-top-right:visible').length;

    const completeness = mandatoryTotal == 0 ? 1 : mandatoryFilled / mandatoryTotal;

    const html = ' '
               + '<i class="fa fa-check form-required-present"></i>'.repeat(  Math.floor(completeness*5))
               + '<i class="fa fa-check form-required-missing"></i>'.repeat(5-Math.floor(completeness*5));

    $('.form-completeness').attr('title', `Required for the vault: ${mandatoryTotal}, currently filled required fields: ${mandatoryFilled}`);
    $('.form-completeness').html(html);

    return mandatoryTotal == mandatoryFilled;
}
