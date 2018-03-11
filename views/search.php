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
        <input type="text" class="form-control search-term<?php echo $showStatus ? ' hide' : ''; ?>" id="search-filter" placeholder="Search term..." value="<?php echo htmlentities($searchTerm); ?>" maxlength="255">
        <span class="input-group-btn search-term<?php echo $showStatus ? ' hide' : ''; ?>">
            <button class="btn btn-default search-btn" data-items-per-page="<?php echo $searchItemsPerPage; ?>" type="button"><span class="glyphicon glyphicon-search"></span></button>
        </span>

        <select name="status" class="form-control search-status<?php echo $showTerm ? ' hide' : ''; ?>">
            <option style="display:none" disabled selected value>Select status...</option>
            <optgroup label="Research">
                <option value="research:LOCKED">Locked</option>
                <option value="research:SUBMITTED">Submitted for vault</option>
                <option value="research:ACCEPTED">Accepted for vault</option>
                <option value="research:REJECTED">Rejected for vault</option>
                <option value="research:SECURED">Secured in vault</option>
            </optgroup>
            <optgroup label="Vault">
                <option value="vault:UNPUBLISHED">Unpublished</option>
                <option value="vault:SUBMITTED_FOR_PUBLICATION">Submitted for publication</option>
                <option value="vault:APPROVED_FOR_PUBLICATION">Approved for publication</option>
                <option value="vault:PUBLISHED">Published</option>
                <option value="vault:REQUEST_DEPUBLICATION">Requested depublication</option>
                <option value="vault:DEPUBLISHED">Depublished</option>
            </optgroup>
        </select>
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
