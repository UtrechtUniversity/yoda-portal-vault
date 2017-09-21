<div class="form-group subproperty" xmlns="http://www.w3.org/1999/html">
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
                <!--
                <textarea
                    <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
                    class="form-control"
                    <?php if ($e->subPropertiesStructID>-1) { ?>
                        name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                    <?php } else { ?>
                        name="<?php echo $e->key; ?>"
                    <?php } ?>
                    ><?php echo htmlentities($e->value); ?>
                </textarea>-->

                <textarea
                    <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
                    <?php if ($e->subPropertiesStructID>-1) { ?>
                        name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                    <?php } else { ?>
                        name="<?php echo $e->key; ?>"
                    <?php } ?>
                        class="form-control"><?php echo htmlentities($e->value); ?></textarea>
            </div>
        </div>
    </div>
</div>