<div class="form-group">
    <label class="col-sm-2 control-label">
        <?php echo $e->label; ?>
        <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" title="<?php echo $e->helpText; ?>"></i>
    </label>
    <div class="col-sm-3">

        <?php if ($e->multipleAllowed()) { ?>
            <div class="input-group">
                <select name="<?php echo $e->key; ?>[]" class="form-control">
                    <option value="">-</option>
                    <?php foreach ($e->options as $option) { ?>
                        <option value="<?php echo $option; ?> <?php echo ($option == $e->value) ? 'selected' : ''; ?>"><?php echo $option; ?></option>
                    <?php } ?>
                </select>
                <span class="input-group-btn">
                    <button class="btn btn-default extend-field" type="button"><i class="fa fa-plus" aria-hidden="true"></i></button>
                </span>
            </div>
        <?php } else { ?>
            <select name="<?php echo $e->key; ?>" class="form-control">
                <option value="">-</option>
                <?php foreach ($this->options as $option) { ?>
                    <option value="<?php echo $option; ?> <?php echo ($option == $e->value) ? 'selected' : ''; ?>"><?php echo $option; ?></option>
                <?php } ?>
            </select>
        <?php } ?>
    </div>
</div>