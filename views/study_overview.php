<?php if(sizeof($studies) > 0) { ?>
<input type="hidden" name="levelglyph" value="<?=$glyph;?>"/>
<table id="studies_overview" width="100%" class="display table table-datatable"
data-tablelanguage="<?=htmlentities($studiesTableLanguage);?>"
>
	<thead>
		<tr>
			<th><?=ucfirst(lang('intake_name'));?></th>
			<th><?=ucfirst(lang('intake_size'));?></th>
			<th><?=ucfirst(lang('intake_files'));?></th>
			<th><?=ucfirst(lang('intake_created'));?></th>
			<th><?=ucfirst(lang('intake_modified'));?></th>
		</tr>
	</thead>

</table>
<?php } else {
	echo sprintf('<div class="alert alert-danger">%1$s</div>', lang('intake_error_no_studies'));
}
