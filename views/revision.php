<div class="row">
    <div class="col-xs-12">
        <div class="input-group" style="margin-bottom:20px;">
            <div class="input-group-btn search-panel">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span id="search_concept">Filter by name</span> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="#contains">Filter by Name</a></li>
                    <li><a href="#its_equal">Filter by Metadata</a></li>
                    <li><a href="#its_equal">Filter by Status</a></li>
                    <li><a href="#its_equal">Find revision for name</a></li>
                </ul>
            </div>
            <input type="hidden" name="search_param" value="all" id="search_param">
            <input type="text" class="form-control" name="x" placeholder="Search term...">
            <span class="input-group-btn">
                        <button class="btn btn-default" type="button"><span class="glyphicon glyphicon-search"></span></button>
                    </span>
        </div>
    </div>
</div>

<div class="row">

    <ol class="breadcrumb">
        <li>Home</li>
        <li>gpr-test</li>
        <li><strong>Project-test</strong></li>
    </ol>
    <h1><i class="fa fa-folder-o" aria-hidden="true"></i>Project-test</h1>


    <div class="col-md-12">
        <div class="row">
        <div class="panel panel-default">
            <div class="panel-body">
                <table id="file-browser" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Modification date</th>
                            <th>Path</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>
        </div>
    </div>
</div>