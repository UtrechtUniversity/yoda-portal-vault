<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if ($header && $hasStudies): ?>
	<div class="container page-body">
		<div class="row page-header">
			<div class="col-sm-6">
				<h1>
					<span class="glyphicon glyphicon-tags"></span>&nbsp;
					<?=lang('header:metadata');?>
				</h1>
<?php 
				if($userIsAllowed) {
					echo '<h3><span class="glyphicon glyphicon-education"></span>&nbsp;' . htmlentities($title) . "</h3>";
?>
					<div class="input-group">
						<div class="input-group-btn">
							<a class="btn btn-default" href="<?=$url->module;?>/intake/index/<?=$studyID;?>/<?=$studyFolder;?>" >
								<span class="glyphicon glyphicon-arrow-left"></span>
							</a>
						</div>
						<span class="form-control"><?=htmlentities($intakePath . ($studyFolder?'/'.$studyFolder:''));?></span>
					</div>
<?php	
				}
?>
			</div>
		</div>
<?php endif; ?>

<?php if($information = $this->session->flashdata('information')): ?>
		<div class="alert alert-<?=$information->type;?>">
			<?=$information->message;?>
		</div>
<?php 
endif;


if($userIsAllowed && $studyFolder != ''): 

	$wrongFields = $this->session->flashdata("incorrect_fields") ? $this->session->flashdata("incorrect_fields") : array();

	$formAttrs = array(
		"id" => "metadata_form"
	);

	$hidden = array(
		"studyRoot" => $intakePath,
		"studyID" => $studyID,
		"dataset" => ($studyFolder ? $studyFolder : false),
		"intake_url" => $url->module
	);
	echo form_open($url->module . "/metadata/update", $formAttrs, $hidden);
	
	if($permissions->administrator): 
?>
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
					$permissions,
					array_key_exists($key, $wrongFields) ? $wrongFields[$key] : array(), // in_array($key, $wrongFields),
					$this->session->flashdata('form_data')
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
