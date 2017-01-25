<div class="row">
    <div class="col-md-12">
        <?php echo $form->open('research/metadata/store', 'form-horizontal metadata-form'); ?>
        <a class="btn btn-default" href="/research/browse?dir=<?php echo $fullPath; ?>">Back to overview</a>
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Metadata form - <?php echo $path; ?></h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="col-sm-12">
                        <?php if ($form->getPermission() == 'write') { ?>
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <?php } ?>
                        <button type="button" class="btn btn-danger pull-right">Delete all metadata</button>
                    </div>
                </div>

                <?php foreach ($form->getSections() as $k => $name) { ?>
                    <fieldset>
                        <legend><?php echo $name; ?></legend>
                        <?php echo $form->show($name); ?>
                    </fieldset>
                <?php } ?>

                <div class="form-group">
                    <div class="col-sm-12">
                        <?php if ($form->getPermission() == 'write') { ?>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        <?php } ?>
                        <button type="button" class="btn btn-danger pull-right">Delete all metadata</button>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $form->close(); ?>
    </div>
</div>
