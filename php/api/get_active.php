<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/config.php');
@$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$res = $mysqli->query("SELECT aktiv_dal_id, scroll_szazalek FROM aktualis_musor WHERE id = 1");
$row = $res->fetch_assoc();
$playlistResult = @$mysqli->query("SELECT s.playlist_id, p.name, p.updated_at FROM live_playlist_selection AS s INNER JOIN saved_playlists AS p ON p.id = s.playlist_id WHERE s.id = 1");
$playlist = $playlistResult ? $playlistResult->fetch_assoc() : null;
echo json_encode([
    "aktiv_dal_id" => (int)($row['aktiv_dal_id'] ?? 0),
    "scroll_szazalek" => (float)($row['scroll_szazalek'] ?? 0),
    "live_playlist_id" => (int)($playlist['playlist_id'] ?? 0),
    "live_playlist_name" => $playlist['name'] ?? '',
    "live_playlist_updated_at" => $playlist['updated_at'] ?? ''
]);
exit;
