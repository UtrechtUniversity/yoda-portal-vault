<script>
    var searchTerm = '<?php echo rawurlencode($searchTerm); ?>';
    var searchStatusValue = '<?php echo addslashes($searchStatusValue); ?>';
    var searchType = '<?php echo $searchType; ?>';
    var searchStart = <?php echo $searchStart; ?>;
    var searchOrderDir = '<?php echo $searchOrderDir; ?>';
    var searchOrderColumn = '<?php echo $searchOrderColumn; ?>';
    var searchPageItems = <?php echo $searchItemsPerPage; ?>;
</script>

<div class="row">
    <div class="input-group mb-3">
        <div class="input-group-prepend search-panel">
            <?php if ($searchType == 'revision') { ?>
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span id="search_concept" data-type="<?php echo $searchType; ?>">Search revision by name</span>
                </button>
            <?php } else { ?>
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span id="search_concept" data-type="<?php echo $searchType; ?>">Search by <?php echo $searchType; ?></span>
                </button>
            <?php } ?>
            <div class="dropdown-menu">
                <a href="#" class="dropdown-item" data-type="filename">Search by filename</a>
                <a href="#" class="dropdown-item" data-type="folder">Search by folder</a>
                <a href="#" class="dropdown-item" data-type="metadata">Search by metadata</a>
                <a href="#" class="dropdown-item" data-type="status">Search by status</a>
                <a href="#" class="dropdown-item" data-type="revision">Search revision by name</a>
            </div>
        </div>
        <div class="search-term">
            <input type="hidden" name="search_param" value="all" id="search_param">
        </div>
        <input type="text" class="form-control search-term<?php echo $showStatus ? ' hide' : ''; ?>" id="search-filter" placeholder="Search term..." value="<?php echo htmlentities($searchTerm); ?>" maxlength="255">
        <div class="input-group-append search-term<?php echo $showStatus ? ' hide' : ''; ?>">
            <button class="btn btn-outline-secondary" data-items-per-page="<?php echo $searchItemsPerPage; ?>" type="button">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
        </div>

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
                <option value="vault:DEPUBLISHED">Depublished</option>
            </optgroup>
        </select>
    </div>

    <div class="col-sm-12">
        <div class="row card panel-default search-results">
            <div class="card-header clearfix">
                <h5 class="mt-1 pull-left">Search results for '<span class="search-string"></span>'</h5>
                <button class="btn btn-secondary float-right clearfix close-search-results">Close</button>
            </div>
            <div class="card-body">
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
</div>
