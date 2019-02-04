    <div class="row hide form-data-errors">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <h3 class="panel-title pull-left">
                        Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities(trim($path))); ?>
                    </h3>
                    <div class="input-group-sm has-feedback pull-right">
                        <a class="btn btn-default" href="/research/browse?dir=<?php echo rawurlencode($path); ?>">Close</a>
                    </div>
                </div>
                <div class="panel-body">
                    <p>
                        It is not possible to load this form as the metadata xml file is not in accordance with the form definition.<br />
                        <br />Check the following in your XML file:
                        <br />
                        <span class="error-fields"></span>
                        <br />
                        When using the 'Delete all metadata' button beware that you will lose all data!
                        <button type="button" onclick="deleteMetadata('<?php echo rawurlencode($path); ?>')" class="btn btn-danger delete-all-metadata-btn pull-right">Delete all metadata </button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="showUnpreservableFiles">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h2>Unpreservable file formats</h2>
                    The data you are submitting to the vault contains files that are likely to become unusable in the future.
                    <br>
                    <br>
                    Following file extensions were found in your dataset:
                    <br>
                    <br>
                    <div class="list-unpreservable-formats"></div>
                    <br>
                    To learn more about unpreservable file formats please visit: <a href="google.com">Unpreservable file formats</a>
                </div>

                <div class="modal-footer">
                    <button class='action-accept-presence-unpreservable-files-form btn btn-default'>Submit to vault anyway</button>
                    <button class="btn btn-default grey cancel" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row metadata-form">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <h3 class="panel-title pull-left">
                        Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities(trim($path))); ?>
                    </h3>
                    <div class="input-group-sm has-feedback pull-right">
                        <a class="btn btn-default" href="/research/browse?dir=<?php echo rawurlencode($path); ?>">Close</a>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (!$writePermission && !$metadataExists) { ?>
                        <p>There is no metadata present for this folder.</p>
                    <?php } else { ?>
                        <div id="form" class="metadata-form"
                             data-path="<?php echo rawurlencode($path); ?>"
                             data-csrf_token_name="<?php echo rawurlencode($tokenName); ?>"
                             data-csrf_token_hash="<?php echo rawurlencode($tokenHash); ?>">
                            <p>Loading metadata <i class="fa fa-spinner fa-spin fa-fw"></i></p>
                        </div>
	            <?php } ?>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    var mode = "<?php echo $mode; ?>";
</script>
<script src="/research/static/js/metadata/form.js" type="text/javascript"></script>
