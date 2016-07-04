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

	$(".chosen").chosen({
		inherit_select_classes : true,
		width : "100%"
	});



	createSelect2Inputs();
	


});

var currentlyEditing = 0;
var inEditAllMode = false;

function createSelect2Inputs() {
	$el = $("input.select-user-from-group");
	
	$el.select2({
		allowClear:  true,
		openOnEnter: false,
		minimumInputLength: 1,
		ajax: {
			quietMillis: 400,
			url:      function(params) {
				var url = $("input[name=intake_url]").val();
				url += '/getGroupUsers/';
				url += $("input[name=studyID]").val();
				return url;
			},
			type:     'get',
			dataType: 'json',
			data: function (term, page) {
				console.log($el.data());
				return {
					query: term,
					showAdmins : $el.data()['displayrolesAdmins'],
					showUsers : $el.data()['displayrolesUsers'],
					showReadonly : $el.data()['displayrolesReadonly']
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

				if (!inputMatches && query.length && $el.data("allowcreate"))
					results.push({
						id:   query,
						text: query,
						exists: false
					}
				);

				return { results: results };
			},
		},
		formatResult: function(result, $container, query, escaper) {
			return escaper(result.text)
				+ (
					'exists' in result && !result.exists
					? ' <span class="grey">(create)</span>'
					: ''
				);
		},
		initSelection: function($el, callback) {
			callback({ id: $el.val(), text: $el.val() });
		},
	});

	$metaSuggestions = $(".meta-suggestions-field");
	$metaSuggestions.select2({
		allowClear:  true,
		openOnEnter: false,
		minimumInputLength: 1,
		ajax: {
			quietMillis: 400,
			url:      function(params) {
				var url = $("input[name=intake_url]").val();
				url += '/metadata/metasuggestions/';
				url += $metaSuggestions.attr('id').substring(6);
				return url;
			},
			type:     'get',
			dataType: 'json',
			data: function (term, page) {
				return {
					query: term
				};
			},
			results: function (options) {
				var query   = $metaSuggestions.data('select2').search.val();
				var results = [];
				var inputMatches = false;

				options.forEach(function(userName) {
					// Exclude options already in the group.
					results.push({
						id:   userName,
						text: userName
					});
					if (query === userName)
						inputMatches = true;
				});

				if (!inputMatches && query.length && $metaSuggestions.data("allowcreate"))
					results.push({
						id:   query,
						text: query,
						exists: false
					}
				);

				return { results: results };
			},
		},
		formatResult: function(result, $container, query, escaper) {
			return escaper(result.text)
				+ (
					'exists' in result && !result.exists
					? ' <span class="grey">(new)</span>'
					: ''
				);
		},
		initSelection: function($el, callback) {
			callback({ id: $el.val(), text: $el.val() });
		},
	});
}

function edit($element) {
	createSelect2Inputs();
	label = $('table#metadata_edittable #label-' + $element);
	// input = $('table#metadata_edittable #input-' + $element);
	input = $('table#metadata_edittable .input-' + $element);
	editButton = $('table#metadata_edittable .button-' + $element + '.hideWhenEdit');
	cancelButton = $('table#metadata_edittable .button-' + $element + '.showWhenEdit');
	cancelAll = $(".metadata-btn-cancelAll");
	submit = $(".metadata-btn-editMetaSubmit");
	addRowBtn = $("table#metadata_edittable #addRow-" + $element);

	_hide(label);
	_show(input);
	_hide(editButton);
	_show(cancelButton);
	_show(addRowBtn);

	inputEnable(cancelAll);
	inputEnable(submit);

	currentlyEditing += 1;
}

function cancelEdit($element) {
	label = $('table#metadata_edittable #label-' + $element);
	// input = $('table#metadata_edittable #input-' + $element);
	input = $('table#metadata_edittable .input-' + $element);
	editButton = $('table#metadata_edittable .button-' + $element + '.hideWhenEdit');
	cancelButton = $('table#metadata_edittable .button-' + $element + '.showWhenEdit');
	cancelAll = $(".metadata-btn-cancelAll");
	submit = $(".metadata-btn-editMetaSubmit");
	editAll = $(".metadata-btn-editAll");
	addRowBtn = $("table#metadata_edittable #addRow-" + $element);
	addedRows = $("table#metadata_edittable .row-" + $element);
	addedRows.remove();

	_show(label);
	_hide(input);
	_show(editButton);
	_hide(cancelButton);
	_hide(addRowBtn);

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

function addValueRow($element) {
	addRowBtn = $("table#metadata_edittable #addRow-" + $element);
	template = addRowBtn[0].dataset['template'].replace("__row_input_id__", addRowBtn[0].dataset['nextindex']);
	template = "<div class='row showWhenEdit row-" + $element + 
		"' id='row-" + $element + "-" + addRowBtn[0].dataset['nextindex'] + "'>" +
		"<span class='col-md-11'>" + template + "</span>" +
		"<span class='btn btn-default col-md-1 glyphicon glyphicon-minus' " +
		"onclick='removeRow(\"#row-" + $element + "-" + addRowBtn[0].dataset['nextindex'] + 
		"\");'></span></div>";
	addRowBtn.before(template);
	// input = $('table#metadata_edittable #input-' + $element);
	input = $('table#metadata_edittable .input-' + $element);
	row = $('table#metadata_edittable .row-' + $element);
	_show(input);
	_show(row);
	addRowBtn[0].dataset['nextindex']++;
	createSelect2Inputs();
}

function removeRow(row) {
	$(row).remove();
}

var _show = function(elem){
	elem.css("display", "block");
	elem.css("visibility", "visible");
	if( _attrIsSelect2(elem) ) {
		_show($("#s2id_" + elem.attr('id')));
	}

	if( _attrIsChosen(elem) ) {
		_hide(elem);
		_show($("#" + elem.attr('id').split("-").join("_") + "_chosen"));
	}
	$("#input_example_select_chosen").css('display', 'block');
};

var _hide = function(elem){
	elem.css("display", "none");
	elem.css("visibility", "hidden");
	if( _attrIsSelect2(elem) ) {
		_hide($("#s2id_" + elem.attr('id')));
	}
	elem.each(function(i, e) {
		$(e).val(e.dataset.defaultvalue).trigger("change");
	});
};

function _attrIsChosen(elem) {
	var val = elem.hasClass("chosen") && !elem.attr('id').match("_chosen$");
	return val;
}

function _attrIsSelect2(elem) {
	var val = 
		(
			elem.hasClass("select-user-from-group") || 
			elem.hasClass('meta-suggestions-field')
		) 
		&& !elem.attr('id').match("^s2");

	return val;
}

function inputEnable(input) {
	input.prop('disabled', false);
}

function inputDisable(input) {
	input.prop('disabled', true);
}

function _iterateAll(showWhenEdit) {
	$("table#metadata_edittable .showWhenEdit").each(function(i, e) {
		e.style.display = showWhenEdit ? "block" : "none";
		e.style.visibility = showWhenEdit ? "visible" : "hidden";
		if(!showWhenEdit) {
			defaultValue = e.dataset.defaultvalue;
			if(defaultValue != undefined) {
				$(e).val(defaultValue).trigger("change");
			}
			if($(e).hasClass('row')) $(e).remove();
		}
		
	});
	$("table#metadata_edittable .hideWhenEdit").each(function(i, e) {
		e.style.display = showWhenEdit ? "none" : "block";
		e.style.visibility = showWhenEdit ? "hidden" : "visible";
	});

	cancelAll = $(".metadata-btn-cancelAll");
	submit = $(".metadata-btn-editMetaSubmit");
	editAll = $(".metadata-btn-editAll");

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