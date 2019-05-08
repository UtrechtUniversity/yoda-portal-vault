<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = '<?php echo rawurlencode($dir); ?>';
    var view = 'browse';
</script>

<script>
    // Added for selection of target for vault package @TODO names to be changed and to be added by controller
//    var revisionItemsPerPage = <?php echo $items; ?>;
    var browseDlgPageItems = <?php echo $items; //$dlgPageItems; ?>;
//    var view = 'revision';
</script>

<div class="modal" id="dlg-select-folder">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="panel-body">
                <div class="alert alert-warning hide" id="dlg-select-alert-panel">
                    <span></span>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title pull-left">
                            Select folder to copy current datapackage
                        </h3>

                        <div class="input-group-sm has-feedback pull-right">
                            <button class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>

                    <input type="hidden" id="restoration-objectid" value="">

                    <div class="panel revision-restore-dialog">
                        <div class="panel-body">
                            <ol class="breadcrumb dlg-breadcrumb">
                                <li class="active">Home</li>
                            </ol>

                            <table id="folder-select-browser" class="table yoda-table table-bordered">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Modified date</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default" id="btn-copy-package"><i class="fa fa-copy" aria-hidden="true"></i> Copy package to research area</button>
            </div>

            <div id="coverAll" class="cover restore-exists hide">
                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title pull-left">
                            Restore revision in selected folder
                        </h3>

                        <div class="input-group-sm has-feedback pull-right">
                            <button class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="mode-dlg-exists hide">
                            <div class="alert alert-warning">
                                The file <strong><span class="orgFileName"></span></strong> (location: <span class='dlg-path'> </span>) already exists.
                            </div>

                            <div class="alert alert-danger hide" id="alertBox">
                                The file <span id="duplicate"></span> The renamed file already you try to add already exists.
                            </div>

                            <div class="panel-body">
                                <div class="row">
                                    <form id="form-restore-overwrite" class="form-inline pull-left">
                                        <p>Overwrite this file</p>
                                        <button class="btn btn-danger" id="btn-restore-overwrite">Overwrite</button>
                                    </form>

                                    <form class="form-inline pull-right">
                                        <p>Enter new name for the revision you want to restore</p>
                                        <div class="form-group">
                                            <label for="newFileName">New filename</label>
                                            <input type="text"  class="form-control" placeholder="Enter new filename" id="newFileName">
                                        </div>
                                        <button  class="btn btn-primary" id="btn-restore-next-to">Restore with a new filename</button>
                                    </form>
                                </div>
                                <div class="row">
                                    <hr>
                                    <button class="btn btn-default pull-right"  id="btn-cancel-overwrite-dialog" >Cancel</button>
                                </div>
                            </div>
                        </div>
                        <div class="mode-dlg-locked hide">
                            <div class="alert alert-danger">
                                Revision of the file <strong><span class="orgFileName"></span></strong> can not be placed in location: <strong><span class='path'> </span></strong>.
                                <br>
                                <br>
                                <br>This folder is in a locked state and can therefore not be changed.
                                <br>
                                <br>Please select another folder for placement of your revision.
                            </div>

                            <button class="btn btn-default pull-right"  id="btn-select-other-folder" >Select other folder...</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo $searchHtml; ?>

<div class="modal" id="confirmAgreementConditions">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
            </div>

            <div class="modal-footer">
                <fieldset>
                    <input type="checkbox" class="confirm-conditions" id="checkbox-confirm-conditions">
                    <label for="checkbox-confirm-conditions">Please confirm that you agree with the above</label>
                </fieldset>
                <hr>
                <button class='action-confirm-submit-for-publication btn btn-default'>Confirm agreement</button>
                <button class="btn btn-default grey cancel" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="showUnpreservableFiles">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <h3>File formats compliance with policy</h3>
                <div class="form-group">
                    <label for="file-formats-list">Select preservable file format list:</label>
                    <select class="form-control" id="file-formats-list">
                        <option value="" disabled selected>Select a file format list</option>
                    </select>
                </div>
                <div class="help"></div><br />
                <div class="advice"></div>
                <div class="preservable">
                    This folder does not contain files that are likely to become unusable in the future.
                </div>
                <div class="unpreservable">
                    Following unpreservable file extensions were found in your dataset:
                    <br />
                    <ul class="list-unpreservable-formats"></ul>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default grey cancel" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="confirmDepublish">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p>Please confirm that you agree to depublish this datapackage.</p>
            </div>

            <div class="modal-footer">
                <button class='action-confirm-depublish-publication btn btn-default'>Confirm depublish</button>
                <button class="btn btn-default grey cancel" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="confirmRepublish">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p>Please confirm that you agree to republish this datapackage.</p>
            </div>

            <div class="modal-footer">
                <button class='action-confirm-republish-publication btn btn-default'>Confirm republish</button>
                <button class="btn btn-default grey cancel" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <ol class="breadcrumb">
        <li class="active">Home</li>
    </ol>

    <div class="top-information">
         <div class="row">
            <div class="col-md-8">
                <h1></h1>
            </div>
            <div class="col-md-4">
                <div class="top-info-buttons">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-default metadata-form" data-path="">Metadata</button>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-default upload" data-path=""><i class="fa fa-upload" aria-hidden="true"></i> Upload</button>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default folder-status" data-toggle="dropdown" disabled="disabled">Actions</button>
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" disabled="disabled">
                            <span class="caret"></span><span class="sr-only">Actions</span>
                        </button>
                        <ul class="dropdown-menu action-list" role="menu"></ul>
                    </div>
                </div>
            </div>
        </div>

        <ul class="list-group lock-items"></ul>
        <ul class="list-group system-metadata-items"></ul>
        <ul class="list-group actionlog-items"></ul>
    </div>

    <div class="col-md-12">
        <div class="row">
            <table id="file-browser" class="table yoda-table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Modified date</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
