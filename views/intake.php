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
	if($levelPermissions->canSnapshot && sizeof($snapshotHistory) > 0) {
?>

	<ul class="nav nav-tabs" id="fileOverviewTabMenu" data-tabs="tabs">
	 	<li role="presentation" class="active"><a href="#files" aria-controls="files" role="tab" data-toggle="tab">ntl:Files</a></li> 
	 	<li role="presentation"><a href="#details" aria-controls="details" role="tab" data-toggle="tab">ntl:Details</a></li> 
	</ul>
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="files">

<?php 
}
form_close();
$this->load->view($content); 
if($levelPermissions->canSnapshot && sizeof($snapshotHistory) > 0) {
$row = '<div class="row"><div class="col-xs-12 col-sm-2"><b>%1$s</b></div><div class="col-xs-12 col-sm-10">%2$s</div></div>';
$itemTemplate = '<li class="list-group-item"><div class="container-fluid">' . $row . '</div></li>';
?>
		</div>
		<div role="tabpanel" class="tab-pane" id="details">
		<h3>ntl:Information</h3><ul class="list-group">
<?php 
	$rodsaccount = $this->rodsuser->getRodsAccount();
	$count = $this->filesystem->countSubFiles($rodsaccount, $current_dir);
	$version = $this->dataset->getCurrentVersion($rodsaccount, $current_dir);
	echo sprintf($itemTemplate, "ntl:Dataset name", $breadcrumbs[sizeof($breadcrumbs) - 1]->segment);
	echo sprintf($itemTemplate, "ntl:Path to dataset", $current_dir);
	echo sprintf($itemTemplate, "ntl:Current version", $version->version ? $version->version : "ntl:N/A");
	echo sprintf($itemTemplate, "ntl:Based on", $version->basedon? $version->basedon : "ntl:N/A");
	echo sprintf($itemTemplate, "ntl:Total folders", $count["dircount"]);
	echo sprintf($itemTemplate, "ntl:Total files", $count["filecount"]);
	echo sprintf($itemTemplate, "ntl:Total size", sprintf('%1$s (%2$s ntl:bytes)', human_filesize($count["totalSize"]), $count["totalSize"]));
	// echo sprintf($row, "ntl:Latest snapshot")

	echo "</ul>";	

	echo "<h3>NTL: Version history</h3>";
	echo "<ul class=\"list-group\">";
	foreach($snapshotHistory as $hist) {
		echo "<li class=\"list-group-item\">";
		echo "<div class=\"container-fluid\">";
		echo sprintf($row, "ntl:Information", sprintf('ntl:%1$s by %2$s', absoluteTimeWithTooltip($hist->time), $hist->user));
		echo sprintf($row, "ntl:Version", $hist->version ? $hist->version : "ntl:N/A");
		echo sprintf($row, "ntl:Vault path", $hist->datasetPath ? $hist->datasetPath : "ntl:N/A");
		echo "</div></li>";
	}
	echo "</ul>";
}

?>
		</div> 
	</div>


</div>
<?php 
}
?>


