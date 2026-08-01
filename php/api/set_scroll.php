<?php
require_once __DIR__ . '/../config.php';

@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    echo json_encode(["status" => "error"]);
    exit;
}
$mysqli->set_charset("utf8mb4");

// A kapott sor számát egész számmá alakítjuk (pl: 5. sor)
$sor_szam = (int)($_POST['szazalek'] ?? 0);

$mysqli->query("UPDATE aktualis_musor SET scroll_szazalek = $sor_szam WHERE id = 1");
echo json_encode(["status" => "success", "line" => $sor_szam]);
exit;
