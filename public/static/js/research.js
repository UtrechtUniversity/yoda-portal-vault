function browse(dir)
{
    var path = makeBreadcrumb(dir);
    changeBrowserUrl(path);
    buildFileBrowser(dir);
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
