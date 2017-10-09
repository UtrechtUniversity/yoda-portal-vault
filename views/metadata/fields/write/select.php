<div class="form-group select2">
    <label class="col-sm-2 control-label">
        <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
            <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-subpropertyBase="<?php echo $e->subPropertiesBase; ?>"  data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
        <?php endif; ?>
        <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>"><?php echo $e->label; ?></span>
    </label>

    <div class="col-sm-9">
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
                        <select
                                <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                                    data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                                    name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                                <?php else: ?>
                                    name="<?php echo $e->key; ?>[]"
                                <?php endif; ?>
                                class="form-control">
                            <option value="">-</option>
                            <?php foreach ($e->options as $option) { ?>
                                <option value="<?php echo $option; ?>" <?php echo ($option == $e->value) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                            <?php } ?>
                        </select>
                        <span class="input-group-btn">
                            <?php if ($e->subPropertiesRole=='subPropertyStartStructure') { ?>
                                <button class="btn btn-default duplicate-subproperty-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                            <?php } else { ?>
                                <button class="btn btn-default duplicate-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                            <?php } ?>
                        </span>

                    </div>
                <?php } else { ?>
                    <select name="<?php echo $e->key; ?>" class="form-control">
                        <option value="">-</option>
                        <?php foreach ($e->options as $option) { ?>
                            <option value="<?php echo $option; ?>" <?php echo ($option == $e->value) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php } ?>
                    </select>
                <?php } ?>
            </div>

            <?php if ($e->compoundFieldPosition ==($e->compoundFieldCount-1) ) {
                ?>
                <span class="input-group-btn">
                    <button class="btn btn-default duplicate-field combined-plus"
                            data-clone="combined"  type="button">
                        <i class="fa fa-plus" aria-hidden="true"></i>
                    </button>
                </span>
                <?php
            } ?>


        </div>
    </div>
</div>