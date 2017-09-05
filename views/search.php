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

        <select class="form-control search-status<?php echo $showTerm ? ' hide' : ''; ?>">
            <optgroup label="Research">
                <option name="status" value="status:LOCKED"<?php echo $searchStatusValue == 'LOCKED' ? ' checked' : ''; ?>>Locked</option>
                <option name="status" value="status:SUBMITTED"<?php echo $searchStatusValue == 'SUBMITTED' ? ' checked' : ''; ?>>Submitted for vault</option>
                <option name="status" value="status:ACCEPTED"<?php echo $searchStatusValue == 'ACCEPTED' ? ' checked' : ''; ?>>Accepted for vault</option>
                <option name="status" value="status:REJECTED"<?php echo $searchStatusValue == 'REJECTED' ? ' checked' : ''; ?>>Rejected for vault</option>
                <option name="status" value="status:SECURED"<?php echo $searchStatusValue == 'SECURED' ? ' checked' : ''; ?>>Secured in vault</option>
            </optgroup>
            <optgroup label="Vault">
                <option name="status" value="vault_status:UNPUBLISHED"<?php echo $searchStatusValue == 'UNPUBLISHED' ? ' checked' : ''; ?>>Unpublished</option>
                <option name="status" value="vault_status:SUBMITTED_FOR_PUBLICATION"<?php echo $searchStatusValue == 'SUBMITTED_FOR_PUBLICATION' ? ' checked' : ''; ?>>Submitted for publication</option>
                <option name="status" value="vault_status:APPROVED_FOR_PUBLICATION"<?php echo $searchStatusValue == 'APPROVED_FOR_PUBLICATION' ? ' checked' : ''; ?>>Approved for publication</option>
                <option name="status" value="vault_status:PUBLISHED"<?php echo $searchStatusValue == 'PUBLISHED' ? ' checked' : ''; ?>>Published</option>
                <option name="status" value="vault_status:DEPUBLISHED"<?php echo $searchStatusValue == 'DEPUBLISHED' ? ' checked' : ''; ?>>Depublished</option>
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
