<?php
/********************************************************************************
*  Copyright notice
*
*  (c) 2014 Christoph Taubmann (info@cms-kit.org)
*  All rights reserved
*
*  This script is part of cms-kit Framework. 
*  This is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License Version 3 as published by
*  the Free Software Foundation, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/licenses/gpl.html
*  A copy is found in the textfile GPL.txt and important notices to other licenses
*  can be found found in LICENSES.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
************************************************************************************/
require '../header.php';

require 'inc/collectExtensionInfos.php';

// namespaced Class-Names
require $projectPath . '__configuration.php';
$conf = $projectName.'\\Configuration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />

<title>cms-kit database modeling</title>

<link href="../../../vendor/cmskit/lib-jquery-ui/themes/<?php echo end($_SESSION[$projectName]['settings']['interface']['theme'])?>/jquery-ui.css" rel="stylesheet" />

<!--<link href="inc/css/chosen.css" rel="stylesheet" />-->
<script src="../../../vendor/cmskit/lib-jquery-ui/jquery.min.js"></script>

<script>$.uiBackCompat = false;</script>
<script src="../../../vendor/cmskit/lib-jquery-ui/jquery-ui.js"></script>
<script src="../../inc/js/rules/disallowedNames.js"></script>

<!--
<script src="inc/js/jsquery.ui.multidraggable.js"></script>
-->
<script>
if (!window.JSON)
{
	document.writeln('<script src="../../../vendor/cmskit/lib-jquery-ui/plugins/json3.min.js"><\/script>')
}
</script>

<link type="text/css" href="../../../vendor/cmskit/lib-jquery-ui/plugins/css/jquery.uix.multiselect.css" rel="stylesheet" />
<link type="text/css" href="inc/css/styles.css" rel="stylesheet" />

<script type="text/javascript" src="../../../vendor/cmskit/lib-jquery-ui/plugins/jquery.uix.multiselect.min.js"></script>
<script src="../../../vendor/cmskit/lib-jquery-ui/plugins/jquery.tmpl.js"></script>


<?php include 'inc/ui-templates.php'; ?>

</head>
<body>

<canvas id="bezier" width="6000" height="6000" style="position:absolute;top:0;left:0;"></canvas>

<span id="objects"></span>

<div id="dialog">
	<div id="dialogbody"></div>
</div>
<div id="dialog2">
	<iframe id="dialogbody2"></iframe>
</div>

<div id="menu" class="ui-widget-header ui-corner-all">
	<button title="<?php echo L('create_new_Object')?>" id="menu_new_object" data-icon="circle-plus" type="button">
		<?php echo L('new_Object')?>
	</button>
	<button title="<?php echo L('save_or_export_new_Model')?>" id="menu_export" data-icon="gear" type="button">
		<?php echo L('Export')?>
	</button>
	<button title="<?php echo L('toggle_Radar')?>" id="menu_radar" data-icon="lightbulb" type="button">
	&nbsp;
	</button>
	<button title="<?php echo L('open_Documentation')?>" id="menu_help" data-icon="help" type="button">
		<?php echo L('Help')?>
	</button>
	<button title="<?php echo L('open_new_Window')?>" id="menu_newwin" data-icon="newwin" type="button">
	&nbsp;
	</button>
</div>

<div id="radar"><div id="radarboxes"></div><div id="radarview"></div></div>

<script type="text/javascript">
/* <![CDATA[ */

var dtypeLabel=[],
	ddefaultLabel=[],
	datatypes = [],
	datatype = {},
	datatype_defaults = {},
	fieldtypecolor = [];
	fieldtypecolor["NUMERIC"]	= "#4682b4",
	fieldtypecolor["CHARACTER"]	= "#a0522d",
	fieldtypecolor["OTHER"]		= "#cdc9a5",
	dbhLabel = [];
	
<?php

// available Databases 
echo "var databases = ['" . implode("','", $conf::$DB_ALIAS) . "'];\n";

echo "var templates = ['" . implode("','", array_keys($_SESSION[$projectName]['config']['templates'])) . "'];\n";

echo "var project = '".$projectName."', wizards = [];\n";

// available Wizards (backend/inc/php/collectExtensionInfos.php)
$embeds = collectExtensionInfos($projectName);

echo  'var wizards = '.json_encode($embeds['wizards']).";\n";
echo  'var hooks = '.json_encode($embeds['hooks']).";\n";

// labels
echo  '
	dbhLabel["List"]  = "'.L('htype_List').'";
	dbhLabel["Tree"]  = "'.L('htype_Tree').'";
	dbhLabel["Graph"] = "'.L('htype_Graph').'";
';

$ddefaultLabel = array();
$datatypes = json_decode(file_get_contents('../../inc/js/rules/datatypes.json'), true);

foreach($datatypes as $k => $v)
{
	echo "dtypeLabel['$k'] = '".L($k)."';\n";
	echo "datatype['$k'] = fieldtypecolor['".$v['type']."'];\n";
	echo "datatypes.push(['$k', fieldtypecolor['".$v['type']."']]);\n";
	echo "datatype_defaults['$k'] = {};\n";
	
	foreach($v['default'] as $dk=>$dv)
	{
		echo "datatype_defaults['$k']['$dk'] = '$dv';\n";
		$ddefaultLabel[] = $dk;
	}
}
$ddefaultLabel = array_unique($ddefaultLabel);
foreach($ddefaultLabel as $dl)
{
	echo "ddefaultLabel['$dl'] = '".L($dl)."';\n";
}


?>

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

var saveContent = function(){}


// define global Variables
var canvas,
	ctx,
	objects = {},
	path = [],
	relations = [],
	dbcolors = ['transparent','#800080','#40e0d0','#a52a2a','#add8e6'],// colors for up to 5 Databases atm (first is transparent)
	relationcolors = ['#0c3', '#03c'],// colors for sibling, parent/child - Connections
	ttypes = [['List','#ccc'],['Tree','#FC9856'],['Graph','#56BAFC']];

// onload-Block
$(function()
{
	// show the "open in new window"-Button if we are in a Frame
	if (top != self){ $('#menu_newwin').show() }
	
	// let's create the radar-view
	$('#radarview')
	.css({'width':($(window).width()/10)+'px','height':($(window).height()/10)+'px'})
	.draggable({
		containment: 'parent',
		stop: function() {
			var pos = $(this).position();
			$('html, body').animate({scrollTop: pos.top*10, scrollLeft: pos.left*10}, 500);
		}
	});
	
	
	
	// define the canvas http://www.w3schools.com/tags/canvas_strokestyle.asp
	canvas = document.getElementById('bezier');
	ctx = canvas.getContext('2d');
	
	
	$.get('json_io.php?project=<?php echo $projectName?>', 
	function(data)
	{
		processObjects(data);
		//alert(data)
	});
	
	
	
	// Menu-Buttons
	$('#menu_new_object').on('click', function(){
		addObject();
	});
	
	$('#menu_export').on('click', function()
	{
		$('#dialogbody').html('');
		
		// check if Session is still alive
		var head = document.getElementsByTagName('head')[0];
		var lnk = document.createElement('script');
			lnk.type = 'text/javascript'; 
			lnk.src = 'inc/sessionCheck.php?project='+project;
		head.appendChild(lnk);
		
		$('#dialog_SaveButton').hide();
		//alert(JSON.stringify(objects))
		// fix if id is missing
		$.each(objects, function(index, item)
		{
			if (item[0] && item[0]['fields']['field'][0]["name"] != 'id')
			{
				var t={};
				t["name"] = "id";
				t["datatype"] = "INTEGER";
				item[0]['fields']['field'].unshift(t);
			}
		});
		
		// create a local Copy of objects
		var J = $.extend(true, {}, objects);
		
		// add Relations to the Object [FROM,TO,TYPE]
		$.each(relations, function(index, item)
		{
			// indexes item[0] == from , item[1] == to, item[2] == type(0/1)
			var i0 = path[item[0]][0],
				i1 = path[item[1]][0],
				si = ((item[2] == 1) ? path[item[0]][1][item[1]+'id'] : 0);//sub-index (child-parent OR sibling)
			
			if (J.object[i0])
			{
				if (!J.object[i0]['fields']['field'][si]['relation']) {
					J.object[i0]['fields']['field'][si]['relation'] = [];
				}
				
				J.object[i0]['fields']['field'][si]['relation'].push( {'object': item[1]} );
			}
		});
		
		$('#objectExportTemplate').tmpl({
			
				// transform object to json-string
				obj: JSON.stringify(J, true, '  ')
		}).appendTo('#dialogbody');
		
		// re-import STR from Textarea
		$('#button_importSTR').on('click', function()
		{
			objects = {}, path = [], relations = [];//reset JS-Objects
			clearLines();//clear BG-Vectors
			$('#objects div').each(function(){ $(this).remove() });//remove all Objects from Stage
			var o = JSON.parse($('#jsonToExport').val());
			if (o) {
				processObjects(o);
				$('#dialog').dialog('close');
			}else {
				alert('<?php echo L('could_not_process_JSON')?>!');
			}
		});
		
		$('#button_saveSTR, #button_exportSTR').on('click', function()
		{
			var action = $(this).data('action');
			
			$.post('json_io.php?project=<?php echo $projectName?>', 
			{
				json: $('#jsonToExport').val(),
				backup: ((action=='export') ? 1 : 0)
			},
			function(data)
			{
				if (data=='saved')
				{
					if (action == 'export')
					{
						var q0 = confirm('<?php echo L('first_open_Database_Management_to_create_a_full_Backup')?>?');
						if (q0)
						{
							window.open('../db_admin/index.php?project=<?php echo $projectName?>', 'DB-Admin');
						}
						else
						{
							var q1 = confirm('<?php echo L('open_Setup_and_write_Model_to_Database')?>!');
							if (q1)
							{
								$('#dialogbody').html('<iframe src="process.php?project=<?php echo $projectName?>"></iframe>');
							}
						}
					}else {
						alert('<?php echo L('saved_Model')?>');
					}
				} else {
					alert('<?php echo L('could_not_save')?>: '+data);
				}
			});
			
		});
		
		// sort internal Order of the objects
		$('#button_sortSTR').on('click', function()
		{
			$('#dialogbody').html('');
			$('#objectSortTemplate').tmpl({ obj: objects.object }).appendTo('#dialogbody');
			
			$('#objectSortUl').sortable({
				update: function(event, ui)
				{
					var order = $(this).sortable('toArray');
					var tmpn = [], tmpo = [];
					for (var i=0,j=order.length; i<j; ++i) { tmpn.push(order[i].split('s_o_r_t').pop()); }
					for (var i=0,j=tmpn.length;  i<j; ++i) { tmpo.push( objects.object[ path[tmpn[i]][0] ] ); }
					for (var i=0,j=tmpn.length;  i<j; ++i) { path[tmpn[i]][0] = i; }
					objects.object = tmpo;
				}
			});
			$('#dialog_SaveButton').hide();
			$('#dialog').dialog('open');
			$('#button_closeSort').on('click', function(){
				$('#menu_export').click();
			});
		});
		
		$('#dialog').dialog('open');
		
	});
	
	$('#menu_help').on('click', function(){
		$('#dialogbody').html('<iframe src="../package_manager/showDoc.php?file=../modeling/doc/<?php echo $lang?>/.object_modeling.md"></iframe>');
		$('#dialog_SaveButton').hide();
		$('#dialog').dialog('open');
	});
	
	$('#menu_newwin').on('click', function(){
		window.open(document.location, document.title)
	});
	
	$('#menu_radar').on('click', function(){
		$('#radar').toggle()
	});
	
	// Button-Styling
	$('#menu button').each(function() {
		$(this).button( {icons:{ primary: 'ui-icon-'+$(this).data('icon')}, text: (($(this).text()=='.')?false:true)})
	});
	
	// Menu END
	
	$('#dialog').dialog(
	{
		autoOpen: false,
		modal: true,
		width: 600,
		height: 650,
		close: function() {
			$('#dialogbody').html('');
			$('#dialog_SaveButton').show();
		},
		buttons: [
			{
				text: '<?php echo L('Save')?>',
				id: 'dialog_SaveButton',
				click: function() {
					var form = $('#dialogForm');
					var action = form.data('action');// get Function-Name
					window[action](form.data('objectname'), form.data('fieldname'), form.serializeArray());// call Function
					$(this).dialog( "close" );
					
				}
			},
			{
				text: '<?php echo L('Close')?>',
				click: function() {
					$(this).dialog( 'close' );
				}
			}
		]
	});
	
	
	
});// (document).ready END

function processObjects(obj)
{
	
	objects = obj;
	
	//alert(JSON.stringify(objects, '\t'));
	
	if (objects && objects.object)
	{
		// wrap in Array if there is only one Object
		if (!$.isArray(objects.object))
		{
			objects.object = [objects.object];
		}

		for (e in objects.object)
		{
			//alert(JSON.stringify(objects.object[e], '\t'));
			
			// ignore empty objects
			if (!objects.object[e]) continue;
			
			path[objects.object[e]['name']] = [e, []];
			
			// create the object
			addObject(objects.object[e]['name'], objects.object[e]['x'], objects.object[e]['y']);
			
			// create the Fields
			for (c in objects.object[e]['fields']['field'])
			{
				// ignore empty/illegal columns
				if (!objects.object[e]['fields']['field'][c]) {
					
					continue;
				}
				
				//we have (probably) only one field (the id) so we have to wrap the object
				if (!objects.object[e]['fields']['field'][c]['name'])
				{
					objects.object[e]['fields']['field'] = [ objects.object[e]['fields']['field'] ];
					c = 0;
				}
				
				
				path[ objects.object[e]['name'] ][1][ objects.object[e]['fields']['field'][c]['name']] = c;
				
				if (objects.object[e]['fields']['field'][c]['name'] == 'id')
				{
					
					// create sibling-relations
					for (r in objects.object[e]['fields']['field'][c]['relation'])
					{
						
						var t = objects.object[e]['fields']['field'][c]['relation'][r],
							t = ( t['object'] ? t['object'] : t );
						relations.push( [objects.object[e]['name'], t, 0] );
					}
				}
				else
				{
					addField(objects.object[e]['name'], objects.object[e]['fields']['field'][c]['name'], datatype[ objects.object[e]['fields']['field'][c]['datatype'] ], true);
					
					// create parent-child-relations (only check "parentid"-Fields)
					if (objects.object[e]['fields']['field'][c]['name'].slice(-2) == 'id')
					{
						for (r in objects.object[e]['fields']['field'][c]['relation'])
						{
							var t = objects.object[e]['fields']['field'][c]['relation'][r],
								t = ( t['object'] ? t['object'] : t );
							relations.push( [objects.object[e]['name'], t, 1] );
						}
					}
				}
				
				// remove Relations from Objects (adding lateron)
				if (objects.object[e]['fields']['field'][c]['relation']) {
					delete objects.object[e]['fields']['field'][c]['relation'];
				}
				
			}
		}
		
		drawLines();
		
	}
	else
	{
		// fallback when Project is empty
		objects = {};
		objects.object = [];
	}
	//alert(JSON.stringify(relations, '\t'));
	
	
};// processSTR END

// dummy-function
function foo(){};

function saveObjectProps(objectname, x, arr)
{
	var i0 = path[objectname][0];
	objects.object[i0]['templates']=[];
	for (var i=0,j=arr.length; i<j; ++i) {
		
		// change Color-Class of the Object
		if (arr[i].name=='db') { $('#'+objectname+'>p').css('border-left','4px solid '+dbcolors[arr[i].value]); }
		if (arr[i].name=='ttype') { $('#'+objectname).removeClass('List Tree Graph').addClass(arr[i].value); }
		
		switch(arr[i].name)
		{
			case 'templates':
				objects.object[i0][arr[i].name].push(arr[i].value);
			break;
			
			default:
				objects.object[i0][arr[i].name] = esc(arr[i].value);// xml2json kein esc() mehr??
			break;
		}
	};
	
	objects.object[i0]['templates'] = objects.object[i0]['templates'].join(',')
	
}

function saveFieldProps(objectname, fieldname, arr)
{
	var i0 = path[objectname][0], 
		i1 = path[objectname][1][fieldname];
	
	for (var i=0,j=arr.length;i<j;++i)
	{
		objects.object[i0]['fields']['field'][i1][arr[i].name] = esc(arr[i].value);
		// change ColorCode of the Field
		if (arr[i].name=='datatype') { $('#'+objectname+'-____-'+fieldname).css('border-left','3px solid '+datatype[arr[i].value]); }
	};
}



// addObject-Function
function addObject(objectname, x, y)
{
	// if object has to be created
	if (!objectname)
	{
		objectname = prompt('<?php echo L('enter_Object_Name')?>','');
		if (!objectname) return;
		
		//
		if ($.inArray(objectname, disallowedTableNames) != -1)
		{
			alert('<?php echo L('Object_Name_not_allowed')?>!');
			return;
		}
		
		objectname = objectname.replace(' ','_').replace(/[^\d\w]/g, '');//.toLowerCase();
		
		if (path[objectname])
		{
			alert('<?php echo L('Objectname_already_exists')?>!');
			return;
		}
		
		// add new Object to objects
		var l = objects.object.length;
		objects.object[l] = {};
		objects.object[l]['name'] = objectname;
		objects.object[l]['fields'] = {};
		objects.object[l]['fields']['field'] = [];
		objects.object[l]['fields']['field'][0] = {};
		objects.object[l]['fields']['field'][0]["name"] = "id" ;
		objects.object[l]['fields']['field'][0]["datatype"] = "INTEGER";
		
		path[objectname] = [l, []];
		path[objectname][1]['id'] = 0;
	}
	
	
	var index = path[objectname][0];
	
	// default position on creation
	if (!y) y = $(window).scrollTop() + 50;
	if (!x) x = $(window).scrollLeft() + 20;
	
	var dbi = (objects.object[index]['db']?objects.object[index]['db']:0);
	
	// create Object-HTML
	var html  = '<div id="'+objectname+'" class="object '+((objects.object[index]['ttype'])?objects.object[index]['ttype']:'');
		html += '" style="top:'+parseInt(y)+'px;left:'+parseInt(x)+'px;">';
		
		// Header
		html += '<p style="border-left:4px solid '+dbcolors[dbi]+';" class="ui-widget-header ui-corner-all">';
		html += '<label title="<?php echo L('delete_Object')?>" class="ui-icon ui-icon-trash"></label>';
		html += '<label title="<?php echo L('edit_Object_Properties')?>" class="ui-icon ui-icon-pencil"></label>';
		html += '<label title="<?php echo L('new_Field')?>" class="ui-icon ui-icon-circle-plus"></label>';
		html +=  objectname + '</p>';
		
		// UL-List-Body
		html += '<ul><li id="'+objectname+'-____-id" class="ui-state-default id_col">';
		html += '<label style="border-color:'+relationcolors[0]+'" title="<?php echo L('create_m:n_Relation')?>" class="ui-icon ui-icon-arrowthick-2-e-w"></label>';
		html += '<label style="border-color:'+relationcolors[1]+'" title="<?php echo L('create_1:n_Relation')?>" class="ui-icon ui-icon-arrowthick-1-ne"></label>';
		html += 'id</li></ul>';
		
		html += '</div>';
	
	$('#objects').append(html);
	
	
	
	// add a representation to the radar
	html = '<div class="radarobject" id="radarobject_'+objectname+'" title="'+objectname+'" style="top:'+(y/10)+'px;left:'+(x/10)+'px;width:20px;height:3px"></div>';
	$('#radarboxes').append(html);
	
	
	
	// start m:n Connecting
	$('#'+objectname+' .ui-icon-arrowthick-2-e-w:first').on('click', function()
	{
		var target = prompt('<?php echo L('enter_Name_of_Sibling_Object')?>','');
		if (target && path[target])
		{
			toggleConnection(objectname, target, 0);
		}
	});
	
	// start 1:n Connecting
	$('#'+objectname+' .ui-icon-arrowthick-1-ne:first').on('click', function()
	{
		var target = prompt('<?php echo L('enter_Name_of_Parent_Object')?>','');
		if (target && path[target])
		{
			toggleConnection(objectname, target, 1);
		}
	});
	
	// edit Object-Function
	$('#'+objectname+' p .ui-icon-pencil:first').on('click', function()
	{
		// prepare Template-Array
		var tpls = [], t = [];
		if (objects.object[index]['templates'])
		{
			t = objects.object[index]['templates'].split(',');
			for (var i=0,j=t.length; i<j; ++i)
			{
				tpls.push([t[i], true]);
			}
		}
		for (var i=0,j=templates.length; i<j; ++i)
		{
			if (t.indexOf(templates[i]) == -1){ tpls.push([templates[i], false]); }
		}
		// prepare Template-Array END
		
		//alert(JSON.stringify(objects.object[i]));
		$('#objectEditTemplate').tmpl({
			obj: objects.object[index],
			ttypes: ttypes,
			hooks: hooks,
			db : databases,
			templates: tpls,
			dbcolors: dbcolors
		}).appendTo('#dialogbody');
		
		//$('#templateSelect').chosen();
		$("#templateSelect").multiselect({sortable:true});
		
		$('#dialog').dialog('open');
		
		//
		$('#dbSelect').on('change', function() {
			alert('<?php echo L('Attention:_all_related_Objects_must_be_in_the_same_Database')?>!');
		});
		
		$('#hookSelect').on('change', function() {
			var v = $('#obj_hooks').val();
			$('#obj_hooks').val((v!=''?v+'\n':'')+$(this).val());
		});
	});
	
	// Add-Field-Function
	$('#'+objectname+' p .ui-icon-circle-plus:first').on('click', function()
	{
		var fieldname = prompt('<?php echo L('enter_Field_Name')?>','');
		if (fieldname)
		{
			fieldname = fieldname.replace(' ','_').replace(/[^\d\w]/g, '');//.toLowerCase();
			
			if ($.inArray(fieldname, disallowedFieldNames) != -1)
			{
				alert('<?php echo L('Field_Name_not_allowed')?>!');
				return;
			}
			
			if (path[objectname][1][fieldname])
			{
				alert('<?php echo L('Field_Name_already_exists')?>!');
				return;
			}
			
			var oi = path[objectname][0], //object-index
				fi = objects.object[oi]['fields']['field'].length; //field-index
			
			// add field to objects.object + path
			path[ objectname ][1][ fieldname ] = fi
			objects.object[oi]['fields']['field'][fi] = {};
			objects.object[oi]['fields']['field'][fi]["name"] = fieldname;
			objects.object[oi]['fields']['field'][fi]["datatype"] = 'INTEGER';
			
			// add field
			addField(objectname, fieldname, datatype['INTEGER']);
		}
	});
	
	// delete Object
	$('#'+objectname+' p .ui-icon-trash:first').on('click', function() {
		var q = confirm('<?php echo L('delete_%s')?>?'.replace('%s', objectname));
		if (q)
		{
			var tmp = [];
			$.each(relations, function(index, item)
			{
				// indexes item[0] == from , item[1] == to, item[2] == type(0/1)
				if (item && item[0]!=objectname && item[1]!=objectname) {
					tmp.push(item);
				}
			});
			relations = tmp;
			
			objects.object[path[objectname][0]] = null;
			$('#'+objectname).remove();
			clearLines();
			drawLines();
			//path[objectname] = false;
			
		}
	});
	
	// make Object draggable 
	$('#'+objectname).draggable(
	{
		//$('#'+objectname).multidraggable({
		
		handle: 'p',

		start: function(event, ui)
		{
			clearLines();
		},
		stop: function(event, ui)
		{
			//save new Position
			var i0 = path[objectname][0];
			objects.object[i0]['x'] = ui.position.left;
			objects.object[i0]['y'] = ui.position.top;
			
			// actualize the radar-representation
			$('#radarobject_'+objectname).css({'top':(ui.position.top/10)+'px','left':(ui.position.left/10)+'px'})
			
			// draw Bezier-Connectors
			drawLines();
		}
	});
	
	// make List-Elements sortable
	$('#'+objectname+'>ul').sortable(
	{
		items: 'li:not(.id_col)',
		handle: 'span',
		update: function(event, ui)
		{
			//serialize the List (returns IDs)
			var order = $(this).sortable('toArray');
				order.unshift(objectname+'-____-id');//add ID because its not within the sortable-array
				
			var index = path[objectname][0],
				tmpn = [],// field-names
				tmpo = [];// tmp-object
			
			// get the field-name
			for (var i=0,j=order.length; i<j; ++i) { tmpn.push(order[i].split('-____-').pop()); }
			
			// get the old index-numbers from the path and re-order the tmp-object
			for (var i=0,j=tmpn.length;  i<j; ++i) {
				
				//var ix = path[objectname][1][tmpn[i]] || 0;// if there is a new created object id is undefined
				//tmpo.push( objects.object[index]['fields']['field'][ ix ] ); 
				tmpo.push( objects.object[index]['fields']['field'][ path[objectname][1][tmpn[i]] ] );
			}
			// re-order the path itself
			for (var i=0,j=tmpn.length;  i<j; ++i) { path[objectname][1][tmpn[i]] = i; }
			
			// assign tmp-object to the official object
			objects.object[index]['fields']['field'] = tmpo;
		}
	});
	
};// addObject END

//php.js
function unesc(str)
{
	return decodeURIComponent((str + '').replace(/\+/g, '%20'));
}

//php.js
function esc(str)
{
	str = (str + '').toString();
	// Tilde should be allowed unescaped in future versions of PHP, but if you want to reflect current
	// PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
	replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}

function editConfig()
{
	window.targetFieldId = 'config_area';
	$('#dialogbody2').attr('src','../../wizards/jsoneditor/index.php?project='+project)
	$('#dialog2').dialog({
		width: 600,
		height: 650,
		close: function() {
			$('#dialogbody2').attr('src','about:blank');
		}
	});
}


function buildFollowers(v, my_type)
{
	var w  = wizards[v],
		df = datatype_defaults[v],
		tn = datatype[v],
		to = datatype[my_type];//
	
	if (to != tn) {
		alert('<?php echo L('Attention:_changing_general_Datatype_can_corrupt_Data')?>');
	}
	var html = '';
	if (w)
	{
		html += '<select id="wizardEmbedSelect"><option value=""><?php echo L('Wizard')?></option>';
		for (e in w) {
            var l = w[e]['embed'].split('\\n').shift();
            html += '<option title="'+w[e]['description']+'" value="'+w[e]['embed']+'">'+l+'</option>';
        }
		html += '</select>';
	}

	$('#wizardSelect').html(html);


    $('#wizardEmbedSelect').on('change',function() {
		$('#field_add').val($(this).val().replace('\\n',"\n").replace('#',"\n"));
	});
	
	var html = '';
	if (df)
	{
		html += '<select onchange="$(\'#field_default\').val(this.value)"><option value=""><?php echo L('Default_Value')?></option>';
		for (e in df) html += '<option value="'+df[e]+'">'+e+'</option>';
		html += '</select>';
		html += '<input type="text" style="margin-left:122px" value="" name="default" id="field_default" />';
		$('#defaultSelect').html(html);
	}
}

// add a Field
function addField (objectname, fieldname, col, norefresh)
{
	if (!fieldname) return;
	
	var ul = $('#'+objectname+'>ul');
	
	// create Column-HTML
	var html  = '<li id="'+objectname+'-____-'+fieldname+'" style="border-left:3px solid '+col+'" class="ui-state-default">';
	if (fieldname.slice(-2) != 'id') {}
		html += '<label title="<?php echo L('delete_Field')?>" class="ui-icon ui-icon-trash"></label>';
	
		html += '<label title="<?php echo L('edit_Field_properties')?>" class="ui-icon ui-icon-pencil"></label>';
		
		html += '<span title="<?php echo L('drag_to_Sort')?>" class="ui-icon ui-icon-arrowthick-2-n-s"></span>';
		html += '' + fieldname.substr(0,15) + '</li>';
	
	ul.append(html);
	
	if (!norefresh) ul.sortable('refresh');
	
	// edit Field-Function
	$('#'+objectname+'-____-'+fieldname+' .ui-icon-pencil:first').on('click', function()
	{
		var i0 = path[objectname][0], 
			i1 = path[objectname][1][fieldname];
		
		var my_type = objects.object[i0]['fields']['field'][i1]['datatype'];
		//alert(JSON.stringify(datatype_defaults[my_type]));
		
		//
		$('#fieldEditTemplate').tmpl({
			obj: objectname,
			field: objects.object[i0]['fields']['field'][i1],
			wizards: wizards[my_type],
			defaults: datatype_defaults[my_type],
			defaultLabel: ddefaultLabel,
			types: datatypes
		}).appendTo('#dialogbody');
		
		buildFollowers(my_type, my_type);
		
		$('#dialog').dialog('open');
		
		// if the User changes the Data-Type
		$('#field_datatype').on('change', function()
		{
			var v  = $(this).val();
			buildFollowers(v, my_type);
			
		});
		
	});
	

	// delete Field-Function
	$('#'+objectname+'-____-'+fieldname+' .ui-icon-trash:first').on('click', function()
	{
		var c = confirm('delete '+fieldname+'?');
		if (c) {
			removeField(objectname,fieldname);
		}
	});
	var ro = $('#radarobject_'+objectname);
	ro.height(ro.height()+4)
	
};// addField END

function removeField (objectname,fieldname)
{
	$('#'+objectname+'-____-'+fieldname).remove();
	var i0 = path[objectname][0], i1 = path[objectname][1][fieldname];
	objects.object[i0]['fields']['field'][i1] = null;// "remove" Element from Object-array
	path[ objectname ][1][ fieldname ] = false;// "remove" Element from path
};

function toggleConnection(from, to, type)
{
	var match = false;
	if (from == to) 
	{
		alert('<?php echo L('Self_Reference_is_not_allowed')?>!');
		return;
	}
	$.each(relations, function(index, item)
	{
		if ((item[0] == from && item[1] == to) || (item[1] == from && item[0] == to))
		{ 
			match = index;
		}
	});
	
	if (match !== false)
	{
		var o = confirm('<?php echo L('Object_are_connected._Delete_Connection')?>?');
		if (o)
		{
			relations.splice(match, 1);
			removeField(from, to+'id');// remove parentid-Field if exists
			clearLines();
			drawLines();
		}
	}
	else
	{
		// add the Relation
		relations.push([from, to, type]);
		
		// if it's a child-parent-Relation
		if (type == 1)
		{
			var i0 = path[from][0], 
				o = objects.object[i0]['fields']['field'], 
				ol = o.length;
				o[ol] = {};
				o[ol]['name'] = to+'id';
				// get the tree-type of the target
				var ttp = objects.object[path[to][0]]['ttype'] || 'List';
				o[ol]['datatype'] = (ttp=='List') ? 'INTEGER' : 'VARCHAR';
			
			path[from][1][to+'id'] = ol;
			
			//alert(objects.object[i0]['fields']['field'][ol]['datatype']);
			
			addField(from, to + 'id', datatype['INTEGER']);
			$('#'+from+'-____-'+to+'id .ui-icon-trash:first').remove();
			$('#'+from+'-____-'+to+'id .ui-icon-pencil:first').hide();
		}
		addRelation(from, to, type);
	}
	
	//alert(JSON.stringify(relations))
};

// add a relation between 2 Objects
function addRelation(from, to, type)
{
	var ff = $( '#'+from+'-____-'+ (type==0 ? '' : to) +'id' ).offset(),
		ft = $( '#'+to+'-____-id' ).offset();
	
	if (ff && ft)
	{
		var x1 = ff.left,
			y1 = ff.top + 15,
			x2 = ft.left,
			y2 = ft.top + 15;
		var bw = 195;
		
		if (x1 > x2) x2 += bw;
		if (x2 > x1) x1 += bw;
		
		ctx.beginPath();
		
		// moveTo(startX, startY)
		ctx.moveTo(x1, y1);
		// bezierCurveTo(control_1_X, control_1_Y, control_2_X, control_2_Y, endX, endY)
		ctx.bezierCurveTo( x2,y1, x1,y2, x2,y2 );
		
		ctx.lineWidth = 3;
		ctx.strokeStyle = relationcolors[type];
		ctx.scale(1, 1);
		ctx.stroke();
	
	}
	else
	{
		alert('<?php echo L('could_not_find_Objects_for')?> '+from+'<->'+to+'!')
	}
	
};// addRelation END

// (re)draw all Connectors
function drawLines()
{
	
	for ( var i=0,j=relations.length; i<j; ++i )
	{
		addRelation(relations[i][0], relations[i][1], relations[i][2]);
	}
};

// clear the canvas
function clearLines()
{
	
	ctx.save();
	ctx.setTransform(1, 0, 0, 1, 0, 0);
	ctx.clearRect(0, 0, canvas.width, canvas.height);
	ctx.restore();
};

$( window ).scroll(function()
{
	$('#radarview').css(
	{
		'top':($(window).scrollTop()/10)+'px',
		'left':($(window).scrollLeft()/10)+'px'
	})
});


/* ]]> */
</script>
</body>
</html>
