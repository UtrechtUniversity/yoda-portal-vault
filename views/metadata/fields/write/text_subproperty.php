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
                       class="form-control"
                       <?php if ($e->subPropertiesStructID==''): ?>
                           name="<?php echo $e->key; ?>"
                        <?php else: ?>
                           data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                           name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                        <?php endif; ?>
                       value="<?php echo htmlentities($e->value); ?>">
            </div>
        </div>
    </div>
</div>
