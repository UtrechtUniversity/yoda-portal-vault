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

    $("#viewMedia.modal").on("hidden.bs.modal", function(){
        $("#viewer").html("");
    });

    $("body").on("click", "a.action-check-for-unpreservable-files", function() {
        // Check for unpreservable file formats.
        // If present, show extensions to user.
        folder = $(this).attr('data-folder');

        // Retrieve preservable file format lists.
        $.getJSON("vault/preservableFormatsLists", function (data) {
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
        $.getJSON("vault/preservableFormatsLists", function (data) {
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
        $.getJSON("vault/checkForUnpreservableFiles?path=" + folder + "&list=" + list, function (data) {
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

    $("body").on("click", "a.action-submit-for-publication", function() {
        $('#confirmAgreementConditions .modal-body').text(''); // clear it first

        $('.action-confirm-submit-for-publication').attr( 'data-folder', $(this).attr('data-folder') );

        folder = $(this).attr('data-folder');
        $.getJSON("vault/terms?path=" + folder, function (data) {
            if (data.status == 'Success') {
                $('#confirmAgreementConditions .modal-body').html(data.result);

                // set default status and show dialog
                $(".action-confirm-submit-for-publication").prop('disabled', true);
                $("#confirmAgreementConditions .confirm-conditions").prop('checked', false);

                $('#confirmAgreementConditions').modal('show');
            } else {
                setMessage('error', data.statusInfo);

                return;
            }
        });
    });

    $("#confirmAgreementConditions").on("click", '.confirm-conditions', function() {
        if ($(this).prop('checked')) {
            $("#confirmAgreementConditions .action-confirm-submit-for-publication").prop('disabled', false);;
        }
        else {
            $("#confirmAgreementConditions .action-confirm-submit-for-publication").prop('disabled', true);
        }
    });

    $("#confirmAgreementConditions").on("click", ".action-confirm-submit-for-publication", function() {
        $('#confirmAgreementConditions').modal('hide');
        vaultSubmitForPublication($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-approve-for-publication", function() {
        vaultApproveForPublication($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-cancel-publication", function() {
        vaultCancelPublication($(this).attr('data-folder'));
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

    $("body").on("click", "a.action-grant-vault-access", function() {
        vaultAccess('grant', $(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-revoke-vault-access", function() {
        vaultAccess('revoke', $(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-depublish-publication", function() {
        // Set the current folder.
        $('.action-confirm-depublish-publication').attr( 'data-folder', $(this).attr('data-folder') );
        // Show depublish modal.
        $('#confirmDepublish').modal('show');
    });

    $("#confirmDepublish").on("click", ".action-confirm-depublish-publication", function() {
        $('#confirmDepublish').modal('hide');
        vaultDepublishPublication($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-republish-publication", function() {
        // Set the current folder.
        $('.action-confirm-republish-publication').attr( 'data-folder', $(this).attr('data-folder') );
        // Show depublish modal.
        $('#confirmRepublish').modal('show');
    });

    $("#confirmRepublish").on("click", ".action-confirm-republish-publication", function() {
        $('#confirmRepublish').modal('hide');
        vaultRepublishPublication($(this).attr('data-folder'));
    });

    $("body").on("click", "a.action-go-to-research", function() {
        window.location.href = '/research/?dir=%2F' +  $(this).attr('research-path');
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

                //console.log(resp.draw);
                if (resp.status == 'Success' ) {
                    return resp.data;
                }
                else {
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

function toggleActionLogList(folder)
{
    var actionList = $('.actionlog-items'),
        isVisible = actionList.is(":visible");

    // Toggle provenance log list.
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
        // Get system metadata.
        $.getJSON("browse/system_metadata?folder=" + folder, function(data) {
            systemMetadata.hide();
            if (data) {
                var html = '<li class="list-group-item disabled">System metadata:</li>';

                if (data.result) {
                    $.each(data.result, function (index, value) {
                        html += '<li class="list-group-item"><span><strong>'
                             + htmlEncode(index)
                             + '</strong>: '
                             + htmlEncode(value)
                             + '</span></li>';
                    });
                } else {
                    html += '<li class="list-group-item">No system metadata present</li>';
                }
                systemMetadata.html(html).show();
            } else {
                setMessage('error', 'Could not retrieve system metadata.');
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
            var vaultStatus = data.result.vaultStatus;
            var vaultActionPending = data.result.vaultActionPending;
            var vaultNewStatus = data.result.vaultNewStatus;
            var userType = data.result.userType;
            var hasWriteRights = "yes";
            var hasDatamanager = data.result.hasDatamanager;
            var isDatamanager = data.result.isDatamanager;
            var isVaultPackage = data.result.isVaultPackage;
            var researchGroupAccess = data.result.researchGroupAccess;
            var inResearchGroup = data.result.inResearchGroup;
            var researchPath = data.result.researchPath;
            var actions = [];

            // User metadata
            if (metadata == 'true') {
                $('.btn-group button.metadata-form').attr('data-path', dir);
                $('.btn-group button.metadata-form').show();
            } else {
                $('.btn-group button.metadata-form').hide();
            }

            // is vault package
            if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
                actions['copy-vault-package-to-research'] = 'Copy datapackage to research space';

                // folder status (vault folder)
                if (typeof vaultStatus != 'undefined' && typeof vaultActionPending != 'undefined') {
                    $('.btn-group button.folder-status').attr('data-datamanager', isDatamanager);

                    // Set actions for datamanager and researcher.
                    if (vaultActionPending == 'no') {
                        if (isDatamanager == 'yes') {
                            if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
                                actions['cancel-publication'] = 'Cancel publication';
                                actions['approve-for-publication'] = 'Approve for publication';
                            } else if (vaultStatus == 'UNPUBLISHED' && inResearchGroup  == 'yes') {
                                actions['submit-for-publication'] = 'Submit for publication';
                            } else if (vaultStatus == 'PUBLISHED') {
                                actions['depublish-publication'] = 'Depublish publication';
                            }  else if (vaultStatus == 'DEPUBLISHED') {
                                actions['republish-publication'] = 'Republish publication';
                            }
                        } else if (hasDatamanager == 'yes') {
                            if (vaultStatus == 'UNPUBLISHED') {
                                actions['submit-for-publication'] = 'Submit for publication';
                            } else if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
                                actions['cancel-publication'] = 'Cancel publication';
                            }
                        }
                    }
                }

                // Datamanager sees access buttons in vault.
                $('.top-info-buttons').show();
                if (isDatamanager == 'yes') {
                    if (researchGroupAccess == 'no') {
                        actions['grant-vault-access'] = 'Grant read access to research group';
                    } else {
                        actions['revoke-vault-access'] = 'Revoke read access to research group';
                    }
                }

                // Add unpreservable files check to actions.
                actions['check-for-unpreservable-files'] = 'Check for compliance with policy';
            }

            // Hide buttons in grp-vault groups.
            if (typeof isVaultPackage == 'undefined') {
                $('.top-info-buttons').hide();
            } else {
                $('.top-info-buttons').show();
            }

            // Provenance action log
            $('.actionlog-items').hide();
            actionLogIcon = ' <i class="fa fa-book actionlog-icon" style="cursor:pointer" data-folder="' + dir + '" aria-hidden="true" title="Provenance action log"></i>';
            if (typeof isVaultPackage == 'undefined' || isVaultPackage == 'no') {
                actionLogIcon = '';
            }

            // System metadata.
            $('.system-metadata-items').hide();
            systemMetadataIcon = ' <i class="fa fa-info-circle system-metadata-icon" style="cursor:pointer" data-folder="' + dir + '" aria-hidden="true" title="System metadata"></i>';
            if (typeof isVaultPackage == 'undefined' || isVaultPackage == 'no') {
                systemMetadataIcon = '';
            }

            $('.btn-group button.folder-status').attr('data-write', hasWriteRights);

            // Add go to research to actions.
            if (typeof researchPath != 'undefined' ) {
                actions['go-to-research'] = 'Go to research';
            }

            // Handle actions
            handleActionsList(actions, dir);

            // Set research path.
            if (typeof researchPath != 'undefined' ) {
                $('a.action-go-to-research').attr('research-path', researchPath);
            }

            // data.basename.replace(/ /g, "&nbsp;")
            folderName = htmlEncode(data.result.basename).replace(/ /g, "&nbsp;");

            // Set status badge.
            statusText = "";
            if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
              if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
                  statusText = "Submitted for publication";
              } else if (vaultStatus == 'APPROVED_FOR_PUBLICATION') {
                  statusText = "Approved for publication";
              } else if (vaultStatus == 'PUBLISHED') {
                  statusText = "Published";
              } else if (vaultStatus == 'DEPUBLISHED') {
                  statusText = "Depublished";
              } else if (vaultStatus == 'PENDING_DEPUBLICATION') {
                  statusText = "Depublication pending";
              } else if (vaultStatus == 'PENDING_REPUBLICATION') {
                  statusText = "Republication pending";
              } else {
                  statusText = "Unpublished";
              }
            }
            statusBadge = '<span id="statusBadge" class="badge">' + statusText + '</span>';

            // Reset action dropdown.
            $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);

            $('.top-information h1').html('<span class="icon">' + icon + '</span> ' + folderName + systemMetadataIcon + actionLogIcon + statusBadge);
            $('.top-information').show();
        });
    } else {
        $('.top-information').hide();
    }
}

function handleActionsList(actions, folder)
{
    var html = '';
    var vaultHtml = '';
    var possibleActions = ['submit-for-publication', 'cancel-publication',
                           'approve-for-publication', 'depublish-publication',
                           'republish-publication'];

    var possibleVaultActions = ['grant-vault-access', 'revoke-vault-access',
                                'copy-vault-package-to-research',
                                'check-for-unpreservable-files',
                                'go-to-research'];

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

function showMetadataForm(path)
{
    window.location.href = 'metadata/form?path=' + path;
}

function vaultSubmitForPublication(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Submit for publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("vault/submit_for_publication", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Submitted for publication');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
        }
        topInformation(folder, false);
    }, "json");
}

function vaultApproveForPublication(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Approve for publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("vault/approve_for_publication", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Approved for publication');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
        }
        topInformation(folder, false);
    }, "json");
}

function vaultCancelPublication(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Cancel publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("vault/cancel_publication", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Unpublished');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
        }
        topInformation(folder, false);
    }, "json");
}

function vaultDepublishPublication(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Depublish publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("vault/depublish_publication", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Depublication pending');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
        }
        topInformation(folder, false);
    }, "json");
}

function vaultRepublishPublication(folder)
{
    var btnText = $('#statusBadge').html();
    $('#statusBadge').html('Republish publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("vault/republish_publication", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Republication pending');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
          }
          topInformation(folder, false);
      }, "json");
}

function vaultAccess(action, folder)
{
    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);

    $.post("vault/access", {"path" : decodeURIComponent(folder), "action" : action}, function(data) {
        if (data.status != 'Success') {
            setMessage('error', data.statusInfo);
        }

        topInformation(folder, false);
    }, "json");
}
