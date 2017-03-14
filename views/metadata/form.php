<?php if (empty($form)) { ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h3 class="panel-title pull-left">
                    Metadata form - <?php echo $path; ?>
                </h3>
                <div class="input-group-sm has-feedback pull-right">
                    <a class="btn btn-default" href="/research/browse?dir=<?php echo urlencode($path); ?>">Close</a>
                </div>
            </div>
            <div class="panel-body">
                <p>It is not possible to load this form due to the formatting of the metadata xml file.<br>
                    Please check the structure of this file. <br>
                    <br>
                    When using the 'Delete all metadata' button beware that you will lose all data!

                    <?php if ($userType == 'normal' || $userType == 'manager') { ?>
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
            <?php echo $form->open('research/metadata/store?path=' . urlencode($path), 'form-horizontal metadata-form'); ?>
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <h3 class="panel-title pull-left">
                        Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities( trim( $path ))); ?>
                    </h3>
                    <div class="input-group-sm has-feedback pull-right">
                        <a class="btn btn-default" href="/research/browse?dir=<?php echo urlencode($path); ?>">Close</a>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if ($form === false) { ?>
                        <p>It is not possible to load this form due to the formatting of the metadata xml file.<br>
                            Please check the structure of this file. <br>
                            <br>
                            When using the 'Delete all metadata' button beware that you will lose all data!


                            <?php if ($form->getPermission() == 'write' && $metadataExists) { ?>
                                <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo urlencode($path); ?>">Delete all metadata</button>
                            <?php } ?>
                        </p>
                    <?php } else { ?>

                        <?php if ($flashMessage) { ?>
                            <div class="alert alert-<?php echo $flashMessageType; ?>">
                                <?php echo $flashMessage; ?>
                            </div>
                        <?php } ?>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <?php if ($form->getPermission() == 'write') { ?>

                                    <button type="submit" class="btn btn-primary">Save</button>

                                    <?php if ($metadataCompleteness == 100 && $submitToVaultBtn) { ?>
                                        <button type="submit" name="vault_submission" value="1" class="btn btn-primary">Submit to vault</button>
                                    <?php } ?>

                                    <span  class="add-pointer" aria-hidden="true" data-toggle="tooltip" title="Required for the vault:  <?php echo $mandatoryTotal; ?>, currently filled required fields: <?php  echo $mandatoryFilled; ?>">
                                        <i class="fa fa-check <?php echo $metadataCompleteness>19 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>39 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>59 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>79 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>99 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                    </span>

                                <?php } ?>
                                <?php if ($form->getPermission() == 'write' && $metadataExists) { ?>
                                    <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo urlencode($path); ?>">Delete all metadata</button>
                                <?php } ?>

                                <?php if (($form->getPermission() == 'write' && $metadataExists === false) && $cloneMetadata) { ?>
                                    <button type="button" class="btn btn-primary clone-metadata-btn pull-right" data-path="<?php echo urlencode($path); ?>">Clone from parent folder</button>
                                <?php } ?>
                            </div>
                            <div class="col-sm-12">


                            </div>
                        </div>

                        <?php if ($form->getPermission() == 'read' && $realMetadataExists === false) { ?>
                            <p>
                                There is no metadata present for this folder.
                            </p>
                        <?php } else { ?>
                            <?php foreach ($form->getSections() as $k => $name) { ?>
                                <fieldset>
                                    <legend><?php echo $name; ?></legend>
                                    <?php echo $form->show($name); ?>
                                </fieldset>
                            <?php } ?>
                        <?php } ?>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <?php if ($form->getPermission() == 'write') { ?>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                <?php } ?>
                                <?php if ($form->getPermission() == 'write' && $metadataExists) { ?>
                                    <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo $path; ?>">Delete all metadata</button>
                                <?php } ?>

                                <?php if (($form->getPermission() == 'write' && $metadataExists === false) && $cloneMetadata) { ?>
                                    <button type="button" class="btn btn-primary clone-metadata-btn pull-right" data-path="<?php echo $path; ?>">Clone from parent folder</button>
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
