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

	$el = $(".select-user-from-group");

	$el.select2({
		allowClear:  true,
		openOnEnter: false,
		minimumInputLength: 1,
		ajax: {
			quietMillis: 400,
			url:      'http://irods.foo.com/intake/getGroupUsers/testgroup',
			type:     'get',
			dataType: 'json',
			data: function (term, page) {
				return {
					query: term
				};
			},
			results: function (users) {
				var query   = $el.data('select2').search.val();
				var results = [];
				var inputMatches = false;

				users.forEach(function(userName) {
					// Exclude users already in the group.
					results.push({
						id:   userName,
						text: userName
					});
					if (query === userName)
						inputMatches = true;
				});

				return { results: results };
			},
		},
		initSelection: function($el, callback) {
			callback({ id: $el.val(), text: $el.val() });
		},
	})

});

var currentlyEditing = 0;
var inEditAllMode = false;

function edit($element) {
	label = $('table#metadata_edittable #label-' + $element);
	input = $('table#metadata_edittable #input-' + $element);
	editButton = $('table#metadata_edittable .button-' + $element + '.hideWhenEdit');
	cancelButton = $('table#metadata_edittable .button-' + $element + '.showWhenEdit');
	cancelAll = $("#cancelAll");
	submit = $("#editMetaSubmit");

	_hide(label);
	_show(input);
	_hide(editButton);
	_show(cancelButton);

	inputEnable(cancelAll);
	inputEnable(submit);

	currentlyEditing += 1;
}

function cancelEdit($element) {
	label = $('table#metadata_edittable #label-' + $element);
	input = $('table#metadata_edittable #input-' + $element);
	editButton = $('table#metadata_edittable .button-' + $element + '.hideWhenEdit');
	cancelButton = $('table#metadata_edittable .button-' + $element + '.showWhenEdit');
	cancelAll = $("#cancelAll");
	submit = $("#editMetaSubmit");
	editAll = $("#editAll");

	_show(label);
	_hide(input);
	_show(editButton);
	_hide(cancelButton);

	currentlyEditing -= 1;

	if(inEditAllMode) {
		inEditAllMode = false;
		currentlyEditing = 0;
		$('table#metadata_edittable span.btn-default.showWhenEdit').each(function(i, obj) {
			if(obj.style.display == "block") {
				currentlyEditing += 1;
			}
		});
	}

	if(currentlyEditing <= 0 && !inEditAllMode) {
		inputDisable(cancelAll);
		inputDisable(submit);
	} else {
		inputEnable(editAll);
	}
}

var _show = function(elem){
	elem.css("display", "block");
};

var _hide = function(elem){
	elem.css("display", "none");
	if(elem[0] != undefined){
		elem.val(elem[0].dataset.defaultvalue).trigger("change");
	}
};

function inputEnable(input) {
	input.prop('disabled', false);
}

function inputDisable(input) {
	input.prop('disabled', true)
}

function _iterateAll(showWhenEdit) {
	$("table#metadata_edittable .showWhenEdit").each(function(i, e) {
		e.style.display = showWhenEdit ? "block" : "none";
		if(!showWhenEdit) {
			defaultValue = e.dataset.defaultvalue;
			if(defaultValue != undefined) {
				$(e).val(defaultValue).trigger("change");
			}
		}
		
	});
	$("table#metadata_edittable .hideWhenEdit").each(function(i, e) {
		e.style.display = showWhenEdit ? "none" : "block";
	});

	cancelAll = $("#cancelAll");
	submit = $("#editMetaSubmit");
	editAll = $("#editAll");

	inEdit = showWhenEdit ? inputEnable : inputDisable;
	outEdit = showWhenEdit ? inputDisable : inputEnable;

	inEdit(cancelAll);
	inEdit(submit);
	outEdit(editAll);
}

function enableAllForEdit($element) {
	_iterateAll(true);
	inEditAllMode = true;
}

function disableAllForEdit() {
	_iterateAll(false);
	inEditAllMode = false;
}

// function editOwner() {
// 	$(".showWhenEdit").css("display", "block");
// 	$(".hideWhenEdit").css("display", "none");
// }

// function cancelEdit() {
// 	$(".showWhenEdit").css("display", "none");
// 	$(".hideWhenEdit").css("display", "block");
// 	$elown = $('[name="owner"]');
// 	$elown.val($elown[0].dataset.defaultvalue).trigger("change");
// }