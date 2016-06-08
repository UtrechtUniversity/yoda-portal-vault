<?php if($userIsAllowed && $permissions->manager && $studyFolder != ''): ?>
	<div class="row page-header">
	    <div class="col-sm-6">
	      <h3>
	        <span class="glyphicon glyphicon-tags"></span>
	        <?=lang('header:metadata');?>
	      </h3>
	    </div>
	</div>
<?php
$attrs = array(
		"studyRoot" => $intakePath,
		"studyID" => $studyID,
		"dataset" => ($studyFolder ? $studyFolder : false)
	);
	echo form_open($url->module . "/metadata/update", null, $attrs);
?>
	<!-- <table id="files_overview" class="display table table-datatable">
	<thead>
		<tr>
			<th width="100"><?=lang('metadata_name');?></th>
			<th><?=lang('metadata_value');?></th>
			<th></th>
		</tr>
	</thead>
	
	<tbody>
		<tr>
			<td>Owner</td>
			<td>
				<?php $owner = $this->metadatamodel->getOwner($rodsaccount, $currentDir); ?>
				<span class="hideWhenEdit"><?=htmlentities($owner);?></span> -->
				<!-- <input type="text" class="showWhenEdit" name="owner" value="<?=htmlentities($owner);?>" style="display: none;"/> -->
				<!-- <select class="showWhenEdit select-user-from-group" name="owner" style="display:none">
					<option value="<?=htmlentities($owner);?>" selected="selected"><?=htmlentities($owner);?></option>
				</select> -->
				<!-- <input name="metadata[dataset_owner]" id="meta-input-owner" type="hidden" 
					class="showWhenEdit select-user-from-group" 
					value="<?=htmlentities($owner);?>" style="display: none"
					data-placeholder--id="<?=htmlentities($owner);?>"
					data-placeholder--text="<?=htmlentities($owner);?>"
					data-defaultvalue="<?=htmlentities($owner);?>"
					/>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="editOwner()">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" onclick="cancelEdit()" style="display: none;">
				</span>
			</td>
		</tr>

		<tr>
			<td>Subject</td>
			<td>
				<input name="metadata[subject]" type="text"/>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="edit('subject')">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" 
					onclick="cancelEdit('subject')" style="display: none;">
				</span>
			</td>
		</tr>

		<tr>
			<td>aggragate level</td>
			<td>
				<input name="metadata[aggragatelevel]" type="text"/>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="edit('aggragatelevel')">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" 
					onclick="cancelEdit('aggragatelevel')" style="display: none;">
				</span>
			</td>
		</tr>

		<tr>
			<td>Location</td>
			<td>
				<input name="metadata[location]" type="text"/>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="edit('location')">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" 
					onclick="cancelEdit('location')" style="display: none;">
				</span>
			</td>
		</tr>

		<tr>
			<td>Time</td>
			<td>
				<input name="metadata[time]" type="text"/>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="edit('time')">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" 
					onclick="cancelEdit('time')" style="display: none;">
				</span>
			</td>
		</tr>

		<tr>
			<td>Method</td>
			<td>
				<textarea name="metadata[method]"></textarea>
			</td>
			<td width="50">
				<span type="button" class="btn btn-default glyphicon glyphicon-pencil hideWhenEdit" onclick="edit('method')">
				</span>
				<span type="button" class="btn btn-default glyphicon glyphicon-remove showWhenEdit" 
					onclick="cancelEdit('method')" style="display: none;">
				</span>
			</td>
		</tr>

	</tbody>
	</table> -->

	<table id="metadata_edittable" 
		class="display table table-datatable">
		<thead>
			<tr>
				<th width="100"><?=lang('metadata_name');?></th>
				<th><?=lang('metadata_value');?></th>
				<th></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($this->metadatafields->fields as $key => $config) {
				echo $this->metadatafields->getHtmlForRow(
					$key,
					$config,
					"test value",
					2
				);
				echo "\n";
			}
			?>
		</tbody>
	</table>

	<div class="container-fluid metadata_form_buttons">
		<div class="row">
			<button class="btn btn-default showWhenEdit col-md-4"
				disabled="disabled" 
				id="editMetaSubmit" type="submit">
				<span class="glyphicon glyphicon-save"></span>
				Submit
			</button>
			<button type="button" class="btn btn-default hideWhenEdit col-md-4" 
				id="editAll" action="" onclick="enableAllForEdit()">
				<span class="glyphicon glyphicon-pencil"></span>
				Edit all
			</button>
			<button type="button" 
				class="btn btn-default showWhenEdit col-md-4"
				disabled="disabled"
				id="cancelAll" action="" onclick="disableAllForEdit()">
				<span class="glyphicon glyphicon-remove"></span>
				Cancel edit
			</button>
		</div>
	</div>
	

<?php 
	echo form_close();
endif; ?>
