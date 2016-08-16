<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if($folderValid === false) {
	if($information = $this->session->flashdata('information')){ ?>
		<div class="alert alert-<?=$information->type;?>">
			<?=$information->message;?>
		</div>
<?php
	}
} else {

if ($header && sizeof($studies) > 0): ?>
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
				formaction="<?=site_url(array($this->modulelibrary->name(), "actions", "snapshot")); ?>"
				>
				<span class="glyphicon glyphicon-camera"></span>&nbsp;<?=lang('create_snapshot');?>
			</button>
	<?php } else if($levelPermissions->canSnapshot && !$currentViewFrozen) { ?>
			<button type="submit" class="btn btn-default" 
				formaction="<?=site_url(array($this->modulelibrary->name(), "actions", "unlock")); ?>"
				<span class="glyphicon glyphicon-lock" title="<?=lang('file_locked');?>"></span>&nbsp;<?=lang('unlock_snapshot');?>
			</button>
	<?php 
	} 
	if($levelPermissions->canEditMeta || $levelPermissions->canViewMeta){ ?>
		<a href="<?=site_url(array($url->module, "intake", "metadata")) . "?dir=" . urlencode($current_dir); ?>" class="btn btn-default">
			<span class="glyphicon glyphicon-tags"></span>&nbsp;<?=lang('header:metadata');?>
		</a>
<?php 
	} 
?>
</div>
<?php
	echo form_close();
	$this->load->view($content); 

}
?>


