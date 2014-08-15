

<!--
	Template for 
-->
<script id="objectExportTemplate" type="text/x-jquery-tmpl">
	<h2><?php echo L('Export')?></h2>
	<div class="ui-widget-header ui-corner-all">
		<button title="<?php echo L('export_Model')?>" style="float:right" data-action="export" id="button_exportSTR" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" type="button" role="button" aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-gear"></span>
			<span class="ui-button-text"><?php echo L('export_Model')?></span>
		</button>
		<button title="<?php echo L('save_Model')?>" style="float:right" data-action="n" id="button_saveSTR" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" type="button" role="button" aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-disk"></span>
			<span class="ui-button-text"><?php echo L('save_Model')?></span>
		</button>
		<button title="<?php echo L('rebuild_from_Input')?>" id="button_importSTR" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" type="button" role="button" aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-arrowreturnthick-1-w"></span>
			<span class="ui-button-text"><?php echo L('rebuild_from_Input')?></span>
		</button>
		<button title="<?php echo L('sort_Objects_internally')?>" id="button_sortSTR" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" type="button" role="button" aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-shuffle"></span>
			<span class="ui-button-text"><?php echo L('sort_Objects_internally')?></span>
		</button>
		
	</div>
	
<form id="dialogForm" data-action="foo" data-objectname="" data-fieldname="">
<textarea id="jsonToExport" style="width:550px;height:450px;">
${obj}
</textarea>
</form>

</script>

<!--
	Template for 
-->
<script id="objectEditTemplate" type="text/x-jquery-tmpl">
<h2><?php echo str_replace('%s', '${obj["name"]}', L('edit_Object_%s'))?></h2>
<form id="dialogForm" data-action="saveObjectProps" data-objectname="${obj['name']}" data-fieldname="">
	{{if db}}
	<p>
		<label><?php echo L('Database')?>:</label>
		<select name="db" id="dbSelect">
		{{each db}}
			<option style="border-left:3px solid ${dbcolors[$index]}" {{if obj['db'] && obj['db']==$index}} selected="selected"{{/if}} value="${$index}">${$value}</option>
		{{/each}}
		</select>
	</p>
	{{/if}}
	<p>
		<label><?php echo L('View')?>:</label>
		<textarea name="view" placeholder="<?php echo L('Enter_select_statement')?>">{{if obj['view']}}${unesc(obj['view'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Increment')?>:</label>
		<select name="increment" onchange="alert('<?php echo L('changing_Increment_needs_probably_adaption_of_existing_DB_Schemes')?>')">
			<option value="0"><?php echo L('Auto_Increment')?></option>
			<option {{if obj.increment==1}} selected="selected" {{/if}}value="1"><?php echo L('Timestamp')?></option>
		</select>
	</p>
	<p>
		<label style="float:left"><?php echo L('Templates')?>:</label>
		<select style="height:200px;float:right" name="templates" id="templateSelect" multiple="multiple"  data-placeholder="<?php echo L('add_Templates_to_Object')?>">
			{{each templates}}
			<option value="${$value[0]}" {{if $value[1]}} selected="selected"{{/if}}>${$value[0]}</option>
			{{/each}}
		</select>
	</p>
	<p style="clear:both">
		<label><?php echo L('Language_Labels')?>:</label>
		<textarea name="lang">{{if obj['lang']}}${unesc(obj['lang'])}{{/if}}</textarea>
	</p>
	<p>
		<label></label>
		<select id="hookSelect"><option value=""><?php echo L('select_Hook')?></option>
		{{each hooks}}
			<option title="${$value.description}" value="${$value.embed}">${$value.embed}</option>
		{{/each}}
		</select>
		<label><?php echo L('Hooks')?>:</label>
		<textarea name="hooks" id="obj_hooks">{{if obj.hooks}}${unesc(obj['hooks'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Wizard_URLs')?>:</label>
		<textarea name="url">{{if obj['url']}}${unesc(obj['url'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Preview_URLs')?>:</label>
		<textarea name="vurl">{{if obj['vurl']}}${unesc(obj['vurl'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Hierarcy')?>:</label>
		<select name="ttype">
			{{each ttypes}}
				<option {{if obj['ttype'] && obj['ttype']==$value[0]}} selected="selected"{{/if}} value="${$value[0]}" style="background:${$value[1]}">${dbhLabel[$value[0]]}</option>
			{{/each}}
		</select>
		<input type="hidden" name="hidettype" id="obj_hidettype" value="${obj['hidettype']}" />
		<input type="checkbox" {{if obj['hidettype'] && obj['hidettype']==='true' && obj['ttype']!='List'}}checked="checked"{{/if}} onchange="$('#obj_hidettype').val(this.checked)" title="<?php echo L('hide_Hiearchy_in_Backend')?>" />
	</p>
	<p>
		<label><?php echo L('Tags')?>:</label>
		<textarea name="tags">{{if obj['tags']}}${unesc(obj['tags'])}{{/if}}</textarea>
	</p>
	<p>
		<label><button onclick="editConfig()" type="button"><?php echo L('Configuration')?></button>:</label>
		<textarea name="config" id="config_area">{{if obj['config']}}${unesc(obj['config'])}{{/if}}</textarea>
		
	</p>
	<p>
		<label><?php echo L('Comment')?>:</label>
		<textarea name="comment">{{if obj['comment']}}${unesc(obj['comment'])}{{/if}}</textarea>
	</p>
</form>
</script>

<!--
	Template for sorting of Objects (Export-Dialog)
-->
<script id="objectSortTemplate" type="text/x-jquery-tmpl">

<button  style="float:right" id="button_closeSort" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" type="button" role="button" aria-disabled="false">
	<span class="ui-button-icon-primary ui-icon ui-icon-close"></span>
	<span class="ui-button-text">close</span>
</button>
<h2><?php echo L('sort_Objects')?></h2>

<form id="dialogForm" data-action="foo" data-objectname="" data-fieldname="">
<ul class="ui-state-default" id="objectSortUl">
	{{each obj}}
		{{if $value}}
			<li class="ui-state-default" id="s_o_r_t${$value['name']}"><span title="<?php echo L('drag_to_Sort')?>" class="ui-icon ui-icon-arrowthick-2-n-s"></span>${$value['name']}</li>
		{{/if}}
	{{/each}}
</ul>
</form>
</script>

<!--
	Template for 
-->
<script id="fieldEditTemplate" type="text/x-jquery-tmpl">

<h2><?php echo str_replace('%s', '<em>${field["name"]}</em>', L('edit_Field_%s'))?></h2>

<form id="dialogForm" data-action="saveFieldProps" data-objectname="${obj}" data-fieldname="${field['name']}">
	<p>
		<label><?php echo L('Language_Labels')?>:</label>
		<textarea name="lang" id="field_lang">{{if field['lang']}}${unesc(field['lang'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Datatype')?>:</label>
		<select name="datatype" id="field_datatype">
			{{each types}}
				<option {{if field['datatype']==$value[0]}}selected="selected"{{/if}} value="${$value[0]}" style="border-left:3px solid ${$value[1]}">${dtypeLabel[$value[0]]} ( ${$value[0]} )</option>
			{{/each}}
		</select>
	</p>
	<p>
		<label><?php echo L('Filter')?>:</label>
		<input type="text" value="{{if field['filter']}}${unesc(field['filter'])}{{/if}}" name="filter" id="field_filter" />
	</p>
	<p>
		<label></label>
		<select id="defaultDefaults" onchange="$('#field_default').val(this.value)"><option value=""><?php echo L('Default_Value')?></option>
			{{each defaults}}
				<option {{if unesc(field['default'])==$value}}selected="selected"{{/if}} value="${$value}">${defaultLabel[$index]}</option>
			{{/each}}
		</select>
	</p>
	<p>
		<label><?php echo L('Default_Value')?>:</label>	
		<textarea name="default" id="field_default">{{if field['default']}}${unesc(field['default'])}{{/if}}</textarea>
	</p>
	<p>
		<label></label>
		<span id="wizardSelect"></span>
	</p>
	<p>
		<label><?php echo L('Addition')?>:</label>
		<textarea name="add" id="field_add">{{if field['add']}}${unesc(field['add'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Tags')?>:</label>
		<textarea name="tags" id="field_tags">{{if field['tags']}}${unesc(field['tags'])}{{/if}}</textarea>
	</p>
	<p>
		<label><?php echo L('Comment')?>:</label>
		<textarea name="comment" id="field_comment">{{if field['comment']}}${unesc(field['comment'])}{{/if}}</textarea>
	</p>
</form>
</script>

