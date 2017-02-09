$( document ).ready(function() {
    if ($('#file-browser').length) {
        startBrowsing(browseStartDir, browsePageItems);
    }

    $(".dropdown-menu li a").click(function(){
        searchSelectChanged($(this));
    });

    $('.btn-group button.directory-type').click(function(){
        toggleDirectoryType($(this).attr('data-type'), $(this).attr('data-path'));
    });

    $('.btn-group button.metadata-form').click(function(){
        showMetadataForm($(this).attr('data-path'));
    });

    $(".search-btn").click(function(){
        search($("#search-filter").val(), $("#search_concept").attr('data-type'), $(".search-btn").attr('data-items-per-page'));
    });

    $("#search-filter").bind('keypress', function(e) {
        if(e.keyCode==13) {
            search($("#search-filter").val(), $("#search_concept").attr('data-type'), $(".search-btn").attr('data-items-per-page'));
        }
    });

    $(".close-search-results").click(function() {
        closeSearchResults();
    });

});

function browse(dir)
{
    var path = makeBreadcrumb(dir);
    changeBrowserUrl(path);
    topInformation(dir);
    buildFileBrowser(dir);
}

function search(value, type, itemsPerPage)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
        // Table columns definition
        var columns = [];
        if (type == 'filename') {
            columns = ['Name', 'Location'];
        } else if (type == 'metadata') {
                columns = ['Location', 'Matches'];
        } else {
            columns = ['Location'];
        }

        var tableHeaders;
        $.each(columns, function(i, val){
            tableHeaders += "<th>" + val + "</th>";
        });

        // Destroy current Datatable
        var datatable = $('#search').DataTable();
        datatable.destroy();

        // Create the columns
        $('#search thead tr').html(tableHeaders);

        // Initialize new Datatable
        var url = "browse/search?filter=" + value + "&type=" + type;
        $('#search').DataTable( {
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            "ajax": url,
            "processing": true,
            "serverSide": true,
            "pageLength": browsePageItems,
            "drawCallback": function(settings) {
                $( ".browse" ).on( "click", function() {
                    browse($(this).attr('data-path'));
                });

                $('.matches').tooltip();
            }
        });

        $('.search-string').text(value);
        showSearchResults();
    }

    return true;
}

function closeSearchResults()
{
    $('.search-results').hide();
}

function showSearchResults()
{
    $('.search-results').show();
}

function searchSelectChanged(sel)
{
    $("#search_concept").html(sel.text());
    $("#search_concept").attr('data-type', sel.attr('data-type'));
}

function makeBreadcrumb(dir)
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
        var html = '<li class="browse">Home</li>';
        var path = "";
        $.each( parts, function( k, part ) {
            path += "/" + part;

            // Active item
            if (k == (totalParts-1)) {
                html += '<li class="active">' + part + '</li>';
            } else {
                html += '<li class="browse" data-path="' + path + '">' + part + '</li>';
            }
        });
    } else {
        var html = '<li class="active">Home</li>';
    }

    $('ol.breadcrumb').html(html);

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
            var type = data.org_type;
            var icon = "fa-folder-o";
            var metadata = data.user_metadata;

            if (type == 'Folder' || typeof type == 'undefined') {
                icon = "fa-folder-o";

                // Folder toggle btn
                $('.btn-group button.directory-type').html('<i class="fa fa-folder-o" aria-hidden="true"></i> Is folder');
                $('.btn-group button.directory-type').attr('data-type', 'folder');
                $('.btn-group button.directory-type').attr('data-path', dir);

            } else if (type == 'Datapackage') {
                icon = "fa-folder";

                // Datapackage toggle btn
                $('.btn-group button.directory-type').html('<i class="fa fa-folder" aria-hidden="true"></i> Is datapackage');
                $('.btn-group button.directory-type').attr('data-type', 'datapackage');
                $('.btn-group button.directory-type').attr('data-path', dir);
            } else if (type == "Research Team") {
                icon = "fa-users";
            }

            // User metadata
            if (metadata == 'true') {
                $('.btn-group button.metadata-form').attr('data-path', dir);
                $('.btn-group button.metadata-form').show();
            } else {
                $('.btn-group button.metadata-form').hide();
            }

            $('.top-information h1').html('<i class="fa '+ icon +'" aria-hidden="true"></i> ' + data.basename);
            $('.top-information').show();
        });
    }
}

function toggleDirectoryType(currentType, path)
{
    //
    var btnText = $('.btn-group button.directory-type').html();

    $('.btn-group button.directory-type').html(btnText + '<i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.directory-type').prop("disabled", true);

    if (currentType == 'folder') {
        var newType = 'datapackage';
    } else {
        var newType = 'folder';
    }

    $.getJSON("browse/change_directory_type?path=" + path + "&type=" + newType, function(data){
        if (data.type == 'folder') {
            $('.btn-group button.directory-type').html('<i class="fa fa-folder-o" aria-hidden="true"></i> Is folder');
            $('.btn-group button.directory-type').attr('data-type', 'folder');

            // Title
            $('.top-information h1 i').removeClass("fa-folder").addClass("fa-folder-o");
        } else {
            $('.btn-group button.directory-type').html('<i class="fa fa-folder" aria-hidden="true"></i> Is datapackage');
            $('.btn-group button.directory-type').attr('data-type', 'datapackage');

            // Title
            $('.top-information h1 i').removeClass("fa-folder-o").addClass("fa-folder");
        }

        buildFileBrowser(path);
        
        $('.btn-group button.directory-type').removeAttr("disabled");
    });
}

function showMetadataForm(path)
{
    window.location.href = 'metadata/form?path=' + path;
}