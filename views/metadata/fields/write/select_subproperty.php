<div class="form-group subproperty rowSubPropertyBase-<?php echo $e->subPropertiesBase;?>-<?php echo $e->subPropertiesStructID; ?> select2" xmlns="http://www.w3.org/1999/html">
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
                <select
                    <?php if ($e->subPropertiesStructID>-1) { ?>
                        name="<?php echo $e->key; ?>[<?php echo $e->subPropertiesStructID; ?>]"
                    <?php } else { ?>
                        name="<?php echo $e->key; ?>"
                    <?php } ?>
                    class="form-control">

                    <option value="">-</option>
                    <?php foreach ($e->options as $option) { ?>
                        <option value="<?php echo $option; ?>" <?php echo ($option == $e->value) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>
</div>