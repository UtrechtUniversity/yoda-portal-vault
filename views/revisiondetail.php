<div class="col-md-12">
    <div class="row">
<!--        <div class="panel panel-default">-->
<!--            <div class="panel-body">-->
                <table id="" class="table" >
                    <thead>
                    <tr>
                        <th>Revisions</th>
                        <th>Revision date</th>
                        <th>File size</th>
                        <th>Path</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach($revisionFiles as $file): ?>
                            <tr>
                                <td>
                                    <?php echo $file->revisionName ?>
                                </td>
                                <td>
                                    <?php echo $file->revisionDate ?>
                                </td>
                                <td>
                                    <?php echo $file->revisionSize ?>
                                </td>
                                <td>
                                    <?php echo $file->revisionPath ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="...">
                                        <?php // all that can get here are allowed .. no specific ?>
                                        <button type="button" class="btn btn-default btn-rev-download"><i class="fa fa-download" aria-hidden="true"></i> Download</button>


                                        <?php if(true): ?>
                                            <button type="button" class="btn btn-default btn-revision-select-dialog" data-objectid="<?php echo $file->revisionObjectId; ?>"><i class="fa fa-magic" aria-hidden="true"></i> RestoreFile</button>
                                        <?php endif; ?>

                                        <?php if(true): ?>
                                            <button class="btn btn-default disabled btn-rev-delete" disabled>
                                                <i class="fa fa-remove"></i> Delete
                                            </button>
                                        <?php endif; ?>
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

            window.parent.showFolderSelectDialog($(this).data('objectid'));
        });

    });

</script>