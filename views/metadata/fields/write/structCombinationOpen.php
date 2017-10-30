<div class="combination-start form-group" data-backendLevel="<?php echo $e->compoundBackendArrayLevel?>">

    <?php // Only toplevel compounds can have titles o subPropertuesBase must be empty
        if (!$e->subPropertiesBase) { ?>

            <label class="col-sm-2 control-label">
                <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                    <?php echo $e->label; ?>
                </span>
            </label>

    <?php } ?>

    <!--
    <div class="form-group combination-start">
        <div class="col-sm-4 col-sm-offset-3 no-padding">
            <label>First name</label>
            <input type="text" class="form-control" name="person[0][Properties][name][0][first]">
        </div>
        <div class="col-sm-4">
            <label>Last name</label>
            <input type="text" class="form-control" name="Related_Datapackage[0][Properties][name][0][last]">
        </div>
        <span class="input-group-btn">
            <button class="btn btn-default duplicate-field combined-plus" data-clone="combined" type="button">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </span>
    </div>
    -->