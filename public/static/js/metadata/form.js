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

    // Disable enter key
    $('.metadata-form input').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });

});

function duplicateField(field)
{
    var newFieldGroup = field.clone();
    var newField = newFieldGroup.find('.form-control');
    newField.val('');
    newFieldGroup.find('button').bind( "click", function() {
        duplicateField(newFieldGroup);
    });
    newFieldGroup.find('[data-toggle="tooltip"]').tooltip();

    if (newField.hasClass('datepicker')) {
        newField.removeClass('hasDatepicker');
        newField.datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true
        });
    }

    $(field).after(newFieldGroup);

}

