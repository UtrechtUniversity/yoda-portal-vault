$( document ).ready(function() {
    if ($('#file-browser').length) {
        startBrowsing(browseStartDir, browsePageItems);

        // Rememeber search results
        if (searchTerm.length > 0) {
            search(decodeURIComponent(searchTerm), searchType, browsePageItems, searchStart, searchOrderDir, searchOrderColumn);
        } else if (searchStatusValue.length > 0) {
            search(searchStatusValue, 'status', browsePageItems, searchStart, searchOrderDir, searchOrderColumn);
        }

    }

    $(".dropdown-menu li a").click(function(){
        searchSelectChanged($(this));
    });

    $('.btn-group button.metadata-form').click(function(){
        showMetadataForm($(this).attr('data-path'));
    });

    $('.btn-group button.folder-status').click(function() {
        if ($(this).attr('data-status') == 'SUBMITTED') {
            alert('Functionality to be developed in coming sprints.');
        } else {
            toggleFolderStatus($(this).attr('data-status'), $(this).attr('data-path'));
        }
    });

    $(".search-btn").click(function(){
        search($("#search-filter").val(), $("#search_concept").attr('data-type'), $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
    });

    $("#search-filter").bind('keypress', function(e) {
        if(e.keyCode==13) {
            search($("#search-filter").val(), $("#search_concept").attr('data-type'), $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
        }
    });

    $( ".search-status input:radio" ).change(function() {
        search($(this).val(), 'status', $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
    });

    $(".close-search-results").click(function() {
        closeSearchResults();
    });

});

function browse(dir)
{
    makeBreadcrumb(dir);

    changeBrowserUrl(dir);
    topInformation(dir);
    buildFileBrowser(dir);
}

function search(value, type, itemsPerPage, displayStart, searchOrderDir, searchOrderColumn)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
        // Display start for first page load
        if (typeof displayStart === 'undefined') {
            displayStart = 0;
        }

        // Find revision
        if (type == 'revision') {
            $('#search').hide();
            $('.search-results').hide();
            window.location.href = "revision?filter=" + encodeURIComponent(value);
            return false;
        }

        // Table columns definition
        var disableSorting = {};
        var columns = [];
        if (type == 'filename') {
            columns = ['Name', 'Location'];
        } else if (type == 'metadata') {
            columns = ['Location', 'Matches'];
            disableSorting = { 'bSortable': false, 'aTargets': [ -1 ] };
        } else {
            columns = ['Location'];
        }

        // Destroy current Datatable
        var datatable = $('#search').DataTable();
        datatable.destroy();

        var tableHeaders = '';
        $.each(columns, function(i, val){
            tableHeaders += "<th>" + val + "</th>";
        });

        // Create the columns
        $('#search thead tr').html(tableHeaders);

        // Remove table content
        $('#search tbody').remove();


        // Initialize new Datatable
        var url = "browse/search?filter=" + encodeURIComponent(value) + "&type=" + type;
        $('#search').DataTable( {
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            "ajax": {
                "url": url,
                "jsonp": false
            },
            "processing": true,
            "serverSide": true,
            "pageLength": browsePageItems,
            "displayStart": displayStart,
            "drawCallback": function(settings) {
                $( ".browse-search" ).on( "click", function() {
                    browse($(this).attr('data-path'));
                });


                $('.matches').tooltip();
            },
            "aoColumnDefs": [
                disableSorting
            ],
            "order": [[ searchOrderColumn, searchOrderDir ]]
        });


        if (type == 'status') {
            value = value.toLowerCase();
            $('.search-string').text(value.substr(0,1).toUpperCase() + value.substr(1));
        } else {
            $('.search-string').html( htmlEncode(value).replace(/ /g, "&nbsp;") );

            // uncheck all status values
            $( ".search-status input:radio" ).prop('checked', false);
        }
        showSearchResults();
    }

    return true;
}

function closeSearchResults()
{
    $('.search-results').hide();
    $('#search-filter').val('');
    $(".search-status input:radio").prop('checked', false);
    $.get("browse/unset_search");
}

function showSearchResults()
{
    $('.search-results').show();
}

function searchSelectChanged(sel)
{
    $("#search_concept").html(sel.text());
    $("#search_concept").attr('data-type', sel.attr('data-type'));

    if (sel.attr('data-type') == 'status') {
        $('.search-term').hide();
        $('.search-status').removeClass('hide').show();
    } else {
        $('.search-term').removeClass('hide').show();
        $('.search-status').hide();
    }
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

    $('ol.breadcrumb').html(html);
}

function htmlEncode(value){
    //create a in-memory div, set it's inner text(which jQuery automatically encodes)
    //then grab the encoded contents back out.  The div never exists on the page.
    return $('<div/>').text(value).html();
}



function makeBreadcrumbPath(dir)
{
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
    if (totalParts > 0) {
        var path = "";
        var index = 0;
        $.each( parts, function( k, part ) {

            if(index) {
                path += "/" + part;
            }
            else {
                path = part;
            }
            index++;
        });
    }

    return path;
}


function buildFileBrowser(dir)
{
    var url = "browse/data";
    if (typeof dir != 'undefined') {
        url += "?dir=" +  dir;
    }

    var fileBrowser = $('#file-browser').DataTable();

    fileBrowser.ajax.url(url).load();

    return true;
}

function startBrowsing(path, items)
{
    $('#file-browser').DataTable( {
        "bFilter": false,
        "bInfo": false,
        "bLengthChange": false,
        "ajax": "browse/data",
        "processing": true,
        "serverSide": true,
        "iDeferLoading": 0,
        "pageLength": items,
        "drawCallback": function(settings) {
            $( ".browse" ).on( "click", function() {
                browse($(this).attr('data-path'));
            });
        }
    });

    if (path.length > 0) {
        browse(path);
    } else {
        browse();
    }


}

function changeBrowserUrl(path)
{

    var url = window.location.pathname;
    if (typeof path != 'undefined') {
        url += "?dir=" +  path;
    }

    history.replaceState({} , {}, url);
}

function topInformation(dir)
{
    $('.top-information').hide();
    if (typeof dir != 'undefined') {
        $.getJSON("browse/top_data?dir=" + dir, function(data){
            var icon = '<i class="fa fa-folder-o" aria-hidden="true"></i>';
            var metadata = data.userMetadata;
            var status = data.folderStatus;
            var userType = data.userType;
            var showStatusBtn = false;

            // User metadata
            if (metadata == 'true') {
                $('.btn-group button.metadata-form').attr('data-path', dir);
                $('.btn-group button.metadata-form').show();
            } else {
                $('.btn-group button.metadata-form').hide();
            }

            // folder status
            if (typeof status != 'undefined') {
                if (status == 'UNPROTECTED') {
                    $('.btn-group button.folder-status').text('Unprotected');
                    $('.btn-group button.folder-status').attr('data-status', 'UNPROTECTED');
                } else if (status == 'SUBMITTED') {
                    $('.btn-group button.folder-status').text('Submitted');
                    $('.btn-group button.folder-status').attr('data-status', 'SUBMITTED');

                    icon = '<span class="fa-stack"><i class="fa fa-folder-o fa-stack-2x"></i><i class="fa fa-shield fa-stack-1x"></i></span>';
                } else {
                    $('.btn-group button.folder-status').text('Protected');
                    $('.btn-group button.folder-status').attr('data-status', 'PROTECTED');

                    icon = '<span class="fa-stack"><i class="fa fa-folder-o fa-stack-2x"></i><i class="fa fa-shield fa-stack-1x"></i></span>';
                }
                $('.btn-group button.folder-status').attr('data-path', dir);
            }

            // Lock position check
            var lockFound = data.lockFound;
            var path = data.path;
            if (lockFound != "no") {
                if (lockFound == "here") {
                    showStatusBtn = true;
                } else {
		    // Lock is either on descendant or ancestor Folder
		    showStatusBtn = false;
		}
            } else {
                // No lock found, show the btn.
                showStatusBtn = true;
            }

            if (userType != 'normal' && userType != "manager") {
                // Hide folder status button for read permission
                showStatusBtn = false;
            }

            // Handle status btn
            if (showStatusBtn) {
                $('.btn-group button.folder-status').show();
            } else {
                $('.btn-group button.folder-status').hide();
            }

            // data.basename.replace(/ /g, "&nbsp;")
            folderName = htmlEncode(data.basename).replace(/ /g, "&nbsp;");

            $('.top-information h1').html('<span class="icon">' + icon + '</span> ' + folderName);
            $('.top-information').show();
        });
    }
}

function toggleFolderStatus(currentStatus, path)
{
    // Get current button text
    var btnText = $('.btn-group button.folder-status').html();

    // Set spinner & disable button
    $('.btn-group button.folder-status').html(btnText + '<i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);

    if (currentStatus == 'PROTECTED') {
        var newStatus = 'UNPROTECTED';
    } else {
        var newStatus = 'PROTECTED';
    }

    // Change folder status call
    $.getJSON("browse/change_folder_status?path=" + path + "&status=" + newStatus, function(data) {
        if (data.status == 'PROTECTED') {
            $('.btn-group button.folder-status').text('Protected');
            $('.btn-group button.folder-status').attr('data-status', 'PROTECTED');
            var icon = '<span class="fa-stack"><i class="fa fa-folder-o fa-stack-2x"></i><i class="fa fa-shield fa-stack-1x"></i></span>';
        } else {
            $('.btn-group button.folder-status').text('Unprotected');
            $('.btn-group button.folder-status').attr('data-status', 'UNPROTECTED');
            var icon = '<i class="fa fa-folder-o" aria-hidden="true"></i>';
        }

        // Change icon
        $('.top-information h1 .icon').empty().html(icon);

        // Remove disable attribute
        $('.btn-group button.folder-status').removeAttr("disabled");
    });
}

function showMetadataForm(path)
{
    window.location.href = 'metadata/form?path=' + path;
}
