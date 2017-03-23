var urlEncodedPath = '',
    folderBrowser = null;

$( document ).ready(function() {
    var url = "revision/data";
    if ($('#search-term').val().length > 0) {
        searchArgument = $('#search-term').val();
        url += '?searchArgument=' + encodeURIComponent($('#search-term').val());
    }

    console.log(url);
    var mainTable = $('#file-browser').DataTable( {
        "bFilter": false,
        "bInfo": false,
        "bLengthChange": false,
        "ajax": url,
        "processing": true,
        "serverSide": true,
        "pageLength": 25,
        "drawCallback": function(settings) {
            mainTable.ajax.url('revision/data?searchArgument=' + $('.form-control[name="searchArgument"]').val());
        }
    } );


    // Click on file browser -> open revision details
    $('#file-browser tbody').on('click', 'tr', function () {
        datasetRowClickForDetails($(this), mainTable);
    });

    $('.btn-search').on('click', function(){
        mainTable.ajax.url('revision/data?searchArgument=' + $('.form-control[name="searchArgument"]').val());
        mainTable.ajax.reload();
    });


    // Button to actually restore the file
    $('#btn-restore').on('click', function(){
        restoreRevision();
    });
});

// Restoration of file
function restoreRevision()
{
    var restorationObjectId = $('#restoration-objectid').val();
    //alert('urlEncoded path: ' + urlEncodedPath);


    $.ajax({
        url: 'revision/restore/' + restorationObjectId + '?targetdir=' + urlEncodedPath,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if(!data.hasError){
                alert(data.result);
                $('#select-folder').modal('hide');
            }
        },
    });
}

// functions for handling of folder selection - easy point of entry for select-folder functionality from the panels within dataTables
// objectid is the Id of the revision that has to be restored
function showFolderSelectDialog(restorationObjectId, path)
{
    $('#restoration-objectid').val(restorationObjectId);

    startBrowsing(path, browseDlgPageItems);
    $('#select-folder').modal('show');
}

function startBrowsing(path, items)
{
    if (!folderBrowser) {
        folderBrowser = $('#folder-browser').DataTable({
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            "ajax": "browse/data",
            "processing": true,
            "serverSide": true,
            "iDeferLoading": 0,
            "pageLength": revisionItemsPerPage,
            "drawCallback": function (settings) {
                $(".browse").on("click", function () {
                    browse($(this).attr('data-path'));
                });
            }
        });
    }
    if (path.length > 0) {
        browse(path);
    } else {
        browse();
    }
}

function browse(dir)
{
    makeBreadcrumb(dir);

    changeBrowserUrl(dir);
//    topInformation(dir);
    buildFileBrowser(dir);
}

function makeBreadcrumb(urlEncodedDir)
{
    var dir = decodeURIComponent((urlEncodedDir + '').replace(/\+/g, '%20'));

    var parts = [];
    if (typeof dir != 'undefined') {
        if (dir.length > 0) {
            var elements = dir.split('/');

            // Remove empty elements
            var parts = $.map(elements, function (v) {
                return v === "" ? null : v;
            });
        }
    }

    // Build html
    var totalParts = parts.length;

    if (totalParts > 0 && parts[0]!='undefined') {
        var html = '<li class="browse">Home</li>';
        var path = "";
        $.each( parts, function( k, part ) {
            path += "%2F" + encodeURIComponent(part);

            // Active item
            valueString = htmlEncode(part).replace(/ /g, "&nbsp;");
            if (k == (totalParts-1)) {
                html += '<li class="active">' + valueString + '</li>';
            } else {
                html += '<li class="browse" data-path="' + path + '">' + valueString + '</li>';
            }
        });
    } else {
        var html = '<li class="active">Home</li>';
    }

    $('ol.dlg-breadcrumb').html(html);
}


function htmlEncode(value){
    //create a in-memory div, set it's inner text(which jQuery automatically encodes)
    //then grab the encoded contents back out.  The div never exists on the page.
    return $('<div/>').text(value).html();
}


function changeBrowserUrl(path)
{
    urlEncodedPath = path;
}

function buildFileBrowser(dir)
{
    var url = "browse/data/collections";
    if (typeof dir != 'undefined') {
        url += "?dir=" +  dir;
    }

    var folderBrowser = $('#folder-browser').DataTable();

    folderBrowser.ajax.url(url).load();

    return true;
}


// Functions for handling of the revision table

function datasetRowClickForDetails(obj, dtTable) {

    var tr = obj.closest('tr');
    var row = dtTable.row(tr);
    var path = $('td:eq(0) span', tr).attr('data-path');

    if ( row.child.isShown() ) {
        // This row is already open - close it
        row.child.hide();
        tr.removeClass('shown');
    }
    else {
        // Open row for panel information

        $.ajax({
            url: 'revision/detail?path=' + path,
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


