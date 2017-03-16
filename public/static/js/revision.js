$( document ).ready(function() {
    var mainTable = $('#file-browser').DataTable( {
        "bFilter": false,
        "bInfo": true,
        "bLengthChange": true,
        "ajax": "revision/data",
        "processing": true,
        "serverSide": true,
        "iDisplayLength": 10,
        "drawCallback": function(settings) {
            mainTable.ajax.url('revision/data?searchArgument=' + $('.form-control[name="searchArgument"]').val());
        }
        /*
        "aoColumnDefs": [{
            "bSortable": false,
            "aTargets": [0, 1, 2, 3]
        }],
        */
        //"order": [[ 4, "asc" ]],
    } );


    $('#file-browser tbody').on('click', 'tr', function () {
        datasetRowClickForDetails($(this), mainTable);
    });

    $('.btn-search').on('click', function(){
        mainTable.ajax.url('revision/data?searchArgument=' + $('.form-control[name="searchArgument"]').val());
        mainTable.ajax.reload();
    });

});


function datasetRowClickForDetails(obj, dtTable) {
    //console.log(dtTable);

    var tr = obj.closest('tr'),
        row = dtTable.row(tr),
        objectId = 3, //@todo $('td:eq(1)', tr).text(),
        revisionObjectId = '',
        revisionStudyID = '';

    console.log(objectId);

    if ( row.child.isShown() ) {
        // This row is already open - close it
        row.child.hide();
        tr.removeClass('shown');
    }
    else {
        // Open row for panel information

        $.ajax({
            url: 'revision/detail/' + objectId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if(!data.hasError){
                    htmlDetailView = data.output;

                    row.child( htmlDetailView ).show();

                    tr.addClass('shown');

                    $('.btn-rev-download').on('click', function () {
                        handleButtonDownload($(this));

                        event.stopPropagation();

//                        alert('download: ' + revisionObjectId + ' - ' + revisionStudyID );
                    });

                    $('.btn-rev-actualise').on('click', function () {
                        // element = $(this).parent().parent().parent();
                        // revisionStudyID = element.data('study-id');
                        // revisionObjectId = element.data('object-id');
                        handleButtonActualise($(this));
                        event.stopPropagation();
                        // alert('actualise: ' + revisionObjectId);
                    });

                    $('.btn-rev-delete').on('click', function () {
                        // element = $(this).parent().parent().parent();
                        // revisionStudyID = element.data('study-id');
                        // revisionObjectId = element.data('object-id');
                        handleButtonDelete($(this));
                        event.stopPropagation();
                        //alert('delete: ' + revisionObjectId);
                    });
                }
            },
        });
    }
}


function handleButtonDownload(button)
{
    var element = button.parent().parent().parent();
    revisionStudyID = element.data('study-id');
    revisionObjectId = element.data('object-id');

    $.ajax({
        url: 'revision/download/' + revisionStudyID + '/' + revisionObjectId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(data.hasError){
                alert('HAS ERROR');
            }
            else {
                alert('ALL OK');
            }
        },
    });
}

function handleButtonActualise(button){
    var element = button.parent().parent().parent();
    revisionStudyID = element.data('study-id');
    revisionObjectId = element.data('object-id');

    $.ajax({
        url: 'revision/actualise/'  + revisionStudyID + '/' + revisionObjectId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(data.hasError){
                alert('HAS ERROR');
            }
            else {
                alert('ALL OK');
            }
        },
    });

}

function handleButtonDelete(button)
{
    var element = button.parent().parent().parent();
    revisionStudyID = element.data('study-id');
    revisionObjectId = element.data('object-id');

    $.ajax({
        url: 'revision/delete/' + revisionStudyID + '/' + revisionObjectId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(data.hasError){
                alert('HAS ERROR');
            }
            else {
                alert('ALL OK');
            }
        },
    });

}

