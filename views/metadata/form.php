<div id="form-errors" class="row hide">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h3 class="panel-title pull-left">
                    Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities(trim($path))); ?>
                </h3>
                <div class="input-group-sm has-feedback pull-right">
                    <a class="btn btn-default" href="/vault/browse?dir=<?php echo rawurlencode($path); ?>">Close</a>
                </div>
            </div>
            <div class="panel-body">
                <p>
                    It is not possible to load this form as the yoda-metadata.json file is not
                    in accordance with the form definition.
                </p>
                <p>
                    Please check the following in your JSON file:
                </p>
                    <ul class="error-fields"></ul>
            </div>
        </div>
    </div>
</div>

<div id="metadata-form" class="row hide">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h3 class="panel-title pull-left">
                    Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities(trim($path))); ?>
                </h3>
                <div class="input-group-sm has-feedback pull-right close-button">
                    <a class="btn btn-default" href="/vault/browse?dir=<?php echo rawurlencode($path); ?>">Close</a>
                </div>
            </div>
            <div class="panel-body">
                <div id="no-metadata" class="hide">
                    <p>There is no metadata present for this folder.</p>
                </div>
                <div id="form"
                     data-path="<?php echo htmlentities($path); ?>"
                     data-csrf_token_name="<?php echo rawurlencode($tokenName); ?>"
                     data-csrf_token_hash="<?php echo rawurlencode($tokenHash); ?>">
                    <p>Loading metadata <i class="fa fa-spinner fa-spin fa-fw"></i></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show "loading" text if loading the form takes longer than expected.
var formLoaded = false;
setTimeout(function(){
    if (!formLoaded) {
        $('#metadata-form').fadeIn(200);
        $('#metadata-form').removeClass('hide');
    }
}, 800);
</script>
<script src="/vault/static/js/metadata/form.js" async></script>
<script id="form-properties" type="text/plain"><?php
// base64-encode to make sure no script tag can be embedded.
echo base64_encode(json_encode($formProperties))
?></script>
