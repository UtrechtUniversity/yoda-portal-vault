<div class="form-group">
    <label class="col-sm-2 control-label"><?php echo $e->label; ?></label>
    <div class="col-sm-6">
        <p class="form-control-static"><?php echo date('Y-m-d', strtotime($e->value)); ?></p>
    </div>
</div>