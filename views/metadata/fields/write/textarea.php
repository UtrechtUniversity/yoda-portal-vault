<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php echo $e->label; ?>
        <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" title="<?php echo $e->helpText; ?>"></i>
    </label>
    <div class="col-sm-6">

        <?php if ($e->multipleAllowed()) { ?>
            <textarea class="form-control" name="<?php echo $e->key; ?>[]"></textarea>
        <?php } else { ?>
            <textarea class="form-control" name="<?php echo $e->key; ?>"></textarea>
        <?php } ?>
    </div>
</div>