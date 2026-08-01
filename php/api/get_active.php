<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$res = $mysqli->query("SELECT aktiv_dal_id, scroll_szazalek FROM aktualis_musor WHERE id = 1");
$row = $res->fetch_assoc();
echo json_encode([
    "aktiv_dal_id" => (int)($row['aktiv_dal_id'] ?? 0),
    "scroll_szazalek" => (float)($row['scroll_szazalek'] ?? 0)
]);
exit;
