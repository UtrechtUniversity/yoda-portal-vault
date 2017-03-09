<div class="form-group">
    <label class="col-sm-2 control-label">
        <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>"><?php echo $e->label; ?></span>
    </label>

    <div class="col-sm-7">
        <div class="row">

            <div class="col-sm-1">
                <?php if ($e->mandatory) { ?>
                    <?php if($metadataExists) { ?>
                        <span class="fa-stack ">
                            <?php
                            // this is added as stacked icons make tooltip handling harder.
                            $toolTipLock = '';
                            $toolTipCheckmark = '';
                            if($e->value) {
                                $toolTipCheckmark = 'aria-hidden="true" data-toggle="tooltip" title="Filled out correctly for the vault"';
                            }
                            else {
                                $toolTipLock = 'aria-hidden="true" data-toggle="tooltip" title="Required for the vault"';
                            }
                            ?>

                            <i class="fa fa-lock safe fa-stack-1x" <?php echo $toolTipLock; ?> ></i>
                            <?php if($e->value) { ?>
                                <i class="fa fa-check fa-stack-1x checkmark-green-top-right" <?php echo $toolTipCheckmark; ?> ></i>
                            <?php } ?>
                        </span>
                    <?php } else { ?>
                        <i class="fa fa-lock safe-single" aria-hidden="true" data-toggle="tooltip" title="Required for the vault"></i>
                    <?php } ?>
                <?php } ?>
            </div>

            <div class="col-sm-11">
                <?php if ($e->multipleAllowed()) { ?>
                    <div class="input-group">
                        <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>[]" readonly="true" value="<?php echo htmlentities($e->value); ?>">
                        <span class="input-group-btn">
                            <button class="btn btn-default duplicate-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                        </span>
                    </div>
                <?php } else { ?>
                    <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>" readonly="true" value="<?php echo htmlentities($e->value); ?>">
                <?php } ?>
            </div>
        </div>
    </div>
</div>