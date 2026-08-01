<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset("utf8mb4");

$id = (int)$_POST['id'];
$dalnev = $mysqli->real_escape_string(trim($_POST['dalnev']));
$eloado = $mysqli->real_escape_string(trim($_POST['eloado']));
$dalszoveg = $mysqli->real_escape_string(trim($_POST['dalszoveg']));

$mysqli->query("UPDATE dalok SET dalnev='$dalnev', eloado='$eloado', dalszoveg='$dalszoveg' WHERE id=$id");
echo json_encode(["status" => "success"]);
