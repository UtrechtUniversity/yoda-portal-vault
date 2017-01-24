<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php echo $e->label; ?>
        <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" title="<?php echo $e->helpText; ?>"></i>
    </label>
    <div class="col-sm-6">

        <?php if ($e->multipleAllowed()) { ?>
            <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>[]" readonly="true">
        <?php } else { ?>
            <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>" readonly="true">
        <?php } ?>

        <!--<p class="help-block"><?php echo $e->helpText; ?></p>-->
    </div>
</div>