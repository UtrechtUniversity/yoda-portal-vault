<div class="form-group">
    <label class="col-sm-2 control-label"><?php echo $e->label; ?></label>
    <div class="col-sm-6">
        <p class="form-control-static">
            <?php if (!empty($e->value)) { ?>
                <?php echo date('Y-m-d', strtotime($e->value)); ?>
            <?php } ?>
        </p>
    </div>
</div>