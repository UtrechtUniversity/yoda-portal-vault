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

                    <?php if ($userType == 'normal' || $userType == 'manager') { ?>
                        <br>
                        When using the 'Delete all metadata' button beware that you will lose all data!

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
            <?php echo $form->open('research/metadata/store?path=' . rawurlencode($path), 'form-horizontal metadata-form'); ?>
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <h3 class="panel-title pull-left">
                        Metadata form - <?php echo str_replace(' ', '&nbsp;', htmlentities( trim( $path ))); ?>
                    </h3>
                    <div class="input-group-sm has-feedback pull-right">
                        <a class="btn btn-default" href="/research/browse?dir=<?php echo rawurlencode($path); ?>">Close</a>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if ($form === false OR $validationResult !== true) { ?>
                        <p>
                            <?php if (is_array($validationResult)){ ?>
                                It is not possible to load this form as the metadata xml file is not in accordance with the form definition.<br>
                                <br>Check the following in your xml file:
                                <?php foreach ($validationResult as $error): ?>
                                    <?php echo '<br>-' . $error; ?>
                                <?php endforeach; ?>
                                <br>
                             <?php } else { ?>
                                It is not possible to load this form due to the formatting of the metadata xml file.<br>
                                Please check the structure of this file. <br>
                            <?php } ?>

                            <?php if ($form->getPermission() == 'write' && $metadataExists) { ?>
                                <br>
                                When using the 'Delete all metadata' button beware that you will lose all data!

                                <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo rawurlencode($path); ?>">Delete all metadata</button>
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

                                <?php if ($showUnsubmitBtn) { ?>
                                    <button type="submit" name="vault_unsubmission" value="1" class="btn btn-primary">Unsubmit</button>
                                <?php } ?>

                                <?php if ($showEditBtn) { ?>
                                    <a href="<?php echo base_url('research/metadata/form?path=' . rawurlencode($path) . '&mode=edit_in_vault'); ?>" class="btn btn-primary">Update metadata</a>
                                <?php } ?>

                                <?php if ($form->getPermission() == 'write') { ?>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                <?php } ?>

                                <?php if ($metadataCompleteness == 100 && $submitToVaultBtn) { ?>
                                    <button type="submit" name="vault_submission" value="1" class="btn btn-primary">Submit</button>
                                <?php } ?>
                                <?php if($form->getPermission() == 'write') { ?>
                                    <span  class="add-pointer" aria-hidden="true" data-toggle="tooltip" title="Required for the vault:  <?php echo $mandatoryTotal; ?>, currently filled required fields: <?php  echo $mandatoryFilled; ?>">
                                        <i class="fa fa-check <?php echo $metadataCompleteness>19 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>39 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>59 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>79 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                        <i class="fa fa-check <?php echo $metadataCompleteness>99 ? 'form-required-present': 'form-required-missing'; ?>"></i>
                                    </span>
                                <?php } ?>

                                <?php if ($form->getPermission() == 'write' && $metadataExists && $isVaultPackage != 'yes') { ?>
                                    <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo rawurlencode($path); ?>">Delete all metadata</button>
                                <?php } ?>

                                <?php if (($form->getPermission() == 'write' && $metadataExists === false) && $cloneMetadata) { ?>
                                    <button type="button" class="btn btn-primary clone-metadata-btn pull-right" data-path="<?php echo rawurlencode($path); ?>">Clone from parent folder</button>
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


                                    <!-- SUB Combined field -->
                                    <?php if (false) { ?>
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
                                    <?php } ?>

                                </fieldset>
                            <?php } ?>
                        <?php } ?>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <?php if ($showUnsubmitBtn) { ?>
                                    <button type="submit" name="vault_unsubmission" value="1" class="btn btn-primary">Unsubmit</button>
                                <?php } ?>

                                <?php if ($form->getPermission() == 'write') { ?>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                <?php } ?>
                                <?php if ($metadataCompleteness == 100 && $submitToVaultBtn) { ?>
                                    <button type="submit" name="vault_submission" value="1" class="btn btn-primary">Submit</button>
                                <?php } ?>
                                <?php if ($form->getPermission() == 'write' && $metadataExists && $isVaultPackage != 'yes') { ?>
                                    <button type="button" class="btn btn-danger delete-all-metadata-btn pull-right" data-path="<?php echo $path; ?>">Delete all metadata</button>
                                <?php } ?>

                                <?php if (($form->getPermission() == 'write' && $metadataExists === false) && $cloneMetadata && $isVaultPackage != 'yes') { ?>
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

<?php if ($messageDatamanagerAfterSaveInVault) { // trick to display data via central messaging system ?>
        <script language="javascript">
            setMessage('success', '<?php echo $messageDatamanagerAfterSaveInVault; ?>');
        </script>
<?php } ?>
