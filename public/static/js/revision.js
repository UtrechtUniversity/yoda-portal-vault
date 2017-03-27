var urlEncodedPath = '',
    folderBrowser = null;

$( document ).ready(function() {
    var url = "revision/data";
    if ($('#search-term').val().length > 0) {
        searchArgument = $('#search-term').val();
        url += '?searchArgument=' + encodeURIComponent($('#search-term').val());
    }

    var mainTable = $('#file-browser').DataTable( {
        "bFilter": false,
        "bInfo": false,
        "bLengthChange": false,
        "ajax": url,
        "processing": true,
        "serverSide": true,
        "pageLength": revisionItemsPerPage,
        "ordering": false,
        "columns": [
            { "width": "70%" },
            { "width": "30%" }
        ],
        "drawCallback": function(settings) {
            mainTable.ajax.url('revision/data?searchArgument=' + encodeURIComponent($('#search-term').val()));
        }
    } );

    // Click on file browser -> open revision details
    $('#file-browser tbody').on('click', 'tr', function () {
        datasetRowClickForDetails($(this), mainTable);
    });

    $('.btn-search').on('click', function() {
        if ($('#search-term').val().length > 0) {
            alertMainPanelHide();
            changeUrlSearchFilter($('#search-term').val());
            mainTable.ajax.url('revision/data?searchArgument=' + encodeURIComponent($('#search-term').val()));
            mainTable.ajax.reload();
        }
    });

    $("#search-term").bind('keypress', function(e) {
        if (e.keyCode==13 && $('#search-term').val().length > 0) {
            alertMainPanelHide();
            changeUrlSearchFilter($(this).val());
            mainTable.ajax.url('revision/data?searchArgument=' + encodeURIComponent($(this).val()));
            mainTable.ajax.reload();
        }
    });

    // Button to actually restore the file
    $('#btn-restore').on('click', function(){
        restoreRevision();
    });
});

function changeUrlSearchFilter(filter)
{
    var url = window.location.pathname + "?filter=" +  encodeURIComponent(filter);
    history.replaceState({} , {}, url);
}

// Restoration of file
function restoreRevision()
{
    var restorationObjectId = $('#restoration-objectid').val();

    $.ajax({
        url: 'revision/restore/' + restorationObjectId + '?targetdir=' + urlEncodedPath,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (!data.hasError) {
                $('#select-folder').modal('hide');
                alertMainPanelShow();

                //console.log(urlEncodedPath);
                window.location.href = '/research/?dir=' + urlEncodedPath;
            }
            else {
                alertPanelShow();

                alert('Restoration went: ' + data.reasonError );
            }
        },
    });
}

// functions for handling of folder selection - easy point of entry for select-folder functionality from the panels within dataTables
// objectid is the Id of the revision that has to be restored
function showFolderSelectDialog(restorationObjectId, path)
{
    $('#restoration-objectid').val(restorationObjectId);

    alertMainPanelHide();
    alertPanelHide()

    startBrowsing(path, browseDlgPageItems);
    $('#select-folder').modal('show');
}

function alertMainPanelShow()
{
    var panel = $('.alert-panel-main')
    if (panel.hasClass('hide')) {
        panel.removeClass('hide');
    }
}

function alertMainPanelHide()
{
    var panel = $('.alert-panel-main')
    if (!panel.hasClass('hide')) {
        panel.addClass('hide');
    }
}


function alertPanelShow()
{
    var panel = $('.alert-panel')
    if (panel.hasClass('hide')) {
        panel.removeClass('hide');
    }
}

function alertPanelHide()
{
    var panel = $('.alert-panel')
    if (!panel.hasClass('hide')) {
        panel.addClass('hide');
    }
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
            "pageLength": browseDlgPageItems,
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
    var url = "browse/data/collections/org_lock_protect";
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
            }
        });
    }
}
