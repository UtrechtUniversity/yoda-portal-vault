<?php


?>
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
                            <tr data-object-id="<?php echo $file->revisionObjectId; ?>" data-study-id="<?php echo $file->revisionStudyId; ?>">
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
                                            <button type="button" class="btn btn-default btn-rev-actualise"><i class="fa fa-magic" aria-hidden="true"></i> Actualise</button>
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
