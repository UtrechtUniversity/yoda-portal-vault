$( document ).ready(function() {
    if ($('#file-browser').length) {
        startBrowsing(browseStartDir, browsePageItems);
    }

    $('.btn-group button.metadata-form').click(function(){
        showMetadataForm($(this).attr('data-path'));
    });

    $('.btn-group button.toggle-folder-status').click(function() {
        if ($(this).attr('data-status') == 'SUBMITTED') {
            alert('Functionality to be developed in coming sprints.');
        } else {
            toggleFolderStatus($(this).attr('data-status'), $(this).attr('data-path'));
        }
    });

    $("body").on("click", "a.action-submit", function() {
        submitToVault($(this).attr('data-folder'));
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

    $("body").on("click", "button.vault-access", function() {
        vaultAccess($(this).attr('data-access'), $(this).attr('data-path'));
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
        "ajax": {
            url: "browse/data",
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
                    html += '<li class="list-group-item"><span class="browse" data-path="' + encodeURIComponent(value) + '">' + value + '</span></li>';
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
                    html += '<li class="list-group-item"><span>' + value[2] + ' - <strong>' + value[1] + '</strong> - ' + value[0] + '</span></li>';
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
                        html += '<li class="list-group-item"><span><strong>' + value[0] + '</strong>: ' + value[1] + '</span></li>';
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
    $('.top-information').hide();
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
            var lockCount = data.result.lockCount;
            var showStatusBtn = false;
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
                $('.btn-group button.folder-status').next().prop("disabled", false);

                if (status == '') {
                    $('.btn-group button.toggle-folder-status').text('Lock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'LOCKED');
                    $('.btn-group button.folder-status').text('Actions');
                    actions['submit'] = 'Submit';
                } else if (status == 'LOCKED') {
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.folder-status').text('Locked');
                    actions['submit'] = 'Submit';
                } else if (status == 'SUBMITTED') {
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.folder-status').text('Submitted');
                    actions['unsubmit'] = 'Unsubmit';
                } else if (status == 'ACCEPTED') {
                    $('.btn-group button.folder-status').text('Accepted');
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.folder-status').next().prop("disabled", true);
                } else if (status == 'SECURED') {
                    $('.btn-group button.folder-status').text('Secured');
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.folder-status').next().prop("disabled", true);
                } else if (status == 'REJECTED') {
                    $('.btn-group button.folder-status').text('Rejected');
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.folder-status').next().prop("disabled", true);
                }
                var icon = '<i class="fa fa-folder-o" aria-hidden="true"></i>';
                $('.btn-group button.toggle-folder-status').attr('data-path', dir);
                $('.btn-group button.folder-status').attr('data-datamanager', isDatamanager);

                $('.top-info-buttons').show();
                $('.top-info-buttons .research').show();
                $('.top-info-buttons .vault').hide();
            } else {
                $('.top-info-buttons').hide();
            }

            // Lock position check
            var lockFound = data.result.lockFound;
            var path = data.result.path;
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

            if (userType == 'reader') {
                // Hide folder status button for read permission
                showStatusBtn = false;
                // disable status dropdown.
                $('.btn-group button.folder-status').next().prop("disabled", true);
                hasWriteRights = 'no';
            }

            if (isDatamanager == 'yes') {
                // Check rights as datamanager.
                if (userType != 'manager' && userType != 'normal') {
                    // Hide folder status button for read permission
                    showStatusBtn = false;
                    // disable status dropdown.
                    var actions = [];
                    $('.btn-group button.folder-status').next().prop("disabled", true);
                    hasWriteRights = 'no';
                }

                if (typeof status != 'undefined') {
                    if (status == 'SUBMITTED') {
                        actions['accept'] = 'Accept';
                        actions['reject'] = 'Reject';
                        $('.btn-group button.folder-status').next().prop("disabled", false);
                    }
                }

                // is vault package
                if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
                    $('button.vault-access').attr('data-path', dir);
                    if (researchGroupAccess == 'no') {
                        $('button.vault-access').text('Grant read access to research group');
                        $('button.vault-access').attr('data-access', 'grant');
                    } else {
                        $('button.vault-access').text('Revoke read access to research group');
                        $('button.vault-access').attr('data-access', 'revoke');
                    }

                    $('.btn-group button.metadata-form').attr('data-path', dir);
                    $('.btn-group button.metadata-form').show();

                    //
                    $('.top-info-buttons').show();
                    $('.top-info-buttons .research').hide();
                    $('.top-info-buttons .vault').show();
                }
            }

	    // is vault package
            if (typeof isVaultPackage != 'undefined' && isVaultPackage == 'yes') {
                // handling of copying cabability from vault to dynamic space @todo -> deze plek kan anders volgens mij
                $('.btn-group button.copy-vault-package-to-research').attr('data-path', dir);
                $('.btn-group button.copy-vault-package-to-research').show();


                // explicitely hide top info buttons related to research - this wasn't always the case
                $('.top-info-buttons .research').hide();
                // folder status (vault folder)
                if (typeof vaultStatus != 'undefined' && typeof vaultActionPending != 'undefined') {
                    $('.btn-group button.folder-status').next().prop("disabled", true);
                    $('.btn-group button.folder-status').attr('data-datamanager', isDatamanager);

                    if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
                        $('.btn-group button.folder-status').text('Submitted for publication');
                    } else if (vaultStatus == 'APPROVED_FOR_PUBLICATION') {
                        $('.btn-group button.folder-status').text('Approved for publication');
                    } else if (vaultStatus == 'PUBLISHED') {
                        $('.btn-group button.folder-status').text('Published');
                    } else if (vaultStatus == 'DEPUBLISHED') {
                        $('.btn-group button.folder-status').text('Depublished');
                    } else {
                        $('.btn-group button.folder-status').text('Unpublished');
                    }

                    // Set actions for datamanager and researcher.
		            if (vaultActionPending == 'no') {
			            $('label.folder-status-pending').hide();

			            if (isDatamanager == 'yes') {
			                if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
				                actions['cancel-publication'] = 'Cancel publication';
				                actions['approve-for-publication'] = 'Approve for publication';
				                $('.btn-group button.folder-status').next().prop("disabled", false);
                            } else if (vaultStatus == 'UNPUBLISHED' && inResearchGroup  == 'yes') {
				                actions['submit-for-publication'] = 'Submit for publication';
				                $('.btn-group button.folder-status').next().prop("disabled", false);
			                } else if (vaultStatus == 'APPROVED_FOR_PUBLICATION') {
                                // Show depublish button.
                                actions['depublish-publication'] = 'Depublish publication';
                                $('.btn-group button.folder-status').next().prop("disabled", false);
                            }


                        } else if (hasDatamanager == 'yes') {
                            if (vaultStatus == 'UNPUBLISHED') {
				                actions['submit-for-publication'] = 'Submit for publication';
				                $('.btn-group button.folder-status').next().prop("disabled", false);
                            } else if (vaultStatus == 'SUBMITTED_FOR_PUBLICATION') {
				                actions['cancel-publication'] = 'Cancel publication';
				                $('.btn-group button.folder-status').next().prop("disabled", false);
                            }
			            }
                    } else {
			            $('label.folder-status-pending').show();

			            if (vaultNewStatus == 'UNPUBLISHED') {
                            $('label.folder-status-pending span.pending-msg').text('Cancellation pending...');
			            } else if (vaultNewStatus == 'SUBMITTED_FOR_PUBLICATION') {
                            $('label.folder-status-pending span.pending-msg').text('Submission pending...');
			            } else if (vaultNewStatus == 'APPROVED_FOR_PUBLICATION') {
                            $('label.folder-status-pending span.pending-msg').text('Approval pending...');
                        }
		            }
                }

                // Datamanager sees all buttons in vault, researcher only folder status.
                if (isDatamanager == 'yes') {
                    $('.top-info-buttons .vault').show();
                } else {
                    $('.top-info-buttons').show();
                    $('.top-info-buttons .vault').show();
                    $('.top-info-buttons .vault .vault-access').hide();
                }
            }

            if (typeof status != 'undefined') {
                if (status == 'SUBMITTED' || status == 'ACCEPTED') {
                    showStatusBtn = false;
                }
            }

            // Handle status btn
            if (showStatusBtn) {
                $('.btn-group button.toggle-folder-status').show();
                $('.btn-group button.toggle-folder-status').prop("disabled", false);
            } else {
                $('.btn-group button.toggle-folder-status').prop("disabled", true);
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

            // Handle actions
            handleActionsList(actions, dir);

            // data.basename.replace(/ /g, "&nbsp;")
            folderName = htmlEncode(data.result.basename).replace(/ /g, "&nbsp;");

            $('.top-information h1').html('<span class="icon">' + icon + '</span> ' + folderName + lockIcon + systemMetadataIcon + actionLogIcon);
            $('.top-information').show();
        });
    }
}

function handleActionsList(actions, folder)
{
    var html = '';
    var possibleActions = ['submit', 'unsubmit', 'accept', 'reject',
                          'submit-for-publication', 'cancel-publication',
                           'approve-for-publication', 'depublish-publication'];

    $.each(possibleActions, function( index, value ) {
        if (actions.hasOwnProperty(value)) {
            html += '<li><a class="action-' + value + '" data-folder="' + folder + '">' + actions[value] + '</a></li>';
        }
    });

    $('.action-list').html(html);
}

function toggleFolderStatus(newStatus, path)
{
    // Get current button text
    var btnText = $('.btn-group button.toggle-folder-status').html();

    // Set spinner & disable button
    $('.btn-group button.toggle-folder-status').html(btnText + '<i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.toggle-folder-status').prop("disabled", true);

    // Change folder status call
    $.getJSON("browse/change_folder_status?path=" + path + "&status=" + newStatus, function(data) {
        if(data.status == 'Success') {
            // Set actions
            var actions = [];
            actions['submit'] = 'Submit';

            if ($('.actionlog-items').is(":visible")) {
                buildActionLog(path);
            }

            if (newStatus == 'LOCKED') {
                $('.btn-group button.toggle-folder-status').text('Unlock');
                $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');

                $('.btn-group button.folder-status').text('Locked');

                var totalLocks = $('.lock-icon').attr('data-locks');
                if (totalLocks == '0') {
                    $('.lock-icon').removeClass('hide');
                    $('.lock-icon').attr('data-locks', 1);
                    $('.lock-icon').attr('title','1 lock(s) found');
                }
                setMessage('success', 'Successfully locked this folder');
            } else {
                $('.btn-group button.toggle-folder-status').text('Lock');
                $('.btn-group button.toggle-folder-status').attr('data-status', 'LOCKED');

                var totalLocks = $('.lock-icon').attr('data-locks');
                if (totalLocks == '1') {
                    $('.lock-icon').addClass('hide');
                    $('.lock-icon').attr('data-locks', 0);
                }

                // unlocking -> hide lock-items as there are none
                if ($('.lock-items').is(":visible")) {
                    $('.lock-items').hide();
                }

                $('.btn-group button.folder-status').text('Actions');
                setMessage('success', 'Successfully unlocked this folder');
            }
            handleActionsList(actions, path);
        } else {
            setMessage('error', data.statusInfo);
            $('.btn-group button.toggle-folder-status').html(btnText);

            // inefficient, but for now sets the button statuses correctly in case of failure on request.
            // requires refactoring!
            topInformation(path, false);
            return;
        }

        // Remove disable attribute
        $('.btn-group button.toggle-folder-status').removeAttr("disabled");
        $('.btn-group button.folder-status').next().prop("disabled", false);
    });
}

function showMetadataForm(path)
{
    window.location.href = 'metadata/form?path=' + path;
}


function submitToVault(folder)
{
    if (typeof folder != 'undefined') {
        // Set spinner & disable button
        var btnText = $('.btn-group button.folder-status').html();
        $('.btn-group button.folder-status').html('Submit <i class="fa fa-spinner fa-spin fa-fw"></i>');
        $('.btn-group button.folder-status').prop("disabled", true);
        $('.btn-group button.folder-status').next().prop("disabled", true);

        $.getJSON("vault/submit?path=" + folder, function (data) {
            if (data.status == 'Success') {
                if (data.folderStatus == 'SUBMITTED') {
                    $('.btn-group button.folder-status').html('Submitted');

                    // Set folder status -> Locked
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.toggle-folder-status').prop("disabled", true);

                    // Set ubsibmit action
                    var actions = [];
                    actions['unsubmit'] = 'Unsubmit';

                    // Datamanager actions
                    var isDatamanager = $('.btn-group button.folder-status').attr('data-datamanager');

                    if (isDatamanager == 'yes') {
                        actions['accept'] = 'Accept';
                        actions['reject'] = 'Reject';
                    }

                    handleActionsList(actions, folder);
                    $('.btn-group button.folder-status').next().removeAttr("disabled");
                } else {
                    $('.btn-group button.folder-status').text('Accepted');
                    $('.btn-group button.toggle-folder-status').text('Unlock');
                    $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                    $('.btn-group button.folder-status').next().prop("disabled", true);
                    $('.btn-group button.toggle-folder-status').prop("disabled", true);
                }

                // lock icon
                var totalLocks = $('.lock-icon').attr('data-locks');
                if (totalLocks == '0') {
                    $('.lock-icon').removeClass('hide');
                    $('.lock-icon').attr('data-locks', 1);
                    $('.lock-icon').attr('title', '1 lock(s) found');
                }
            } else {
                $('.btn-group button.folder-status').html(btnText);
                setMessage('error', data.statusInfo);

                // inefficient, but for now sets the button statuses correctly in case of failure on request.
                // requires refactoring!
                topInformation(folder,false);
            }
        });
    }
}

function unsubmitToVault(folder) {
    if (typeof folder != 'undefined') {
        var btnText = $('.btn-group button.folder-status').html();
        $('.btn-group button.folder-status').html('Unsubmit <i class="fa fa-spinner fa-spin fa-fw"></i>');
        $('.btn-group button.folder-status').prop("disabled", true);
        $('.btn-group button.folder-status').next().prop("disabled", true);
        $.getJSON("vault/unsubmit?path=" + folder, function (data) {
            if (data.status == 'Success') {
                // Set folder status -> Locked
                $('.btn-group button.toggle-folder-status').text('Unlock');
                $('.btn-group button.toggle-folder-status').attr('data-status', 'UNLOCKED');
                $('.btn-group button.folder-status').html('Locked');
                $('.btn-group button.toggle-folder-status').removeAttr("disabled");

                // Set submit action
                var actions = [];
                actions['submit'] = 'Submit';
                handleActionsList(actions, folder);
            } else {
                $('.btn-group button.folder-status').html(btnText);
                setMessage('error', data.statusInfo);

                // Inefficient, but for now sets the button statuses correctly in case of failure on request.
                // requires refactoring!
                topInformation(folder, false);
                return;
            }

            $('.btn-group button.folder-status').next().removeAttr("disabled");
        });

    }
}

function acceptFolder(folder)
{
    var btnText = $('.btn-group button.folder-status').html();
    $('.btn-group button.folder-status').html('Accept <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);
    $('.btn-group button.folder-status').next().prop("disabled", true);
    $.getJSON("vault/accept?path=" + folder, function (data) {
        if (data.status == 'Success') {
            $('.btn-group button.folder-status').html('Accepted');

        } else {
            $('.btn-group button.folder-status').html(btnText);
            setMessage('error', data.statusInfo);

            // Inefficient, but for now sets the button statuses correctly in case of failure on request.
            // requires refactoring!
            topInformation(folder, false);
        }
    });
}

function rejectFolder(folder)
{
    var btnText = $('.btn-group button.folder-status').html();
    $('.btn-group button.folder-status').html('Reject <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);
    $('.btn-group button.folder-status').next().prop("disabled", true);
    $.getJSON("vault/reject?path=" + folder, function (data) {
        if (data.status == 'Success') {
            $('.btn-group button.folder-status').html('Rejected');
        } else {
            $('.btn-group button.folder-status').html(btnText);
            setMessage('error', data.statusInfo);

            // Inefficient, but for now sets the button statuses correctly in case of failure on request.
            // requires refactoring!
            topInformation(folder, false);
            return;
        }

        // Make unlock btn clickable if write rights.
        var hasWriteRights =  $('.btn-group button.folder-status').attr('data-write');
        if (hasWriteRights == 'yes') {
            $('.btn-group button.toggle-folder-status').prop("disabled", false);
        }
    });
}

function vaultSubmitForPublication(folder)
{
    var btnText = $('.btn-group button.folder-status').html();
    $('.btn-group button.folder-status').html('Submit for publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);
    $('.btn-group button.folder-status').next().prop("disabled", true);
    $.getJSON("vault/submit_for_publication?path=" + folder, function (data) {
        if (data.status == 'Success') {
            $('.btn-group button.folder-status').html(btnText);
            $('label.folder-status-pending span.pending-msg').html('Submission pending...');
            $('label.folder-status-pending').show();
        } else {
            $('.btn-group button.folder-status').html(btnText);
            setMessage('error', data.statusInfo);

            topInformation(folder, false);
            return;
        }
    });
}

function vaultApproveForPublication(folder)
{
    var btnText = $('.btn-group button.folder-status').html();
    $('.btn-group button.folder-status').html('Approve for publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);
    $('.btn-group button.folder-status').next().prop("disabled", true);
    $.getJSON("vault/approve_for_publication?path=" + folder, function (data) {
        if (data.status == 'Success') {
            $('.btn-group button.folder-status').html(btnText);
            $('label.folder-status-pending').hide();
        } else {
            $('.btn-group button.folder-status').html(btnText);
            setMessage('error', data.statusInfo);

            topInformation(folder, false);
            return;
        }
    });
}

function vaultDepublishPublication(folder)
{
    var btnText = $('.btn-group button.folder-status').html();
    $('.btn-group button.folder-status').html('Depublish publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);
    $('.btn-group button.folder-status').next().prop("disabled", true);
    $.getJSON("vault/depublish_publication?path=" + folder, function (data) {
        if (data.status == 'Success') {
            $('.btn-group button.folder-status').html(btnText);
            $('label.folder-status-pending').hide();
        } else {
            $('.btn-group button.folder-status').html(btnText);
            setMessage('error', data.statusInfo);

            topInformation(folder, false);
            return;
        }
    });
}

function vaultCancelPublication(folder)
{
    var btnText = $('.btn-group button.folder-status').html();
    $('.btn-group button.folder-status').html('Cancel publication <i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('.btn-group button.folder-status').prop("disabled", true);
    $('.btn-group button.folder-status').next().prop("disabled", true);
    $.getJSON("vault/cancel_publication?path=" + folder, function (data) {
        if (data.status == 'Success') {
            $('.btn-group button.folder-status').html(btnText);
            $('label.folder-status-pending span.pending-msg').html('Cancellation pending...');
            $('label.folder-status-pending').show();
        } else {
            $('.btn-group button.folder-status').html(btnText);
            setMessage('error', data.statusInfo);

            topInformation(folder, false);
            return;
        }
    });
}

function vaultAccess(action, folder)
{
    var btnText = $('button.vault-access').html();
    $('button.vault-access').html(btnText + '<i class="fa fa-spinner fa-spin fa-fw"></i>');
    $('button.vault-access').prop("disabled", true);
    $.getJSON("vault/access?path=" + folder + "&action=" + action, function (data) {
        if (data.status == 'Success') {
            if (action == 'grant') {
                $('button.vault-access').text('Revoke read access to research group');
                $('button.vault-access').attr('data-access', 'revoke');
            } else {
                $('button.vault-access').text('Grant read access to research group');
                $('button.vault-access').attr('data-access', 'grant');
            }
        } else {
            $('button.vault-access').html(btnText);
            setMessage('error', data.statusInfo);
        }

        $('button.vault-access').prop("disabled", false);
    });
}
