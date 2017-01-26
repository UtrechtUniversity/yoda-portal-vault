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
            <input type="text" class="form-control" name="<?php echo $e->key; ?>[]">
        <?php } else { ?>
            <input type="text" class="form-control" name="<?php echo $e->key; ?>">
        <?php } ?>

        <!--<p class="help-block"><?php echo $e->helpText; ?></p>-->
    </div>
</div>