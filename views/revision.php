<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseDlgPageItems = <?php echo $dlgPageItems; ?>;
    var browseStartDir = '<?php echo urlencode($dir); ?>';
</script>

<div class="modal" id="select-folder">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">Select folder</span>
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

                                <p class="alert-panel" style="color:red;">
                                    <i class="fa fa-exclamation-triangle"></i> Something went wrong restoring the file!
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

        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12">
        <div class="input-group" style="margin-bottom:20px;">
            <div class="input-group-btn search-panel">
<!--                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">-->
<!--                    <span id="search_concept">Filter by name</span> <span class="caret"></span>-->
<!--                </button>-->
<!--                <ul class="dropdown-menu" role="menu">-->
<!--                    <li><a href="#contains">Filter by Name</a></li>-->
<!--                    <li><a href="#its_equal">Filter by Metadata</a></li>-->
<!--                    <li><a href="#its_equal">Filter by Status</a></li>-->
<!--                    <li><a href="#its_equal">Find revision for name</a></li>-->
<!--                </ul>-->
            </div>
            <input type="hidden" name="search_param" value="all" id="search_param">
            <input type="text" class="form-control" name="searchArgument" placeholder="Search term...">
            <span class="input-group-btn">
                        <button class="btn btn-default btn-search" type="button"><span class="glyphicon glyphicon-search"></span></button>
                    </span>
        </div>
    </div>
</div>


<div class="row">

    <ol class="breadcrumb">
        <li>Home</li>
        <li>gpr-test</li>
        <li><strong>Project-test</strong></li>
    </ol>
    <h1><i class="fa fa-folder-o" aria-hidden="true"></i>Project-test</h1>


    <div class="col-md-12">
        <div class="row">
        <div class="panel panel-default">
            <div class="panel-body">
                <table id="file-browser" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>studyID</th>
                            <th>objectID</th>
                            <th>Name</th>
                            <th>Revision date</th>
                            <th>Path</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>
        </div>
    </div>
</div>