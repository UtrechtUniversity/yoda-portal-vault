<?php if (empty($form)) { ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h3 class="panel-title pull-left">
                    Metadata form - <?php echo $path; ?>
                </h3>
                <div class="input-group-sm has-feedback pull-right">
                    <a class="btn btn-default" href="/research/browse?dir=<?php echo $path; ?>">Close</a>
                </div>
            </div>
            <div class="panel-body">
                <p>It is not possible to load this form due to the formatting of .yoda-metadata.xml.<br>
                    Please check the structure of this file. <br>
                    <br>
                    When using the 'Delete all metadata' button beware that you will lose all data!

                    <?php if ($userType != 'reader') { ?>
                        <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo $path; ?>">Delete all metadata</button>
                    <?php } ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php } else { ?>
    <div class="row">
        <div class="col-md-12">
            <?php echo $form->open('research/metadata/store?path=' . $path, 'form-horizontal metadata-form'); ?>
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <h3 class="panel-title pull-left">
                        Metadata form - <?php echo $path; ?>
                    </h3>
                    <div class="input-group-sm has-feedback pull-right">
                        <a class="btn btn-default" href="/research/browse?dir=<?php echo $path; ?>">Close</a>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if ($form === false) { ?>
                        <p>It is not possible to load this form due to the formatting of .yoda-metadata.xml.<br>
                            Please check the structure of this file. <br>
                            <br>
                            When using the 'Delete all metadata' button beware that you will lose all data!


                            <?php if ($userType != 'reader' && $metadataExists) { ?>
                                <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo $path; ?>">Delete all metadata</button>
                            <?php } ?>
                        </p>
                    <?php } else { ?>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <?php if ($form->getPermission() == 'write') { ?>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                <?php } ?>
                                <?php if ($userType != 'reader' && $metadataExists) { ?>
                                    <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo $path; ?>">Delete all metadata</button>
                                <?php } ?>

                                <?php if (($userType != 'reader' && $metadataExists === false) && $cloneMetadata) { ?>
                                    <button type="button" class="btn btn-primary clone-metadata-btn pull-right" data-path="<?php echo $path; ?>">Clone from parent folder</button>
                                <?php } ?>
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
                                    <button type="submit" class="btn btn-primary">Save</button>
                                <?php } ?>
                                <?php if ($userType != 'reader' && $metadataExists) { ?>
                                    <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo $path; ?>">Delete all metadata</button>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php echo $form->close(); ?>
        </div>
    </div>
<?php } ?>
