import React, { Component } from "react";
import axios from 'axios';
import { render } from "react-dom";
import Modal from 'react-modal';
import { Map, TileLayer, Marker, Popup, FeatureGroup } from 'react-leaflet';
import L from 'leaflet';
import { EditControl } from "react-leaflet-draw";
import Form from "react-jsonschema-form";
import Select from 'react-select';

var globalGeoBoxCounter = 0; // Additions for being able to manually add geoBoxes
var globalThis = null;

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
var writePermission   = false;
var save              = false;
var submit            = false;
var unsubmit          = false;
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
               onChange={(event) => props.onChange(event.target.value)} />
    );
};

const customModalStyles = {
    content : {
        top                   : '50%',
        left                  : '50%',
        right                 : 'auto',
        bottom                : 'auto',
        marginRight           : '-50%',
        transform             : 'translate(-50%, -50%)',
        width                 : '58%',
        height                : '625px',
    }
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

class GeoLocation extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            modalIsOpen: false,
            ...props.formData
        };

        this.openModal = this.openModal.bind(this);
        this.closeModal = this.closeModal.bind(this);
        this.afterOpenModal = this.afterOpenModal.bind(this);
        this.drawCreated = this.drawCreated.bind(this);
        this.drawEdited = this.drawEdited.bind(this);
        this.drawDeleted = this.drawDeleted.bind(this);
        this.drawStop = this.drawStop.bind(this);
        this.setFormData = this.setFormData.bind(this);
        this.geoBoxID = globalGeoBoxCounter;
        globalGeoBoxCounter++;
    }

    openModal(e) {
        e.preventDefault();

        globalThis = this; // @todo: get rid of this dirty trick

        this.setState({modalIsOpen: true});
    }

    closeModal(e) {
        e.preventDefault();
        this.setState({modalIsOpen: false});
    }

    afterOpenModal(e) {
        const {northBoundLatitude, westBoundLongitude, southBoundLatitude, eastBoundLongitude} = this.state;
        let map = this.refs.map.leafletElement;
        if (typeof northBoundLatitude !== 'undefined' &&
            typeof westBoundLongitude !== 'undefined' &&
            typeof southBoundLatitude !== 'undefined' &&
            typeof eastBoundLongitude !== 'undefined'
        ) {
            let bounds = [
                [northBoundLatitude,       westBoundLongitude],
                [southBoundLatitude + 0.1, eastBoundLongitude + 0.1]
            ];

            // Coordinates are a point.
            if (northBoundLatitude == southBoundLatitude && westBoundLongitude == eastBoundLongitude) {
                var latlng = L.latLng(northBoundLatitude, westBoundLongitude);
                L.marker(latlng).addTo(map);
            } else {
                L.rectangle(bounds).addTo(map);
            }
            map.fitBounds(bounds, {'padding': [150, 150]});
        }

        this.fillCoordinateInputs(northBoundLatitude, westBoundLongitude, southBoundLatitude, eastBoundLongitude);

        $('.geoInputCoords').on('input propertychange paste', function() {
            var boxID = $(this).attr("boxID");

            // Remove earlier markers and rectangle(s)
            map.eachLayer(function (layer) {
                if (layer instanceof L.Marker || layer instanceof L.Rectangle) {
                    map.removeLayer(layer);
                }
            });

            // only make persistent when correct coordinates are added by user
            var lat0 = Number($(".geoLat0[boxID='" + boxID + "']").val()),
                lng0 = Number($(".geoLng0[boxID='" + boxID + "']").val()),
                lat1 = Number($(".geoLat1[boxID='" + boxID + "']").val()),
                lng1 = Number($(".geoLng1[boxID='" + boxID + "']").val()),
                alertText = '';

            // Validation of coordinates - resetten als dialog wordt heropend
            if(!$.isNumeric( lng0 )){
                alertText += ', WEST';
            }
            if(!$.isNumeric( lat0 )){
                alertText = ', NORTH';
            }
            if(!$.isNumeric( lng1 )){
                alertText += ', EAST';
            }
            if(!$.isNumeric( lat1 )){
                alertText += ', SOUTH';
            }

            if (alertText) {
                $('.geoAlert[boxID="' + boxID + '"]').html('Invalid coordinates: ' + alertText.substring(2));
            } else {
                $('.geoAlert[boxID="' + boxID + '"]').html(''); // reset the alert box -> no alert required
                let bounds = [[lat0, lng0], [lat1 + 0.1, lng1 + 0.1]];

                // Coordinates are a point.
                if (lat0 == lat1 && lng0 == lng1) {
                    var latlng = L.latLng(lat0, lng0);
                    L.marker(latlng).addTo(map);
                } else {
                    L.rectangle(bounds).addTo(map);
                }
                map.fitBounds(bounds, {'padding': [150, 150]});

                globalThis.setFormData('northBoundLatitude', lat0);
                globalThis.setFormData('westBoundLongitude', lng0);
                globalThis.setFormData('southBoundLatitude', lat1);
                globalThis.setFormData('eastBoundLongitude', lng1);
            }
       });
    }

    fillCoordinateInputs(northBoundLatitude, westBoundLongitude, southBoundLatitude, eastBoundLongitude) {
        $('.geoLat0').val(northBoundLatitude);
        $('.geoLng0').val(westBoundLongitude);
        $('.geoLat1').val(southBoundLatitude);
        $('.geoLng1').val(eastBoundLongitude);
    }

    drawCreated(e) {
        let layer = e.layer;

        if (layer instanceof L.Marker) {
            this.setFormData('northBoundLatitude', layer.getLatLng().lat);
            this.setFormData('westBoundLongitude', layer.getLatLng().lng);
            this.setFormData('southBoundLatitude', layer.getLatLng().lat);
            this.setFormData('eastBoundLongitude', layer.getLatLng().lng);

            this.fillCoordinateInputs(
                layer.getLatLng().lat, layer.getLatLng().lng,
                layer.getLatLng().lat, layer.getLatLng().lng
            );
        } else if (layer instanceof L.Rectangle)  {
            this.setFormData('northBoundLatitude', layer.getLatLngs()[0][2].lat);
            this.setFormData('westBoundLongitude', layer.getLatLngs()[0][2].lng);
            this.setFormData('southBoundLatitude', layer.getLatLngs()[0][0].lat);
            this.setFormData('eastBoundLongitude', layer.getLatLngs()[0][0].lng);

            this.fillCoordinateInputs(
                layer.getLatLngs()[0][2].lat, layer.getLatLngs()[0][2].lng,
                layer.getLatLngs()[0][0].lat, layer.getLatLngs()[0][0].lng
            );
        }
    }

    drawEdited(e) {
        e.layers.eachLayer( (layer) => {
            if (layer instanceof L.Marker) {
                this.setFormData('northBoundLatitude', layer.getLatLng().lat);
                this.setFormData('westBoundLongitude', layer.getLatLng().lng);
                this.setFormData('southBoundLatitude', layer.getLatLng().lat);
                this.setFormData('eastBoundLongitude', layer.getLatLng().lng);

                this.fillCoordinateInputs(
                    layer.getLatLng().lat, layer.getLatLng().lng,
                    layer.getLatLng().lat, layer.getLatLng().lng
                );
            } else if (layer instanceof L.Rectangle)  {
                this.setFormData('northBoundLatitude', layer.getLatLngs()[0][2].lat);
                this.setFormData('westBoundLongitude', layer.getLatLngs()[0][2].lng);
                this.setFormData('southBoundLatitude', layer.getLatLngs()[0][0].lat);
                this.setFormData('eastBoundLongitude', layer.getLatLngs()[0][0].lng);

                this.fillCoordinateInputs(
                    layer.getLatLngs()[0][2].lat, layer.getLatLngs()[0][2].lng,
                    layer.getLatLngs()[0][0].lat, layer.getLatLngs()[0][0].lng
                );
            }
        });
    }

    drawDeleted(e) {
        this.setFormData('northBoundLatitude', undefined);
        this.setFormData('westBoundLongitude', undefined);
        this.setFormData('southBoundLatitude', undefined);
        this.setFormData('eastBoundLongitude', undefined);

        this.fillCoordinateInputs("", "", "", "");
    }

    drawStop(e) {
        let map = this.refs.map.leafletElement;
        map.eachLayer(function (layer) {
            if (layer instanceof L.Marker || layer instanceof L.Rectangle) {
                map.removeLayer(layer);
            }
        });
    }

    setFormData(fieldName, fieldValue) {
        this.setState({
            [fieldName]: fieldValue
        }, () => this.props.onChange(this.state));
    }

    render() {
        const {northBoundLatitude, westBoundLongitude, southBoundLatitude, eastBoundLongitude} = this.state;
        return (
                <div class={'form-group geoDiv' + this.geoBoxID}>
                  <label class="col-sm-2 control-label">
                    <span>Geolocation</span>
                  </label>
                  <span class="fa-stack col-sm-1"></span>
                  <div class="col-sm-9">
                    <button class='btn' onClick={(e) => {this.openModal(e); }}>Open Map</button>&nbsp;
                    WN: {westBoundLongitude}, {northBoundLatitude} ES: {eastBoundLongitude}, {southBoundLatitude}
                  </div>

                <Modal
                    isOpen={this.state.modalIsOpen}
                    onAfterOpen={this.afterOpenModal}
                    onRequestClose={this.closeModal}
                    style={customModalStyles}
                    ariaHideApp={false}
                >
                    <Map ref='map' center={[48.760, 13.275]} zoom={4} animate={false}>
                        <TileLayer
                            attribution='&amp;copy <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        />
                        <FeatureGroup>
                            <EditControl
                                position='topright'
                                onCreated={this.drawCreated}
                                onEdited={this.drawEdited}
                                onDeleted={this.drawDeleted}
                                onDrawStart={this.drawStop}
                                draw={{
                                    circle: false,
                                    polygon: false,
                                    marker: true,
                                    circlemarker: false,
                                    polyline: false
                                }}
                            />
                        </FeatureGroup>
                    </Map>

                    <div class='row'>
                        <div class='col-sm-11'>
                            <label>West:</label> <input type='text' class='geoInputCoords geoLng0' boxID={this.geoBoxID}></input>
                            <label>North:</label> <input type='text' class='geoInputCoords geoLat0' boxID={this.geoBoxID}></input>
                            <label>East:</label> <input type='text' class='geoInputCoords geoLng1' boxID={this.geoBoxID}></input>
                            <label>South:</label> <input type='text' class='geoInputCoords geoLat1' boxID={this.geoBoxID}></input>
                        </div>
                        <div class='col-sm-1'>
                            <button class='btn' onClick={(e) => {this.closeModal(e); }}>Close</button>
                        </div>
                    </div>
                    <div class='geoAlert' boxID={this.geoBoxID}></div>
                </Modal>
            </div>
        );
    }
}

const widgets = {
    numberWidget: numberWidget,
    SelectWidget: enumWidget
};

const fields = {
    geo: GeoLocation
};

const onSubmit = ({formData}) => submitData(formData);

class YodaForm extends React.Component {
    constructor(props) {
        super(props);

        const formContext = {
            submit: false,
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
            submit: false,
            save: false
        };

        this.setState({
            formData: form.formData,
            formContext: formContext
        });
    }

    onError(form) {
        let formContext = {...this.state.formContext};
        formContext.submit = submit;
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
        if (!submit) {
            var i = errors.length
            while (i--) {
                if (errors[i].name === "required"     ||
                    errors[i].name === "dependencies") {
                    errors.splice(i,1);
                }
            }
        }

        if (errors.length === 0) {
            return(<div></div>);
        } else {
            // Show error list only on save or submit.
            if (formContext.save || formContext.submit) {
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

    renderSaveVaultButton() {
        return (<button onClick={this.props.saveVaultMetadata} type="submit" className="btn btn-primary">Save</button>);
    }

    renderSubmitButton() {
        return (<button onClick={this.props.submitMetadata} type="submit" className="btn btn-primary">Submit</button>);
    }

    renderUnsubmitButton() {
        return (<button onClick={this.props.unsubmitMetadata} type="submit" className="btn btn-primary">Unsubmit</button>);
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
                return (<div>{this.renderSaveVaultButton()}</div>);
            } else if (updateButton) {
                // Show 'Update' button.
                return (<div>{this.renderUpdateButton()}</div>);
            }
        } else if (writePermission) {
            // Write permission in Research space.
            if (!metadataExists && locked) {
                // Show no buttons.
                return (<div></div>);
            } else if (!metadataExists && parentHasMetadata) {
                // Show 'Save' button.
                return (<div>{this.renderSaveButton()} {this.renderFormCompleteness()}</div>);
            } else if (!metadataExists) {
                // Show 'Save' button.
                return (<div>{this.renderSaveButton()} {this.renderFormCompleteness()}</div>);
            } else if (!locked && submitButton) {
                // Show 'Save' and 'Submit' buttons.
                return (<div> {this.renderSaveButton()} {this.renderSubmitButton()} {this.renderFormCompleteness()} </div>);
            } else if (locked && submitButton) {
                // Show 'Submit' button.
                return (<div>{this.renderSubmitButton()}</div>);
            } else if (!locked && !submitButton) {
                // Show 'Save' button.
                return (<div>{this.renderSaveButton()} {this.renderFormCompleteness()} </div>);
            } else if (unsubmitButton) {
                // Show 'Unsubmit' button.
                return (<div>{this.renderUnsubmitButton()}</div>);
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
        this.saveVaultMetadata = this.saveVaultMetadata.bind(this);
        this.submitMetadata = this.submitMetadata.bind(this);
        this.unsubmitMetadata = this.unsubmitMetadata.bind(this);
    }

    saveMetadata() {
        save = true
        submit = unsubmit = false;
        this.form.submitButton.click();
    }

    saveVaultMetadata() {
        submit = true;
        save = unsubmit = false;
        this.form.submitButton.click();
    }

    submitMetadata() {
        submit = true;
        save = unsubmit = false;
        this.form.submitButton.click();
    }

    unsubmitMetadata() {
        unsubmit = true;
        save = submit = false;
        this.form.submitButton.click();
    }

    updateMetadata() {
        window.location.href = '/vault/metadata/form?path=' + path + '&mode=edit_in_vault';
    }

    render() {
        return (
        <div>
          <YodaButtons saveMetadata={this.saveMetadata}
                       saveVaultMetadata={this.saveVaultMetadata}
                       submitMetadata={this.submitMetadata}
                       unsubmitMetadata={this.unsubmitMetadata}
                       updateMetadata={this.updateMetadata} />
          <YodaForm ref={(form) => {this.form=form;}}/>
          <YodaButtons saveMetadata={this.saveMetadata}
                       saveVaultMetadata={this.saveVaultMetadata}
                       submitMetadata={this.submitMetadata}
                       unsubmitMetadata={this.unsubmitMetadata}
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
        parentHasMetadata = response.data.parentHasMetadata;
        metadataExists    = response.data.metadataExists;
        submitButton      = response.data.submitButton;
        unsubmitButton    = response.data.unsubmitButton;
        updateButton      = response.data.updateButton;
        locked            = response.data.locked;
        writePermission   = response.data.writePermission;
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
    if (submit) {
        bodyFormData.set('vault_submission', "1");
    } else if (unsubmit) {
        bodyFormData.set('vault_unsubmission', "1");
    }

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

    // Only show error messages after submit.
    if (formContext.submit || formContext.save) {
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
                <div className="col-sm-6 field compound-field">
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
                <div className="has-btn">
                    {element.children}
                    <div className={"btn-controls btn-group btn-count-" + btnCount} role="group">
                        {canRemove &&
                        <button type="button" className="clone-btn btn btn-default" onClick={item.onDropIndexClick(item.index)}>
                            <i class="fa fa-minus" aria-hidden="true"></i>
                        </button>}
                        <button type="button" className="clone-btn btn btn-default" onClick={props.onAddClick}>
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            );
        } else {
            if (canRemove) {
                return (
                    <div className="has-btn">
                        {element.children}
                        <div className="btn-controls">
                            <button type="button" className="clone-btn btn btn-default" onClick={item.onDropIndexClick(item.index)}>
                                <i class="fa fa-minus" aria-hidden="true"></i>
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
