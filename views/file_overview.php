<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$rodsaccount = $this->rodsuser->getRodsAccount();
if(
	array_key_exists($this->config->item('role:reader'), $permissions) && 
	$permissions[$this->config->item('role:reader')] || 
	array_key_exists($this->config->item('role:contributor'), $permissions) && 
	$permissions[$this->config->item('role:contributor')] // TODO, should have reader access to
){ 
	if(sizeof($this->config->item("level-hierarchy")) > $level_depth + 1) {
		$lh = $this->config->item('level-hierarchy')[$level_depth+1];
	} else {
		$lh = $this->config->item('default-level');
	}
	
	$glyphLabel = "";
	$glyph = "folder-open";
	$tab = "";

	if(array_key_exists("title", $lh)) {
		$glyphLabel = $lh["title"];
	}
	if(array_key_exists("glyphicon", $lh) && $lh["glyphicon"]) {
		$glyph = $lh["glyphicon"];
	}
	if(array_key_exists("tab", $lh)) {
		$tab = $lh["tab"];
	} else {
		$tab = $glyphLabel;
	}
	
		
	if(lang('intake_config_' . $glyphLabel)) {
		$glyphLabel = lang('intake_config_' . $glyphLabel);
	}

	if(lang('intake_config_' . $tab)) {
		$tab = lang('intake_config_' . $tab);
	}
?>

<input type="hidden" name="levelglyph" value="<?=$glyph;?>"/>
<input type="hidden" name="nextLevelCanSnapshot" value="<?=($nextLevelPermissions->canSnapshot !== false) ? "1" : "0";?>"/>

<ul class="nav nav-tabs" id="fileOverviewTabMenu" data-tabs="tabs">
		<li role="presentation" class="active">
	 		<a href="#directories" aria-controls="directories" role="tab" data-toggle="tab">
	 			<?=ucfirst(htmlentities($tab));?>
	 		</a>
	 	</li> 
	 	<li role="presentation">
	 		<a href="#files" aria-controls="files" role="tab" data-toggle="tab">
	 			<?=ucfirst(lang('intake_tab_file_overview'));?>
	 		</a>
	 	</li> 

<?php if($levelPermissions->canSnapshot) { ?>
	 	<li role="presentation">
		 	<a href="#details" aria-controls="details" role="tab" data-toggle="tab">
		 		<?=ucfirst(lang('intake_tab_details'));?>
		 	</a>
	 	</li> 
<?php
}
?>
</ul>
<div class="tab-content">
	<div role="tabpanel" class="tab-pane active" id="directories">

		<table id="directories_overview" width="100%"
			class="display table table-datatable<?php 
			if($currentViewLocked) 
				echo " table-disabled";
			?>"
			data-tablelanguage="<?=htmlentities($folderTableLanguage);?>"
			>
			<thead>
				<tr>
					<th><?=ucfirst(lang('intake_name'));?></th>
					<th><?=ucfirst(lang('intake_size'));?></th>
					<th><?=ucfirst(lang('intake_files'));?></th>
					<th><?=ucfirst(lang('intake_created'));?></th>
					<th><?=ucfirst(lang('intake_modified'));?></th>
					<th<?=($nextLevelPermissions->canSnapshot === false) ? ' style="display: none"' : '';?>><?=ucfirst(lang('intake_snapshot_latest'));?></th>
				</tr>
			</thead>
			
			<tbody>

			</tbody>
		</table>
	</div>
<div role="tabpanel" class="tab-pane" id="files">
	<?php if(!$levelPermissions->canSnapshot) { ?>
	<div class="alert alert-warning">
		<?=lang('intake_files_not_recognized');?>
	</div>
	<?php } ?>

	<table id="files_overview" width="100%"
		class="display table table-datatable<?php 
		if($currentViewLocked) 
			echo " table-disabled";
		?>" 
		data-tablelanguage="<?=htmlentities($fileTableLanguage);?>"
		>
		<thead>
			<tr>
				<th><?=ucfirst(lang('intake_name'));?></th>
				<th><?=ucfirst(lang('intake_size'));?></th>
				<th><?=ucfirst(lang('intake_created'));?></th>
				<th><?=ucfirst(lang('intake_modified'));?></th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>

<?php }
if($levelPermissions->canSnapshot) {
$row = '<div class="row"><div class="col-xs-12 col-sm-2"><b>%1$s</b></div><div class="col-xs-12 col-sm-10">%2$s</div></div>';
$itemTemplate = '<li class="list-group-item"><div class="container-fluid">' . $row . '</div></li>';
?>
	</div>
	<div role="tabpanel" class="tab-pane" id="details">

<?php
	 if(sizeof($snapshotHistory) > 0) {
?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<?=ucfirst(lang('intake_head_snapshot_history'));?>
			</h3>
		</div>
		<ul class="list-group">
<?php
		foreach($snapshotHistory as $hist) {
?>
			<li class="list-group-item">
				<div class="container-fluid">
<?php
			echo sprintf(
				$row, 
				ucfirst(lang('intake_snapshot_head_summary')), 
				sprintf(
					lang('intake_snapshot_created_at_by'), 
					// absoluteTimeWithTooltip($hist->time), 
					absoluteTimeWithTooltip($hist->createdDatetime),
					$hist->createdUser
				)
			);
			echo sprintf(
				$row, 
				ucfirst(lang('intake_version')),
				$hist->version ? $hist->version : 
					lang('intake_info_not_available')
			);
			echo sprintf(
				$row, 
				ucfirst(lang('intake_snapshot_vault_path')), 
				$hist->vaultPath ? $hist->vaultPath : 
					lang('intake_info_not_available')
			);
			echo sprintf(
				$row,
				ucfirst(lang('intake_snapshot_depends')),
				($hist->dependsPath && $hist->dependsVersion !== "0") ?
					sprintf(ucfirst(lang('intake_snapshot_depends_version_path')), $hist->dependsVersion, $hist->dependsPath) :
					ucfirst(lang('intake_snapshot_first_version'))
			);
?>
				</div>
			</li>
<?php
		}
?>
		</ul>
	</div>
<?php
	}
}
?>
</div> 
