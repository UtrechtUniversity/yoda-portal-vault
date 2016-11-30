$( document ).ready(function() {
    $('#file-browser').dataTable( {
        "bFilter": false,
        "bInfo": false,
        "bLengthChange": false,
        "ajax": "browse/data",
        "processing": true,
        "serverSide": true,
        "pageLength": 20,
    } );

});