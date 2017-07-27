<div class="col-md-12">
    <div class="row">
<!--        <div class="panel panel-default">-->
<!--            <div class="panel-body">-->
                <?php if (!$collectionExists): ?>
                    <i class="fa fa-exclamation-circle"></i> This collection no longer exists.
                <?php endif; ?>
                <table id="" class="table" >
                    <thead>
                    <tr>
                        <th>Revision date</th>
                        <th>Owner</th>
                        <th>Size</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach($revisionFiles as $row): ?>
                            <tr>
                                <td>
                                    <?php echo date('Y-m-d H:i:s', $row['org_original_modify_time']); ?>
                                </td>
                                <td>
                                    <?php echo $row['org_original_data_owner_name']; ?>
                                </td>
                                <td>
                                    <?php echo $row['org_original_filesize'] ?> bytes
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="...">
                                        <button type="button" class="btn btn-default btn-revision-select-dialog"
                                                data-orgfilename="<?php echo rawurlencode($orgFileName); ?>"
                                                data-objectid="<?php echo $row['id']; ?>"
                                                data-path="<?php echo rawurlencode($revisionStartPath); ?>"><i class="fa fa-magic" aria-hidden="true"></i> Restore</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
<!--        </div>-->
<!--    </div>-->
</div>
<script>
    $( document ).ready(function() {

        $('.btn-revision-select-dialog').on('click', function(){
            var id = $(this).data('objectid'),
                path = $(this).data('path'),
                orgFileName = $(this).data('orgfilename');

            window.parent.showFolderSelectDialog(id, path, orgFileName);
        });

    });

</script>
