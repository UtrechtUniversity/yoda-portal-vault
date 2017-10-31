<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>

    <?php if ($e->compoundFieldPosition == 0) { // First field, add offset. ?>
         <?php if (!$e->subPropertiesBase ) { // This is first field of a compound ?>
             <div class="col-sm-4 col-sm-offset-2 no-padding">
        <?php }
            else // This is a start of a compound as a subproperty -> adjust space to accommodate label
            { ?>
            <div class="col-sm-4 col-sm-offset-1 no-padding">
        <?php } ?>

    <?php } else { ?>
        <div class="col-sm-4">
    <?php } ?>
        <label><?php echo $e->label; ?></label>
        <p class="form-control-static"><?php echo htmlentities($e->value); ?></p>
    </div>
    <?php } else { ?>


<div class="form-group">
    <label class="col-sm-2 control-label"><?php echo $e->label; ?></label>
    <div class="col-sm-9">
        <p class="form-control-static"><?php echo htmlentities($e->value); ?></p>
    </div>
</div>
<?php } ?>