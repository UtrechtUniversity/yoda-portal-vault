<?php if($userIsAllowed && $permissions->manager && $studyFolder != ''): ?>
	<div class="row page-header">
	    <div class="col-sm-6">
	      <h3>
	        <span class="glyphicon glyphicon-tags"></span>
	        <?=lang('header:metadata');?>
	      </h3>
	    </div>
	</div>
<?php
$attrs = array(
		"studyRoot" => $intakePath,
		"studyID" => $studyID,
		"dataset" => ($studyFolder ? $studyFolder : false)
	);
	echo form_open($url->module . "/metadata/update", null, $attrs);
?>
	<table id="metadata_edittable" 
		class="display table table-datatable">
		<thead>
			<tr>
				<th width="100"><?=lang('metadata_name');?></th>
				<th><?=lang('metadata_value');?></th>
				<th></th>
			</tr>
		</thead>

		<tbody>
			<?php 
				// foreach($this->metadatafields->fields as $key => $config) {

				$fields = $this->metadatafields->getFields($currentDir, true);
				foreach($fields as $key => $config){
				echo $this->metadatafields->getHtmlForRow(
					$key,
					$config,
					$config["value"],
					2
				);
				echo "\n";
			}
			?>
		</tbody>
	</table>

	<div class="container-fluid metadata_form_buttons">
		<div class="row">
			<button class="btn btn-default showWhenEdit col-md-4"
				disabled="disabled" 
				id="editMetaSubmit" type="submit">
				<span class="glyphicon glyphicon-save"></span>
				Submit
			</button>
			<button type="button" class="btn btn-default hideWhenEdit col-md-4" 
				id="editAll" action="" onclick="enableAllForEdit()">
				<span class="glyphicon glyphicon-pencil"></span>
				Edit all
			</button>
			<button type="button" 
				class="btn btn-default showWhenEdit col-md-4"
				disabled="disabled"
				id="cancelAll" action="" onclick="disableAllForEdit()">
				<span class="glyphicon glyphicon-remove"></span>
				Cancel edit
			</button>
		</div>
	</div>
	

<?php 
	echo form_close();
endif; ?>
