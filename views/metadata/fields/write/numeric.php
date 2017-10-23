<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>

<?php if ($e->compoundFieldPosition == 0) { // First field, add offset. ?>
<div class="col-sm-4 col-sm-offset-3 no-padding">
    <?php } else { ?>
    <div class="col-sm-4">
        <?php } ?>
        <label class="control-label">
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
            <?php endif; ?>

            <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                <?php echo $e->label; ?>
            </span>
        </label>

        <input type="text"
               placeholder="Enter a valid number..."
            <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
               class="form-control numeric-field"
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
            <?php else: ?>
                name="<?php echo $e->key; ?>[]"
            <?php endif; ?>
               value="<?php echo htmlentities($e->value); ?>">

    <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { // present button only when compound is clonable and at end of element range ?>

        <span class="input-group-btn">
            <button class="btn btn-default duplicate-field combined-plus"
                    data-clone="combined"  type="button">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </span>

    <?php } ?>
    <?php } else { ?>

<div class="form-group" xmlns="http://www.w3.org/1999/html">
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
                    <?php if($e->value and is_numeric($e->value)) { ?>
                        <span class="fa-stack ">
                            <?php
                                // this is added as stacked icons make tooltip handling harder.
                                $toolTipLock = '';
                                $toolTipCheckmark = 'aria-hidden="true" data-toggle="tooltip" title="Filled out correctly for the vault"';
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
                        <input type="text"
                               placeholder="Enter a valid number..."
                            <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
                               class="form-control numeric-field"
                            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                                data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                                name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                            <?php else: ?>
                                name="<?php echo $e->key; ?>[]"
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

                    <input type="text"
                           placeholder="Enter a valid number..."
                        <?php if($e->maxLength>0) { echo 'maxlength="' . $e->maxLength .'"'; } ?>
                           class="form-control numeric-field" name="<?php echo $e->key; ?>" value="<?php echo htmlentities($e->value); ?>">

                <?php } ?>
            </div>
         </div>
        <?php  get_instance()->load->view('metadata/fields/write/compound-duplicate-button', array('e',$e)); ?>
    </div>
</div>
<?php } ?>