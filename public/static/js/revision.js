$( document ).ready(function() {
    var mainTable = $('#file-browser').DataTable( {
        "bFilter": false,
        "bInfo": false,
        "bLengthChange": false,
        "ajax": "revision/data",
        "processing": true,
        "serverSide": true,
        "pageLength": 20,
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

});


function datasetRowClickForDetails(obj, dtTable) {
    //console.log(dtTable);

    var tr = obj.closest('tr'),
        row = dtTable.row(tr),
        studyId = tr.find("td:first").html(),
        objectId = $('td:eq(1)', tr).text(),
        revisionObjectId = '',
        revisionStudyID = '';

    if ( row.child.isShown() ) {
        // This row is already open - close it
        row.child.hide();
        tr.removeClass('shown');
    }
    else {
        // Open row for panel information

        $.ajax({
            url: 'revision/detail/' + studyId + '/' + objectId,
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

