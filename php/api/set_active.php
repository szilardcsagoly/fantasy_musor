<?php
require_once __DIR__ . '/../config.php';

@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    echo json_encode(["status" => "error"]);
    exit;
}
$mysqli->set_charset("utf8mb4");

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // JAVÍTÁS: Amikor új dalt aktiválunk, a scroll_szazalek-ot (az aktív sort) FIXEN VISSZAÁLLÍTJUK 0-RA!
    $sql = "UPDATE aktualis_musor SET aktiv_dal_id = $id, scroll_szazalek = 0 WHERE id = 1";
    
    if ($mysqli->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $mysqli->error]);
    }
}
exit;
