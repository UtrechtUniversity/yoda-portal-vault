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
                        <div class="form-group">
                            <div class="col-sm-12">

                                <?php if ($showUnsubmitBtn) { ?>
                                    <button type="submit" name="vault_unsubmission" value="1" class="btn btn-primary">Unsubmit</button>
                                <?php } ?>

                                <?php if ($showEditBtn) { ?>
                                    <a href="<?php echo base_url('research/metadata/form?path=' . urlencode($path) . '&mode=edit_in_vault'); ?>" class="btn btn-primary">Update metadata</a>
                                <?php } ?>

                                <?php if (($form->getPermission() == 'write' && $metadataExists === false) && $cloneMetadata) { ?>
                                    <button type="button" class="btn btn-primary clone-metadata-btn pull-right" data-path="<?php echo urlencode($path); ?>">Clone from parent folder</button>
                                <?php } ?>

                            </div>
                        </div>

                        <div id="form" class="metadata-form"
                             data-path="<?php echo urlencode($path); ?>"
                             data-csrf_token_name="<?php echo urlencode($tokenName); ?>"
                             data-csrf_token_hash="<?php echo urlencode($tokenHash); ?>">
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <?php if ($showUnsubmitBtn) { ?>
                                    <button type="submit" name="vault_unsubmission" value="1" class="btn btn-primary">Unsubmit</button>
                                <?php } ?>

                                <?php if ($metadataCompleteness == 100 && $submitToVaultBtn) { ?>
                                    <button type="submit" name="vault_submission" value="1" class="btn btn-primary">Submit</button>
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

<?php if ($messageDatamanagerAfterSaveInVault) { // trick to display data via central messaging system ?>
        <script language="javascript">
            setMessage('success', '<?php echo $messageDatamanagerAfterSaveInVault; ?>');
        </script>
<?php } ?>

<script src="/research/static/js/metadata/bundle.js" type="text/javascript"></script>