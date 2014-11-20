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

*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
************************************************************************************/

// PREPARATION 
require '../header.php';
$projectPath = $frontend . '/objects/';
$relpath = '../../../projects/' . $projectName;

// VARIABLES

$queries 		= array(); // array of DB-Queries
$tables 		= array(); // array to hold existing Column-Names
$reduced_tables = array(); // array to hold Tables with dropped columns for sqlite


// additional settings piped to the ORM generator
$dsettings = array
					(
						// get, default_on, title
						array('debug', 'debug_mode', ''),// creates some debug outputs
						array('nofilter', 'no_filter', ''),// disables variable filtering
						array('psr2', 'convert_php_code_to_PSR2', ''),// see http://www.php-fig.org/psr/psr-2
						array('icomments', 'write_comments_into_the_code', ''),// writes comments from the model as inline comments into the code
					);


$conf 	= $projectName . '\\Configuration';
$db 	= $projectName . '\\DB';

foreach(	array(
					'inc/process_label.php',
					'inc/process_includes.php',// load Helper-Functions
					'inc/objecttemplate.php',// load Template-Class
					
					'../../../vendor/pclzip/pclzip/pclzip.lib.php',// load ZIP-Library
					'../../inc/php/functions.php',// get Version-No
					
					$projectPath . '__model.php',// load old Model => (object)$objects
					$projectPath . '__draft.php',// load draft Model => (string)$model
					$projectPath . '__database.php',// load Database-Connector
					
				) as $inc)
{
	if (file_exists($inc))
	{
		require $inc;
	}
	else
	{
		exit( $inc . ' does not exist!' );
	}
}


if(!is_writable($projectPath)) exit('Folder "objects" is not writable!');


// OBJECTS
$datatypes 		= json_decode(file_get_contents('../../inc/js/rules/datatypes.json'), true);// load Datatypes

$dbModel 		= getTableStructure(); //
$dbViews 		= getViewDefinitions(); //

$jsonModel		= $objects;
$draftModel		= json_decode($model, true);
$newModel 		= array();

//print_r($draftModel);
//exit;

$oldKeys 		= array_keys($dbModel);
$newKeys 		= array();
$relations 		= array();
$objects_to_rebuild = array();
$objects_to_delete = array();

// define ZIP-Object
// name containing timestamp + obfuscated string (to prevent direct downloading by guessing Filename)
$zipName = time() . '_' . md5(rand()) . '.zip';
$zipPath = $projectPath . 'backup/' . $zipName;
$archive = new PclZip ( $zipPath );

// Query-Array for HTML-Output
$queryHtmlOutput 	= array();
$fileHtmlOutput 	= array();
$errorHtmlOutput	= array();

// fix it, if we have only one Object
if ( !isset($draftModel['object'][0]['name']) )
{
	//$draftModel['object'] = array($draftModel['object']);
}


/**
* 
* 
* 
*/
foreach ($draftModel['object'] as $object)
{
	if(isset($object))
	{
		// temporary Object-Array
		$tmp = array();
		
		// get Object-Name
		$name = strtolower($object['name']);
		
		// abort if invalid objectname!
		if(!preg_match("#^[\w]+$#", $name))
		{ 
			exit('Object-Name: "'.$name.'" is not valid!');
		}
		
		
		// prepare & convert Nodes (key, simple, deep, default)
		$nodes = array(	
						array('lang',		false, false, ''),
						array('tags',		false, true, ''),
						array('hooks',		false, true, ''),
						array('url',		false, false, ''),
						array('vurl',		false, false, ''),
						array('view',	    true,  false, null),
						array('templates',	true,  false, ''),
						array('ttype',		true,  false, 'List'),
						array('hidettype',	true,  false, ''),
						array('config',		true,  false, '{}'),
						array('comment',	true,  false, ''),
					  );
		foreach($nodes as $a)
		{
			if( isset($object[$a[0]]) && $b = text2array($object[$a[0]], $a[1], $a[2]) )
			{
                if(!empty($b)) $tmp[$a[0]] = $b;
			}
			else // set default-value
			{
				$tmp[$a[0]] = $a[3];
			}
		}
		
		$tmp['config'] = json_decode($tmp['config']);
		
		// test the Tables 
		
		@$tmp['db']  = intval($object['db']);// define Database-Index
		@$tmp['inc'] = intval($object['increment']);// define Database-Increment (0/1)
		
		if(!isset($queries[$tmp['db']])) $queries[$tmp['db']] = array();
		
		// test & process Fields
		$tmp['col'] = processObject ($name, $object, $tmp);
		
		// test Hierarchy
		checkHierarchy ($queries, $name, $tmp['db'], $tmp['inc'], $tmp['ttype'], $tmp);


        // if a view-statement is detected
        if(!empty($tmp['view'])) {
            // clear the array with table query-statements
            $queries[$tmp['db']][$name] = array();

            // if the view was created as a real table before, drop the table
            if(isset($dbModel[$tmp['db']][$name])) {
                $queries[$tmp['db']][$name][] = 'DROP TABLE IF EXISTS `'.$name.'`;';
            }

            // if the view-statement is new or is different from the stored one
            $createViewStm = 'CREATE VIEW `'.$name.'` AS ';

            // SqLite gives us the "create view..." as view-statement - clear it
            $dbViews[$tmp['db']][$name] = str_replace($createViewStm,'',$dbViews[$tmp['db']][$name]);

            if(!isset($dbViews[$tmp['db']][$name]) ||  strtolower($dbViews[$tmp['db']][$name]) != strtolower($tmp['view'])) {

                if(!empty($dbViews[$tmp['db']][$name])) {
                    $errorHtmlOutput[] = 'SELECT statement in "'.$name.'" was (probably translated to):<pre>'
                        .$dbViews[$tmp['db']][$name]
                        .'</pre>';
                }
                // don't create a normal table so drop the old view
                $queries[$tmp['db']][$name][] = 'DROP VIEW IF EXISTS `'.$name.'`;';
                // create the view
                $queries[$tmp['db']][$name][] = $createViewStm.$tmp['view'].';';
            }



        } // if view END
		
		// assign temporary Object to the new Model
		$newModel[$name] = $tmp;
	}
}// foreach $draftModel END


//add Relations to newModel [type, name1, name2]
foreach($relations as $r)
{
	$types = ($r[0]=='p') ? array('p','c') : array('s','s');
	$newModel[$r[1]]['rel'][$r[2]] = $types[0];
	$newModel[$r[2]]['rel'][$r[1]] = $types[1];
	
}// foreach relations END




/**
* 
* 
* 
*/
if (is_array($jsonModel))
{
	foreach ($jsonModel as $old_name => $old_object)
	{
		// delete the whole Object/Table
		if (!isset($newModel[$old_name]) && is_array($old_object['rel']))
		{
			// delete relations
			foreach($old_object['rel'] as $k=>$v)
			{
				deleteTable ($queries, mapName($k, $old_name), $old_object['db']);
			}
			// delete the old object itself
			deleteTable ($queries, $old_name, $old_object['db']);
		}
		
		// check for Columns to delete
		else
		{
			$columnsToDelete = array();
			if (is_array($old_object['col']))
			{
				$columnsToDelete = array_diff (
												array_keys($old_object['col']),
												array_keys($newModel[$old_name]['col'])
											  );
			}
			foreach ($columnsToDelete as $d)
			{
				deleteColumn ($queries, $old_name, $d, $old_object['db']);
			}
		}
		
	}// foreach old Model END
}


// create Database-Backup
if (!isset($_SESSION[$projectName]['config']['modeling']['no_backup']))
{
	// put old model (backup-file) to zip 
	require $projectPath . '__draft.bac.php';
	$z = $archive->create( array(
									array(
										PCLZIP_ATT_FILE_NAME 	=> 'model.json',
										PCLZIP_ATT_FILE_CONTENT => $model
									)
								)
						);
	//@unlink($projectPath . '__draft.bac.php');
	
	// loop the databases
	for ($i=0; $i<count($conf::$DB_TYPE); $i++)
	{
		
		if ($conf::$DB_TYPE[$i] == 'sqlite')
		{
			$z = $archive->add( $projectPath.$conf::$DB_DATABASE[$i], PCLZIP_OPT_REMOVE_PATH, $projectPath );
		}
		
		if ($conf::$DB_TYPE[$i] == 'mysql')
		{
			
			$sql_filename = $conf::$DB_DATABASE[$i] . '.sql';
			$sql_string = dumpMySqlTables($i, $queries, isset($_SESSION[$projectName]['config']['modeling']['full_backup']));
			
			file_put_contents($projectPath.$sql_filename, $sql_string);
			$z = $archive->add( $projectPath.$sql_filename, PCLZIP_OPT_REMOVE_PATH, $projectPath );
			@unlink($projectPath.$sql_filename);
			
		}
		
	}
}



//print_r($queries);
foreach ($queries as $i => $db_queries)
{
	$obj = new $db();

    foreach ($db_queries as $name => $arr)
	{
		// detect structural changes within the Object
		if (count($arr) > 0)
		{
			// process Queries
			foreach ($arr as $q)
			{
				$err = '';
				
				// (try to) save changes to the Database
				try
				{
					$obj->instance( $i )->query( $q );
				}
				catch(Exception $e)
				{
					$err = ' <span style="color:red">(' . $e->getMessage() . ')</span> ';
				}
				
				// record Query for HTML-Output (with simple SQL-Syntax-Highlighting)
				$queryHtmlOutput[] = '<div>' . preg_replace_callback('/[A-Z]{2,}/', create_function('$matches','return "<strong>".$matches[0]."</strong>";'), $q) . $err . '</div>';
				
			}
		}
		
		if (	
				!in_array($name, $objects_to_delete) && 
				
				(	count($arr) > 0 || 
					in_array($name, $objects_to_rebuild) || 
					isset($_GET['rebuild_objects'])
				)
			)
		{
			
			// call Object-Generator ($name, $new_model, $types, $savepath)
			new ObjectGenerator($projectName, $name, $newModel, $datatypes, $projectPath, $KITVERSION, $_GET);
			
			$fileHtmlOutput[] = '<div class="grn">' . L('PHP_Class') . ' "<strong>' . $name . '</strong>" ' . 
								(file_exists($projectPath .'class.'.$name.'.php') ?
											(is_writable($projectPath .'class.'.$name.'.php') ? L('updated') : '<span class="rd">'.L('could_not_be_written').'</span>' ) :
											L('created')
								) .
								" - ( <a target='_blank' href='helper/phprev.php?project=$projectName&file=$name'>" . L('view_Source') . 
								"</a> )</div>";
		}
		
	}
    // close the connection??
    //$obj->closeInstance( $i );
}
//print_r($newModel);
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////



$fileHtmlOutput[] = '<div class="bld">
					<strong>__model.php</strong> ' . L('updated') . 
					' - ( <a target="_blank" href="helper/phprev.php?project='.$projectName.'&file=__model">' . 
					L('view_Source') . 
					'</a> )</div>';


// save the new Model as JSON

$jsonstr0 = '<?php
//cms-kit Data-Model for: "'.$projectName.'" (Nowdoc style)
$stringified_objects = <<<\'EOD\'
';

$jsonstr1 = indentJson(json_encode($newModel));
//$jsonstr1 = json_encode($newModel);

$jsonstr2 = '
EOD;

$objects = json_decode($stringified_objects, true);

';



if($test = json_decode($jsonstr1, true)) {
    file_put_contents( $projectPath . "__model.php", $jsonstr0 . $jsonstr1 . $jsonstr2 );
    //print_r($test);
} else {
    $errorHtmlOutput[] = 'json for "__model.php" is not valid: ' . json_last_error();
    $x = array_pop($fileHtmlOutput);
}



@chmod($projectPath . "__model.php", 0777);
@chmod($zipPath, 0777);
$zipSize = filesize($zipPath) . ' B';

// register new Model for instant Adaption of Backend-Settings
$_SESSION[$projectName]['objects'] = json_decode($jsonstr1, true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>cms-kit-process</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
	<link href="../../../vendor/cmskit/jquery-ui/themes/<?php echo end($_SESSION[$projectName]['config']['theme'])?>/jquery-ui.css" rel="stylesheet" />
	<link href="../../inc/css/<?php echo end($_SESSION[$projectName]['config']['theme'])?>/style.css" rel="stylesheet" />
<style>
body {
	font: 65% "Trebuchet MS", sans-serif;
}

#controls {
	position: fixed;
	top: 5px;
	left: 5px;
	padding: 5px;
}

#working_area {
	position: absolute;
	width: 96%;
	top: 80px;
	left: 10px;
}

#process_settings, fieldset { 
	border: 2px solid #ccc; 
	border-radius: 6px; 
	background: white;
	margin-bottom: 40px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	-khtml-border-radius: 5px;
	-moz-box-shadow: 4px 4px 8px #888; /* FF 3.5+ */
	-webkit-box-shadow: 4px 4px 8px #888; /* Safari 3.0+, Chrome */
	box-shadow: 4px 4px 8px #888; /* Opera 10.5, IE 9.0 */
	filter: progid:DXImageTransform.Microsoft.Shadow(Strength=5, Direction=135, Color='#888888'); /* IE 6, IE 7 */
	-ms-filter: progid:DXImageTransform.Microsoft.Shadow(Strength=5, Direction=135, Color='#888888'); /* IE 8 */
}

fieldset div {
	border: 1px solid #eee; 
	border-radius: 6px; 
	margin-bottom: 5px;
	padding: 3px;
}

#sql-fieldset strong {color: #006;}
.grn {color: green;}
.orn {color: orange;}
.rd {color: red;}
.bl {color: blue;}

#del_backup iframe {
	width: 50px;
	height: 10px;
	margin: 0;
	border: 0px none;
}


/*
#controls button, #controls  iframe {
	width: 170px;
	margin-top: 5px;
}

#clear-frame {
	width: 90%;
	height: 35px;
	
}
#clearForm input[type=number] {
	width: 20px;
	float: right;
}
#clearForm div {
	clear: both;
}

*/

#process_settings {
	position: absolute;
	top: 50px;
	z-index: 2;
	padding: 5px;
}
.ui-buttonset {
	display: inline-block;
}

.errors p{
		color: red;
}

</style>
<!--[if lt IE 9]>
	<style type="text/css" title="text/css">
		fieldset { border: 1px solid silver; padding: 3px; }
	</style>
<![endif]-->
<script>

function showDiff(el)
{ 
	if(el.value != '') {
		window.open('helper/json_diff.php?project=<?php echo $projectName;?>&zip='+el.value, 'diff')
	}
}
function deleteBackups(el)
{
	var i = el.selectedIndex, v = el.value;
	if(v != '') {
		var q = confirm('<?php echo L('really_delete_Backups_before');?>: ' + el.options[i].text);
		if(q) {
			document.getElementById('del_backup').innerHTML = '<iframe src="helper/clear_backups.php?project=<?php echo $projectName;?>&ts='+v.split('_').shift()+'"></iframe>';
		}
	}
}

function rebuildObjects()
{
	var cb = document.getElementById('process_settings').getElementsByTagName('input');
	var g = [];
	for(var i=0; i<cb.length; ++i)
	{
		if(cb[i].checked) g.push(cb[i].id+'=1');
	}
	window.location = 'process.php?rebuild_objects=1&project=<?php echo $projectName;?>&'+g.join('&');
}

function toggle (id)
{
	var el = document.getElementById(id);
	el.style.display = (el.style.display=='none') ? 'block' : 'none';
}
</script>
</head>
<body>


<div id="controls" class="ui-widget-header ui-corner-all">
	<div class="ui-buttonset">
	<button
		onclick="rebuildObjects()"
		class="ui-button ui-widget ui-state-default ui-corner-left ui-button-text-icon-primary"  
		role="button" 
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-refresh"></span>
			<span class="ui-button-text"><?php echo L('Rebuild_Objects');?></span>
	</button>
	<button 
		onclick="toggle('process_settings')"
		class="ui-button ui-widget ui-state-default ui-button-icon-only ui-corner-right" 
		role="button" 
		aria-disabled="false" 
		title="Settings">
		<span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span>
		<span class="ui-button-text">Settings</span>
	</button>
	</div>
	
	<button
		onclick="window.open('../file_manager/index.php?project=<?php echo $projectName;?>','fm')"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" 
		title=""  
		role="button" 
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-folder-open"></span>
			<span class="ui-button-text"><?php echo L('file_manager');?></span>
	</button>
	<button
		onclick="window.open('../db_admin/index.php?project=<?php echo $projectName;?>','db')"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" 
		title=""  
		role="button" 
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-calculator"></span>
			<span class="ui-button-text"><?php echo L('db_admin');?></span>
	</button>
	<button
		onclick="top.location.reload()"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only"
		title="reload"
		role="button"
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-refresh"></span>
			<span class="ui-button-text">reload</span>
	</button>
</div>
<div id="process_settings" style="display:none">
<?php
	
	foreach($dsettings as $s)
	{
		echo '<p><input type="checkbox" id="'.$s[0].'" ' . (isset($_GET[$s[0]]) ? 'checked="checked"':'') . ' /> '.L($s[1]).'</p>';
	}
?>
</div>
	
<form id="working_area">
	
<?php

$backupList = getBackupList();
echo '
<fieldset>
	<legend>1. '.L('Backup').'</legend>
	<div><strong>'.L('Backup').'</strong>: <a href="'.$relpath.'/objects/backup/'.$zipName.'">'.$zipName.'</a> '.$zipSize.'</div>
	<div>
	<div><strong>'.L('old_Backups').'</strong>: 
	<select class="ui-button ui-widget ui-state-default ui-corner-all" onchange="showDiff(this)"><option value="">' . L('show_diff') . '</option>' . $backupList . '</select>
	<span id="del_backup"><select class="ui-button ui-widget ui-state-default ui-corner-all" onchange="deleteBackups(this)"><option value="">' . L('delete_backups_before') . '</option>' . $backupList . '</select></span>
	</div>
</fieldset>

<fieldset id="sql-fieldset">
	<legend>2. '.L('SQL_Queries').'</legend>
' . (count($queryHtmlOutput) ? implode("\n", $queryHtmlOutput) : L('no_SQL_Queries')) . '
</fieldset>

<fieldset>
	<legend>3. '.L('PHP_Objects').'</legend>
' . implode("\n", $fileHtmlOutput) . 
'</fieldset>
';

if(count($errorHtmlOutput) > 0)
{
	echo '<div class="errors"><p>';
	echo implode('</p><p>', $errorHtmlOutput);
	echo '</p></div';
}

?>
	
</form>
		
</body>
</html>
