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

	$el = $("input.select-user-from-group");
	createUserFromGroupInput($el);

	$metaSuggestions = $(".meta-suggestions-field");
	createMetaSuggestionsInput($metaSuggestions);

	$('#metadata_form').submit(function(e){
		$('.fixed-row-removed').remove();
	});

	handleDependencyFields();
	

});

var currentlyEditing = 0;
var inEditAllMode = false;

var operators = {
	'==' : function(a, b) {return a == b},
	'!=' : function(a, b) {return a != b},
	'>' : function(a, b) {return a > b},
	'<' : function(a, b) {return a < b},
	'<=' : function(a, b) {return a <= b},
	'>=' : function(a, b) {return a >= b}
};

var likeOperators = {
	'==' : function(a, b) {return a.toLowerCase().indexOf(b.toLowerCase()) >= 0; },
	'!=' : function(a, b) {return a.toLowerCase().indexOf(b.toLowerCase()) < 0; }
};

var regexOperators = {
	'==' : function(a, b) {return RegExp(b).exec(a); },
	'!=' : function(a, b) {return !RegExp(b).exec(a); }
}

var comparors = {
	'none' : function(lst) { return !lst.reduce(function(a,b){return a || b}, false); },
	'all' : function(lst) { return lst.reduce(function(a,b){return a && b}, true); },
	'any' : function(lst) { return lst.reduce(function(a,b) {return a || b}, false); }
};


function handleDependencyFields() {
	$("#metadata_edittable > tbody > tr").each(function(i, e) {
		elem = $(e);
		if(elem.data('depends') != undefined && elem.data('depends') != "\"false\"") {
			objstr = elem.data('depends').substr(1,elem.data('depends').length - 2);
			var obj = JSON.parse(objstr);
			$(obj.fields).each(function(f_i, field) {
				var selector = "input[name=\"metadata[" + field.field_name + "]\"]";
				$(selector).bind('input', (function(el, dep){
					return function(){
						checkRowForDependency(el, dep, 300);
					};
				}(elem, obj)));
			});
			checkRowForDependency(elem, obj, 0);
		}
	});
}

var checkRowForDependency = function(row, fieldDepends, speed) {
	if(fieldDepends == undefined || fieldDepends === false) 
		return;

	var truthVals = new Array();

	$(fieldDepends.fields).each(function(i, e) {
		truthVals.push(evaluateOneField(e));
	});

	var condition = comparors[fieldDepends.if](truthVals);
	if(condition === false || fieldDepends.action === 'hide') {
		row.hide(speed);
	} else {
		row.show(speed);
	}

}

function evaluateOneField(fieldRequirements) {
	var selector = "input[name=\"metadata[" + fieldRequirements.field_name + "]\"]";
	var field = $(selector);
	if(fieldRequirements.value.fixed != undefined) {
		return operators[fieldRequirements.operator](field.val(), fieldRequirements.value.fixed);
	} else if(fieldRequirements.value.like != undefined) {
		return likeOperators[fieldRequirements.operator](field.val(), fieldRequirements.value.like);
	} else if(fieldRequirements.value.regex != undefined) {
		return regexOperators[fieldRequirements.operator](field.val(), fieldRequirements.value.regex);
	} else {
		return true;
	}
}


/** 
 * Transforms the called element in a select2 input box,
 * where users can be selected.
 * Uses data-attributes to detect which users to show as
 * suggestions
 *
 * @param elem 	The transformed element
 */
function createUserFromGroupInput(elem) {
	if(!elem.hasClass('select-user-from-group')) return;
	elem.select2({
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
				console.log(elem.data());
				return {
					query: term,
					showAdmins : elem.data()['displayrolesAdmins'],
					showUsers : elem.data()['displayrolesUsers'],
					showReadonly : elem.data()['displayrolesReadonly']
				};
			},
			results: function (users) {
				var query   = elem.data('select2').search.val();
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

				if (!inputMatches && query.length && elem.data("allowcreate"))
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
}

/**
 * Creates a select2 input from the element given as argument
 * which provides meta data suggestions. The suggestions are
 * previously used values for the key on the same object
 */
function createMetaSuggestionsInput(elem) {
	if(!elem.hasClass('meta-suggestions-field')) return;

	elem.select2({
		allowClear:  true,
		openOnEnter: false,
		minimumInputLength: 1,
		ajax: {
			quietMillis: 400,
			url:      function(params) {
				var url = $("input[name=intake_url]").val();
				url += '/metadata/metasuggestions/';
				url += elem.attr('id').substring(6);
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
				var query   = elem.data('select2').search.val();
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

				if (!inputMatches && query.length && elem.data("allowcreate"))
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
	label = $('table#metadata_edittable #label-' + $element);
	row = $('table#metadata_edittable .fixed-row-' + $element);
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
	_show(row);

	inputEnable(cancelAll);
	inputEnable(submit);

	currentlyEditing += 1;
}

function cancelEdit($element) {
	label = $('table#metadata_edittable #label-' + $element);
	input = $('table#metadata_edittable .input-' + $element);
	row = $('table#metadata_edittable .fixed-row-' + $element);
	editButton = $('table#metadata_edittable .button-' + $element + '.hideWhenEdit');
	cancelButton = $('table#metadata_edittable .button-' + $element + '.showWhenEdit');
	cancelAll = $(".metadata-btn-cancelAll");
	submit = $(".metadata-btn-editMetaSubmit");
	editAll = $(".metadata-btn-editAll");
	addRowBtn = $("table#metadata_edittable #addRow-" + $element);
	addedRows = $("table#metadata_edittable .row-" + $element);
	addedRows.remove();

	fixedRows = $("table#metadata_edittable .fixed-row-" + $element);
	fixedRows.removeClass("fixed-row-removed");

	_show(label);
	_hide(input);
	_show(editButton);
	_hide(cancelButton);
	_hide(addRowBtn);
	_hide(row);

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
		"<span class='col-md-1'><span class='btn btn-default glyphicon glyphicon-trash' " +
		"onclick='removeRow(\"#row-" + $element + "-" + addRowBtn[0].dataset['nextindex'] + 
		"\");'></span></span></div>";
	addRowBtn.before(template);
	input = $('table#metadata_edittable .input-' + $element);
	row = $('table#metadata_edittable .row-' + $element);
	_show(input);
	_show(row);
	newElem = $('input[name="metadata[' + $element + '][' + addRowBtn[0].dataset['nextindex'] + ']"]');
	newElem.each(function(i, e) {
		e = $(e);
		createMetaSuggestionsInput(e);
		createUserFromGroupInput(e);
	});
	addRowBtn[0].dataset['nextindex']++;
}

function removeRow(row) {
	$(row).remove();
}

function removeFixedRow(row) {
	$(row).addClass("fixed-row-removed");
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
		restoreValueFromShadow(e);
	});
};

function restoreValueFromShadow(elem) {
	name = $(elem).attr('name');
	if(name == undefined || !name.startsWith("metadata") || name.startsWith("metadata-shadow")) return;
	names = name.substr(8).split("]");
	targetstr = "metadata-shadow";
	$(names).each(function(index, selector) {
		if(selector != "") {
			targetstr += selector + "]";
		}
	});

	shadowElem = $("input[name=\"" + targetstr + "\"]");
	if(shadowElem == undefined || shadowElem.length == 0) return;

	$(elem).val(shadowElem.val()).trigger("change");
		
}

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
		&& elem.attr('id') != undefined && !elem.attr('id').match("^s2");

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
			restoreValueFromShadow(e);
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

