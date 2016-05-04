<?php
?>

<?php if($userIsAllowed): ?>
<table id="files_overview" class="display">
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
							$studyID, 
							$dir->getName(),
							$dir->getName()
						) :
						$dir->getName();
				?>
			</td>

			<td><?=human_filesize($count['totalSize']);?></td> <!-- Size -->

			<td><!-- File / Dir information -->
				<?=sprintf("%d (in %d directories)", $count['filecount'], $count['dircount']);?>
			</td>

			<td> <!-- Created -->
				&plusmn;
				<?=explode(",",timespan($dir->stats->ctime))[0];?> ago
			</td>

			<td> <!-- Modified -->
				&plusmn;
				<?=explode(",",timespan($dir->stats->mtime))[0];?> ago
			</td>
			
			<td> <!-- Comment -->
				<?=$dir->stats->comments; ?>
			</td>
		</tr>
<?php
	endforeach;
	foreach($files as $file): 
		$inf = $this->dataset2->getFileInformation($rodsaccount, $currentDir, $file->getName());
?>
	<tr>
			<td> <!-- Name -->
				<span class="glyphicon glyphicon-none" style="margin-right: 24px"></span>
				<?=$file->getName(); ?>
			</td>

			<td>
				<?=human_filesize(intval($inf["*size"]));?>				
			</td> <!-- Size -->

			<td></td>

			<td> <!-- Created -->
				&plusmn;
				<?=explode(",",timespan($file->stats->ctime))[0];?> ago
			</td>

			<td> <!-- Modified -->
				&plusmn;
				<?=explode(",",timespan($file->stats->mtime))[0];?> ago
			</td>
			<td>
				<?=$file->stats->comments; ?>
			</td>
		</tr>
<?php
	endforeach;
?>
		
		


	</tbody>
</table>

<?php endif;

