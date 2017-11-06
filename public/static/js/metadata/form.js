var arrayCounterBackEnd = 1000; // is incremented each time used thus securing uniqueness and consistent passing to back end

function encodeToXML(s)
{
    var sText = ("" + s).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;").split("&").join("&amp;");
    sText = sText.replace(/[\r]/g, '&#13;');
    sText = sText.replace(/[\n]/g, '&#10;');

    return sText;
}

function bytesLengthOfUTF8String(str) {
  // returns the byte length of an utf8 string
  var s = str.length;
  for (var i=str.length-1; i>=0; i--) {
    var code = str.charCodeAt(i);
    if (code > 0x7f && code <= 0x7ff) s++;
    else if (code > 0x7ff && code <= 0xffff) s+=2;
    else if (code > 0x10000 && code <= 0x10ffff) s+=3;
    if (code >= 0xdc00 && code <= 0xdfff) i--; //trail surrogate
  }
  return s;
}

function excessUTF8Characters(str, maxLength) {
  
  var s = 0;
  var end = str.length;
  var lastPos = 0;
  var excess = 0;

  for (var i=0; i<end; i++) {
    var code = str.charCodeAt(i);
    if (code < 0x007f) s++;
    if (code > 0x7f && code <= 0x7ff) s+=2;
    else if (code > 0x7ff && code <= 0xffff) s+=3;
    else if (code > 0x10000 && code <= 0x10ffff) s+=4;
    if (s>maxLength) {
       lastPos = i;
       break;
    }
  }
  
  if (lastPos !=0) {
      excess = str.substring(lastPos, end).length;
  } 
  return excess;
}


function validateTextLengths()
{
    var canSubmit = true;

    $('.form-control').each(function () {
        var $this = $(this);

        type = '';
        if ($this.is("input")) {
            type = 'text';
        } else if ($this.is("select")) {
            type = 'select';
        } else if ($this.is("textarea")) {
            type = 'textarea';
        }

        if ( type == 'text' || type == 'textarea') {
            maxLength = $(this).attr('maxLength');
	    if (maxLength) {
                valXML = encodeToXML($(this).val());
            	excess = excessUTF8Characters(valXML, maxLength);
		if (excess > 0) {
                    label='';
                    // determine label to indicate where the length problem occurs:
                    $(this).closest('.form-group').find('.control-label span').each(function(){
                        label =  $(this).html();
                    });

                    //mainLabel =
                    $(this).closest('.subproperties').prev('.form-group').find('.control-label span').each(function(){
                       label = $(this).html() + ' - ' + label;
                    });

                    setMessage('error', 'The information cannot be saved as following field is ' + excess + ' characters too long: ' + label);

                    canSubmit = false;
                    return false;
                }
	    }
        }
    });
    return canSubmit;
}

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $( ".datepicker" ).datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        selectCurrent: true,
        yearRange: "c-2000:2100",
        minDate: new Date(500, 1 - 1, 1),
        closeText: 'Clear'
    }).focus(function() {
        var thisDatepicker = $(this);
        $('.ui-datepicker-close').click(function() {
            $.datepicker._clearDate(thisDatepicker);
        });
    });
    $('select').select2();

    // Delete all metadata btn
    $( ".delete-all-metadata-btn" ).on('click', function(e){
        e.preventDefault();

        var path = $(this).attr('data-path');

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
    });

    // clone metadata btn
    $( ".clone-metadata-btn" ).on('click', function(e){
        e.preventDefault();
        var path = $(this).attr('data-path');
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
    });

    $("button.duplicate-field").on( "click", function() {
        var cloneType = $(this).data('clone');
        if (cloneType == 'combined') {
            var field = $(this).closest('.combination-start');
        }
        else {
            var field = $(this).closest('.form-group');
        }
        duplicateField(field, cloneType);
    });

    // Disable enter key
    $('.metadata-form input').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });

    // numeric validation
    $('.numeric-field').keypress(validateNumber);

    // Supproperty handling
    $(document).on('click', ".subproperties-toggle", function () {
        if ($(this).hasClass('glyphicon-chevron-down')) {
            $(this).removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-right');
            $(this).parents('.form-group').next().toggle();
        } else {
            $(this).removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down');
            $(this).parents('.form-group').next().toggle();
        }
    });
});

function validateNumber(event) {
    var key = window.event ? event.keyCode : event.which;
    if (event.keyCode === 8 || event.keyCode === 46 || event.keyCode === 9) {
        return true;
    } else if ( key < 48 || key > 57 ) {
        return false;
    } else {
        return true;
    }
}

function duplicateField(field, cloneType)
{
    // Dublicate one single field.
    var html = "";

    if (field.hasClass('select2')) {
        // Destroy select2 before cloning
        // https://stackoverflow.com/questions/17175534/cloned-select2-is-not-responding
        $(field).find('select').select2('destroy');
    }

    var newFieldGroup = field.clone();


    var newField = newFieldGroup.find('.form-control');
    newField.val('');
    newFieldGroup.find('button').bind( "click", function() {
        duplicateField(newFieldGroup, cloneType);
    });
    newFieldGroup.find('[data-toggle="tooltip"]').tooltip();

    if (newField.hasClass('numeric-field')) {
        newField.keypress(validateNumber);
    }

    if (newField.hasClass('datepicker')) {
        newField.removeClass('hasDatepicker');
        newField.datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true,
            yearRange: "c-2000:c+2000",
            minDate: new Date(500, 1 - 1, 1),
            showButtonPanel: true,
            closeText: 'Clear'
        }).focus(function() {
            var thisDatepicker = $(this);
            $('.ui-datepicker-close').click(function() {
                $.datepicker._clearDate(thisDatepicker);
            });
        });
    }

    if (newFieldGroup.hasClass('select2')) {
        // Init select2 for the 2 fields.
        newFieldGroup.find('select').select2();
        $(field).find('select').select2();
    }

    // Main property with properties, clone the whole set.
    if (cloneType == 'main') {
        var currentFieldSubPropertiesGroup = field.next();
        currentFieldSubPropertiesGroup.find('select').select2('destroy');

        var fieldSubPropertiesGroup = currentFieldSubPropertiesGroup.clone();
        var newStructureId = null;

        // Set name counter.
        var name = newField.attr('name');
        var nameParts = name.match(/\[(.*?)\]/g);
        var structureId = nameParts[0].slice(1, -1);

        // Find new structure id
        for (i = structureId; i < 1000; i++) {
            var tmpName = name.replace('[' + structureId + ']', '[' + i + ']');
            if ($("input[name='" + tmpName + "']").length == 0 && $("select[name='" + tmpName + "']").length == 0) {
                newStructureId = i;
                break;
            }
        }
        newField.attr('name', name.replace('[' + structureId + ']', '[' + newStructureId + ']'));

        // loop all sub properties
        fieldSubPropertiesGroup.find('.form-group').not('.row').each(function () {
            var groupFields = $(this);
            var newMainFieldGroup = $(this);

            // Combination field, multiple fields.
            if ($(this).hasClass('combination-start')) {
                var groupFields = $(this).find('div').closest('.form-group').find('.field');
            }

            groupFields.each(function () {
                // Destroy select2 before cloning.
                var isSelect2 = $(this).hasClass('select2');
                if (isSelect2) {
                    $(this).find('select').select2('destroy');
                }

                // Field
                var newField = $(this).find('.form-control'); // gaat er vanuit dat er maar 1 control is

                // Get the native select field from the select2 plugin.
                if (isSelect2) {
                    newField = $(this).find('select');
                }

                // Change the field name
                var name = newField.attr('name');

                newField.attr('name', name.replace('[' + structureId + ']', '[' + newStructureId + ']')); // example: [0] for [1]

                // Add the new field handlers
                newField.val('');

                // Field bindings.
                if (newField.hasClass('numeric-field')) {
                    newField.keypress(validateNumber);
                }

                if (newField.hasClass('datepicker')) {
                    newField.removeAttr('id');
                    newField.removeClass('hasDatepicker');
                    newField.datepicker({
                        dateFormat: "yy-mm-dd",
                        changeMonth: true,
                        changeYear: true,
                        yearRange: "c-2000:2100",
                        minDate: new Date(500, 1 - 1, 1),
                        showButtonPanel: true,
                        closeText: 'Clear'
                    }).focus(function () {
                        var thisDatepicker = $(this);
                        $('.ui-datepicker-close').click(function () {
                            $.datepicker._clearDate(thisDatepicker);
                        });
                    });
                }

                if (isSelect2) {
                    // Init select2 for the 2 fields.
                    $(this).find('select').select2();
                    newMainFieldGroup.find('select').select2();
                    $(newField).find('select').select2();
                }
            });

            // Fieldgroup bindings
            newMainFieldGroup.find('button').bind("click", function () {
                duplicateField(newMainFieldGroup, $(this).data('clone'));
            });

            newMainFieldGroup.find('[data-toggle="tooltip"]').tooltip();
        });

        currentFieldSubPropertiesGroup.find('select').select2();

        // Insert main field
        $(field).next().after(newFieldGroup);

        // Insert subproperties.
        $(newFieldGroup).after(fieldSubPropertiesGroup);

    } else if (cloneType == 'combined') { // werkt alleen als je op het plusje klikt bij het veld.
        // combined field.

        // to be passed by frontend button designating which level of the array structure is variant. This, as there can be various levels
        var arrayBackendLevel = field.attr('data-backendLevel');

        $(field).after(newFieldGroup);

        var fields = newFieldGroup.find('.form-control');  // finc the fields in the newly added group and adjust the properties accordingly
        var newCombinedStructure;

        arrayCounterBackEnd++; // globally increment this value as any element can use this to guarentee uniqueness

        // Step through all controls and adjust properties that require changing
        // 1) name attribute (when used as arrays in backend)
        // 2) 3rd party fields require extra care to be able to be reused again fully
        // 3) numberic fields
        keepOrgSelect2ForNextLoop = false;
        fields.each(function () {

            var name = $(this).attr('name');

            // solution to get the tab navigation working
            if ($(this).prop('tabindex')=='-1') {
                $(this).prop('tabindex',0);
            }

            // the select2 control comes by twices in this loop. As the container and as the select.
            // The container designates the entire frontend representation and defines that we have a select2
            //
            var isSelect2 = $(this).hasClass('select2-container');
            if (isSelect2) {
               keepOrgSelect2ForNextLoop = $(this); // keep it for next loop when the actual select is found
            }

            // Construct new element name with newly created counter
            // arrayBackendLevel holds which arrayLevel must be adjusted as there can be many
            // Do not do this for the select2 container as this does not have a name.
            // That must be dealt with in the next round of the loop when the actual select itzelf comes by

            if(!isSelect2) {
                baseSplitName = name.split('[', 1); // get base name for element

                newElementName = baseSplitName[0];

                // Now get all the other parts that constitute the full name
                var nameParts = name.match(/\[(.*?)\]/g);
                for (i = 0; i < nameParts.length; i++) {
                    if (i == arrayBackendLevel) {
                        newElementName += '[' + arrayCounterBackEnd + ']'
                    }
                    else {
                        newElementName += nameParts[i];
                    }
                }

                $(this).attr('name', newElementName);

                // Clear value
                $(this).val('');

                // numeric field
                if ($(this).hasClass('numeric-field')) {
                    $(this).keypress(validateNumber);
                }

                if ($(this).hasClass('datepicker')) {
                    $(this).removeAttr('id');
                    $(this).removeClass('hasDatepicker');
                    $(this).datepicker({
                        dateFormat: "yy-mm-dd",
                        changeMonth: true,
                        changeYear: true,
                        yearRange: "c-2000:2100",
                        minDate: new Date(500, 1 - 1, 1),
                        showButtonPanel: true,
                        closeText: 'Clear'
                    }).focus(function() {
                        var thisDatepicker = $(this);
                        $('.ui-datepicker-close').click(function() {
                            $.datepicker._clearDate(thisDatepicker);
                        });
                    });
                }
            }

            // Kept it from previous loop and now finalize situation
            if (keepOrgSelect2ForNextLoop) {
                keepOrgSelect2ForNextLoop.remove();
                keepOrgSelect2ForNextLoop = false;
            }

            // Select select2
            if ($(this).is("select")) {
                $(this).select2();
            }
        });

        // Activate the old dropdowns
        newFieldGroup.find('select').select2();

        // Insert field group
        $(field).after(newFieldGroup);

    } else {
        // Insert field group
        $(field).after(newFieldGroup);
    }
}
