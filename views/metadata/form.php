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

    <div class="row <?php echo !$transformation ? ' hide' : ''; ?>">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <h3 class="panel-title pull-left">
                        Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities(trim($path))); ?>
                    </h3>
                    <?php if (!$transformationButtons) { ?>
                    <div class="input-group-sm has-feedback pull-right">
                        <a class="btn btn-default" href="/research/browse?dir=<?php echo rawurlencode($path); ?>">Close</a>
                    </div>
                    <?php } ?>
                </div>
                <div class="panel-body">
                    <?php echo $transformationText; ?>

                    <?php if ($transformationButtons) { ?>
                    <a class="reject-transformation btn btn-danger delete-all-metadata-btn pull-right" href="/research/browse?dir=<?php echo rawurlencode($path); ?>">Reject transformation</a>
                    <a class="accept-transformation btn btn-success delete-all-metadata-btn pull-right" href="/research/metadata/transformation?path=<?php echo rawurlencode($path); ?>">Accept transformation</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row metadata-form<?php echo !$showForm ? ' hide' : ''; ?>">
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

<script src="/research/static/js/metadata/form.js" type="text/javascript"></script>
