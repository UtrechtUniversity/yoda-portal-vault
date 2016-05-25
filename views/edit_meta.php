<?php if($userIsAllowed && $permissions->manager && $studyFolder != ''): ?>
	<div class="row page-header">
	    <div class="col-sm-6">
	      <h3>
	        <span class="glyphicon glyphicon-tags"></span>
	        <?=lang('HDR_METADATA');?>
	      </h3>
	    </div>
	</div>
<?php
$attrs = array(
		"studyRoot" => $intakePath,
		"studyID" => $studyID,
		"dataset" => ($studyFolder ? $studyFolder : false)
	);
	echo form_open("/intake-ilab/actions/updateMetadata", null, $attrs);
?>
	<table id="files_overview" class="display table table-datatable">
	<thead>
		<tr>
			<th width="100">Name</th>
			<th>Value</th>
			<th></th>
		</tr>
	</thead>
	
	<tbody>
		<tr>
			<td>Owner</td>
			<td>
				<?php $owner = $this->metadata->getOwner($rodsaccount, $currentDir); ?>
				<span class="hideWhenEdit"><?=htmlentities($owner);?></span>
				<input type="text" class="showWhenEdit" name="owner" value="<?=htmlentities($owner);?>" style="display: none;"/>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="editOwner()">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" onclick="cancelEdit()" style="display: none;">
				</span>
			</td>
		</tr>
	</tbody>
	</table>

	<button class="btn btn-default" type="submit">
		<span class="glyphicon glyphicon-save"/>
		Submit
	</button>


<?php 
	echo form_close();
endif; ?>
