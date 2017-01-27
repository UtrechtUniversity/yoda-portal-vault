$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $( ".datepicker" ).datepicker({
        dateFormat: "yy-mm-dd"
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
            closeOnConfirm: false
        },
        function(isConfirm){
            if (isConfirm) {
                window.location.href = '/research/metadata/delete?path=' + path;
            }
        });
    });
});