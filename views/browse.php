<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = '<?php echo urlencode($dir); ?>';
    var searchTerm = '<?php echo str_replace('+',' ',addslashes(urlencode( $searchTerm))); ?>';
    var searchStatusValue = '<?php echo addslashes($searchStatusValue); ?>';
    var searchType = '<?php echo $searchType; ?>';
    var searchStart = <?php echo $searchStart; ?>;
    var searchOrderDir = '<?php echo $searchOrderDir; ?>';
    var searchOrderColumn = <?php echo $searchOrderColumn; ?>;
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
                <li><a href="#" data-type="status">Search by status</a></li>
                <li><a href="#" data-type="revision">Search revision by name</a></li>
            </ul>
        </div>
        <div class="search-term">
            <input type="hidden" name="search_param" value="all" id="search_param">
        </div>
        <input type="text" class="form-control search-term<?php echo $showStatus ? ' hide' : ''; ?>" id="search-filter" placeholder="Search term..." value="<?php echo htmlentities($searchTerm); ?>">
        <span class="input-group-btn search-term<?php echo $showStatus ? ' hide' : ''; ?>">
            <button class="btn btn-default search-btn" data-items-per-page="<?php echo $items; ?>" type="button"><span class="glyphicon glyphicon-search"></span></button>
        </span>

        <div class="search-status<?php echo $showTerm ? ' hide' : ''; ?>">
            <label class="radio-inline"><input type="radio" name="status" value="SUBMITTED"<?php echo $searchStatusValue == 'SUBMITTED' ? ' checked' : ''; ?>>Submitted</label>
        </div>
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
            <button type="button" class="btn btn-default folder-status" data-status="" data-path=""></button>
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