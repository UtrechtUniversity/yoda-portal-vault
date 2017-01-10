$( document ).ready(function() {
    $(".dropdown-menu li a").click(function(){
        searchSelectChanged($(this));
    });

    $('.btn-group button.information-type').click(function(){
        toggleButtonType($(this).attr('data-type'), $(this).attr('data-path'));
    });

    $('.btn-group button.information-type')

    $(".search-btn").click(function(){
        search($("#search-filter").val(), $("#search_concept").attr('data-type'));
    });

    $("#search-filter").bind('keypress', function(e) {
        if(e.keyCode==13) {
            search($("#search-filter").val(), $("#search_concept").attr('data-type'));
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

function search(value, type)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
        // Table columns definition
        var columns = [];
        if (type == 'filename') {
            columns = ['Name', 'Location'];
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
            "pageLength": 10,
            "drawCallback": function(settings) {
                $( ".browse" ).on( "click", function() {
                    browse($(this).attr('data-path'));
                });
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

            if (type == 'Folder' || typeof type == 'undefined') {
                icon = "fa-folder-o";

                // Folder toggle btn
                $('.btn-group button.information-type').html('<i class="fa fa-folder-o" aria-hidden="true"></i> Is folder');
                $('.btn-group button.information-type').attr('data-type', 'folder');
                $('.btn-group button.information-type').attr('data-path', dir);

            } else if (type == 'Datapackage') {
                icon = "fa-folder";

                // Datapackage toggle btn
                $('.btn-group button.information-type').html('<i class="fa fa-folder" aria-hidden="true"></i> Is datapackage');
                $('.btn-group button.information-type').attr('data-type', 'datapackage');
                $('.btn-group button.information-type').attr('data-path', dir);
            } else if (type == "Research Team") {
                icon = "fa-users";
            }

            $('.top-information h1').html('<i class="fa '+ icon +'" aria-hidden="true"></i> ' + data.basename);
            $('.top-information').show();
        });
    }
}

function toggleButton(currentType, path)
{
    
}