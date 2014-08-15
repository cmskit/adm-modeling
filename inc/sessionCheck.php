<?php
/*
 * Script to test if the needed Session is still alive
 * js-include-check when setup-window is opened in index.php
 */
require dirname(dirname(dirname(__DIR__))) . '/inc/php/session.php';
header('Content-Type: text/plain; charset=utf-8');
if(!isset($_SESSION[$_GET['project']]['root'])) {
	echo 'alert("Session expired! Please save your Work locally and re-login!");';
}
?>
