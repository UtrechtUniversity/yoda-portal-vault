<?php 
	echo sprintf(
		'<h3><span class="glyphicon glyphicon-%3$s"></span> %2$s %1$s</h3>',
		$metalevel->name,
		$metalevel->level["title"],
		$metalevel->level["glyphicon"]
	);

	$fields = $metalevel->meta;

	if(!$fields || !is_array($fields) || sizeof($fields) === 0) {
?>
		<div class="alert alert-warning">
			<?=lang('intake_metadata_error_no_schema');?>
		</div>
<?php
	}
?>

	<table class="display table table-datatable metadata_table" width="100%">
		<thead>
			<tr>
				<th width="100"><?=lang('metadata_name');?></th>
				<th><?=lang('metadata_value');?></th>
				<th></th>
			</tr>
		</thead>

		<tbody>
<?php 
	if(is_array($fields) && sizeof($fields) > 0) {
		foreach($fields as $key => $config){
			echo $this->metadatafields->getHtmlForRow(
				$key,
				$config,
				$config["value"],
				2,
				false,
				array(), // in_array($key, $wrongFields),
				false
			);
			echo "\n";
		}
	}
?>
		</tbody>
	</table>