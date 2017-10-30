<?php if ($e->compoundFieldCount > 0) { ?>
    <?php if ($e->compoundFieldPosition == 0) { // First field, add offset. ?>
        <?php if (!$e->subPropertiesBase ) { // This is first field of a compound ?>
            <div class="field select2 col-sm-4 col-sm-offset-3 no-padding">
        <?php }
            else // This is a start of a compound as a subproperty -> adjust space to accommodate label
            { ?>
            <div class="field select2 col-sm-4 col-sm-offset-1 no-padding">
        <?php } ?>
    <?php } else { ?>
        <div class="field select2 col-sm-4">
    <?php } ?>

        <label class="control-label">
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
            <?php endif; ?>

            <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                <?php echo $e->label; ?>
            </span>
        </label>
        <select
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                name="<?php echo $e->key; ?>"
            <?php else: ?>
                name="<?php echo $e->key; ?>"
            <?php endif; ?>
                class="form-control">
            <option value="">-</option>
            <?php foreach ($e->options as $option) { ?>
                <option value="<?php echo $option; ?>" <?php echo ($option == $e->value) ? 'selected' : ''; ?>><?php echo $option; ?></option>
            <?php } ?>
        </select>
    </div>

    <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { ?>

    <span class="input-group-btn">
        <button class="btn btn-default duplicate-field combined-plus"
                data-clone="combined"  type="button">
            <i class="fa fa-plus" aria-hidden="true"></i>
        </button>
    </span>

<?php } ?>
<?php } else { ?>
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
                    <span class="fa-stack">
                    <?php
                        // simple reasoning: if mandatory and value is valid then checkmark can be placed. (be it by default or not)
                        if(in_array($e->value, $e->options)) { ?>
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
                     </span>
                <?php } ?>
            </div>

            <div class="col-sm-11">
                <?php if ($e->multipleAllowed()) { ?>
                    <div class="input-group">
                        <select
                                <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                                    data-structure-id="<?php echo $e->subPropertiesStructID; ?>"
                                    name="<?php echo $e->key; ?>"
                                <?php else: ?>
                                    name="<?php echo $e->key; ?>"
                                <?php endif; ?>
                                class="form-control">
                            <option value="">-</option>
                            <?php foreach ($e->options as $option) { ?>
                                <option value="<?php echo $option; ?>" <?php echo ($option == $e->value) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                            <?php } ?>
                        </select>
                        <span class="input-group-btn">
                            <button
                                    class="btn btn-default duplicate-field"
                                <?php if ($e->subPropertiesRole=='subPropertyStartStructure') { ?>
                                    data-clone="main"
                                <?php } else if ($e->subPropertiesRole=='subProperty') { ?>
                                    data-clone="subproperty"
                                <?php } ?>
                                    type="button">
                                <i class="fa fa-plus" aria-hidden="true"></i>
                            </button>
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
        </div>
    </div>
</div>

<?php } ?>