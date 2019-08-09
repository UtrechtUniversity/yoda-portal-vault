$( document ).ready(function() {
    if ($('#file-browser').length && (view == 'browse' && searchType != 'revision')) {
        // Rememeber search results
        if (searchStatusValue.length > 0) {
            $('[name=status]').val(searchStatusValue);
            search(searchStatusValue, 'status', browsePageItems, searchStart, searchOrderDir, searchOrderColumn);
	    showSearchResults();
         } else if (searchTerm.length > 0) {
             search(decodeURIComponent(searchTerm), searchType, browsePageItems, searchStart, searchOrderDir, searchOrderColumn);
	     showSearchResults();
        }
    }

    $(".search-panel .dropdown-menu li a").click(function(){
        searchSelectChanged($(this));
    });

    $(".search-btn").click(function(){
	value = $("#search-filter").val();
	type = $("#search_concept").attr('data-type');
        search(value, type, $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
	saveSearchRequest(value, type);
    });

    $("#search-filter").bind('keypress', function(e) {
        if(e.keyCode==13) {
            value = $("#search-filter").val();
            type = $("#search_concept").attr('data-type');
            search(value, type, $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
	    saveSearchRequest(value, type);
        }
    });

    $(".search-status").change(function() {
        search($(this).val(), 'status', $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
	saveSearchRequest($(this).val(), 'status');
    });

    $(".close-search-results").click(function() {
        closeSearchResults();
    });
});

function search(value, type, itemsPerPage, displayStart, searchOrderDir, searchOrderColumn)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
        // Display start for first page load.
        if (typeof displayStart === 'undefined') {
            displayStart = 0;
        }

        // Table columns definition.
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

        // Destroy current Datatable.
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

	var encodedSearchString = encodeURIComponent(value);
	/* limit the length of the encoded string to the worst case of 255*4*3=3060
 	*  maxLength of characters (255) * max bytes in UTF-8 encoded character (4) * URL encoding of byte (%HH) (3)
 	*/
	if (encodedSearchString.length > 3060) {
		setMessage('error', 'The search string is too long');
		return true;
	}

        // Initialize new Datatable
        var url = "search/data?filter=" + encodedSearchString + "&type=" + type;
        $('#search').DataTable( {
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": true,
            "language": {
                "emptyTable": "Your search did not match any documents",
                "lengthMenu": "_MENU_"
            },
            "dom": '<"top">rt<"bottom"lp><"clear">',
            "ajax": {
                "url": url,
                "jsonp": false,
                error: function (xhr, error, thrown) {
                    $("#file-browser_processing").hide()
                    setMessage('error', 'Something went wrong. Please try again or refresh page.');
                    return true;
                },
                dataSrc: function (json) {
                    jsonString = JSON.stringify(json);

                    resp = JSON.parse(jsonString);

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
                    var path = $(this).attr('data-path');
                    if (path.startsWith('%2Fresearch-')) {
                        browse(path);
                    } else {
                        window.location = "/vault/?dir=" + path;
                    }
                });

                $('.matches').tooltip();
            },
            "aoColumnDefs": [
                disableSorting
            ],
            "ordering": false
        });

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
	value = $('.search-status').val();
	type = "status";
	search(value, type, $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
    } else {
        $('.search-term').removeClass('hide').show();
        $('.search-status').hide();
	value = $("#search-filter").val();
	type = $("#search_concept").attr('data-type');
	search(value, type, $(".search-btn").attr('data-items-per-page'), 0, 'asc', 0);
    }
    saveSearchRequest(value, type);
}

function saveSearchRequest(value, type)
{
    if (typeof value != 'undefined' && value.length > 0 ) {
	var url = "search/set_session";
	$.ajax({
            url: url,
            method: "POST",
            async: false, //blocks window close
            data: {
		value: value,
		type: type
            },
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
}
