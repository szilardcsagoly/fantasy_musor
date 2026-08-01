<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset("utf8mb4");

if (isset($_GET['artist'])) {
    $artist = $mysqli->real_escape_string($_GET['artist']);
    $res = $mysqli->query("SELECT id, dalnev FROM dalok WHERE eloado = '$artist' ORDER BY dalnev ASC");
    $data = [];
    while($row = $res->fetch_assoc()) { $data[] = $row; }
    echo json_encode($data);
} else {
    $id = (int)($_GET['id'] ?? 0);
    $res = $mysqli->query("SELECT id, dalnev, eloado, dalszoveg FROM dalok WHERE id = $id");
    
    // JAVÍTÁS: Közvetlenül a talált sort adjuk át, tömb (zárójelek) nélkül!
    if ($row = $res->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(null);
    }
}
exit;
