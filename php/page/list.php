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
                            <button type="button" class="btn btn-sm btn-outline-danger" id="deleteSavedPlaylistBtn" disabled>Lista törlése</button>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-info" id="resetPlaylist">Új lista</button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="savePlaylistBtn">Lista mentése</button>
                            </div>
                        </div>
                    </div>
                    <div class="w-100 mt-2" style="min-height: 1.25rem;">
                        <span id="playlistSaveStatus" class="small text-white-50 text-truncate d-block w-100" aria-live="polite"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border border-secondary rounded p-3 bg-black bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Elérhető dalok</h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary"><?= count($songs) ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-info" id="loadAllSongs">Összes dal betöltése</button>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mb-3" id="alphabetFilter">
                                    <?php foreach (range('A', 'Z') as $letter): ?>
                                        <?php $isActive = isset($availableLetters[$letter]); ?>
                                        <button type="button" class="btn btn-xs btn-outline-info<?= $isActive ? '' : ' disabled' ?>" data-letter="<?= $letter ?>"<?= $isActive ? '' : ' disabled' ?>><?= $letter ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="search" class="form-control form-control-sm bg-dark text-white border-secondary mb-2" id="songSearch" placeholder="Keresés cím vagy előadó alapján" aria-label="Keresés cím vagy előadó alapján">
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
                                    <div class="list-group-item bg-dark border-secondary text-white-50 text-center d-none" id="songSearchEmpty">Nincs találat.</div>
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

<div class="modal fade" id="savePlaylistModal" tabindex="-1" aria-labelledby="savePlaylistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="savePlaylistModalLabel">Lista mentése</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Bezárás"></button>
            </div>
            <div class="modal-body">
                <p id="savePlaylistPrompt" class="mb-3"></p>
                <div id="newPlaylistNameGroup" class="d-none">
                    <label for="newPlaylistName" class="form-label">Új lista neve</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" id="newPlaylistName" maxlength="120" autocomplete="off">
                </div>
                <div id="savePlaylistError" class="text-danger small mt-2 d-none" role="alert"></div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Mégse</button>
                <button type="button" class="btn btn-outline-info d-none" id="updateSavedPlaylistBtn">Mentés</button>
                <button type="button" class="btn btn-outline-info d-none" id="startSaveAsNewBtn">Mentés új néven</button>
                <button type="button" class="btn btn-info text-dark d-none" id="createNamedPlaylistBtn">Mentés</button>
            </div>
        </div>
    </div>
</div>