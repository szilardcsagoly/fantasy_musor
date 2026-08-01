<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/init.php');

header('Content-Type: application/json; charset=utf-8');

$playlist = $_POST['playlist'] ?? '[]';
$decoded = json_decode($playlist, true);
if (!is_array($decoded)) {
    $decoded = [];
}

$stateJson = json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$mysqli->query("CREATE TABLE IF NOT EXISTS playlist_state (
    id INT PRIMARY KEY,
    state_json LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$escaped = $mysqli->real_escape_string($stateJson);
$mysqli->query("INSERT INTO playlist_state (id, state_json) VALUES (1, '$escaped')
    ON DUPLICATE KEY UPDATE state_json = '$escaped', updated_at = NOW()");

echo json_encode(['status' => 'success', 'stored_as' => 'json']);
