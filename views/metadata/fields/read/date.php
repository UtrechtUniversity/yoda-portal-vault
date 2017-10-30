<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>

    <?php if ($e->compoundFieldPosition == 0) { // First field, add offset. ?>
        <div class="col-sm-4 col-sm-offset-2 no-padding">
    <?php } else { ?>
        <div class="col-sm-4">
    <?php } ?>
            <label><?php echo $e->label; ?></label>
            <p class="form-control-static">
                <?php if (!empty($e->value)) { ?>
                    <?php echo date('Y-m-d', strtotime($e->value)); ?>
                <?php } ?>
            </p>
        </div>
<?php } else { ?>

<div class="form-group">
    <label class="col-sm-2 control-label"><?php echo $e->label; ?></label>
    <div class="col-sm-9">
        <p class="form-control-static">
            <?php if (!empty($e->value)) { ?>
                <?php echo date('Y-m-d', strtotime($e->value)); ?>
            <?php } ?>
        </p>
    </div>
</div>
<?php } ?>