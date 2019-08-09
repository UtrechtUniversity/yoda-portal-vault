$(document).ajaxSend(function(e, request, settings) {
    // Append a CSRF token to all AJAX POST requests.
    if (settings.type === 'POST' && settings.data.length) {
         settings.data
             += '&' + encodeURIComponent(YodaPortal.csrf.tokenName)
              + '=' + encodeURIComponent(YodaPortal.csrf.tokenValue);
    }
});

$( document ).ready(function() {
    if ($('#file-browser').length) {
        startBrowsing(browseStartDir, browsePageItems);
    }

    $('.btn-group button.metadata-form').click(function(){
        showMetadataForm($(this).attr('data-path'));
    });

    $('.btn-group button.upload').click(function(){
        $("#upload").trigger("click");
    });

    $("#upload").change(function() {
        handleUpload($(this).attr('data-path'), this.files);
    });

    $("body").on("click", "a.view-video", function() {
        path = $(this).attr('data-path');
        viewerHtml = '<video width="570" controls autoplay><source src="' + path + '"></video>';
        $('#viewer').html(viewerHtml);
        $('#viewMedia').modal('show');
    });

    $("body").on("click", "a.view-audio", function() {
        path = $(this).attr('data-path');
        viewerHtml = '<audio width="570" controls autoplay><source src="' + path + '"></audio>';
        $('#viewer').html(viewerHtml);
        $('#viewMedia').modal('show');
    });

    $("body").on("click", "a.view-image", function() {
        path = $(this).attr('data-path');
        viewerHtml = '<img width="570" src="' + path + '" />';
        $('#viewer').html(viewerHtml);
        $('#viewMedia').modal('show');
    });

    $("#viewMedia.modal").on("hidden.bs.modal", function() {
        $("#viewer").html("");
    });

    $("body").on("click", "a.action-lock", function() {
        lockFolder($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-unlock", function() {
        unlockFolder($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-submit", function() {
        submitToVault($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-check-for-unpreservable-files", function() {
        // Check for unpreservable file formats.
        // If present, show extensions to user.
        folder = $(this).attr('data-folder');

        // Retrieve preservable file format lists.
        $.getJSON("research/preservableFormatsLists", function (data) {
            if (Object.keys(data.lists).length > 0) {
                lists = data.lists
                $('#file-formats-list').html("<option value='' disabled selected>Select a file format list</option>");
                for (var list in lists) {
                    if (lists.hasOwnProperty(list)) {
                        $("#file-formats-list").append(new Option(lists[list]["name"], list));
                    }
                }
                $('#showUnpreservableFiles .help').hide();
                $('#showUnpreservableFiles .preservable').hide();
                $('#showUnpreservableFiles .advice').hide();
                $('#showUnpreservableFiles .unpreservable').hide();
                $('#showUnpreservableFiles').modal('show');
            } else {
                setMessage('error', "Something went wrong while checking for compliance with policy.");
            }
        });
    });

    $("#file-formats-list").change(function() {
        list = $("#file-formats-list option:selected").val();

        // Retrieve preservable file format lists.
        $.getJSON("research/preservableFormatsLists", function (data) {
            if (Object.keys(data.lists).length > 0) {
                lists = data.lists
                if (lists.hasOwnProperty(list)) {
                        $('#showUnpreservableFiles .help').text(lists[list]["help"]);
                        $('#showUnpreservableFiles .advice').text(lists[list]["advice"]);
                }
            } else {
                setMessage('error', "Something went wrong while checking for compliance with policy.");
            }
        });

        // Retrieve unpreservable files in folder.
        $.getJSON("research/checkForUnpreservableFiles?path=" + folder + "&list=" + list, function (data) {
            if (data.formats) {
                $('#showUnpreservableFiles .help').hide();
                $('#showUnpreservableFiles .preservable').hide();
                $('#showUnpreservableFiles .advice').hide();
                $('#showUnpreservableFiles .unpreservable').hide();
                if(data.formats.length > 0) {
                    $('#showUnpreservableFiles .list-unpreservable-formats').html("");
                    for (var i = 0; i < data.formats.length; i++) {
                        $('#showUnpreservableFiles .list-unpreservable-formats').append("<li>" + htmlEncode(data.formats[i]) + "</li>");
                    }
                    $('#showUnpreservableFiles .help').show();
                    $('#showUnpreservableFiles .advice').show();
                    $('#showUnpreservableFiles .unpreservable').show();
                } else {
                    $('#showUnpreservableFiles .help').show();
                    $('#showUnpreservableFiles .preservable').show();
                }
                $('#showUnpreservableFiles').modal('show');
            } else {
                setMessage('error', "Something went wrong while checking for compliance with policy.");
            }
        });
    });

    $("body").on("click", "a.action-unsubmit", function() {
        unsubmitToVault($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-accept", function() {
        acceptFolder($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-reject", function() {
        rejectFolder($(this).attr('data-folder'));
    });

    $("body").on("click", "i.lock-icon", function() {
        toggleLocksList($(this).attr('data-folder'));
    });

    $("body").on("click", "i.actionlog-icon", function() {
        toggleActionLogList($(this).attr('data-folder'));
    });

    $("body").on("click", "i.system-metadata-icon", function() {
        toggleSystemMetadata($(this).attr('data-folder'));
    });

    $("body").on("click", ".browse", function() {
        browse($(this).attr('data-path'));
    });

    $("body").on("click", "a.action-go-to-vault", function() {
        window.location.href = '/vault/?dir=%2F' +  $(this).attr('vault-path');
    });
});

function browse(dir)
{
    makeBreadcrumb(dir);
    changeBrowserUrl(dir);
    topInformation(dir, true); //only here topInformation should show its alertMessage
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
        "bLengthChange": true,
        "language": {
            "emptyTable": "No accessible files/folders present",
            "lengthMenu": "_MENU_"
        },
        "dom": '<"top">rt<"bottom"lp><"clear">',
        "ajax": {
            url: "browse/data",
            error: function (xhr, error, thrown) {
                $("#file-browser_processing").hide()
                setMessage('error', 'Something went wrong. Please try again or refresh page.');
                return true;
            },
            dataSrc: function (json) {
                jsonString = JSON.stringify(json);
                resp = JSON.parse(jsonString);

                if (resp.status == 'Success' ) {
                    return resp.data;
                } else {
                    setMessage('error', resp.statusInfo);
                    return true;
                }
            }
        },
        "ordering": false,
        "processing": true,
        "serverSide": true,
        "iDeferLoading": 0,
        "pageLength": items,
        "drawCallback": function(settings) {
        }
    });

    if (path.length > 0) {
        browse(path);
    } else {
        browse();
    }
}

function toggleLocksList(folder)
{
    var isVisible = $('.lock-items').is(":visible");

    // toggle locks list
    if (isVisible) {
        $('.lock-items').hide();
    } else {
        // Get locks
        $.getJSON("browse/list_locks?folder=" + folder, function (data) {
            $('.lock-items').hide();

            if (data.status == 'Success') {
                var html = '<li class="list-group-item disabled">Locks:</li>';
                var locks = data.result;
                $.each(locks, function (index, value) {
                    html += '<li class="list-group-item"><span class="browse" data-path="' + encodeURIComponent(value) + '">' + htmlEncode(value) + '</span></li>';
                });
                $('.lock-items').html(html);
                $('.lock-items').show();
            } else {
                setMessage('error', data.statusInfo);
            }

        });
    }
}

function toggleActionLogList(folder)
{
    var actionList = $('.actionlog-items'),
        isVisible = actionList.is(":visible");

    // toggle locks list
    if (isVisible) {
        actionList.hide();
    } else {
        buildActionLog(folder);
    }
}

function buildActionLog(folder)
{
    var actionList = $('.actionlog-items');

    // Get provenance information
    $.getJSON("browse/list_actionlog?folder=" + folder, function (data) {
        actionList.hide();

        if (data.status == 'Success') {
            var html = '<li class="list-group-item disabled">Provenance information:</li>';
            var logItems = data.result;
            if (logItems.length) {
                $.each(logItems, function (index, value) {
                    html += '<li class="list-group-item"><span>'
                         + htmlEncode(value[2])
                         + ' - <strong>'
                         + htmlEncode(value[1])
                         + '</strong> - '
                         + htmlEncode(value[0])
                         + '</span></li>';
                });
            }
            else {
                html += '<li class="list-group-item">No provenance information present</li>';
            }
            actionList.html(html).show();
        } else {
            setMessage('error', data.statusInfo);
        }
    });
}

function toggleSystemMetadata(folder)
{
    var systemMetadata = $('.system-metadata-items');
    var isVisible = systemMetadata.is(":visible");

    // Toggle system metadata.
    if (isVisible) {
        systemMetadata.hide();
    } else {
        // Get locks
        $.getJSON("browse/system_metadata?folder=" + folder, function (data) {
            systemMetadata.hide();

            if (data.status == 'Success') {
                var html = '<li class="list-group-item disabled">System metadata:</li>';
                var logItems = data.result;
                if (logItems.length) {
                    $.each(logItems, function (index, value) {
                        html += '<li class="list-group-item"><span><strong>'
                             + htmlEncode(value[0])
                             + '</strong>: '
                             + value[1]
                             + '</span></li>';
                    });
                }
                else {
                    html += '<li class="list-group-item">No system metadata present</li>';
                }
                systemMetadata.html(html).show();
            } else {
                setMessage('error', data.statusInfo);
            }
        });
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

function topInformation(dir, showAlert)
{
    if (typeof dir != 'undefined') {
        $.getJSON("browse/top_data?dir=" + dir, function(data){

            if (data.status != 'Success' && showAlert) {
                setMessage('error', data.statusInfo);
                return;
            }

            var icon = '<i class="fa fa-folder-o" aria-hidden="true"></i>';
            var metadata = data.result.userMetadata;
            var status = data.result.folderStatus;
            var userType = data.result.userType;
            var hasWriteRights = "yes";
            var hasDatamanager = data.result.hasDatamanager;
            var isDatamanager = data.result.isDatamanager;
            var researchGroupAccess = data.result.researchGroupAccess;
            var inResearchGroup = data.result.inResearchGroup;
            var lockFound = data.result.lockFound;
            var lockCount = data.result.lockCount;
            var vaultPath = data.result.vaultPath;
            var actions = [];

            // User metadata
            if (metadata == 'true') {
                $('.btn-group button.metadata-form').attr('data-path', dir);
                $('.btn-group button.metadata-form').show();
            } else {
                $('.btn-group button.metadata-form').hide();
            }
            $('#upload').attr('data-path', "");

            // folder status (normal folder)
            $('.btn-group button.upload').prop("disabled", true);
            if (typeof status != 'undefined') {
                if (status == '') {
                    actions['lock'] = 'Lock';
                    actions['submit'] = 'Submit';

                    // Enable uploads.
                    $('#upload').attr('data-path', dir);
                    $('.btn-group button.upload').prop("disabled", false);
                } else if (status == 'LOCKED') {
                    actions['unlock'] = 'Unlock';
                    actions['submit'] = 'Submit';
                } else if (status == 'SUBMITTED') {
                    actions['unsubmit'] = 'Unsubmit';
                } else if (status == 'ACCEPTED') {

                } else if (status == 'SECURED') {
                    actions['lock'] = 'Lock';
                    actions['submit'] = 'Submit';

                    // Enable uploads.
                    $('#upload').attr('data-path', dir);
                    $('.btn-group button.upload').prop("disabled", false);
                } else if (status == 'REJECTED') {
                    actions['lock'] = 'Lock';
                    actions['submit'] = 'Submit';
                }

                var icon = '<i class="fa fa-folder-o" aria-hidden="true"></i>';
                $('.btn-group button.folder-status').attr('data-datamanager', isDatamanager);

                $('.top-info-buttons').show();
            } else {
                $('.top-info-buttons').hide();
            }

            if (userType == 'reader') {
                var actions = [];
                hasWriteRights = 'no';
            }

            if (isDatamanager == 'yes') {
                // Check rights as datamanager.
                if (userType != 'manager' && userType != 'normal') {
                    var actions = [];
                    hasWriteRights = 'no';
                }

                if (typeof status != 'undefined') {
                    if (status == 'SUBMITTED') {
                        actions['accept'] = 'Accept';
                        actions['reject'] = 'Reject';
                    }
                }
            }

            // Lock icon
            $('.lock-items').hide();
            var lockIcon = '';
            if (lockCount != '0' && typeof lockCount != 'undefined') {
                lockIcon = '<i class="fa fa-exclamation-circle lock-icon" data-folder="' + dir + '" data-locks="' + lockCount + '" title="' + lockCount + ' lock(s) found" aria-hidden="true"></i>';
            } else {
                lockIcon = '<i class="fa fa-exclamation-circle lock-icon hide" data-folder="' + dir + '" data-locks="0" title="0 lock(s) found" aria-hidden="true"></i>';
            }

            // Provenance action log
            $('.actionlog-items').hide();
            actionLogIcon = ' <i class="fa fa-book actionlog-icon" style="cursor:pointer" data-folder="' + dir + '" aria-hidden="true" title="Provenance action log"></i>';

            // System metadata.
            $('.system-metadata-items').hide();
            systemMetadataIcon = ' <i class="fa fa-info-circle system-metadata-icon" style="cursor:pointer" data-folder="' + dir + '" aria-hidden="true" title="System metadata"></i>';

            $('.btn-group button.folder-status').attr('data-write', hasWriteRights);

            // Add unpreservable files check to actions.
            actions['check-for-unpreservable-files'] = 'Check for compliance with policy';

            // Add go to vault to actions.
            if (typeof vaultPath != 'undefined' ) {
                actions['go-to-vault'] = 'Go to vault';
            }

            // Handle actions
            handleActionsList(actions, dir);

            // Set vault paths.
            if (typeof vaultPath != 'undefined' ) {
                $('a.action-go-to-vault').attr('vault-path', vaultPath);
            }

            // data.basename.replace(/ /g, "&nbsp;")
            folderName = htmlEncode(data.result.basename).replace(/ /g, "&nbsp;");

            // Set status badge.
            statusText = "";
            if (typeof status != 'undefined') {
              if (status == '') {
                  statusText = "";
              } else if (status == 'LOCKED') {
                  statusText = "Locked";
              } else if (status == 'SUBMITTED') {
                  statusText = "Submitted";
              } else if (status == 'ACCEPTED') {
                  statusText = "Accepted";
              } else if (status == 'SECURED') {
                  statusText = "Secured";
              } else if (status == 'REJECTED') {
                  statusText = "Rejected";
              }
            }
            statusBadge = '<span id="statusBadge" class="badge">' + statusText + '</span>';

            // Reset action dropdown.
            $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);

            $('.top-information h1').html('<span class="icon">' + icon + '</span> ' + folderName + lockIcon + systemMetadataIcon + actionLogIcon + statusBadge);
            $('.top-information').show();
        });
    } else {
        $('#upload').attr('data-path', "");
        $('.top-information').hide();
    }
}

function handleActionsList(actions, folder)
{
    var html = '';
    var vaultHtml = '';
    var possibleActions = ['lock', 'unlock',
                           'submit', 'unsubmit',
                        'accept', 'reject'];

    var possibleVaultActions = ['check-for-unpreservable-files',
                                'go-to-vault'];

    $.each(possibleActions, function( index, value ) {
        if (actions.hasOwnProperty(value)) {
            html += '<li><a class="action-' + value + '" data-folder="' + folder + '">' + actions[value] + '</a></li>';
        }
    });

    $.each(possibleVaultActions, function( index, value ) {
        if (actions.hasOwnProperty(value)) {
            vaultHtml += '<li><a class="action-' + value + '" data-folder="' + folder + '">' + actions[value] + '</a></li>';
        }
    });

    if (html != '' && vaultHtml != '') {
        html += '<li class="divider"></li>' + vaultHtml;
    } else if (vaultHtml != '') {
        html += vaultHtml;
    }

    $('.action-list').html(html);
}

function lockFolder(folder)
{
    // Get current button text
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Lock <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    // Change folder status call
    $.post("browse/change_folder_status", {"path" : decodeURIComponent(folder), "status" : "LOCKED"}, function(data) {
        if(data.status == 'Success') {
            // Set actions
            var actions = [];

            if ($('.actionlog-items').is(":visible")) {
                buildActionLog(folder);
            }

            $('#statusBadge').text('Locked');
            actions['unlock'] = 'Unlock';
            actions['submit'] = 'Submit';

            var totalLocks = $('.lock-icon').attr('data-locks');
            if (totalLocks == '0') {
                $('.lock-icon').removeClass('hide');
                $('.lock-icon').attr('data-locks', 1);
                $('.lock-icon').attr('title','1 lock(s) found');
            }
            setMessage('success', 'Successfully locked this folder');

            handleActionsList(actions, folder);
        } else {
            setMessage('error', data.statusInfo);
            $('#statusBadge').html(btnText);
        }
        topInformation(folder, false);
    }, "json");
}

function unlockFolder(folder)
{
    // Get current button text
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Unlock <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    // Change folder status call
    $.post("browse/change_folder_status", {"path" : decodeURIComponent(folder), "status" : "UNLOCKED"}, function(data) {
        if(data.status == 'Success') {
            // Set actions
            var actions = [];

            if ($('.actionlog-items').is(":visible")) {
                buildActionLog(folder);
            }

            $('#statusBadge').text('');
            actions['lock'] = 'Lock';
            actions['submit'] = 'Submit';

            var totalLocks = $('.lock-icon').attr('data-locks');
            if (totalLocks == '1') {
                $('.lock-icon').addClass('hide');
                $('.lock-icon').attr('data-locks', 0);
            }

            // unlocking -> hide lock-items as there are none
            if ($('.lock-items').is(":visible")) {
                $('.lock-items').hide();
            }

            setMessage('success', 'Successfully unlocked this folder');

            handleActionsList(actions, folder);
        } else {
            setMessage('error', data.statusInfo);
            $('#statusBadge').html(btnText);
        }
        topInformation(folder, false);
    }, "json");
}

function showMetadataForm(path)
{
    window.location.href = 'metadata/form?path=' + path;
}

function submitToVault(folder)
{
    if (typeof folder != 'undefined') {
        // Set spinner & disable button
        var btnText = $('#statusBadge').html();
        $('#statusBadge').html('Submit <i class="fa fa-spinner fa-spin fa-fw"></i>');
        $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

        $.post("research/submit", {"path" : decodeURIComponent(folder)}, function(data) {
            if (data.status == 'Success') {
                if (data.folderStatus == 'SUBMITTED') {
                    $('#statusBadge').html('Submitted');
                } else {
                    $('#statusBadge').html('Accepted');
                }

                // lock icon
                var totalLocks = $('.lock-icon').attr('data-locks');
                if (totalLocks == '0') {
                    $('.lock-icon').removeClass('hide');
                    $('.lock-icon').attr('data-locks', 1);
                    $('.lock-icon').attr('title', '1 lock(s) found');
                }
            } else {
                $('#statusBadge').html(btnText);
                setMessage('error', data.statusInfo);
            }
            topInformation(folder, false);
        }, "json");
    }
}

function unsubmitToVault(folder) {
    if (typeof folder != 'undefined') {
        var btnText = $('#statusBadge').html();
        $('#statusBadge').html('Unsubmit <i class="fa fa-spinner fa-spin fa-fw"></i>');
        $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

        $.post("research/unsubmit", {"path" : decodeURIComponent(folder)}, function(data) {
            if (data.status == 'Success') {
                $('#statusBadge').html('');
            } else {
                $('#statusBadge').html(btnText);
                setMessage('error', data.statusInfo);
            }
            topInformation(folder, false);
        }, "json");
    }
}

function acceptFolder(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Accept <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("research/accept", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Accepted');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
          }
          topInformation(folder, false);
      }, "json");
}

function rejectFolder(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Reject <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("research/reject", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Rejected');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
          }
          topInformation(folder, false);
      }, "json");
}

// File uploads.
function handleUpload(path, files) {
    // Check if path is specified.
    if (path == "") {
        return;
    }

    // Check if files are uploaded.
    if (files.length < 1) {
        return;
    }

    var promises = [];
    $('#files').html("");
    $('#uploads').modal('show');

    // Send files one by one.
    for (var i = 0; i < files.length; i++) {
        const file = files[i];

        // Log file upload.
        const timestamp = new Date().getUTCMilliseconds();
        const id = "upload" + timestamp;
        this.logUpload(id, file);

        // Check file size.
        if(file.size > 25*1024*1024) {
            $("#" + id + " .msg").html("Exceeds file limit");
            continue;
        }

        // Send file.
        var promise = sendFile(id, path, file);
        promises.push(promise);
    }

    // Reload file browser if all promises are resolved.
    Promise.all(promises).then(function() {
        browse(path);
    });
}

function sendFile(id, path, file) {
    // Return a new promise.
    return new Promise(function(resolve, reject) {

        const uri = "browse/upload";
        const xhr = new XMLHttpRequest();
        const fd = new FormData();

        xhr.open("POST", uri, true);

        xhr.onloadend = function (e) {
            if (xhr.readyState == 4 && xhr.status == 200) {
                response = JSON.parse(xhr.response);
                if (response.status == "OK") {
                    $("#" + id + " .msg").html("OK");
                    resolve(xhr.response);
                } else {
                    $("#" + id + " .msg").html(response.statusInfo);
                    $("#" + id + " progress").val(0);
                    resolve(xhr.response);
                }
            } else {
                $("#" + id + " .msg").html("FAILED");
                $("#" + id + " progress").val(0);
                resolve(xhr.response);
            }
        }

        xhr.upload.addEventListener('progress', function(e) {
            var percent = parseInt((e.loaded / e.total) * 100);
            $("#" + id + " progress").val(percent);
        });

        fd.append(YodaPortal.csrf.tokenName, YodaPortal.csrf.tokenValue);
        fd.append('filepath', decodeURIComponent(path));
        fd.append('file', file);

        // Initiate a multipart/form-data upload.
        xhr.send(fd);
    });
}

function logUpload(id, file) {
    log = '<div class="row" id="' + id + '">' +
          '<div class="col-md-6" style="word-wrap: break-word;">' + htmlEncode(file.name) + '</div>' +
          '<div class="col-md-3"><progress value="0" max="100"></progress></div>' +
          '<div class="col-md-3 msg"><i class="fa fa-spinner fa-spin fa-fw"></i></div>'
          '</div>';
    $('#files').append(log);
}

function dropHandler(ev) {
    // Prevent default behavior (Prevent file from being opened)
    ev.preventDefault();

    handleUpload($("#upload").attr('data-path'), ev.dataTransfer.files);
}

function dragOverHandler(ev) {
  // Prevent default behavior (Prevent file from being opened)
  ev.preventDefault();
}
