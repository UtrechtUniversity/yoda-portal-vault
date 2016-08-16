<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if($folderValid === false) {
	if($information = $this->session->flashdata('information')) { ?>
		<div class="alert alert-<?=$information->type;?>">
			<?=$information->message;?>
		</div>
<?php
	}
} else if($levelPermissions->canViewMeta === false && $levelPermissions->canEditMeta === false) {
	?>
		<div class="alert alert-danger">
			ntl: You do not have permission to view or edit metadata for this directory
		</div>
	<?php
	} else { ?>
		<div class="container page-body">
			<div class="row page-header">
				<div class="col-sm-6">
					<h1>
						<span class="glyphicon glyphicon-tags"></span>&nbsp;
						<?=lang('header:metadata');?>
					</h1>
						<?php 
							$title = "<h3>";
							if($head["glyphicon"] !== false)
								$title .= sprintf('<span class="glyphicon glyphicon-%s"></span>&nbsp;', $head["glyphicon"]);
							if($head["title"] != false)
								$title .= $head["title"] . "&nbsp;";
							$title .= $breadcrumbs[sizeof($breadcrumbs) - 1]->segment . "</h3>";
							echo $title;
						?>
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
	<?php
		if($information = $this->session->flashdata('information')){ ?>
			<div class="alert alert-<?=$information->type;?>">
				<?=$information->message;?>
			</div>
		<?php 
		}

		if(sizeof($previousLevelsMeta) > 0) {
			echo '<ul class="nav nav-tabs" data-tabs="tabs">';
			echo sprintf('<li class="active" role="presentation"><a href="#metadata-level-%1$s" aria-controls="metadata-level-%1$s" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-%2$s"></span> %1$s</a></li>',
				$head["title"],
				$head["glyphicon"]
			);
			foreach($previousLevelsMeta as $m) {
				echo sprintf('<li role="presentation"><a href="#metadata-level-%1$s" aria-controls="metadata-level-%1$s" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-%2$s"></span> %1$s</a></li>',
						$m->level["title"],
						$m->level["glyphicon"]
					);
			}

			echo '</ul>';
		}
?>
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="metadata-level-<?=$head["title"];?>">

<?php
		$fields = $this->metadatafields->getFields($current_dir, true);

		if(!$fields || !is_array($fields) || sizeof($fields) === 0) {
	?>
			<div class="alert alert-warning">
				ntl: There is no meta data schema defined for this object, so no meta data can be shown
			</div>
	<?php
		}

		$wrongFields = $this->session->flashdata("incorrect_fields") ? $this->session->flashdata("incorrect_fields") : array();

		$formAttrs = array(
			"id" => "metadata_form",

		);

		$hidden = array(
			"directory" => $current_dir,
			"studyID" => $studyID,
			"intake_url" => site_url($url->module, "intake")
		);

		echo form_open($url->module . "/metadata/update", $formAttrs, $hidden);

		if($levelPermissions->canEditMeta && is_array($fields) && sizeof($fields) > 0) {
			echo $this->metadatafields->getEditButtons();
		}
	?>
		
			<table id="metadata_edittable" width="100%"
				class="display table table-datatable">
				<thead>
					<tr>
						<th width="100"><?=lang('metadata_name');?></th>
						<th><?=lang('metadata_value');?></th>
						<th></th>
					</tr>
				</thead>

				<tbody>
<?php 
			
		if(is_array($fields) && sizeof($fields) > 0) {
			foreach($fields as $key => $config){
				echo $this->metadatafields->getHtmlForRow(
					$key,
					$config,
					$config["value"],
					2,
					$levelPermissions->canViewMeta && $levelPermissions->canEditMeta,
					array_key_exists($key, $wrongFields) ? $wrongFields[$key] : array(), // in_array($key, $wrongFields),
					$this->session->flashdata('form_data')
				);
				echo "\n";
			}
		}
?>
				</tbody>
			</table>

	<?php

		if($levelPermissions->canEditMeta && is_array($fields) && sizeof($fields) > 0) {
			echo $this->metadatafields->getEditButtons();
		}

		echo form_close();

	?>
	</div>
	<?php
		foreach($previousLevelsMeta as $m) { 
	?>
		<div role="tabpanel" class="tab-pane" id="metadata-level-<?=$m->level["title"];?>">
			<?=$this->load->view("parent_level_meta_overview", array("metalevel" => $m));?>
		</div>
	<?php
		}
	?>
	</div>
</div>	
<?php
}

