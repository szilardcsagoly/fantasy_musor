<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset("utf8mb4");

$letter = $mysqli->real_escape_string($_GET['letter'] ?? '');
$res = $mysqli->query("SELECT DISTINCT eloado FROM dalok WHERE eloado LIKE '$letter%' ORDER BY eloado ASC");

$artists = [];
while($row = $res->fetch_assoc()) {
    if(!empty($row['eloado'])) $artists[] = $row['eloado'];
}
echo json_encode($artists);
