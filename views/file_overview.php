<?php
?>

<?php if($userIsAllowed): ?>
<table id="files_overview" class="display table table-datatable">
	<thead>
		<tr>
			<th>Name</th>
			<th>Size</th>
			<th>Files</th>
			<th>Created</th>
			<th>Modified</th>
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
		?>
		<tr>
			<td data-toggle="tool-tip" title="<?=$glyphLabel;?>"> <!-- Name -->
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
			</td>

			<td><?=human_filesize($count['totalSize']);?></td> <!-- Size -->

			<td><!-- File / Dir information -->
				<?=sprintf("%d (in %d directories)", $count['filecount'], $count['dircount']);?>
			</td>

			<td> <!-- Created -->
				&plusmn;
				<?=explode(",",timespan(htmlentities($dir->stats->ctime)))[0];?> ago
			</td>

			<td> <!-- Modified -->
				&plusmn;
				<?=explode(",",timespan(htmlentities($dir->stats->mtime)))[0];?> ago
			</td>
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
			<th>Files</th>
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
?>
	<tr>
			<td> <!-- Name -->
				<span class="glyphicon glyphicon-none" style="margin-right: 24px"></span>
				<?=htmlentities($file->getName()); ?>
			</td>

			<td>
				<?=human_filesize(intval(htmlentities($inf["*size"])));?>				
			</td> <!-- Size -->

			<td></td>

			<td> <!-- Created -->
				&plusmn;
				<?=explode(",",timespan(htmlentities($file->stats->ctime)))[0];?> ago
			</td>

			<td> <!-- Modified -->
				&plusmn;
				<?=explode(",",timespan(htmlentities($file->stats->mtime)))[0];?> ago
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

