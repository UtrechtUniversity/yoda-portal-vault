$( document ).ready(function() {
    $(".dropdown-menu li a").click(function(){
        searchSelectChanged($(this));
    });

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
    buildFileBrowser(dir);
}

function search(value, type)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
        var url = "browse/search?filter=" + value + "&type=" + type;

        var search = $('#search').DataTable();
        search.ajax.url(url).load();

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