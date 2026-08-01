<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/init.php');

header('Content-Type: application/json; charset=utf-8');

$result = $mysqli->query("SELECT state_json FROM playlist_state WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['state_json'];
} else {
    echo '[]';
}
