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
        // alert('hier');
        datasetRowClickForDetails($(this), mainTable);
    });

});


function datasetRowClickForDetails(obj, dtTable) {
    //console.log(dtTable);

    var tr = obj.closest('tr'),
        row = dtTable.row(tr),
        cohortId = tr.find('.row-id').data('row-id');

    if ( row.child.isShown() ) {
        // This row is already open - close it
        row.child.hide();
        tr.removeClass('shown');
    }
    else {
        // Open row for panel information
        //var url = base_url + ['cohortqueries','getCohortRowDetailView',cohortId].join('/'),
        //    ;

        $.ajax({
            url: 'revision/detail',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if(!data.hasError){
                    htmlDetailView = data.output;

                    row.child( htmlDetailView ).show();

                    tr.addClass('shown');
                }
            },
        });
    }
}

