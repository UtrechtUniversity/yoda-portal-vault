<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
if($levelPermissions->canSnapshot) { ?>

<ul class="nav nav-tabs" id="fileOverviewTabMenu" data-tabs="tabs">
	 	<li role="presentation" class="active">
	 		<a href="#files" aria-controls="files" role="tab" data-toggle="tab">
	 			<?=ucfirst(lang('intake_tab_file_overview'));?>
	 		</a>
	 	</li> 
	 	<li role="presentation">
		 	<a href="#details" aria-controls="details" role="tab" data-toggle="tab">
		 		<?=ucfirst(lang('intake_tab_details'));?>
		 	</a>
	 	</li> 
</ul>
<div class="tab-content">
	<div role="tabpanel" class="tab-pane active" id="files">
<?php
}
$rodsaccount = $this->rodsuser->getRodsAccount();
if(
	array_key_exists($this->config->item('role:reader'), $permissions) && 
	$permissions[$this->config->item('role:reader')] || 
	array_key_exists($this->config->item('role:contributor'), $permissions) && 
	$permissions[$this->config->item('role:contributor')] // TODO, should have reader access to
){ 
	if(sizeof($this->config->item("level-hierarchy")) > $level_depth + 1) {
		$lh = $this->config->item('level-hierarchy')[$level_depth+1];
		if(array_key_exists("title", $lh)) {
			$glyphLabel = $lh["title"];
		}
		if(array_key_exists("glyphicon", $lh)) {
			$glyph = $lh["glyphicon"];
		}
	} else {
		$lh = $this->config->item('default-level');
		$glyphLabel = "";
		$glyph = "folder-open";
	}
?>

	<table id="files_overview" 
		class="display table table-datatable<?php 
		if($currentViewLocked) 
			echo " table-disabled";
		?>">
		<thead>
			<tr>
				<th><?=ucfirst(lang('intake_name'));?></th>
				<th><?=ucfirst(lang('intake_size'));?></th>
				<th><?=ucfirst(lang('intake_files'));?></th>
				<th><?=ucfirst(lang('intake_created'));?></th>
				<th><?=ucfirst(lang('intake_modified'));?></th>
	<?php if($nextLevelPermissions->canSnapshot){ ?> 
				<th><?=ucfirst(lang('intake_snapshot_latest'));?></th>
	<?php } ?>
				<th><?=ucfirst(lang('intake_comment'));?></th>
			</tr>
		</thead>
		
		<tbody>
<?php 
	foreach($directories as $dir){ 
    	$count = $this->filesystem->countSubFiles(
    		$rodsaccount, 
    		$current_dir . "/" . $dir->getName()
    	);
    	$lock = $this->dataset->getLockedStatus(
    		$rodsaccount, 
    		$current_dir . "/" . $dir->getName(), 
    		true
    	);

    	if($nextLevelPermissions->canSnapshot !== false) {
    		$latestSnapshot = 
    			$this->dataset->getLatestSnapshotInfo(
    				$rodsaccount, 
    				$current_dir . "/" . $dir->getName()
    			);
    	} else {
    		$latestSnapshot = false;
    	}
?>
			<tr <?php if($lock["locked"]) echo "class=\"table-row-disabled\"";?>>
				<th data-toggle="tool-tip" title="<?=$glyphLabel;?>" > <!-- Name -->
					<span class="glyphicon glyphicon-<?=$glyph;?>" style="margin-right: 10px"></span>
<?php
		$lnk = isset($studyID) ? 
			'<a href="%1$s/intake/index?dir=%2$s/%3$s">%3$s</a>' : 
			'<span class="grey">%4$s</span><a href="%1$s/intake/index?dir=%2$s/%3$s">%5$s</a>';
			echo sprintf(
					$lnk,
					htmlentities($url->module),
					htmlentities($current_dir),
					htmlentities($dir->getName()),
					$intake_prefix,
					substr($dir->getName(), strlen($intake_prefix))
				);
				?>
				</th>

				<td><?=human_filesize($count['totalSize']);?></td> <!-- Size -->

				<td><!-- File / Dir information -->
					<?=sprintf(
						lang('intake_n_files_in_n_dirs'), 
						$count['filecount'], 
						$count['dircount']
					);?>
				</td>

				<td> <!-- Created -->
					<?=absoluteTimeWithTooltip($dir->stats->ctime); ?>
				</td>

				<td> <!-- Modified -->
					<?=absoluteTimeWithTooltip($dir->stats->mtime);?>
				</td>
				<?php if($nextLevelPermissions->canSnapshot){ ?>
					<td>
						<?php 
							if($latestSnapshot !== false) { 
								// remove isset once $latestSnapshot is defined
								echo sprintf(
									lang('intake_latest_snapshot_by'), 
									relativeTimeWithTooltip(
										$latestSnapshot["datetime"]->getTimestamp(), true
									),
									htmlentities($latestSnapshot["username"])
								);
							} else {
								echo lang('intake_no_snapshots');
							}
						?>
					</td>
				<?php } ?>
				<td>
					<?=htmlentities($dir->stats->comments); ?>
				</td>
			</tr>
<?php
	}
	
	if(
		$levelPermissions->canArchive === false && 
		sizeof($files) > 0 && 
		$level_depth < $levelSize
	) {
?>
		</tbody>
	</table>

	<div class="row page-header">
	    <div class="col-sm-6">
	      <h3>
	        <span class="glyphicon glyphicon-alert"></span>
	        <?=lang('intake_head_files_not_recognised');?>
	      </h3>
	    </div>
	</div>

	<div class="alert alert-warning">
		<?=lang('intake_files_not_recognized');?>
	</div>

	<table id="unknown_files_overview" class="display table table-datatable">
		<thead>
			<tr>
				<th><?=ucfirst(lang('intake_name'));?></th>
				<th><?=ucfirst(lang('intake_size'));?></th>
				<th><?=ucfirst(lang('intake_created'));?></th>
				<th><?=ucfirst(lang('intake_modified'));?></th>
				<th><?=ucfirst(lang('intake_comment'));?></th>
			</tr>
		</thead>
		
		<tbody>

<?php
	}

	foreach($files as $file) { 
		$inf = $this->filesystem->getFileInformation(
			$rodsaccount, 
			$current_dir, 
			$file->getName()
		);
		$lock = $this->dataset->getLockedStatus(
			$rodsaccount, 
			$current_dir . "/" . $file->getName(), 
			false
		);
?>
			<tr>
				<th> <!-- Name -->
					<span class="glyphicon glyphicon-file"></span>
					<?=htmlentities($file->getName()); ?>
				</th>

				<td>
					<?=human_filesize(intval(htmlentities($inf["*size"])));?>				
				</td> <!-- Size -->

				<?php if($levelPermissions->canArchive !== false) {
					echo "<td></td>";
				}?>

				<td> <!-- Created -->
					<?=absoluteTimeWithTooltip($file->stats->ctime);?>
				</td>

				<td> <!-- Modified -->
					&plusmn;
					<?=absoluteTimeWithTooltip($file->stats->mtime);?>
				</td>
				<td>
					<?=htmlentities($file->stats->comments); ?>
				</td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>

<?php }
if($levelPermissions->canSnapshot) {
$row = '<div class="row"><div class="col-xs-12 col-sm-2"><b>%1$s</b></div><div class="col-xs-12 col-sm-10">%2$s</div></div>';
$itemTemplate = '<li class="list-group-item"><div class="container-fluid">' . $row . '</div></li>';
?>
</div>
	<div role="tabpanel" class="tab-pane" id="details">
	
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<?=ucfirst(
					sprintf(
						lang('intake_head_dataset_information'), 
						$head["title"])
					);
				?>
			</h3>
		</div>
		<ul class="list-group">
<?php 
	$count = $this->filesystem->countSubFiles($rodsaccount, $current_dir);
	$version = $this->dataset->getCurrentVersion($rodsaccount, $current_dir);
	echo sprintf($itemTemplate, "ntl:Dataset name", $breadcrumbs[sizeof($breadcrumbs) - 1]->segment);
	echo sprintf($itemTemplate, "ntl:Path to dataset", $current_dir);
	echo sprintf($itemTemplate, "ntl:Current version", $version->version ? $version->version : "ntl:N/A");
	echo sprintf($itemTemplate, "ntl:Based on", $version->basedon? $version->basedon : "ntl:N/A");
	echo sprintf($itemTemplate, "ntl:Total folders", $count["dircount"]);
	echo sprintf($itemTemplate, "ntl:Total files", $count["filecount"]);
	echo sprintf($itemTemplate, "ntl:Total size", sprintf('%1$s (%2$s ntl:bytes)', human_filesize($count["totalSize"]), $count["totalSize"]));
?>
		</ul>
	</div>	

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
				"ntl:Information", 
				sprintf(
					'ntl:%1$s by %2$s', 
					absoluteTimeWithTooltip($hist->time), 
					$hist->user
				)
			);
			echo sprintf($row, "ntl:Version", $hist->version ? $hist->version : "ntl:N/A");
			echo sprintf(
				$row, 
				"ntl:Vault path", 
				$hist->datasetPath ? $hist->datasetPath : "ntl:N/A"
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
