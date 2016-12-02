<div class="col-md-12">
    <div class="row">
<!--        <div class="panel panel-default">-->
<!--            <div class="panel-body">-->
                <table id="" class="table" >
                    <thead>
                    <tr>
                        <th>File revisions</th>
                        <th>Modification date</th>
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
                                        <button type="button" class="btn btn-default"><i class="fa fa-download" aria-hidden="true"></i> Download</button>
                                        <button type="button" class="btn btn-default"><i class="fa fa-magic" aria-hidden="true"></i> Actualise</button>
                                        <button class="btn btn-default disabled" href="#">
                                            <i class="fa fa-remove"></i> Delete
                                        </button>
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
