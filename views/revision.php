<script>
    var revisionItemsPerPage = <?php echo $items; ?>;
    var browseDlgPageItems = <?php echo $dlgPageItems; ?>;
    var view = 'revision';
</script>
<style>
    .cover {
        background-color: #ffffff;
        border-radius: 5px;
        height: 100%;
        left: 0;
        opacity: 1;
        position: absolute;
        top: 0;
        width: 100%;
        z-index:1000;
        padding:15px;
    }
</style>

<div class="modal" id="select-folder">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">Select folder to restore revision</span>
            </div>

            <input type="hidden" id="restoration-objectid" value="">

            <ol class="breadcrumb dlg-breadcrumb">
                <li class="active">Home</li>
            </ol>

            <div class="modal-body">
                <div class="col-md-12">
                    <div class="row">
                        <div class="panel panel-default">
                            <div class="panel-body">

                                <p class="alert-panel-error alert-panel hide" style="color:red;">
                                    <i class="fa fa-exclamation-triangle"></i> Something has gone wrong
                                    <br>
                                    <span></span>
                                </p>
                                <p class="alert-panel-warning alert-panel hide" >
                                    <i class="fa fa-exclamation-circle"></i>
                                    <span></span>
                                </p>


                                <p class="alert-panel-path-not-exists alert-panel hide">
                                    <i class="fa fa-exclamation-circle"></i> The folder you selected does not exist anymore. Please select another folder.
                                </p>
                                <p class="alert-panel-path-permission-denied alert-panel hide">
                                    <i class="fa fa-exclamation-circle"></i> You do not have enough permissions for the folder you selected. Please select another folder.
                                </p>


                                <table id="folder-browser" class="table yoda-table table-bordered">
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
            </div>

            <div class="modal-footer">
                <button class="btn button btn-success" id="btn-restore"><i class="fa fa-magic" aria-hidden="true"></i> Restore your file</button>
                <button class="btn button grey" data-dismiss="modal">Close</button>
            </div>
            <div id="coverAll" class="cover hide">
                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title pull-left">
                            Restore revision in selected folder
                        </h3>

                        <div class="input-group-sm has-feedback pull-right">
                            <button class="btn btn-default"  id="btn-cancel-overite-dialog" >Cancel</button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <p class="alert-panel-overwrite alert-panel hide">
                            The file
                            <span id="orgFileName"></span>
                            <br>
                            <br>in folder

                            <span id='path'> </span>
                            <br><br>
                            already exists.
                            <br>
                            <div class="col-md-6">
                                You can choose to overwrite this file
                            </div>
                            <div class="col-md-6">
                                Enter new name for the revision you want to restore
                            </div>

                            <div class="col-md-6">
                            </div>
                            <div class="col-md-6">
                                <input type="text"  class="form-control" placeholder="Enter new filename" id="newFileName">
                            </div>

                            <div class="col-md-6">
                                <button class="btn btn-danger form-control" id="btn-restore-overwrite"><i class="fa fa-file-o" aria-hidden="true"></i> Overwrite</button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-info form-control" id="btn-restore-next-to"><i class="fa fa-files-o" aria-hidden="true"></i> Restore with a new filename</button>
                            </div>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php echo $searchHtml; ?>

<div class="row">
    <div class="panel panel-default">
        <div class="panel-heading clearfix">
            <h3 class="panel-title pull-left">
                Revisions
            </h3>
            <div class="input-group-sm has-feedback pull-right">
                <a class="btn btn-default" href="/research/browse">Close</a>
            </div>
        </div>
        <div class="panel-body">
            <p class="alert-panel-main hide" style="color:green;">
                <i class="fa fa-check"></i> Your file was successfully restored!
            </p>


            <table id="file-browser" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Number of revisions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>