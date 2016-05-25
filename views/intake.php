<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ($header && $hasStudies): ?>
	<div class="container page-body">
		<div class="row page-header">
			<div class="col-sm-6">
				<h1>
					<span class="glyphicon glyphicon-education"></span>
					<?php echo htmlentities($title); ?>
				</h1>
				<?php if($userIsAllowed) echo htmlentities($intakePath . ($studyFolder?'/'.$studyFolder:'')); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if($information = $this->session->userdata('information')): ?>
		<div class="alert alert-<?=$information->type;?>">
			<?=$information->message;?>
		</div>
	<?php 
		$this->session->unset_userdata('information');
	endif;
	if($currentViewLocked): ?>
		<div class="alert alert-danger">
			This dataset is currently locked because a snapshot is in progress. The dataset is locked to ensure that a single state is snapshot, and no different versions of files can occur in the same snapshot version. Please wait until the snapshot is finished, after which you will have access to your files again.
		</div>
	<?php endif; ?>

<?php
	$attrs = array(
		"studyRoot" => $intakePath,
		"studyID" => $studyID,
		"dataset" => ($studyFolder ? $studyFolder : false)
	);
	echo form_open(null,null, $attrs);
	if($studyFolder == ''):
?>
		<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" <?php if(!$hasStudies) echo "disabled";?> data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="glyphicon glyphicon-option-vertical"></span>
				Change study <span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<?php foreach($studies as $study):
					$class = $study == $studyID ? 'glyphicon-ok' : 'pad-left';
					$str = "<li ><a class=\"glyphicon %s\" href=\"/intake-ilab/intake/index/%s\">&nbsp;%s</a></li>";
					echo sprintf($str, $class, $study, $study);
				endforeach;
				?>
			</ul>
		</div>
	<?php else: ?>
		<button type="submit" class="btn btn-default" formaction="/intake-ilab/intake/index/<?=$studyID;?>" >
		<span class="glyphicon glyphicon-arrow-left"></span>
		</button>
	<?php endif;
		if(!$currentViewLocked): ?>
			<button type="submit" class="btn btn-default" formaction="/intake-ilab/actions/snapshot"<?php if(sizeof($directories) == 0 && $studyFolder == '') echo " disabled";?>>
				<span class="glyphicon glyphicon-camera"></span> Snapshot
			</button>
	<?php elseif($studyFolder && !$currentViewFrozen): ?>
			<button type="submit" class="btn btn-default"  name="unlock_study" value="<?=$studyFolder;?>"
				formaction="/intake-ilab/actions/unlock">
				<span class="glyphicon glyphicon-lock" title="<?=lang('file_locked');?>"></span> Unlock
			</button>

		<?php endif; ?>

		<button type="submit" class="btn btn-default" formaction="/intake-ilab/actions/unlockAll">
			<span class="glyphicon glyphicon-lock">Unlock all</span>
		</button>

		<button type="submit" class="btn btn-default" formaction="/intake-ilab/actions/testFunction">
			<span class="glyphicon glyphicon-gift">Run test</span>
		</button>

		<?php $this->load->view($content); ?>
	<?=form_close();
	$this->load->view($meta_editor); ?>
</div>


