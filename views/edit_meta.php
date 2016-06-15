<?php if($userIsAllowed && $studyFolder != ''): ?>
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
		"dataset" => ($studyFolder ? $studyFolder : false),
		"intake_url" => $url->module
	);
	echo form_open($url->module . "/metadata/update", null, $attrs);
?>

<?php if($permissions->administrator): ?>
	<div class="container-fluid metadata_form_buttons">
		<div class="row">
			<button class="btn btn-default showWhenEdit col-md-4 metadata-btn-editMetaSubmit"
				disabled="disabled" type="submit">
				<span class="glyphicon glyphicon-save"></span>
				Submit
			</button>
			<button type="button" class="btn btn-default hideWhenEdit col-md-4 metadata-btn-editAll" 
				action="" onclick="enableAllForEdit()">
				<span class="glyphicon glyphicon-pencil"></span>
				Edit all
			</button>
			<button type="button" 
				class="btn btn-default showWhenEdit col-md-4 metadata-btn-cancelAll"
				disabled="disabled" action="" onclick="disableAllForEdit()">
				<span class="glyphicon glyphicon-remove"></span>
				Cancel edit
			</button>
		</div>
	</div>
<?php endif; // if administrator ?>

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
				$fields = $this->metadatafields->getFields($currentDir, true);
				foreach($fields as $key => $config){
				echo $this->metadatafields->getHtmlForRow(
					$key,
					$config,
					$config["value"],
					2,
					$permissions
				);
				echo "\n";
			}
			?>
		</tbody>
	</table>
<?php if($permissions->administrator): ?>
	<div class="container-fluid metadata_form_buttons">
		<div class="row">
			<button class="btn btn-default showWhenEdit col-md-4 metadata-btn-editMetaSubmit"
				disabled="disabled" type="submit">
				<span class="glyphicon glyphicon-save"></span>
				Submit
			</button>
			<button type="button" class="btn btn-default hideWhenEdit col-md-4 metadata-btn-editAll" 
				action="" onclick="enableAllForEdit()">
				<span class="glyphicon glyphicon-pencil"></span>
				Edit all
			</button>
			<button type="button" 
				class="btn btn-default showWhenEdit col-md-4 metadata-btn-cancelAll"
				disabled="disabled" action="" onclick="disableAllForEdit()">
				<span class="glyphicon glyphicon-remove"></span>
				Cancel edit
			</button>
		</div>
	</div>
<?php endif; // if administrator ?>

<?php 
	echo form_close();
endif; ?>
