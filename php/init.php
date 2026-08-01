<?php
$included = strtolower(realpath(__FILE__)) != strtolower(realpath($_SERVER['SCRIPT_FILENAME']));
if(!$included) die();


define('REMOTE_ADDR', isset($_SERVER['HTTP_X_FORWARDED_FOR'])?reset(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])):$_SERVER['REMOTE_ADDR']);


ini_set('max_execution_time', 300);
set_time_limit(300);

if(!defined('DOC_ROOT')) //a DOCUMENT_ROOT változtatható
	define('DOC_ROOT', dirname(dirname(__FILE__))); // /php/ feletti könyvtár

$lang='hu';

if($lang=='belépés' || $lang=='admin' || $lang=='kilépés' )
{
	$lang='hu';
	// if(isset($_GET['langpage'])) $mpage=$_GET['langpage']; else $mpage='';
	if(isset($_GET['mpage'])) $mpage=$_GET['mpage']; else $mpage='';
	if(isset($_GET['subpage'])) $subpage=$_GET['subpage']; else $subpage='';
	if(isset($_GET['subsubpage'])) $subsubpage=$_GET['subsubpage']; else $subsubpage='';
	if(isset($_GET['subsubsubpage'])) $subsubsubpage=$_GET['subsubsubpage']; else $subsubsubpage='';
}
else
{
	if(isset($_GET['mpage'])) $mpage=$_GET['mpage']; else $mpage='';
	if(isset($_GET['subpage'])) $subpage=$_GET['subpage']; else $subpage='';
	if(isset($_GET['subsubpage'])) $subsubpage=$_GET['subsubpage']; else $subsubpage='';
	if(isset($_GET['subsubsubpage'])) $subsubsubpage=$_GET['subsubsubpage']; else $subsubsubpage='';
}




require_once(DOC_ROOT.'/php/config.php'); //beállítások

date_default_timezone_set('Europe/Berlin');


global $mysqli; //kell, ha class-on belül van include-álva az init
@$mysqli= new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if(mysqli_connect_errno())
{
	die("Az adatbázis nem érhető el!"); // (".mysqli_connect_error().")
}


?>