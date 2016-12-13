<script>
    $( document ).ready(function() {
        $('#file-browser').DataTable( {
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            "ajax": "browse/data",
            "processing": true,
            "serverSide": true,
            "pageLength": <?php echo $items; ?>,
            "drawCallback": function(settings) {
                $( ".browse" ).on( "click", function() {
                    browse($(this).attr('data-path'));
                });
            }
        });
    });
</script>

<div class="row">
    <div class="input-group" style="margin-bottom:20px;">
        <div class="input-group-btn search-panel">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span id="search_concept">Filter by name</span> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li><a href="#">Filter by name</a></li>
                <li><a href="#">Filter by metadata</a></li>
                <li><a href="#">Filter by status</a></li>
                <li><a href="#">Find revision for name</a></li>
            </ul>
        </div>
        <input type="hidden" name="search_param" value="all" id="search_param">
        <input type="text" class="form-control" name="x" placeholder="Search term...">
        <span class="input-group-btn">
            <button class="btn btn-default" type="button"><span class="glyphicon glyphicon-search"></span></button>
        </span>
    </div>
</div>

<div class="row">

    <ol class="breadcrumb">
        <li class="active">Home</li>
    </ol>
    <!--<h1><i class="fa fa-folder-o" aria-hidden="true"></i>Project test</h1>-->


    <div class="btn-group" role="group" aria-label="...">
        <!--
        <button type="button" class="btn btn-default"><i class="fa fa-folder-o" aria-hidden="true"></i> Is folder</button>

        <button class="btn btn-default disabled" href="#">
            <i class="fa fa-unlock"></i> Unlocked
        </button>
        <button type="button" class="btn btn-default disabled">
            <i class="fa fa-university" aria-hidden="true"></i>
            Save to vault</button>
        <button type="button" class="btn btn-default">Edit metadata</button>
        -->
    </div>




    <div class="col-md-12">
        <div class="row">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="file-browser" class="table table-bordered">
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