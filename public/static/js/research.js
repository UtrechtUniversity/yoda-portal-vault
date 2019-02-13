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

        $.getJSON("vault/checkForUnpreservableFiles?path=" + folder, function (data) {
            if (data.status == 'Success') {
                $('#showUnpreservableFiles .unpreservable').hide();
                $('#showUnpreservableFiles .preservable').hide();
                if(data.result) {
                    $('#showUnpreservableFiles .list-unpreservable-formats').html(data.result);
                    $('#showUnpreservableFiles .unpreservable').show();
                } else {
                    $('#showUnpreservableFiles .preservable').show();
                }
                $('#showUnpreservableFiles').modal('show');
            } else {
                setMessage('error', data.statusInfo);
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
        "bLengthChange": false,
        "language": {
            "emptyTable": "No accessible files/folders present"
  },
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
            var lockFound = data.result.lockFound;
            var lockCount = data.result.lockCount;
            var actions = [];

            // User metadata
            if (metadata == 'true') {
                $('.btn-group button.metadata-form').attr('data-path', dir);
                $('.btn-group button.metadata-form').show();
            } else {
                $('.btn-group button.metadata-form').hide();
            }

            // folder status (normal folder)
            if (typeof status != 'undefined' && typeof isVaultPackage == 'undefined') {
                // reset action dropdown.
                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);

                if (status == '') {
                    actions['lock'] = 'Lock';
                    actions['submit'] = 'Submit';
                } else if (status == 'LOCKED') {
                    actions['unlock'] = 'Unlock';
                    actions['submit'] = 'Submit';
                } else if (status == 'SUBMITTED') {
                    actions['unsubmit'] = 'Unsubmit';
                } else if (status == 'ACCEPTED') {
                    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);
                } else if (status == 'SECURED') {
                    // Check for locks is here for backwards compatibility with release v1.2.
                    if (lockFound == "here") {
                        actions['lock'] = 'Lock';
                    } else {
                        actions['unlock'] = 'Unlock';
                    }
                    actions['submit'] = 'Submit';
                } else if (status == 'REJECTED') {
                    // Check for locks is here for backwards compatibility with release v1.2.
                    if (lockFound == "here") {
                        actions['lock'] = 'Lock';
                    } else {
                        actions['unlock'] = 'Unlock';
                    }
                    actions['submit'] = 'Submit';
                }

                var icon = '<i class="fa fa-folder-o" aria-hidden="true"></i>';
                $('.btn-group button.folder-status').attr('data-datamanager', isDatamanager);

                $('.top-info-buttons').show();
            } else {
                $('.top-info-buttons').hide();
            }

            if (userType == 'reader') {
                // Disable status dropdown.
                $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);
                hasWriteRights = 'no';
            }

            if (isDatamanager == 'yes') {
                // Check rights as datamanager.
                if (userType != 'manager' && userType != 'normal') {
                    // Disable status dropdown.
                    var actions = [];
                    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);
                    hasWriteRights = 'no';
                }

                if (typeof status != 'undefined') {
                    if (status == 'SUBMITTED') {
                        actions['accept'] = 'Accept';
                        actions['reject'] = 'Reject';
                        $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                    }
                }

                // is vault package
                if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
                    $('.top-info-buttons').show();
                }
            }

            // is vault package
            if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
                actions['copy-vault-package-to-research'] = 'Copy datapackage to research space';

                // folder status (vault folder)
                if (typeof vaultStatus != 'undefined' && typeof vaultActionPending != 'undefined') {
                    $('.btn-group button.folder-status').prop("disabled", true).next().prop("disabled", true);
                    $('.btn-group button.folder-status').attr('data-datamanager', isDatamanager);

                    // Set actions for datamanager and researcher.
                    if (vaultActionPending == 'no') {
                        if (isDatamanager == 'yes') {
                            if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
                                actions['cancel-publication'] = 'Cancel publication';
                                actions['approve-for-publication'] = 'Approve for publication';
                                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                            } else if (vaultStatus == 'UNPUBLISHED' && inResearchGroup  == 'yes') {
                                actions['submit-for-publication'] = 'Submit for publication';
                                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                            } else if (vaultStatus == 'PUBLISHED') {
                                actions['depublish-publication'] = 'Depublish publication';
                                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                            }  else if (vaultStatus == 'DEPUBLISHED') {
                                actions['republish-publication'] = 'Republish publication';
                                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                            }
                        } else if (hasDatamanager == 'yes') {
                            if (vaultStatus == 'UNPUBLISHED') {
                                actions['submit-for-publication'] = 'Submit for publication';
                                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                            } else if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
                                actions['cancel-publication'] = 'Cancel publication';
                                $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                            }
                        }
                    }
                }

                // Datamanager sees access buttons in vault.
                $('.top-info-buttons').show();
                if (isDatamanager == 'yes') {
                    if (researchGroupAccess == 'no') {
                        actions['grant-vault-access'] = 'Grant read access to research group';
                        $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
                    } else {
                        actions['revoke-vault-access'] = 'Revoke read access to research group';
                        $('.btn-group button.folder-status').prop("disabled", false).next().prop("disabled", false);
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
            if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'no') {
                actionLogIcon = '';
            }

            // System metadata.
            $('.system-metadata-items').hide();
            systemMetadataIcon = ' <i class="fa fa-info-circle system-metadata-icon" style="cursor:pointer" data-folder="' + dir + '" aria-hidden="true" title="System metadata"></i>';
            if (typeof isVaultPackage == 'undefined' || isVaultPackage == 'no') {
                systemMetadataIcon = '';
            }

            $('.btn-group button.folder-status').attr('data-write', hasWriteRights);

            // Add unpreservable files check to actions.
            actions['check-for-unpreservable-files'] = 'Check for unpreservable files';

            // Handle actions
            handleActionsList(actions, dir);

            // data.basename.replace(/ /g, "&nbsp;")
            folderName = htmlEncode(data.result.basename).replace(/ /g, "&nbsp;");

            // Set status badge.
            statusText = "";
            if (typeof status != 'undefined' && typeof isVaultPackage == 'undefined') {
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
            } else if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
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

            $('.top-information h1').html('<span class="icon">' + icon + '</span> ' + folderName + lockIcon + systemMetadataIcon + actionLogIcon + statusBadge);
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
    var possibleActions = ['lock', 'unlock',
                           'submit', 'unsubmit', 'accept', 'reject',
                           'submit-for-publication', 'cancel-publication',
                           'approve-for-publication', 'depublish-publication',
                           'republish-publication'];

    var possibleVaultActions = ['grant-vault-access', 'revoke-vault-access',
                                'copy-vault-package-to-research',
                                'check-for-unpreservable-files'];

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

        $.post("vault/submit", {"path" : decodeURIComponent(folder)}, function(data) {
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

        $.post("vault/unsubmit", {"path" : decodeURIComponent(folder)}, function(data) {
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

    $.post("vault/accept", {"path" : decodeURIComponent(folder)}, function(data) {
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

    $.post("vault/reject", {"path" : decodeURIComponent(folder)}, function(data) {
        if (data.status == 'Success') {
            $('#statusBadge').html('Rejected');
        } else {
            $('#statusBadge').html(btnText);
            setMessage('error', data.statusInfo);
          }
          topInformation(folder, false);
      }, "json");
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
