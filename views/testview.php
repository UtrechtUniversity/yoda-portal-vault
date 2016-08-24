<table id="example" class="display table " width="100%" cellspacing="0">
	<thead>
		<tr>
			<th><?=ucfirst(lang('intake_name'));?></th>
			<th><?=ucfirst(lang('intake_size'));?></th>
			<th><?=ucfirst(lang('intake_created'));?></th>
			<th><?=ucfirst(lang('intake_modified'));?></th>
		</tr>
	</thead>
</table>

<script>
	$(document).ready(function(){
		$('#example').DataTable( {
			"dom" : '<"top"lpf>rt<"bottom"lfp>i<"clear">',
			"processing" : true,
			"serverSide" : true,
			"ajax" : "http://irods.foo.com/projects/intake/getFilesInformation/?dir=<?=$current_dir;?>",
			"columns" : [
				{"data" : "filename"},
				{"data" : "size"},
				{"data" : "created"},
				{"data" : "modified"},
			]
		});
	});
</script> 