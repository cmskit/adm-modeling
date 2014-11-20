<?php

require dirname(dirname(__DIR__)) . '/inc/php/session.php';

$projectName = preg_replace('/\W/', '', $_GET['project']);
if(!isset($_SESSION[$projectName]['root'])) exit('no Rights to edit!');
$draft_path = '../../../projects/' . $projectName . '/objects/__draft.php';

if (isset($_POST['json']))
{
// create a backup for export
if ($_POST['backup'] == 1)
{
	copy($draft_path, substr($draft_path,0,-3).'bac.php');
}

$str = 
'<?php
$model = <<<\'EOD\'
'.trim(str_replace('\\"','"',$_POST['json'])).'
EOD;
?>
';
	file_put_contents($draft_path, $str);
	@chmod($draft_path, 0777);
	echo 'saved';
}
else
{
	$model = '{"object":[]}';
	@include $draft_path;
	header ("Content-Type:text/json");
	echo $model;
}
?>
