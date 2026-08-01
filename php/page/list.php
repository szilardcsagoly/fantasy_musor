<?php
$mysqli->set_charset("utf8mb4");

$res = $mysqli->query("SELECT id, dalnev, eloado FROM dalok WHERE dalnev != 'Új dal cím' ORDER BY dalnev ASC");
$songs = [];
while ($row = $res->fetch_assoc()) {
    $songs[] = $row;
}

$availableLetters = [];
foreach ($songs as $song) {
    $title = trim($song['dalnev']);
    if ($title !== '') {
        $letter = strtoupper(mb_substr($title, 0, 1, 'UTF-8'));
        if (preg_match('/[A-ZÁÉÍÓÖŐÚÜŰ]/u', $letter)) {
            $availableLetters[$letter] = true;
        }
    }
}
ksort($availableLetters);
?>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="mb-1">Műsorlista szerkesztő</h3>
                            <p class="text-white-50 mb-0">A meglévő dalokból összeállítható egy egyszerű, rendezhető lista.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <select class="form-select form-select-sm bg-dark text-white border-secondary" id="savedPlaylistsSelect" style="max-width: 220px;">
                                <option value="">-- Mentett listák --</option>
                            </select>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-info" id="resetPlaylist">Új lista</button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="savePlaylistBtn">Lista mentése</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border border-secondary rounded p-3 bg-black bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Elérhető dalok</h5>
                                    <span class="badge bg-secondary"><?= count($songs) ?></span>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mb-3" id="alphabetFilter">
                                    <?php foreach (range('A', 'Z') as $letter): ?>
                                        <?php $isActive = isset($availableLetters[$letter]); ?>
                                        <button type="button" class="btn btn-xs btn-outline-info<?= $isActive ? '' : ' disabled' ?>" data-letter="<?= $letter ?>"<?= $isActive ? '' : ' disabled' ?>><?= $letter ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="list-group list-group-dark-custom" id="availableSongs">
                                    <?php
                                    $currentLetter = '';
                                    foreach ($songs as $song):
                                        $title = trim($song['dalnev']);
                                        $letter = strtoupper(mb_substr($title, 0, 1, 'UTF-8'));
                                        if (!preg_match('/[A-ZÁÉÍÓÖŐÚÜŰ]/u', $letter)) {
                                            $letter = '#';
                                        }
                                        if ($letter !== $currentLetter) {
                                            $currentLetter = $letter;
                                            echo '<div class="list-group-item bg-secondary bg-opacity-25 text-white-50 small fw-bold py-2" data-letter-group="' . htmlspecialchars($letter, ENT_QUOTES) . '">' . htmlspecialchars($letter) . '</div>';
                                        }
                                    ?>
                                        <div class="list-group-item list-group-item-action bg-dark border-secondary text-white d-flex justify-content-between align-items-center draggable-song"
                                             draggable="true"
                                             data-song-id="<?= (int)$song['id'] ?>"
                                             data-title="<?= htmlspecialchars($song['dalnev'], ENT_QUOTES) ?>"
                                             data-artist="<?= htmlspecialchars($song['eloado'], ENT_QUOTES) ?>"
                                             data-letter="<?= htmlspecialchars($letter, ENT_QUOTES) ?>">
                                            <span>
                                                <span class="fw-bold d-block"><?= htmlspecialchars($song['dalnev']) ?></span>
                                                <span class="small text-white-50"><?= htmlspecialchars($song['eloado']) ?></span>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border border-secondary rounded p-3 bg-black bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Jelenlegi lista</h5>
                                    <span class="badge bg-info text-dark" id="playlistCount">0 dal</span>
                                </div>
                                <div class="list-group playlist-dropzone list-group-dark-custom" id="playlistPreview">
                                    <div id="playlistItems">
                                        <div class="list-group-item bg-dark border-secondary text-white text-center text-white-50 py-4 playlist-empty">
                                            Itt jelennek meg a kiválasztott dalok kártyaként.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>