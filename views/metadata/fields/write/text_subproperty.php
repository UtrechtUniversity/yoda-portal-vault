<div class="form-group rowSubPropertyBase-<?php echo $e->subPropertiesBase;?>" xmlns="http://www.w3.org/1999/html">
    <label class="col-sm-2 control-label">
    </label>

    <div class="col-sm-7">
        <div class="row">
            <div class="col-sm-1">
            </div>
            <div class="col-sm-2">
                <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                    <?php echo $e->label; ?>
                </span>
            </div>
            <div class="col-sm-9">
                <input type="text"
                    <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
                       class="form-control" name="<?php echo $e->key; ?>" value="<?php echo htmlentities($e->value); ?>">
            </div>
        </div>
    </div>
</div>
