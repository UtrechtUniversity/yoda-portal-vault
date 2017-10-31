<div class="combination-start form-group">
    <?php // Only toplevel compounds can have titles o subPropertuesBase must be empty
    if (!$e->subPropertiesBase) { ?>

        <label class="col-sm-2 control-label">
                <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                    <?php echo $e->label; ?>
                </span>
        </label>

    <?php } else { // This is a compound witin a subproperty structure -> sm-1 instead of sm-2
        //  - This only works with select as being a first element as an addition is required for each element!
        // - at the moment only implemeneted for selects
        ?>
        <label class="col-sm-1 control-label">
                <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                    <?php echo $e->label; ?>
                </span>
        </label>

    <?php } ?>

    <!--
    <hr>
    COMBINATION  Start tag <?php echo $e->key; ?>
    <br>Is mandatory? <?php echo $e->mandatory ? 'YES' : 'NO' ?>
    <br>Multi? <?php echo $e->multipleAllowed() ? 'YES' : 'NO' ?>
    <hr>
    -->