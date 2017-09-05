<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = '<?php echo urlencode($dir); ?>';
    var view = 'browse';
</script>

<?php echo $searchHtml; ?>

<?php /**
 *
 *  @todo: Following dialog only to be taken in source when is vault and datapackage status is UNPUBLISHED
 */
?>
<div class="modal" id="confirmAgreementConditions">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <br>Version: <?php echo $confirmationVersion; ?>
                <br><i>Please scroll down to read the entire text and confirm to the conditions</i>
                <h3>Please confirm that you agree with following terms and conditions</h3>

                <?php $this->load->view('/publication_confirmation_versions/' . $confirmationVersion ); ?>

            </div>

            <div class="modal-footer">
                <input type="checkbox" class="confirm-conditions"> Please confirm that you agree with the above
                <hr>
                <button class='action-confirm-submit-for-publication btn btn-default disabled'>Confirm agreement</button>
                <button class="btn btn-default grey cancel" data-dismiss="modal">Annuleren</button>
            </div>
        </div>
    </div>
</div>


<div class="row">

    <ol class="breadcrumb">
        <li class="active">Home</li>
    </ol>
    <div class="top-information">
        <h1></h1>

        <div class="row">
            <div class="col-md-12">
                <div class="top-info-buttons">
                    <div class="research">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-default metadata-form" data-path="">Metadata</button>
                            <button type="button" class="btn btn-default toggle-folder-status" data-status="" data-path=""></button>
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-default folder-status" disabled="disabled">
                                Actions</button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <span class="caret"></span><span class="sr-only">Actions</span>
                            </button>
                            <ul class="dropdown-menu action-list" role="menu">
                            </ul>
                        </div>
                    </div>

                    <div class="vault">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-default metadata-form" data-path="">Metadata</button>
                        </div>

                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-default vault-access" data-access="" data-path="">
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-default folder-status" disabled="disabled">
                                Actions</button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <span class="caret"></span><span class="sr-only">Actions</span>
                            </button>
                            <ul class="dropdown-menu action-list" role="menu">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ul class="list-group lock-items"></ul>
        <ul class="list-group actionlog-items"></ul>
    </div>




    <div class="col-md-12">
        <div class="row">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="file-browser" class="table yoda-table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Modified date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>