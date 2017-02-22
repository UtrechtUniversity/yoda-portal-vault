<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = '<?php echo $dir; ?>';
    var searchTerm = '<?php echo addslashes($searchTerm); ?>';
    var searchType = '<?php echo $searchType; ?>';
    var searchStart = <?php echo $searchStart; ?>;
</script>

<div class="row">
    <div class="input-group" style="margin-bottom:20px;">
        <div class="input-group-btn search-panel">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span id="search_concept" data-type="<?php echo $searchType; ?>">Search by <?php echo $searchType; ?></span> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li><a href="#" data-type="filename">Search by filename</a></li>
                <li><a href="#" data-type="folder">Search by folder</a></li>
                <li><a href="#" data-type="metadata">Search by metadata</a></li>
            </ul>
        </div>
        <input type="hidden" name="search_param" value="all" id="search_param">
        <input type="text" class="form-control" id="search-filter" placeholder="Search term..." value="<?php echo $searchTerm; ?>">
        <span class="input-group-btn">
            <button class="btn btn-default search-btn" data-items-per-page="<?php echo $items; ?>" type="button"><span class="glyphicon glyphicon-search"></span></button>
        </span>
    </div>

    <div class="panel panel-default search-results">
        <div class="panel-heading clearfix">
            <h3 class="panel-title pull-left">Search results for '<span class="search-string"></span>'</h3>

            <button class="btn btn-default pull-right close-search-results input-group-sm has-feedback">Close</button>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <table class="table yoda-table table-bordered" id="search" width="100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="row">

    <ol class="breadcrumb">
        <li class="active">Home</li>
    </ol>
    <div class="top-information">
        <h1></h1>


        <div class="btn-group" role="group">
            <button type="button" class="btn btn-default metadata-form" data-path=""><i class="fa fa-list" aria-hidden="true"></i> Metadata</button>


            <!--
            <button type="button" class="btn btn-default" data-type=""><i class="fa fa-folder-o" aria-hidden="true"></i> Is folder</button>
            <button class="btn btn-default disabled" href="#">
                <i class="fa fa-unlock"></i> Unlocked
            </button>
            <button type="button" class="btn btn-default disabled">
                <i class="fa fa-university" aria-hidden="true"></i>
                Save to vault</button>
            <button type="button" class="btn btn-default">Edit metadata</button>
            -->
        </div>
    </div>




    <div class="col-md-12">
        <div class="row">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="file-browser" class="table yoda-table table-bordered">
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