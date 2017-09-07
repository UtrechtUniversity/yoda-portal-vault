<div class="form-group subproperty rowSubPropertyBase-<?php echo $e->subPropertiesBase;?>-<?php echo $e->subPropertiesStructID; ?>" xmlns="http://www.w3.org/1999/html">
    <label class="col-sm-2 control-label">
    </label>

    <div class="col-sm-7">
        <div class="row">
            <div class="col-sm-1">
            </div>
            <div class="col-sm-2">
                <label data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                    <small><?php echo $e->label; ?></small>
                </label>
            </div>
            <div class="col-sm-9">
                <input type="text"
                       placeholder="Enter a valid number..."
                    <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
                       class="form-control numeric-field"

                    <?php if ($e->subPropertiesStructID>-1) { ?>
                        name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                    <?php } else { ?>
                        name="<?php echo $e->key; ?>"
                    <?php } ?>

                       value="<?php echo htmlentities($e->value); ?>">
            </div>
        </div>
    </div>
</div>