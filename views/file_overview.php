<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(array_key_exists($this->config->item('role:reader'), $permissions) && $permissions[$this->config->item('role:reader')]
	|| array_key_exists($this->config->item('role:contributor'), $permissions) && $permissions[$this->config->item('role:contributor')] // TODO, should have reader access to
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

<table id="files_overview" class="display table table-datatable<?php if($currentViewLocked) echo " table-disabled";?>">
	<thead>
		<tr>
			<?php if($nextLevelPermissions->canSnapshot || $nextLevelPermissions->canArchive) { ?>
				<!--<th><input type="checkbox"/></th> -->
			<?php } ?>
			<th><?=lang('snapshot_name');?></th>
			<th><?=lang('size');?></th>
			<th><?=lang('files');?></th>
			<th><?=lang('created');?></th>
			<th><?=lang('modified');?></th>
		<?php if($nextLevelPermissions->canSnapshot): ?> 
			<th><?=lang('snapshot_latest');?></th>
		<?php endif; ?>
			<th><?=lang('comment');?></th>
		</tr>
	</thead>
	
	<tbody>
<?php 
	$rodsaccount = $this->rodsuser->getRodsAccount();

	foreach($directories as $dir){ 
    	$count = $this->filesystem->countSubFiles($rodsaccount, $current_dir . "/" . $dir->getName());
    	$lock = $this->dataset->getLockedStatus($rodsaccount, $current_dir . "/" . $dir->getName(), true);
    	if($nextLevelPermissions->canSnapshot !== false) {
    		$latestSnapshot = $this->dataset->getLatestSnapshotInfo($rodsaccount, $current_dir . "/" . $dir->getName());
    	} else {
    		$latestSnapshot = false;
    	}
?>
		<tr <?php if($lock["locked"]) echo "class=\"table-row-disabled\"";?>>
			<?php if(false && ($nextLevelPermissions->canSnapshot || $nextLevelPermissions->canArchive)): ?>
				<td>
				<?php if(!$lock["locked"]) : ?>
					<input type="checkbox" name="checked_studies[]" value="<?=htmlentities($dir->getName());?>"/>
				<?php elseif($lock["frozen"]): ?>
					<span class="glyphicon glyphicon-lock glyphicon-button-disabled" title="<?=lang('file_frozen');?>"/>
				<?php elseif($lock["locked"]) : ?>
						<button class="btn btn-link" type="submit" name="unlock_study" value="<?=$dir->getName();?>"
							formaction="<?=$url->module;?>/actions/unlock">
							<span class="glyphicon glyphicon-lock" title="<?=lang('file_locked');?>"/>
						</button>
				<?php endif;?>
				</td>
			<?php endif;?>
			<th data-toggle="tool-tip" title="<?=$glyphLabel;?>" > <!-- Name -->
				<span class="glyphicon glyphicon-<?=$glyph;?>" style="margin-right: 10px"></span>
				<?php
					$lnk = isset($studyID) ? '<a href="%1$s/intake/index?dir=%2$s/%3$s">%3$s</a>' : '<span class="grey">%4$s</span><a href="%1$s/intake/index?dir=%2$s/%3$s">%5$s</a>';
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
				<?=sprintf(lang('files_in_dirs'), $count['filecount'], $count['dircount']);?>
			</td>

			<td> <!-- Created -->
				<?=absoluteTimeWithTooltip($dir->stats->ctime); ?>
			</td>

			<td> <!-- Modified -->
				<?=absoluteTimeWithTooltip($dir->stats->mtime);?>
			</td>
			<?php if($nextLevelPermissions->canSnapshot): ?>
				<td>
					<?php 
						if(isset($latestSnapshot) && $latestSnapshot !== false) { // remove isset once $latestSnapshot is defined
							echo sprintf(lang('latest_snapshot_by'), relativeTimeWithTooltip($latestSnapshot["datetime"]->getTimestamp(), true),htmlentities($latestSnapshot["username"]));
						} else {
							echo lang('no_snapshots');
						}
					?>
				</td>
			<?php endif; ?>
			<td>
				<?=htmlentities($dir->stats->comments); ?>
			</td>
		</tr>
		
<?php
	}
	if($levelPermissions->canArchive === false && sizeof($files) > 0 && $level_depth < $levelSize) {
?>
	</tbody>
</table>

<div class="row page-header">
    <div class="col-sm-6">
      <h3>
        <span class="glyphicon glyphicon-alert"></span>
        <?=lang('header:files_not_recognised');?>
      </h3>
    </div>
</div>

<div class="alert alert-warning">
	<?=lang('header:files_not_in_dataset');?>
</div>

<table id="unknown_files_overview" class="display table table-datatable">
	<thead>
		<tr>
			<th><?=lang('dataset_name');?></th>
			<th><?=lang('size');?></th>
			<th><?=lang('created');?></th>
			<th><?=lang('modified');?></th>
			<th><?=lang('comment');?></th>
		</tr>
	</thead>
	
	<tbody>

<?php
	}

	foreach($files as $file) { 
		$inf = $this->filesystem->getFileInformation($rodsaccount, $current_dir, $file->getName());
		$lock = $this->dataset->getLockedStatus($rodsaccount, $current_dir . "/" . $file->getName(), false);
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