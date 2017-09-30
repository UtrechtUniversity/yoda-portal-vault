$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $( ".datepicker" ).datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true
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
        var field = $(this).closest('.form-group');
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
            changeYear: true
        });
    }

    if (newFieldGroup.hasClass('select2')) {
        // Init select2 for the 2 fields.
        newFieldGroup.find('select').select2();
        $(field).find('select').select2();
    }

    // Main property with properties, clone the whole set.
    if (cloneType == 'main') {
        field.next().find('select').select2('destroy');
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
            if ($("input[name='" + tmpName + "']").length == 0) {
                newStructureId = i;
                break;
            }
        }
        newField.attr('name', name.replace('[' + structureId + ']', '[' + newStructureId + ']'));


        // loop all sub properties
        fieldSubPropertiesGroup.find('.form-group').each(function () {
            // Destroy select2 before cloning.
            var isSelect2 = $(this).hasClass('select2');
            if (isSelect2) {
                $(this).find('select').select2('destroy');
            }

            // Field
            var newMainFieldGroup = $(this);
            var newField = newMainFieldGroup.find('.form-control');

            // Get the native select field from the select2 plugin.
            if (isSelect2) {
                newField = $(newMainFieldGroup).find('select');
            }

            // Change the field name
            var name = newField.attr('name');
            newField.attr('name', name.replace('[' + structureId + ']', '[' + newStructureId + ']')); // example: [0] for [1]

            // Add the new field handlers
            newField.val('');

            newMainFieldGroup.find('button').bind("click", function () {
                duplicateField(newMainFieldGroup, 'subproperty');
            });

            newMainFieldGroup.find('[data-toggle="tooltip"]').tooltip();

            if (newField.hasClass('numeric-field')) {
                newField.keypress(validateNumber);
            }

            if (newField.hasClass('datepicker')) {
                newField.removeAttr('id');
                newField.removeClass('hasDatepicker');
                newField.datepicker({
                    dateFormat: "yy-mm-dd",
                    changeMonth: true,
                    changeYear: true
                });
            }

            if (isSelect2) {
                // Init select2 for the 2 fields.
                $(this).find('select').select2();
                newMainFieldGroup.find('select').select2();
                $(newField).find('select').select2();
            }
        });

        currentFieldSubPropertiesGroup.find('select').select2();

        // Insert main field
        $(field).next().after(newFieldGroup);

        // Insert subproperties.
        $(newFieldGroup).after(fieldSubPropertiesGroup);

    } else if (cloneType == 'combined') { // werkt alleen als je op het plusje klikt bij het veld. Als subproperty komt hij hier niet.
        // combined field.
        var fields = newFieldGroup.find('.form-control');
        var newCombinedStructure;
        fields.each(function () {
            var name = $(this).attr('name');
            // strip alles tussen [] om de counter te pakken.
            var nameParts = name.match(/\[(.*?)\]/g);
            var combinedStructure = nameParts[3].slice(1, -1);

            /*
            // Find new combined structure id
            for (i = combinedStructure; i < 1000; i++) {
                console.log(name);
                // Hij mag hier alleen de laatste counter replacen,
                var tmpName = name.replace('[' + combinedStructure + ']', '[' + i + ']');
                if ($("input[name='" + tmpName + "']").length == 0) {
                    newStructureId = i;
                    break;
                }
                console.log(tmpName);
            }
            // Hij mag hier alleen de laatste counter replacen,
            //$(this).attr('name', name.replace('[' + combinedStructure + ']', '[' + newCombinedStructure + ']'));
            */

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
                    changeYear: true
                });
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

// Add the new field handlers
newField.val('');

newMainFieldGroup.find('button').bind("click", function () {
    duplicateField(newMainFieldGroup, 'subproperty');
});

newMainFieldGroup.find('[data-toggle="tooltip"]').tooltip();

if (newField.hasClass('numeric-field')) {
    newField.keypress(validateNumber);
}

if (newField.hasClass('datepicker')) {
    newField.removeAttr('id');
    newField.removeClass('hasDatepicker');
    newField.datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true
    });
}

if (isSelect2) {
    // Init select2 for the 2 fields.
    $(this).find('select').select2();
    newMainFieldGroup.find('select').select2();
    $(newField).find('select').select2();
}