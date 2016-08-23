<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if ($folderValid) {
	if(isset($errorMessage)) {
		echo sprintf('<div class="alert alert-danger">%1$s</div>', $errorMessage);
	}
	if ($header && (sizeof($studies) > 0 || $level_depth === -1)) { ?>
		<div class="container page-body">
			<div class="row page-header">
				<div class="col-sm-6">
					<h1>
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
		<?php if($previousLevelLink) { ?>
					<a class="btn btn-default pull-left back-button" 
						href="<?=$previousLevelLink?>">
						<span class="glyphicon glyphicon-back"></span>&nbsp;<?=ucfirst(lang('intake_button_back'));?>
					</a>
		<?php } ?>
				<ol class="breadcrumb">

					<?php foreach($breadcrumbs as $bc) {
						$html = "\t<li class=\"breadcrumb-item";
						if($bc->is_current) $html .= " active";
						$html .= "\">";
						if($bc->prefix !== false) $html .= $bc->prefix ;
						if($bc->link !== false && $bc->is_current === false) $html .= "<a href=\"" . $bc->link . "\">";
						$html .= $bc->segment;
						if($bc->link !== false && $bc->is_current === false) $html .= "</a>";
						if($bc->postfix !== false) $html .= $bc->postfix;
						$html .= "</li>\n";
						echo $html;
					}
					?>
				</ol>
			</div>
		<?php } ?>

		<?php if($information = $this->session->flashdata('information')){ ?>
			<div class="alert alert-<?=$information->type;?>">
				<?=$information->message;?>
			</div>
		<?php 
		}
		
		if($currentViewLocked) { ?>
			<div class="alert alert-danger"><?=lang('intake_dataset_locked');?></div>
	<?php 
		} 
		$attrs = array(
			"directory" => $current_dir,
		);
		echo form_open(null, null, $attrs);
	?>

	<div class="btn-group">
	<?php
		if($levelPermissions->canSnapshot && !$currentViewLocked) { 
			?>
				<button type="<?=(sizeof($directories) === 0 && sizeof($files) === 0) ? "button" : "submit";?>" 
					class="btn btn-default <?php if(sizeof($directories) === 0 && sizeof($files) === 0) echo " disabled";?>"
					formaction="<?=site_url(array($this->module->name(), "actions", "snapshot")); ?>"
					>
					<span class="glyphicon glyphicon-camera"></span>&nbsp;<?=ucfirst(lang('intake_button_create_snapshot'));?>
				</button>
		<?php } else if($levelPermissions->canSnapshot && !$currentViewFrozen) { ?>
				<button type="submit" class="btn btn-default" 
					formaction="<?=site_url(array($this->module->name(), "actions", "unlock")); ?>"
					<span class="glyphicon glyphicon-lock" title="<?=lang('intake_file_locked');?>"></span>&nbsp;<?=ucfirst(lang('intake_button_unlock_snapshot'));?>
				</button>
		<?php 
		}
		if($levelPermissions->canEditMeta || $levelPermissions->canViewMeta){ ?>
			<a href="<?=site_url(array($url->module, "intake", "metadata")) . "?dir=" . urlencode($current_dir); ?>" class="btn btn-default">
				<span class="glyphicon glyphicon-tags"></span>&nbsp;&nbsp;<?=ucfirst(lang('intake_header_metadata'));?>
			</a>
<?php 
		} 
?>
	</div>
<?php
	echo form_close();
	$this->load->view($content); 

} else {
	if($information = $this->session->flashdata('information')){ ?>
	<div class="alert alert-<?=$information->type;?>">
		<?=$information->message;?>
	</div>
<?php
	} 
}
?>


