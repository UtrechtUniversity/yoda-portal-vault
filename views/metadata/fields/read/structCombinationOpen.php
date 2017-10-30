<div class="combination-start form-group">
    <?php // Only toplevel compounds can have titles o subPropertuesBase must be empty
    if (!$e->subPropertiesBase) { ?>

        <label class="col-sm-2 control-label">
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