$( document ).ready(function() {
    $('#file-browser').dataTable( {
        "bFilter": false,
        "bInfo": false,
        "bLengthChange": false,
        "ajax": "tree/data",
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

});