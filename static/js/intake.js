$(function() {
	$('.table.table-datatable').DataTable( {
	    "paging": false,
	    "columnDefs": [
	    	{
	    		"targets" : 0,
	    		"orderData" : false
	    	}
	    ]
	} );

	$('[data-toggle="tooltip"]').tooltip();
});

function editOwner() {
	console.log("hi");
	$(".showWhenEdit").show();
	$(".hideWhenEdit").hide();
}

function cancelEdit() {
	$(".showWhenEdit").hide();
	$(".hideWhenEdit").show();
}