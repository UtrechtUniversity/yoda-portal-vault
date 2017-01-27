<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php echo $e->label; ?>
        <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" title="<?php echo $e->helpText; ?>"></i>
        <?php if ($e->mandatory) { ?>
            <i class="fa fa-lock safe" aria-hidden="true" data-toggle="tooltip" title="Required for the safe"></i>
        <?php } ?>
    </label>
    <div class="col-sm-6">

        <?php if ($e->multipleAllowed()) { ?>
            <div class="input-group">
                <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>[]" readonly="true">
                <span class="input-group-btn">
                    <button class="btn btn-default extend-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                </span>
            </div>
        <?php } else { ?>
            <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>" readonly="true">
        <?php } ?>

        <!--<p class="help-block"><?php echo $e->helpText; ?></p>-->
    </div>
</div>