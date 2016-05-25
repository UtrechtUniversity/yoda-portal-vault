<?php
?>

<?php if($userIsAllowed): ?>

<table id="files_overview" class="display table table-datatable<?php if($currentViewLocked) echo " table-disabled";?>">
	<thead>
		<tr>
		<?php if($studyFolder == ''): ?>
			<th><input type="checkbox"/></th>
		<?php endif; ?>
			<th>Name</th>
			<th>Size</th>
			<th>Files</th>
			<th>Created</th>
			<th>Modified</th>
		<?php if($studyFolder == ''): ?>
			<th>Latest snapshot</th>
		<?php endif; ?>
			<th>Comment</th>
		</tr>
	</thead>
	
	<tbody>
<?php 		
	foreach($directories as $dir): ?>
		<?php
			$isSet = $studyFolder == ''; // Only directories in root of study are dataset
			$glyph = ($studyFolder == '') ? "inbox" : "folder-open";
			$glyphLabel = $dir->getName() . sprintf(" (%s)", $isSet ? "dataset" : "directory");
        	$count = $this->dataset2->countSubFiles($rodsaccount, $intakePath . "/" . $dir->getName());
        	$lock = $this->dataset2->getLockedStatus($rodsaccount, $intakePath . "/" . $dir->getName(), true);

        	if($isSet) {
        		$latestSnapshot = $this->dataset2->getLatestSnapshotInfo($rodsaccount, $intakePath . "/" . $dir->getName());
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
							formaction="/intake-ilab/actions/unlock">
							<span class="glyphicon glyphicon-lock" title="<?=lang('file_locked');?>"/>
						</button>
				<?php endif;?>
				</td>
			<?php endif;?>
			<th data-toggle="tool-tip" title="<?=$glyphLabel;?>" > <!-- Name -->
				<span class="glyphicon glyphicon-<?=$glyph;?>" style="margin-right: 10px"></span>
				<?php
					$lnk = "<a href=\"/intake-ilab/intake/index/%1s/%2s\">%2s</a>";
					echo $isSet ? sprintf(
							$lnk,
							htmlentities($studyID), 
							htmlentities($dir->getName()),
							htmlentities($dir->getName())
						) :
						htmlentities($dir->getName());
				?>
			</th>

			<td><?=human_filesize($count['totalSize']);?></td> <!-- Size -->

			<td><!-- File / Dir information -->
				<?=sprintf("%d (in %d directories)", $count['filecount'], $count['dircount']);?>
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
							echo relativeTimeWithTooltip($latestSnapshot["datetime"]->getTimestamp(), true);
							echo " by " . htmlentities($latestSnapshot["username"]);
						} else {
							echo "None";
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
        <?=lang('HDR_FILES_NOT_RECOGNISED');?>
      </h3>
    </div>
</div>

<div class="alert alert-warning">
	<?=lang('FILES_NOT_IN_DATASET');?>
</div>

<table id="unknown_files_overview" class="display table table-datatable">
	<thead>
		<tr>
			<th>Name</th>
			<th>Size</th>
			<th>Created</th>
			<th>Modified</th>
			<th>Comment</th>
		</tr>
	</thead>
	
	<tbody>

<?php
	endif;
	foreach($files as $file): 
		$inf = $this->dataset2->getFileInformation($rodsaccount, $currentDir, $file->getName());
		$lock = $this->dataset2->getLockedStatus($rodsaccount, $currentDir . "/" . $file->getName(), false);
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

