<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset("utf8mb4");

$default_title = "Új dal cím";
$default_artist = "Ismeretlen előadó";
$default_lyrics = "[Am]Ide írd a szöveget...";

// 1. Megnézzük, van-e már egy korábbról ottfelejtett üres rekord
$sql_check = "SELECT id FROM dalok WHERE dalnev = '$default_title' AND eloado = '$default_artist' LIMIT 1";
$res_check = $mysqli->query($sql_check);

if ($res_check && $res_check->num_rows > 0) {
    // Ha találtunk üres sort, újrahasznosítjuk azt
    $row = $res_check->fetch_assoc();
    $target_id = $row['id'];
} else {
    // Ha nincs üres sor, akkor létrehozunk egy újat
    $sql_insert = "INSERT INTO dalok (dalnev, eloado, dalszoveg) VALUES ('$default_title', '$default_artist', '$default_lyrics')";
    if ($mysqli->query($sql_insert)) {
        $target_id = $mysqli->insert_id;
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        echo json_encode(["status" => "error", "message" => $mysqli->error]);
        exit;
    }
}

// 2. Azonnal beállítjuk az aktív műsorba ezt az ID-t
$mysqli->query("UPDATE aktualis_musor SET aktiv_dal_id = $target_id WHERE id = 1");

echo json_encode(["status" => "success", "id" => $target_id]);
exit;
