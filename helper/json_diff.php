<?php
/**
* show a Diff between old and new Model
* 
*/
require dirname(dirname(dirname(__DIR__))) . '/inc/php/session.php';
//if(!$_SESSION[$projectName]['root']) exit('no Rights to edit!');
$projectName = preg_replace('/[^-\w]/', '', $_GET['project']);


/*
	Paul's Simple Diff Algorithm v 0.1
	(C) Paul Butler 2007 <http://www.paulbutler.org/>
	May be used and distributed under the zlib/libpng license.
	
	Given two arrays, the function diff will return an array of the changes.
	I won't describe the format of the array, but it will be obvious
	if you use print_r() on the result of a diff on some test data.
	
	htmlDiff is a wrapper for the diff command, it takes two strings and
	returns the differences in HTML. The tags used are <ins> and <del>,
	which can easily be styled with CSS.  


function diff($old, $new)
{
	foreach($old as $oindex => $ovalue)
	{
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex)
		{
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
				$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen)
			{
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}	
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

function htmlDiff($old, $new)
{
	$diff = diff(explode(' ', $old), explode(' ', $new));
	foreach($diff as $k)
	{
		if(is_array($k))
		{
			$ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
				(!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
		}
		else {
			$ret .= $k . ' ';
		}
	}
	return $ret;
}*/

function button($label, $id='', $icon='gear', $title='')
{
return '<button
			id='.$id.'
			class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" 
			title="'.$title.'"  
			role="button" 
			aria-disabled="false">
				<span class="ui-button-icon-primary ui-icon ui-icon-'.$icon.'"></span>
				<span class="ui-button-text">'.L($label).'</span>
		</button>';
}

function L($str)
{
	return str_replace('_',' ',$str);
}

$projectPath = '../../../../projects/' . $projectName . '/objects/';

// get actual Model
require $projectPath . '__model.php';


// get the old Version
require '../../../../vendor/pclzip/pclzip/pclzip.lib.php';
$zip = $projectPath . 'backup/' . $_GET['zip'];
if(!file_exists($zip)) exit('Backup dosen\'t exist');

$archive = new PclZip($zip);
$list = $archive->extract(PCLZIP_OPT_BY_NAME, 'model.json', PCLZIP_OPT_EXTRACT_AS_STRING);


print_r($list);

if($list != 0)
{
	@$r1 = $list[0]['content'];
	$r2 = $stringified_objects;
	
	echo '
<!DOCTYPE html>
<html>
	<head>
		<title>Data-Diff</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="../../../../vendor/cmskit/jquery-ui/themes/'.end($_SESSION[$projectName]['config']['theme']).'/jquery-ui.css" rel="stylesheet" />
		<link type="text/css" rel="stylesheet" href="json_diff/style.css" media="screen" />
		<link type="text/css" rel="stylesheet" href="json_diff/jsondiffpatch.html.css" media="screen" />
		
		<script type="text/javascript" src="../../../../vendor/cmskit/jquery-ui/jquery.min.js"></script>
		<script type="text/javascript" src="json_diff/jsondiffpatch.js"></script>
		<script type="text/javascript" src="json_diff/jsondiffpatch.html.js"></script>
		<script type="text/javascript" src="json_diff/diff_match_patch_uncompressed.js"></script>
		
		<!--https://github.com/benjamine/JsonDiffPatch-->
	</head>
	<body>
		<div class="header">
		<span style="float:right">'.button('toggle_expert_mode','toggle_expert','triangle-2-n-s').'</span>
		</div>
		<div class="jsontext expert">
			<div>
				<label id="for_json1" for="json1">
					'.L('old').'
				</label>
				'.button('pretty_format','json1pretty','script').'
				<input id="json1" type="hidden" value="'.htmlspecialchars($r1).'">
				<textarea id="json1_out" class="json-input">'.htmlspecialchars($r1).'</textarea>
				<span id="json1errormessage" class="jsonerrormessage"></span>
			</div>
			<div>
				<label id="for_json2" for="json2">
					'.L('now').'
				</label>
				
				'.button('pretty_format','json2pretty','script').'
				<input id="json2" type="hidden" value="'.htmlspecialchars($r2).'">
				<textarea id="json2_out" class="json-input">'.htmlspecialchars($r2).'</textarea>
				<span id="json2errormessage" class="jsonerrormessage"></span>
			</div>
		</div>
		<div class="buttons expert">
			<input id="live" type="checkbox" checked="checked">
			<label for="live">
				'.L('live').'
			</label>
			'.button('Compare','compare','wrench').'
			'.button('Swap','swap','transfer-e-w').'
			'.button('Clear','clear','trash').'
		</div>
		<div class="results">
			<h2>'.L('Visual_Diff').'</h2>
			<div class="header-options">
				<input id="showunchanged" type="checkbox">
				<label for="showunchanged">
					'.L('show_unchanged_values').'
				</label>
			</div>
			<p class="visualdiff" id="visualdiff">
			</p>
			<div class="jsondiff expert">
				<h2>'.L('Data_Diff').'</h2>
				<p>
					(<span id="jsondifflength"></span> KB)
				</p>
				<textarea id="jsondiff">
				</textarea>
			</div>
		</div>
		
		
		<script type="text/javascript" src="json_diff/functions.js"></script>
	</body>
</html>
';
} // if($str != 0) END
else
{
	 echo "ERROR : ".$archive->errorInfo(true);
}
?>
