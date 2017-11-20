<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>

    <div class="col-sm-6 field">
        <label class="control-label">
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
            <?php endif; ?>

            <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                <?php echo $e->label; ?>
            </span>
        </label>

    <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { // present button only when compound is clonable and at end of element range ?>
        <div class="input-group">
    <?php } ?>

        <input
            type="text"
            placeholder="<?php echo $e->pattern; ?>"
            class="form-control"
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
            <?php else: ?>
                name="<?php echo $e->key; ?>"
            <?php endif; ?>
            value="<?php echo htmlentities($e->value); ?>">

        <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { // present button only when compound is clonable and at end of element range ?>


            <span class="input-group-btn">
                <button class="btn btn-default duplicate-field combined-plus"
                        data-clone="combined"  type="button">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </button>
            </span>
        </div>

        <?php } ?>

    </div>
    <?php } else { ?>

<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
            <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-subpropertyBase="<?php echo $e->subPropertiesBase; ?>"  data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
        <?php endif; ?>

        <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>"><?php echo $e->label; ?></span>
    </label>

     <span class="fa-stack col-sm-1">
        <?php if ($e->mandatory) { ?>

            <?php if($e->value) { ?>
                <?php
                // this is added as stacked icons make tooltip handling harder.
                $toolTipLock = '';
                $toolTipCheckmark = 'aria-hidden="true" data-toggle="tooltip" title="Filled out correctly for the vault"';
                ?>

                <i class="fa fa-lock safe fa-stack-1x" <?php echo $toolTipLock; ?> ></i>
                <i class="fa fa-check fa-stack-1x checkmark-green-top-right" <?php echo $toolTipCheckmark; ?> ></i>
            <?php } else { ?>
                <i class="fa fa-lock safe fa-stack-1x" aria-hidden="true" data-toggle="tooltip" title="Required for the vault"></i>
            <?php } ?>
        <?php } ?>
     </span>

    <div class="col-sm-9">
        <div class="row">

            <div class="col-sm-12">
                <?php if ($e->multipleAllowed()) { ?>
                    <!--
                    <div class="input-group">

                        <input type="text" class="form-control datepicker" name="<?php echo $e->key; ?>[]" readonly="true" value="<?php echo htmlentities($e->value); ?>">
                        <span class="input-group-btn">
                            <button class="btn btn-default duplicate-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                        </span>
                    </div>
                    -->

                    <div class="input-group">
                        <input
                                type="text"
                                placeholder="<?php echo $e->pattern; ?>"
                                class="form-control"
                                <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                                    data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                                    name="<?php echo $e->key; ?>"
                                <?php else: ?>
                                    name="<?php echo $e->key; ?>"
                                <?php endif; ?>
                                value="<?php echo htmlentities($e->value); ?>">
                        <span class="input-group-btn">
                            <?php if ($e->subPropertiesRole=='subPropertyStartStructure') { ?>
                                <button class="btn btn-default duplicate-subproperty-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                            <?php } else { ?>
                                <button class="btn btn-default duplicate-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                            <?php } ?>
                        </span>
                    </div>
                <?php } else { ?>
                        <input type="text" placeholder="<?php echo $e->pattern; ?>" class="form-control" name="<?php echo $e->key; ?>" value="<?php echo htmlentities($e->value); ?>">
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>