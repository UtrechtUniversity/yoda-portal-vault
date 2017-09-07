<div class="form-group">
    <label class="col-sm-2 control-label"></label>
    <div class="col-sm-6">
        <div class="row">
            <div class="col-sm-2">
                <label>
                    <small><?php echo $e->label; ?></small>
                </label>
            </div>
            <div class="col-sm-10">
                <?php if (!empty($e->value)) { ?>
                    <?php echo date('Y-m-d', strtotime($e->value)); ?>
                <?php } ?>
            </div>
        </div>

    </div>
</div>