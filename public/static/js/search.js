$( document ).ready(function() {
    if ($('#file-browser').length && (view == 'browse' && searchType != 'revision')) {
        // Rememeber search results
        if (searchStatusValue.length > 0) {
            $('[name=status]').val(searchStatusValue);
            search(searchStatusValue, 'status', browsePageItems, searchStart, searchOrderDir, searchOrderColumn);
         } else if (searchTerm.length > 0) {
            search(decodeURIComponent(searchTerm), searchType, browsePageItems, searchStart, searchOrderDir, searchOrderColumn);
        }
    }

    $(".search-panel .dropdown-menu li a").click(function(){
        searchSelectChanged($(this));
    });

    $(".search-btn").click(function(){
        search($("#search-filter").val(), $("#search_concept").attr('data-type'), $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
    });

    $("#search-filter").bind('keypress', function(e) {
        if(e.keyCode==13) {
            search($("#search-filter").val(), $("#search_concept").attr('data-type'), $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
        }
    });

    $(".search-status").change(function() {
        search($(this).val(), 'status', $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
    });

    $(".close-search-results").click(function() {
        closeSearchResults();
    });
});

function search(value, type, itemsPerPage, displayStart, searchOrderDir, searchOrderColumn)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
        // Display start for first page load
        if (typeof displayStart === 'undefined') {
            displayStart = 0;
        }

        saveSearchRequest(value, type);

        // Table columns definition
        var disableSorting = {};
        var columns = [];
        if (type == 'filename') {
            columns = ['Name', 'Location'];
        } else if (type == 'metadata') {
            columns = ['Location', 'Matches'];
            disableSorting = { 'bSortable': false, 'aTargets': [ -1 ] };
        } else {
            columns = ['Location'];
        }

        // Destroy current Datatable
        var datatable = $('#search').DataTable();
        datatable.destroy();

        var tableHeaders = '';
        $.each(columns, function(i, val){
            tableHeaders += "<th>" + val + "</th>";
        });

        // Create the columns
        $('#search thead tr').html(tableHeaders);

        // Remove table content
        $('#search tbody').remove();


        // Initialize new Datatable
        var url = "search/data?filter=" + encodeURIComponent(value) + "&type=" + type;
        $('#search').DataTable( {
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            "ajax": {
                "url": url,
                "jsonp": false,
                dataSrc: function (json) {
                    jsonString = JSON.stringify(json);

                    resp = JSON.parse(jsonString);

                    //console.log(resp.draw);
                    if (resp.status == 'Success' ) {
                        return resp.data;
                    }
                    else {
                        setMessage('error', resp.statusInfo);
                        return true;
                    }
                }
            },
            "processing": true,
            "serverSide": true,
            "pageLength": searchPageItems,
            "displayStart": displayStart,
            "drawCallback": function(settings) {
                $( ".browse-search" ).on( "click", function() {
                    browse($(this).attr('data-path'));
                });


                $('.matches').tooltip();
            },
            "aoColumnDefs": [
                disableSorting
            ],
            "ordering": false
        });
        //"order": [[ searchOrderColumn, searchOrderDir ]]  // save for future purposes - it intervenes with newly added "ordering": false

        if (type == 'status') {
            searchStatus = $(".search-status option:selected").text();
            $('.search-string').text(searchStatus);
        } else {
            $('.search-string').html( htmlEncode(value).replace(/ /g, "&nbsp;") );
        }
    }

    return true;
}

function closeSearchResults()
{
    $('.search-results').hide();
    $('#search-filter').val('');
    $('[name=status]').val('');
    $.get("search/unset_session");
}

function showSearchResults()
{
    $('.search-results').show();
}

function searchSelectChanged(sel)
{
    $("#search_concept").html(sel.text());
    $("#search_concept").attr('data-type', sel.attr('data-type'));

    if (sel.attr('data-type') == 'status') {
        $('.search-term').hide();
        $('.search-status').removeClass('hide').show();
    } else {
        $('.search-term').removeClass('hide').show();
        $('.search-status').hide();
    }
}

function saveSearchRequest(value, type)
{
    var url = "search/set_session?value=" + encodeURIComponent(value) + "&type=" + type;
    $.ajax({
        url: url,
        async: false, //blocks window close
        success: function() {
            if (type == 'revision' && view == 'revision') {
                $('#search').hide();
                $('.search-results').hide();
                return false;
            }

            if (type == 'revision' && view == 'browse') {
                $('#search').hide();
                $('.search-results').hide();

                window.location.href = "revision?filter=" + encodeURIComponent(value);
                return false;
            }

            if (type != 'revision' && view == 'revision') {
                window.location.href = "browse";
                return false;
            }

            showSearchResults();
        }
    });
}
