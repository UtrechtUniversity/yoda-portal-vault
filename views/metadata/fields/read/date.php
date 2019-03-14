<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>
    <div class="col-sm-6 field">

        <label class="control-label">
            <?php echo $e->label; ?>
        </label>
        <p class="form-control-static">
            <?php if (!empty($e->value)) { ?>
                <?php echo date('Y-m-d', strtotime($e->value)); ?>
            <?php } ?>
        </p>
    </div>
<?php } else { ?>

<div class="form-group">
    <label class="col-sm-2 control-label"><?php echo $e->label; ?></label>

    <span class="fa-stack col-sm-1"></span>

    <div class="col-sm-9">
        <p class="form-control-static">
            <?php if (!empty($e->value)) { ?>
                <?php echo date('Y-m-d', strtotime($e->value)); ?>
            <?php } ?>
        </p>
    </div>
</div>
<?php } ?>