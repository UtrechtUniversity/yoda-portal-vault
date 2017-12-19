<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>
    <div class="col-sm-6 field">
        <label class="control-label">
            <?php echo $e->label; ?>
        </label>
        <button type="button" class="btn" data-toggle="modal" data-target="#<?php echo str_replace(array('[', ']'), array(), $e->key); ?>Modal">
            <i class="fa fa-plus" aria-hidden="true"></i> Show map
        </button>
    </div>
<?php } else { ?>
    <div class="form-group">
        <label class="col-sm-2 control-label"><?php echo $e->label; ?></label>
        <span class="fa-stack col-sm-1"></span>
        <div class="col-sm-9">
            <button type="button" class="btn" data-toggle="modal" data-target="#<?php echo str_replace(array('[', ']'), array(), $e->key); ?>Modal">
                <i class="fa fa-plus" aria-hidden="true"></i> Show map
            </button>
        </div>
    </div>
<?php } ?>

<input type="hidden"
       name="<?php echo $e->key . '[westBoundLongitude]'; ?>"
       value="<?php echo htmlentities($e->value['westBoundLongitude']); ?>">

<input type="hidden"
       name="<?php echo $e->key . '[eastBoundLongitude]'; ?>"
       value="<?php echo htmlentities($e->value['eastBoundLongitude']); ?>">

<input type="hidden"
       name="<?php echo $e->key . '[southBoundLatitude]'; ?>"
       value="<?php echo htmlentities($e->value['southBoundLatitude']); ?>">

<input type="hidden"
       name="<?php echo $e->key . '[northBoundLatitude]'; ?>"
       value="<?php echo htmlentities($e->value['northBoundLatitude']); ?>">

<script>
    $(function () {
        $('#<?php echo str_replace(array('[', ']'), array(), $e->key); ?>Modal').on('show.bs.modal', function (e) {

            var mapId = $(this).data('map');
            setTimeout(function() {
                var map = loadReadOnlyMap(mapId);
                map.invalidateSize();
            }, 10, mapId);

        });
    });
</script>

<!-- Modal -->
<div class="modal" id="<?php echo str_replace(array('[', ']'), array(), $e->key); ?>Modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-map="<?php echo str_replace(array('[', ']'), array(), $e->key); ?>">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div id="<?php echo str_replace(array('[', ']'), array(), $e->key); ?>" data-key="<?php echo $e->key ?>" style="width: 860px; height: 500px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

