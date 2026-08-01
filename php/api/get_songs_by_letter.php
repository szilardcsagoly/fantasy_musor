<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset("utf8mb4");

$letter = $mysqli->real_escape_string($_GET['letter'] ?? '');
$res = $mysqli->query("SELECT id, dalnev, eloado FROM dalok WHERE dalnev LIKE '$letter%' AND dalnev != 'Új dal cím' ORDER BY dalnev ASC");

$songs = [];
while($row = $res->fetch_assoc()) {
    $songs[] = $row;
}
echo json_encode($songs);
