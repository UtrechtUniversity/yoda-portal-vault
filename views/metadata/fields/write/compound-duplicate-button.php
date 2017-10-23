<?php
// present button only when compound is clonable and at end of element range
?>
    <?php if ( $e->compoundMultipleAllowed AND   $e->compoundFieldPosition ==($e->compoundFieldCount-1) ) { ?>

        <span class="input-group-btn">
            <button class="btn btn-default duplicate-field combined-plus"
                    data-clone="combined"  type="button">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </span>

    <?php } ?>