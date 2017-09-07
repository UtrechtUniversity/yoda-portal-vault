$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $( ".datepicker" ).datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true
    });
    $('select').select2();

    $('button[type="submit"]').on('click', function() {
        var html = $(this).html();

        // Handling of submit buttons -> show as disabled and add spinner.
        // Original button must be hidden as otherwise the submit itself does not work correctly anymore
        // 1) Clone this button, add spinner and disable
        var disBtn = $("<button class='" + $(this).attr('class') + "'>").html(html + ' <i class="fa fa-spinner fa-spin fa-fw"></i>').attr("disabled", "disabled");

        // 2) Hide this button and show the cloned disabled.
        $(this).hide().after(disBtn);

        return true;
    });

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

    // Destroy select2 before cloning. (https://stackoverflow.com/questions/17175534/cloned-select2-is-not-responding)
    var isSelect2Field = $(field).hasClass('select2');
    if (isSelect2Field) {
        $(field).find('select').select2('destroy');
    }

    // Clone main field
    var newMainField = field.clone();

    // Change the structure id
    newMainField.find('label i').attr('data-structure-id', newStructureId);

    // Change the field name
    var newField = newMainField.find('.form-control');
    // Get the native select from the select2 plugin.
    if (isSelect2Field) {
        newField = $(newMainField).find('select');
    }
    newField.val('');

    var name = newField.attr('name');

    name = name.replace('['+structureId+']', '['+newStructureId+']');
    newField.attr('name', name);

    // Add the new field handlers
    newMainField.find('button').bind( "click", function() {
        duplicateSubpropertyField(newMainField);
    });
    newMainField.find('[data-toggle="tooltip"]').tooltip();

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

    // Init all select2 select fields.
    if (isSelect2Field) {
        // Init select2 for the 2 fields.
        $(this).find('select').select2();
        $(field).find('select').select2();
        $(newMainField).find('select').select2();
    }

    groups.push(newMainField);

    // Find all sub property fields by the current field structure.
    var subpropertyFieldGroups = $('.rowSubPropertyBase-' + structureBase + '-' + structureId);
    subpropertyFieldGroups.each(function(){
        // Destroy select2 before cloning.
        var isSelect2 = $(this).hasClass('select2');
        if (isSelect2) {
            $(this).find('select').select2('destroy');
        }

        // Clone field
        var newMainFieldGroup = $(this).clone();

        // remove property base selector
        newMainFieldGroup.removeClass('rowSubPropertyBase-' + structureBase + '-' + structureId);
        newMainFieldGroup.addClass('rowSubPropertyBase-' + structureBase + '-' + newStructureId);

        // Change the field name
        var newField = newMainFieldGroup.find('.form-control');

        // Get the native select field from the select2 plugin.
        if (isSelect2) {
            newField = $(newMainFieldGroup).find('select');
        }
        var name = newField.attr('name');
        name = name.replace('['+structureId+']', '['+newStructureId+']');
        newField.attr('name', name);

        // Add the new field handlers
        newField.val('');
        newMainFieldGroup.find('button').bind( "click", function() {
            duplicateSubpropertyField(newMainFieldGroup);
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

        groups.push(newMainFieldGroup);
    });

    // Get the position at new insert.
    var last = subpropertyFieldGroups.last();

    $(last).after(groups);
}
