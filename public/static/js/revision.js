var urlEncodedPath = '',
    folderBrowser = null;

$( document ).ready(function() {
    var url = "revision/data";
    if ($('#search-filter').val().length > 0) {
        searchArgument = $('#search-filter').val();
        url += '?searchArgument=' + encodeURIComponent($('#search-filter').val());
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
            mainTable.ajax.url('revision/data?searchArgument=' + encodeURIComponent($('#search-filter').val()));
        }
    } );

    // Click on file browser -> open revision details
    $('#file-browser tbody').on('click', 'tr', function () {
        datasetRowClickForDetails($(this), mainTable);
    });

    $('.search-btn').on('click', function() {
        if ($('#search-filter').val().length > 0) {
            changeUrlSearchFilter($("#search-filter").val());
            mainTable.ajax.url('revision/data?searchArgument=' + encodeURIComponent($('#search-filter').val()));
            mainTable.ajax.reload();
        }
    });

    $("#search-filter").bind('keypress', function(e) {
        if (e.keyCode==13 && $('#search-filter').val().length > 0) {
            changeUrlSearchFilter($(this).val());
            mainTable.ajax.url('revision/data?searchArgument=' + encodeURIComponent($(this).val()));
            mainTable.ajax.reload();
        }
    });

    // Button to actually restore the file
    $('#btn-restore').on('click', function(){
        //restoreRevision('restore_no_overwrite');
        restoreRevision('restore_no_overwrite');
    });

    $('#btn-restore-overwrite').on('click', function(event){
        event.preventDefault();
        restoreRevision('restore_overwrite');
    });

    $('#btn-restore-next-to').on('click', function(event){
        event.preventDefault();
        restoreRevision('restore_next_to');
    });

    $('#btn-cancel-overite-dialog').on('click', function(){
        $('.cover').addClass('hide');
        $('.revision-restore-dialog').removeClass('hide');
    });

});

function changeUrlSearchFilter(filter)
{
    var url = window.location.pathname + "?filter=" +  encodeURIComponent(filter);
    history.replaceState({} , {}, url);
}

function setAlert(message)
{
    var obj = $('#alertBox');
    if (obj.hasClass('hide')) {
        obj.removeClass('hide');
    }
    obj.html(message);
}


// Restoration of file
function restoreRevision(overwriteFlag)
{
    if (typeof urlEncodedPath == 'undefined') {
        errorMessage = 'The HOME folder cannot be used for restoration purposes';
        $('.alert-panel-warning').removeClass('hide');
        $('.alert-panel-warning span').html(errorMessage);
        return;
    }

    var restorationObjectId = $('#restoration-objectid').val(),
        newFileName = $('#newFileName').val();

    if(newFileName.length==0 && overwriteFlag == 'restore_next_to') {
        setAlert('Please enter a name for the file you want to restore');
        return;
    }

    $.ajax({
        url: 'revision/restore/' + restorationObjectId + '/' + overwriteFlag + '?targetdir=' + urlEncodedPath + '&newFileName=' +  encodeURIComponent(newFileName),
        type: 'GET',
        dataType: 'json',
        success: function(data) {

            if(data.status== 'UNRECOVERABLE') {
               // alertPanelsHide();
                $('.alert-panel-error').removeClass('hide');
                $('.alert-panel-error span').html('Error information: ' + data.statusInfo);
            }
            else if (data.status == 'PROMPT_Overwrite') {
                //alertPanelsHide();
                $('.alert-panel-overwrite').removeClass('hide');
                $('.cover').removeClass('hide');
                $('.revision-restore-dialog').addClass('hide');
            }
            else if (data.status == 'PROMPT_SelectPathAgain') {
                //alertPanelsHide();
                //$('.alert-panel-path-not-exists').removeClass('hide');
                setAlert('The folder you selected does not exist anymore. Please select another folder.');
            }
            else if (data.status == 'PROMPT_FileExistsEnteredByUser') {
                setAlert('This filename already exists. Please enter another.');
                return false;
            }
            else if (data.status == 'PROMPT_PermissionDenied') {
                //alertPanelsHide();
                //$('.alert-panel-path-permission-denied').removeClass('hide')
                setAlert('You do not have enough permissions for the folder you selected. Please select another folder.');
            }
            else if (data.status == 'SUCCESS') {
                //alertPanelsHide();
                window.location.href = '/research/?dir=' + urlEncodedPath;
            }
        },
        error: function(data) {
            //alertPanelsHide();
            //$('.alert-panel-error').removeClass('hide');
            setAlert('Something went wrong. Please check your internet connection');
        }
    });
}

// functions for handling of folder selection - easy point of entry for select-folder functionality from the panels within dataTables
// objectid is the Id of the revision that has to be restored
function showFolderSelectDialog(restorationObjectId, path, orgFileName)
{
    var decodedFileName = decodeURIComponent(orgFileName);

    $('#restoration-objectid').val(restorationObjectId);
    $('#newFileName').val(decodedFileName.replace(/\+/g, ' '));
    //$('#path').html( '<strong>' + path + '</strong>');
    $('#path').html(decodeURIComponent(path).replace(/\+/g, '%20'));
    $('#orgFileName').html('<strong>' + decodedFileName.replace(/\+/g, '%20') + '</strong>');

    //alertPanelsHide();

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
    //alertPanelsHide();
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
    var collection_exists = $('td:eq(0) span', tr).attr('data-collection-exists');

    if ( row.child.isShown() ) {
        // This row is already open - close it
        row.child.hide();
        tr.removeClass('shown');
    }
    else {
        // Open row for panel information

        $.ajax({
            url: 'revision/detail?path=' + path + '&collection_exists=' + collection_exists,
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
