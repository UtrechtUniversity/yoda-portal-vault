<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = <?php echo json_encode($dir); ?>;
    var view = 'browse';
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

<div class="modal" tabindex="-1" role="dialog" id="showUnpreservableFiles">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <h3 class="modal-title">File formats compliance with policy</h3>
                <div class="form-group">
                    <label for="file-formats-list">Select preservable file format list:</label>
                    <select class="form-control" id="file-formats-list">
                        <option value="" disabled selected>Select a file format list</option>
                    </select>
                </div>
                <p class="help"></p><br />
                <p class="advice"></p>
                <p class="checking">Checking files <i class="fa fa-spinner fa-spin fa-fw"></i></p>
                <p class="preservable">
                    This folder does not contain files that are likely to become unusable in the future.
                </p>
                <div class="unpreservable">
                    <p>The following unpreservable file extensions were found in your dataset:</p>
                    <ul class="list-unpreservable-formats"></ul>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Close</button>
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

<div class="modal" id="viewMedia">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div id="viewer"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default grey cancel" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php echo $searchHtml; ?>

<div class="row d-block">
    <nav aria-label="breadcrumb flex-column">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Home</li>
        </ol>
    </nav>

    <div class="top-information">
         <div class="row">
            <div class="col-md-9">
                <h2 class="pt-3"></h2>
            </div>
            <div class="col-md-3">
                <div class="top-info-buttons">
                    <div class="btn-toolbar pull-right" role="toolbar">
                        <div class="btn-group mr-2" role="group">
                            <button type="button" class="btn btn-outline-secondary metadata-form metadata-form" data-path="" title="Show metadata form">Metadata</button>
                        </div>
                        <div class="btn-group">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="actionMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Actions
                                </button>
                                <div class="dropdown-menu action-list" role="menu"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card system-metadata">
            <div class="card-header">System metadata</div>
            <div class="list-group system-metadata-items"></div>
        </div>
        <div class="card actionlog">
            <div class="card-header">Provenance information</div>
            <div class="list-group actionlog-items"></div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="row d-block">
            <table id="file-browser" class="table yoda-table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Modified date</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
