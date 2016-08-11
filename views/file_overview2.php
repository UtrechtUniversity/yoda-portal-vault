<?php
?>
<?php if($userIsAllowed): ?>

<table id="files_overview" class="display table table-datatable<?php if($currentViewLocked) echo " table-disabled";?>">
	<thead>
		<tr>
		<?php if($studyFolder == ''): ?>
			<th><input type="checkbox"/></th>
		<?php endif; ?>
			<th><?=lang('snapshot_name');?></th>
			<th><?=lang('size');?></th>
			<th><?=lang('files');?></th>
			<th><?=lang('created');?></th>
			<th><?=lang('modified');?></th>
		<?php if($studyFolder == ''): ?>
			<th><?=lang('snapshot_latest');?></th>
		<?php endif; ?>
			<th><?=lang('comment');?></th>
		</tr>
	</thead>
	
	<tbody>
<?php 		
	foreach($directories as $dir): ?>
		<?php
			$isSet = $studyFolder == ''; // Only directories in root of study are dataset
			$glyph = ($studyFolder == '') ? "inbox" : "folder-open";
			$glyphLabel = $dir->getName() . sprintf(" (%s)", $isSet ? lang("dataset") : lang("directory"));
        	$count = $this->filesystem->countSubFiles($rodsaccount, $intakePath . "/" . $dir->getName());
        	$lock = $this->dataset->getLockedStatus($rodsaccount, $intakePath . "/" . $dir->getName(), true);

        	if($isSet) {
        		$latestSnapshot = $this->dataset->getLatestSnapshotInfo($rodsaccount, $intakePath . "/" . $dir->getName());
        	} else {
        		$latestSnapshot = false;
        	}
		?>
		<tr <?php if($lock["locked"]) echo "class=\"table-row-disabled\"";?>>
			<?php if($studyFolder == ''): ?>
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
					// $lnk = "<a href=\"" . $url->module . "/intake/index/%1s/%2s\">%2s</a>";
					$lnk = '<a href="%1$s/intake/index?dir=%2$s/%3$s">%3$s</a>';
					echo $isSet ? sprintf(
							$lnk,
							// htmlentities($studyID), 
							// htmlentities($dir->getName()),
							// htmlentities($dir->getName())
							htmlentities($url->module),
							htmlentities($currentDir),
							htmlentities($dir->getName())
						) :
						htmlentities($dir->getName());
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
			<?php if($studyFolder == ''): ?>
				<td>
					<?php 
						if($latestSnapshot) {
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
	endforeach;
	if($studyFolder == '' && sizeof($files) > 0):
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
	endif;
	foreach($files as $file): 
		$inf = $this->filesystem->getFileInformation($rodsaccount, $currentDir, $file->getName());
		$lock = $this->dataset->getLockedStatus($rodsaccount, $currentDir . "/" . $file->getName(), false);
?>
	<tr>
			<th> <!-- Name -->
				<span class="glyphicon glyphicon-none" style="margin-right: 24px"></span>
				<?=htmlentities($file->getName()); ?>
			</th>

			<td>
				<?=human_filesize(intval(htmlentities($inf["*size"])));?>				
			</td> <!-- Size -->

			<?php if($studyFolder != ''): ?>
				<td></td>
			<?php endif; ?>

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
	endforeach;
?>
	</tbody>
</table>

<?php endif;
