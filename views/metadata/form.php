<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $( ".datepicker" ).datepicker({
            dateFormat: "yy-mm-dd"
        });
    })
</script>
<div class="row">
    <div class="col-md-12">
        <?php echo $form->open('research/metadata/store', 'form-horizontal'); ?>
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Metadata form</h3>
                <span class="pull-right">
                    <!-- Tabs -->

                    <ul class="nav panel-tabs">
                        <?php foreach ($form->getSections() as $k => $name) { ?>
                            <?php if ($k == 0) { ?>
                                <li class="active">
                                    <a href="#tab0" data-toggle="tab"><?php echo $name; ?></a>
                                </li>
                            <?php } else { ?>
                                <li>
                                    <a href="#tab<?php echo $k; ?>" data-toggle="tab"><?php echo $name; ?></a>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
            </span>
            </div>
            <div class="panel-body">
                <div class="tab-content">
                    <?php foreach ($form->getSections() as $k => $name) { ?>
                        <div class="tab-pane <?php echo ($k == 0) ? 'active' : ''; ?>" id="tab<?php echo $k; ?>">
                            <?php echo $form->show($name); ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="form-group">
                    <div class="col-sm-10">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $form->close(); ?>
    </div>
</div>
