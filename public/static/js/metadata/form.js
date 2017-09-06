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
        var field = $(this).closest('.form-group');
        duplicateField(field);
    });

    $("button.duplicate-subproperty-field").on( "click", function() {
        var field = $(this).closest('.form-group');
        duplicateSubpropertyField(field);
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
    $(".subproperties-toggle").on("click", function() {
        var subPropertiesBase = $(this).attr('data-subpropertyBase');
        var structureId = $(this).data('structure-id');
        if ($(this).hasClass('glyphicon-chevron-down')) {
            $(this).removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-right');
            $('.rowSubPropertyBase-' + subPropertiesBase + '-' + structureId).addClass('hide');
        }
        else {
            $(this).removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down');
            $(this).parent().parent().removeClass('hide');
            $('.rowSubPropertyBase-'+ subPropertiesBase + '-' + structureId).removeClass('hide');
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

function duplicateField(field)
{
    if (field.hasClass('select2')) {
        // Destroy select2 before cloning
        // https://stackoverflow.com/questions/17175534/cloned-select2-is-not-responding
        $(field).find('select').select2('destroy');
    }

    var newFieldGroup = field.clone();
    var newField = newFieldGroup.find('.form-control');
    newField.val('');
    newFieldGroup.find('button').bind( "click", function() {
        duplicateField(newFieldGroup);
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

    $(field).after(newFieldGroup);

}

function duplicateSubpropertyField(field)
{
    if (field.hasClass('select2')) {
        // Destroy select2 before cloning
        // https://stackoverflow.com/questions/17175534/cloned-select2-is-not-responding
        $(field).find('select').select2('destroy');
    }

    var structureBase = field.find('label i').data('subpropertybase');
    var structureId = field.find('label i').data('structure-id');
    var newStructureId = null;
    var groups = [];

    // Find new structure id
    for (i = 1; i < 1000; i++) {
        if ($('.rowSubPropertyBase-' + structureBase + '-' + i).length == 0) {
            newStructureId = i;
            break;
        }
    }

    // Clone main field
    var newMainField = field.clone();

    // Change the structure id
    newMainField.find('label i').attr('data-structure-id', newStructureId);

    // Change the field name
    var newField = newMainField.find('.form-control');
    var name = newField.attr('name');
    name = name.replace('['+structureId+']', '['+newStructureId+']');
    newField.attr('name', name);

    // Add the new field handlers
    newMainField = applySubpropertyFieldHandlers(newMainField);
    groups.push(newMainField);

    var subpropertyFieldGroups = $('.rowSubPropertyBase-' + structureBase + '-' + structureId);

    subpropertyFieldGroups.each(function(){
        var newMainFieldGroup = $(this).clone();

        // remove property base selector
        newMainFieldGroup.removeClass('rowSubPropertyBase-' + structureBase + '-' + structureId);
        newMainFieldGroup.addClass('rowSubPropertyBase-' + structureBase + '-' + newStructureId);

        console.log(newMainFieldGroup.html());

        // Change the field name
        var newField = newMainFieldGroup.find('.form-control');
        // Select2
        if (newMainFieldGroup.hasClass('select2')) {
            newField = $(newMainFieldGroup).find('select');
        }
        var name = newField.attr('name');
        name = name.replace('['+structureId+']', '['+newStructureId+']');
        newField.attr('name', name);

        // Add the new field handlers
        newMainFieldGroup = applySubpropertyFieldHandlers(newMainFieldGroup);
        groups.push(newMainFieldGroup);
    });

    var last = subpropertyFieldGroups.last();

    $(last).after(groups);
}

function applySubpropertyFieldHandlers(fieldGroup)
{
    var newField = fieldGroup.find('.form-control');
    newField.val('');
    fieldGroup.find('button').bind( "click", function() {
        duplicateSubpropertyField(fieldGroup);
    });
    fieldGroup.find('[data-toggle="tooltip"]').tooltip();

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

    if (fieldGroup.hasClass('select2')) {
        console.log('select2');
        // Init select2 for the 2 fields.
        fieldGroup.find('select').select2();
        $(newField).find('select').select2();
    }

    return fieldGroup;
}
