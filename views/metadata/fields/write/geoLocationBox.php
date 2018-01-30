<?php if ($e->compoundFieldCount > 0) { // Compound field structure ?>

    <div class="col-sm-6 field">
        <label class="control-label">
            <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
                <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
            <?php endif; ?>

            <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
                <?php echo $e->label; ?>
            </span>
        </label>

    <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { // present button only when compound is clonable and at end of element range ?>
        <div class="input-group">
    <?php } ?>
            <!-- Button trigger modal -->
            <button type="button" class="btn geo-location-modal-btn" data-toggle="modal">
                <i class="fa fa-plus" aria-hidden="true"></i> Show map
            </button>
            N: <span class="north small"><?php echo htmlentities($e->value['northBoundLatitude']); ?></span> E: <span class="east small"><?php echo htmlentities($e->value['eastBoundLongitude']); ?></span> S: <span class="south small"><?php echo htmlentities($e->value['southBoundLatitude']); ?></span> W: <span class="west small"><?php echo htmlentities($e->value['westBoundLongitude']); ?></span>
            <!-- Modal -->
            <div class="modal geo-location-modal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="geo-location-map" data-key="<?php echo $e->key ?>" style="width: 860px; height: 500px;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>

            <input type="hidden"
                   name="<?php echo $e->key . '[westBoundLongitude]'; ?>"
                   value="<?php echo htmlentities($e->value['westBoundLongitude']); ?>">

            <input type="hidden"
                   name="<?php echo $e->key . '[eastBoundLongitude]'; ?>"
                   value="<?php echo htmlentities($e->value['eastBoundLongitude']); ?>">

            <input type="hidden"
                   name="<?php echo $e->key . '[southBoundLatitude]'; ?>"
                   value="<?php echo htmlentities($e->value['southBoundLatitude']); ?>">

            <input type="hidden"
                   name="<?php echo $e->key . '[northBoundLatitude]'; ?>"
                   value="<?php echo htmlentities($e->value['northBoundLatitude']); ?>">


            <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { // present button only when compound is clonable and at end of element range ?>

            <span class="input-group-btn">
                <button class="btn btn-default clone-btn duplicate-field combined-plus"
                        data-clone="combined"  type="button">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </button>
            </span>

        </div>

        <?php } ?>

    </div>
<?php } else { ?>
<div class="form-group geo-location" xmlns="http://www.w3.org/1999/html">
    <label class="col-sm-2 control-label">
        <?php if ($e->subPropertiesRole=='subPropertyStartStructure'): ?>
            <i data-structure-id="<?php echo $e->subPropertiesStructID; ?>" class="glyphicon glyphicon-chevron-down subproperties-toggle" data-toggle="tooltip" title="Click to open or close view on subproperties" data-html="true"></i>&nbsp;
        <?php endif; ?>

        <span data-toggle="tooltip" title="<?php echo $e->helpText; ?>">
            <?php echo $e->label; ?>
        </span>
    </label>

    <span class="fa-stack col-sm-1">
        <?php if ($e->mandatory) { ?>
            <?php if($e->value) { ?>
                    <?php
                        // this is added as stacked icons make tooltip handling harder.
                        $toolTipLock = '';
                        $toolTipCheckmark = 'aria-hidden="true" data-toggle="tooltip" title="Filled out correctly for the vault"';
                    ?>

                    <i class="fa fa-lock safe fa-stack-1x" <?php echo $toolTipLock; ?> ></i>
                    <i class="fa fa-check fa-stack-1x checkmark-green-top-right" <?php echo $toolTipCheckmark; ?> ></i>
            <?php } else { ?>
                <i class="fa fa-lock safe fa-stack-1x" aria-hidden="true" data-toggle="tooltip" title="Required for the vault"></i>
            <?php } ?>
        <?php } ?>
    </span>

    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-12">
                <?php if ($e->multipleAllowed()) { ?>
                    <div class="input-group">
                        <!-- Button trigger modal -->
                        <button type="button" class="btn geo-location-modal-btn" data-toggle="modal">
                            <i class="fa fa-plus" aria-hidden="true"></i> Show map
                        </button>
                        <i class="fa fa-compass" aria-hidden="true"></i>
                        N: <span class="north small"><?php echo htmlentities($e->value['northBoundLatitude']); ?></span> E: <span class="east small"><?php echo htmlentities($e->value['eastBoundLongitude']); ?></span> S: <span class="south small"><?php echo htmlentities($e->value['southBoundLatitude']); ?></span> W: <span class="west small"><?php echo htmlentities($e->value['westBoundLongitude']); ?></span>
                        <!-- Modal -->
                        <div class="modal geo-location-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <div class="geo-location-map" data-key="<?php echo $e->key ?>" ></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden"
                               name="<?php echo $e->key . '[westBoundLongitude]'; ?>"
                               value="<?php echo htmlentities($e->value['westBoundLongitude']); ?>">

                        <input type="hidden"
                               name="<?php echo $e->key . '[eastBoundLongitude]'; ?>"
                               value="<?php echo htmlentities($e->value['eastBoundLongitude']); ?>">

                        <input type="hidden"
                               name="<?php echo $e->key . '[southBoundLatitude]'; ?>"
                               value="<?php echo htmlentities($e->value['southBoundLatitude']); ?>">

                        <input type="hidden"
                               name="<?php echo $e->key . '[northBoundLatitude]'; ?>"
                               value="<?php echo htmlentities($e->value['northBoundLatitude']); ?>">


                        <span class="input-group-btn">
                            <button
                                    class="btn btn-default clone-btn duplicate-field"
                                <?php if ($e->subPropertiesRole=='subPropertyStartStructure') { ?>
                                    data-clone="main"
                                <?php } else if ($e->subPropertiesRole=='subProperty') { ?>
                                    data-clone="subproperty"
                                <?php } ?>
                                    type="button">
                                <i class="fa fa-plus" aria-hidden="true"></i>
                            </button>
                        </span>
                    </div>
                <?php } else { ?>
                <!-- Button trigger modal -->
                <button type="button" class="btn geo-location-modal-btn" data-toggle="modal">
                    <i class="fa fa-plus" aria-hidden="true"></i> Show map
                </button>

                <!-- Modal -->
                <div class="modal geo-location-modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-body">
                                <div class="geo-location-map" data-key="<?php echo $e->key ?>"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>


                    <input type="hidden"
                           name="<?php echo $e->key . '[westBoundLongitude]'; ?>"
                           value="<?php echo htmlentities($e->value['westBoundLongitude']); ?>">

                    <input type="hidden"
                           name="<?php echo $e->key . '[eastBoundLongitude]'; ?>"
                           value="<?php echo htmlentities($e->value['eastBoundLongitude']); ?>">

                    <input type="hidden"
                           name="<?php echo $e->key . '[southBoundLatitude]'; ?>"
                           value="<?php echo htmlentities($e->value['southBoundLatitude']); ?>">

                    <input type="hidden"
                           name="<?php echo $e->key . '[northBoundLatitude]'; ?>"
                           value="<?php echo htmlentities($e->value['northBoundLatitude']); ?>">

                <?php } ?>


            </div>
        </div>
    </div>
</div>
<?php } ?>