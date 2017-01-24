<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php echo $e->label; ?>
        <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" title="<?php echo $e->helpText; ?>"></i>
    </label>
    <div class="col-sm-3">

        <?php if ($e->multipleAllowed()) { ?>
            <select name="<?php echo $e->key; ?>[]" class="form-control">
                <?php foreach ($e->options as $option) { ?>
                    <option value="<?php echo $option; ?> <?php echo ($option == $e->value) ? 'selected' : ''; ?>"><?php echo $option; ?></option>
                <?php } ?>
            </select>
        <?php } else { ?>
            <select name="<?php echo $e->key; ?>" class="form-control">
                <?php foreach ($this->options as $option) { ?>
                    <option value="<?php echo $option; ?> <?php echo ($option == $e->value) ? 'selected' : ''; ?>"><?php echo $option; ?></option>
                <?php } ?>
            </select>
        <?php } ?>
    </div>
</div>