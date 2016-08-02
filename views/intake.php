<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if ($header && sizeof($studies) > 0): ?>
	<div class="container page-body">
		<div class="row page-header">
			<div class="col-sm-6">
				<h1>
					<!-- <?php echo ($title); ?> -->
					<?php 
						$title = "<h1>";
						if($head["glyphicon"] !== false)
							$title .= sprintf('<span class="glyphicon glyphicon-%s"></span>&nbsp;', $head["glyphicon"]);
						if($head["title"] != false)
							$title .= $head["title"] . "&nbsp;";
						$title .= $breadcrumbs[sizeof($breadcrumbs) - 1]->segment . "</h1>";
						echo $title;
					?>
				</h1>
			</div>
			
		</div>
		<div class="row">
			<ol class="breadcrumb">
				<?php foreach($breadcrumbs as $bc) {
					$html = "\t<li class=\"breadcrumb-item";
					if($bc->is_current) $html .= " active";
					$html .= "\">";
					if($bc->prefix !== false) $html .= $bc->prefix ;
					if($bc->link !== false) $html .= "<a href=\"" . $bc->link . "\">";
					$html .= $bc->segment;
					if($bc->link !== false) $html .= "</a>";
					if($bc->postfix !== false) $html .= $bc->postfix;
					$html .= "</li>\n";
					echo $html;
				}
				?>
			</ol>
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
<?php 
endif; 
?>

<div class="btn-group">
	<button type="button" class="btn btn-default dropdown-toggle" 
		<?php if(sizeof($studies) == 0) echo "disabled";?> 
		data-toggle="dropdown" 
		aria-haspopup="true" 
		aria-expanded="false"
	>
		<span class="glyphicon glyphicon-option-vertical"></span>
		<?=lang('intake:change-project');?>&nbsp;<span class="caret"></span>
	</button>
	<ul class="dropdown-menu">
		<?php 
			foreach($studies as $study){
				$class = $study == $studyID ? 'glyphicon-ok' : 'pad-left';
				$str = '<li><a class="glyphicon %5$s" href="%1$s/intake?dir=/%2$s/home/%3$s%4$s">&nbsp;%4$s</a><li>';
				echo sprintf(
					$str, 
					$url->module, 
					$this->config->item('rodsServerZone'), 
					$this->config->item('intake-prefix'),
					$study, 
					$class
				);
			}
		?>
	</ul>
<?php
	if($levelPermissions->canSnapshot && !$currentViewLocked): ?>
			<button type="submit" class="btn btn-default" formaction="<?=$url->module;?>/actions/snapshot"<?php if(sizeof($directories) == 0) echo " disabled";?>>
				<span class="glyphicon glyphicon-camera"></span>&nbsp;<?=lang('create_snapshot');?>
			</button>
	<?php elseif($levelPermissions->canSnapshot && !$currentViewFrozen): ?>
			<button type="submit" class="btn btn-default"  name="unlock_study" value="<?=$studyFolder;?>"
				formaction="<?=$url->module;?>/actions/unlock">
				<span class="glyphicon glyphicon-lock" title="<?=lang('file_locked');?>"></span>&nbsp;<?=lang('unlock_snapshot');?>
			</button>

	<?php 
	endif; 
	if($levelPermissions->canEditMeta || $levelPermissions->canViewMeta): ?>
		<!-- <a type="button" class="btn btn-default" href="<?=$url->module;?>/intake/metadata/<?=$studyID;?>/<?=$studyFolder;?>">
			<span class="glyphicon glyphicon-tags"></span> Meta data
		</a> -->
		<a href="#" class="btn btn-default">
			<span class="glyphicon glyphicon-tags"></span> NTL: Meta data (todo)
		</a>
	<?php endif; ?>
</div>
<?php $this->load->view($content); ?>

</div>


