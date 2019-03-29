var urlEncodedPath = '',
    urlEncodedOrigin = '';
    folderSelectBrowser = null;

$( document ).ready(function() {
    $("body").on("click", "a.action-copy-vault-package-to-research", function() {
        dlgShowFolderSelectDialog($(this).attr('data-folder'));
    });

    $('#btn-copy-package').on('click', function(){
        copyVaultPackageToDynamic(urlEncodedOrigin, urlEncodedPath);
    })
});


/// --------------------- Dit moet mogelijk in research.js??
function copyVaultPackageToDynamic(urlEncodedOrigin, urlEncodedTarget)
{
    dlgSelectAlertHide();

    if (typeof urlEncodedOrigin == 'undefined') {
        errorMessage = 'Please select a package from the vault';
        // dlgAlertShow(errorMessage);
        dlgSelectAlertShow(errorMessage);
        return;
    }
    if (typeof urlEncodedTarget == 'undefined') {
        errorMessage = 'The home folder cannot be used for restoration purposes. Please choose another folder';
        //dlgAlertShow(errorMessage);
        dlgSelectAlertShow(errorMessage);
        return;
    }

    if (urlEncodedOrigin.indexOf('%2Fvault-')!=0) {
        dlgSelectAlertShow('Origin must be vault folder. Please choose another folder');
        return;
    }
    //
    // Target CAN NOT be vault folder!
    if (urlEncodedTarget.indexOf('%2Fvault-')==0) {
        dlgSelectAlertShow('Target can not be vault folder. Please select again');
        return;
    }

    $.post( "vault/copyVaultPackageToDynamicArea",
	    { "targetdir" : decodeURIComponent(urlEncodedPath), "orgdir" : decodeURIComponent(urlEncodedOrigin) },
	    function(data) {
        if (data.status == 'SUCCESS') {
            // @todo: Success handling has to become generic.
            // Now it is setup to handle the sitation of copying data datapackge to research .
            window.location.href = '/research/?dir=' + urlEncodedPath;
        } else {
            // Errors handled so far. Maybe differentiate the handling:
            // ErrorDataPackageAlreadyExists
            // ErrorTargetPermissions
            // ErrorTargetLocked
            // ErrorVaultCollectionDoesNotExist
            // irods:
            // PermissionDenied
            dlgSelectAlertShow(data.statusInfo);
        }
    });
}


/// ----------------- Basic functions for Dialog

// functions for handling of folder selection - easy point of entry for select-folder functionality from the panels within dataTables
// objectid is the Id of the revision that has to be restored
function dlgShowFolderSelectDialog(orgPath)
{
    urlEncodedOrigin = orgPath;

    path = ''; //start in root as user is in Vault now
    startBrowsingFolderSelect(path, browseDlgPageItems);

    // initialisation of alerts/warning thins -> to be taken out
    $('.mode-dlg-locked').addClass('hide');
    $('.mode-dlg-exists').addClass('hide');
    $('.alert-panel-overwrite').addClass('hide');
    $('.cover').addClass('hide');
    $('.revision-restore-dialog').removeClass('hide');

    $('#dlg-select-folder').modal('show');
}

/// alert handling
function dlgSelectAlertShow(errorMessage)
{
    $('#dlg-select-alert-panel').removeClass('hide');
    $('#dlg-select-alert-panel span').html(errorMessage);
}

function dlgSelectAlertHide()
{
    $('#dlg-select-alert-panel').addClass('hide');
}

// dialog handling
function startBrowsingFolderSelect(path, items)
{
    if (!folderSelectBrowser) {
        folderSelectBrowser = $('#folder-select-browser').DataTable({
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            "ajax": "browse/data",
            "processing": true,
            "serverSide": true,
            "iDeferLoading": 0,
            "ordering": false,
            "pageLength": browseDlgPageItems,
            "drawCallback": function (settings) {
                $(".browse-select").on("click", function (event) { // differentiation from main browser screen
                    //alert('asd');

                    dlgBrowse($(this).attr('data-path'));
                });
            }
        });
    }
    if (path.length > 0) {
        dlgBrowse(path);
    } else {
        dlgBrowse();
    }
}

function dlgBrowse(dir)
{
    dlgSelectAlertHide();

    dlgMakeBreadcrumb(dir);

    dlgChangeBrowserUrl(dir);

    dlgBuildFileBrowser(dir);
}


function dlgChangeBrowserUrl(path)
{
    urlEncodedPath = path;
}

function dlgBuildFileBrowser(dir)
{
    var url = "browse/selectData/collections/org_lock_protect";
    if (typeof dir != 'undefined') {
        url += "?dir=" +  dir;
    }

    folderSelectBrowser.ajax.url(url).load();

    return true;
}

function dlgMakeBreadcrumb(urlEncodedDir)
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
        var html = '<li class="browse-select">Home</li>'; // HdR added to differentiate from main browser and avoid collisions
        var path = "";
        $.each( parts, function( k, part ) {
            path += "%2F" + encodeURIComponent(part);

            // Active item
            valueString = htmlEncode(part).replace(/ /g, "&nbsp;");
            if (k == (totalParts-1)) {
                html += '<li class="active">' + valueString + '</li>';
            } else {
                html += '<li class="browse-select" data-path="' + path + '">' + valueString + '</li>'; // HdR added to differentiate from main browser and avoid collisions
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
