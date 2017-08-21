<script>
    var searchTerm = '<?php echo str_replace('+',' ',addslashes(urlencode( $searchTerm))); ?>';
    var searchStatusValue = '<?php echo addslashes($searchStatusValue); ?>';
    var searchType = '<?php echo $searchType; ?>';
    var searchStart = <?php echo $searchStart; ?>;
    var searchOrderDir = '<?php echo $searchOrderDir; ?>';
    var searchOrderColumn = '<?php echo $searchOrderColumn; ?>';
    var searchPageItems = <?php echo $searchItemsPerPage; ?>;
</script>

<div class="row">
    <div class="input-group" style="margin-bottom:20px;">
        <div class="input-group-btn search-panel">
            <?php if ($searchType == 'revision') { ?>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span id="search_concept" data-type="<?php echo $searchType; ?>">Search revision by name</span> <span class="caret"></span>
                </button>
            <?php } else { ?>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span id="search_concept" data-type="<?php echo $searchType; ?>">Search by <?php echo $searchType; ?></span> <span class="caret"></span>
                </button>
            <?php } ?>
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
            <button class="btn btn-default search-btn" data-items-per-page="<?php echo $searchItemsPerPage; ?>" type="button"><span class="glyphicon glyphicon-search"></span></button>
        </span>

        <div class="search-status<?php echo $showTerm ? ' hide' : ''; ?>">
            <label class="radio-inline"><input type="radio" name="status" value="LOCKED"<?php echo $searchStatusValue == 'LOCKED' ? ' checked' : ''; ?>>Locked</label>
            <label class="radio-inline"><input type="radio" name="status" value="SUBMITTED"<?php echo $searchStatusValue == 'SUBMITTED' ? ' checked' : ''; ?>>Submitted</label>
            <label class="radio-inline"><input type="radio" name="status" value="ACCEPTED"<?php echo $searchStatusValue == 'ACCEPTED' ? ' checked' : ''; ?>>Accepted</label>
            <label class="radio-inline"><input type="radio" name="status" value="REJECTED"<?php echo $searchStatusValue == 'REJECTED' ? ' checked' : ''; ?>>Rejected</label>
            <label class="radio-inline"><input type="radio" name="status" value="SECURED"<?php echo $searchStatusValue == 'SECURED' ? ' checked' : ''; ?>>Secured</label>
            <label class="radio-inline"><input type="radio" name="status" value="APPROVED"<?php echo $searchStatusValue == 'APPROVED' ? ' checked' : ''; ?>>Approved</label>
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