<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = <?php echo json_encode($dir); ?>;
    var browseDlgPageItems = <?php echo $items; ?>;
    var view = 'browse';
</script>

<div class="modal" id="dlg-select-folder">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="card-body">
                <div class="alert alert-warning hide" id="dlg-select-alert-panel">
                    <span></span>
                </div>

                <div class="card">
                    <div class="card-header clearfix">
                        <h5 class="card-title pull-left">
                            Select folder to copy current datapackage
                        </h5>
                    </div>

                    <input type="hidden" id="restoration-objectid" value="">

                    <div class="card revision-restore-dialog">
                        <div class="card-body">
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
                <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="btn-copy-package"><i class="fa fa-copy" aria-hidden="true"></i> Copy package to research area</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" role="dialog" id="confirmAgreementConditions">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset>
                            <input type="checkbox" class="confirm-conditions" id="checkbox-confirm-conditions">
                            <label for="checkbox-confirm-conditions">Please confirm that you agree with the above</label>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                   <div class="col-sm-12">
                        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button class='btn btn-primary ml-2 action-confirm-submit-for-publication'>Confirm agreement</button>
                    </div>
                </div>
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

<div class="modal" tabindex="-1" role="dialog" id="confirmDepublish">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p>Please confirm that you agree to depublish this datapackage.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class='btn btn-primary action-confirm-depublish-publication'>Confirm depublish</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" role="dialog" id="confirmRepublish">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p>Please confirm that you agree to republish this datapackage.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class='btn btn-primary action-confirm-republish-publication'>Confirm republish</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" role="dialog" id="viewMedia">
    <div class="modal-dialog mw-100 w-50">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div id="viewer"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Close</button>
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
