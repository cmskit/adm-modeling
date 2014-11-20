<?php
require dirname(dirname(dirname(__DIR__))) . '/inc/php/session.php';

error_reporting(0);
$projectName 	= preg_replace('/[^-\w]/', '', $_GET['project']);
$bpath 			= '../../../../projects/' . $projectName . '/objects/backup/';

$msg = '';

if (!$_SESSION[$projectName]['root']) 
{
	$msg = 'no Rights to edit!';
}
else
{
	$ts = intval($_GET['ts']);
	$deleted = 0;
	if ($ts > 0)
	{
		$files = glob($bpath . '{*.zip,*.php}', GLOB_BRACE);
		$cnt = count($files);
		foreach($files as $file)
		{
			// extract the savetime from the path
			$st = array_shift(explode('_', basename($file)));
			
			if (is_numeric($st))
			{
				if (intval($st) < $ts)
				{
					//unlink($file);
					$deleted++;
				}
			}
			
		}
		$msg = $deleted . ' of ' . $cnt . ' Backups deleted!';
	}
	else
	{
		$msg = 'no Timestamp submitted!';
	}
}

echo '<html><body><script>alert("'.$msg.'")</script></body></html>';
?>
