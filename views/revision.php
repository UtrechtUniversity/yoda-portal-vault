<script>
    var revisionItemsPerPage = <?php echo $items; ?>;
    var browseDlgPageItems = <?php echo $dlgPageItems; ?>;
    var view = 'revision';
</script>

<div class="modal" id="select-folder">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="panel-body">

                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title pull-left">
                            Select folder to restore revision
                        </h3>

                        <div class="input-group-sm has-feedback pull-right">
    <!--                        <button class="btn btn-default"  id="btn-cancel-overite-dialog" >Cancel</button>-->
                            <button class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>

                    <input type="hidden" id="restoration-objectid" value="">
<!--                    <br>-->

<!--                    <div class="modal-body">-->
                        <div class="panel revision-restore-dialog">
                            <div class="panel-body">
                                <ol class="breadcrumb dlg-breadcrumb">
                                    <li class="active">Home</li>
                                </ol>

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
<!--                    </div>-->


                </div>
            </div>
<!--            <div class="modal-header">-->
<!--                <span class="modal-title">Select folder to restore revision</span>-->
<!--            </div>-->



            <div class="modal-footer">
                <button class="btn btn-default" id="btn-restore"><i class="fa fa-magic" aria-hidden="true"></i> Restore</button>
            </div>

            <div id="coverAll" class="cover restore-exists hide">
                <div class="panel panel-default">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title pull-left">
                            Restore revision in selected folder
                        </h3>

                        <div class="input-group-sm has-feedback pull-right">
                            <button class="btn btn-default" data-dismiss="modal">Close</button>
<!--                            <button class="btn btn-default"  id="btn-cancel-overite-dialog" >Cancel</button>-->
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="mode-dlg-exists hide">
                            <div class="alert alert-warning">
                                The file <strong><span class="orgFileName"></span></strong> (location: <span class='path'> </span>) already exists.
                            </div>

                            <div class="alert alert-danger hide" id="alertBox">
                                The file <span id="duplicate"></span> The renamed file already you try to add already exists.
                            </div>

                            <div class="panel-body">
                                <div class="row">
                                    <form class="form-inline pull-left">
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
                                    <button class="btn btn-default pull-right"  id="btn-cancel-overite-dialog" >Cancel</button>
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