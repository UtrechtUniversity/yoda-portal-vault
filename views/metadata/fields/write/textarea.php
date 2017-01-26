<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php echo $e->label; ?>
        <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" title="<?php echo $e->helpText; ?>"></i>
    </label>
    <div class="col-sm-6">

        <?php if ($e->multipleAllowed()) { ?>
            <div class="input-group">
                <textarea class="form-control" name="<?php echo $e->key; ?>[]"></textarea>
                <span class="input-group-btn">
                    <button class="btn btn-default extend-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                </span>
            </div>
        <?php } else { ?>
            <textarea class="form-control" name="<?php echo $e->key; ?>"></textarea>
        <?php } ?>
    </div>
</div>