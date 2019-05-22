<script>
    var browsePageItems = <?php echo $items; ?>;
    var browseStartDir = '<?php echo rawurlencode($dir); ?>';
    var view = 'browse';
</script>

<?php echo $searchHtml; ?>

<div class="modal" id="showUnpreservableFiles">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <h3>File formats compliance with policy</h3>
                <div class="form-group">
                    <label for="file-formats-list">Select preservable file format list:</label>
                    <select class="form-control" id="file-formats-list">
                        <option value="" disabled selected>Select a file format list</option>
                    </select>
                </div>
                <div class="help"></div><br />
                <div class="advice"></div>
                <div class="preservable">
                    This folder does not contain files that are likely to become unusable in the future.
                </div>
                <div class="unpreservable">
                    Following unpreservable file extensions were found in your dataset:
                    <br />
                    <ul class="list-unpreservable-formats"></ul>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default grey cancel" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="uploads">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-body">
                <h3>Uploads</h3>
                <div id="files"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default grey cancel" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="viewMedia">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div id="viewer"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default grey cancel" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <ol class="breadcrumb">
        <li class="active">Home</li>
    </ol>

    <div class="top-information">
         <div class="row">
            <div class="col-md-8">
                <h1></h1>
            </div>
            <div class="col-md-4">
                <div class="top-info-buttons">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-default metadata-form" data-path="">Metadata</button>
                    </div>
                    <div class="btn-group" role="group">
                        <input type="file" id="upload" multiple style="display: none" />
                        <button type="button" class="btn btn-default upload" data-path=""><i class="fa fa-upload" aria-hidden="true"></i> Upload</button>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default folder-status" data-toggle="dropdown" disabled="disabled">Actions</button>
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" disabled="disabled">
                            <span class="caret"></span><span class="sr-only">Actions</span>
                        </button>
                        <ul class="dropdown-menu action-list" role="menu"></ul>
                    </div>
                </div>
            </div>
        </div>

        <ul class="list-group lock-items"></ul>
        <ul class="list-group system-metadata-items"></ul>
        <ul class="list-group actionlog-items"></ul>
    </div>

    <div class="col-md-12">
        <div class="row">
            <table id="file-browser" class="table yoda-table table-striped" ondrop="dropHandler(event);" ondragover="dragOverHandler(event);">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Modified date</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
