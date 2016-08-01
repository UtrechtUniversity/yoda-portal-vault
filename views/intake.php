<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if ($header && $hasStudies): ?>
	<div class="container page-body">
		<div class="row page-header">
			<div class="col-sm-6">
				<h1>
					<?php echo ($title); ?>
				</h1>
				<?php if($userIsAllowed) echo htmlentities($intakePath . ($studyFolder?'/'.$studyFolder:'')); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if($information = $this->session->flashdata('information')): ?>
		<div class="alert alert-<?=$information->type;?>">
			<?=$information->message;?>
		</div>
	<?php 
	endif;
	
	if($currentViewLocked): ?>
		<div class="alert alert-danger"><?=lang('dataset_locked');?></div>
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
					// $str = "<li ><a class=\"glyphicon %s\" href=\"" . $url->module . "/intake/index/%s\">&nbsp;%s</a></li>";
					// echo sprintf($str, $class, $study, $study);
					$str = '<li><a class="glyphicon %5%s" href="%1$s/intake?dir=/%2$s/home/%3$s%4$s">&nbsp;%4$s</a><li>';
					echo sprintf($str, $url->module, $this->config->item('rodsServerZone'), $this->config->item('intake-prefix'), $study, $class);
				endforeach;
				?>
			</ul>
		</div>
	<?php else: ?>
		<button type="submit" class="btn btn-default" formaction="<?=$url->module;?>/intake/index/<?=$studyID;?>" >
			<span class="glyphicon glyphicon-arrow-left"></span>
		</button>
	<?php endif;
		if(!$currentViewLocked): ?>
			<button type="submit" class="btn btn-default" formaction="<?=$url->module;?>/actions/snapshot"<?php if(sizeof($directories) == 0 && $studyFolder == '') echo " disabled";?>>
				<span class="glyphicon glyphicon-camera"></span>&nbsp;<?=lang('create_snapshot');?>
			</button>
	<?php elseif($studyFolder && !$currentViewFrozen): ?>
			<button type="submit" class="btn btn-default"  name="unlock_study" value="<?=$studyFolder;?>"
				formaction="<?=$url->module;?>/actions/unlock">
				<span class="glyphicon glyphicon-lock" title="<?=lang('file_locked');?>"></span>&nbsp;<?=lang('unlock_snapshot');?>
			</button>

	<?php endif; ?>
	<?php if($studyFolder): ?>
		<a type="button" class="btn btn-default" href="<?=$url->module;?>/intake/metadata/<?=$studyID;?>/<?=$studyFolder;?>">
			<span class="glyphicon glyphicon-tags"></span> Meta data
		</a>
	<?php endif; ?>
		<?php //$this->load->view($content); ?>
	<?=form_close();
?>
</div>


