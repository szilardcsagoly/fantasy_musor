<?php
define('DOC_ROOT', dirname(dirname(dirname(__FILE__))));
require_once(DOC_ROOT.'/php/init.php');

header('Content-Type: application/json; charset=utf-8');
$mysqli->set_charset('utf8mb4');

function respond($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$mysqli->query("CREATE TABLE IF NOT EXISTS saved_playlists (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    state_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $statement = $mysqli->prepare('SELECT id, name, state_json FROM saved_playlists WHERE id = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $playlist = $statement->get_result()->fetch_assoc();
        if (!$playlist) {
            respond(['success' => false, 'message' => 'A mentett lista nem található.'], 404);
        }

        respond([
            'id' => (int)$playlist['id'],
            'name' => $playlist['name'],
            'playlist' => json_decode($playlist['state_json'], true) ?: []
        ]);
    }

    $result = $mysqli->query('SELECT id, name FROM saved_playlists ORDER BY name ASC');
    $playlists = [];
    while ($playlist = $result->fetch_assoc()) {
        $playlists[] = ['id' => (int)$playlist['id'], 'name' => $playlist['name']];
    }
    respond($playlists);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Nem támogatott kérés.'], 405);
}

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id < 1) {
        respond(['success' => false, 'message' => 'Érvénytelen listaazonosító.'], 400);
    }

    $statement = $mysqli->prepare('DELETE FROM saved_playlists WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    if ($statement->affected_rows !== 1) {
        respond(['success' => false, 'message' => 'A mentett lista nem található.'], 404);
    }

    respond(['success' => true]);
}

$items = json_decode($_POST['playlist'] ?? '', true);
if (!is_array($items)) {
    respond(['success' => false, 'message' => 'Érvénytelen listaadat.'], 400);
}

$stateJson = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($action === 'update') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id < 1) {
        respond(['success' => false, 'message' => 'Érvénytelen listaazonosító.'], 400);
    }

    $statement = $mysqli->prepare('UPDATE saved_playlists SET state_json = ? WHERE id = ?');
    $statement->bind_param('si', $stateJson, $id);
    $statement->execute();

    $statement = $mysqli->prepare('SELECT id, name FROM saved_playlists WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $playlist = $statement->get_result()->fetch_assoc();
    if (!$playlist) {
        respond(['success' => false, 'message' => 'A mentett lista nem található.'], 404);
    }

    respond(['success' => true, 'id' => (int)$playlist['id'], 'name' => $playlist['name']]);
}

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
        respond(['success' => false, 'message' => 'A lista neve 1 és 120 karakter közötti legyen.'], 400);
    }

    try {
        $statement = $mysqli->prepare('INSERT INTO saved_playlists (name, state_json) VALUES (?, ?)');
        $statement->bind_param('ss', $name, $stateJson);
        $statement->execute();
    } catch (mysqli_sql_exception $exception) {
        if ((int)$exception->getCode() === 1062) {
            respond(['success' => false, 'message' => 'Már létezik ilyen nevű lista. Válassz másik nevet.'], 409);
        }
        throw $exception;
    }

    respond(['success' => true, 'id' => (int)$mysqli->insert_id, 'name' => $name]);
}

respond(['success' => false, 'message' => 'Érvénytelen művelet.'], 400);