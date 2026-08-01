<?php
// Aktív betűk kinyerése a DALOK CÍMEI alapján (SZŰRVE)
$active_letters = [];
$letter_res = $mysqli->query("SELECT DISTINCT UPPER(LEFT(dalnev, 1)) AS betu FROM dalok WHERE dalnev != 'Új dal cím' ORDER BY betu");
if ($letter_res) {
    while ($l_row = $letter_res->fetch_assoc()) {
        if (!empty($l_row['betu'])) $active_letters[] = $l_row['betu'];
    }
}
$abc = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];
?>
<div class="accordion" id="searchAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingSearch">
            <button class="accordion-button collapsed bg-secondary text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSearch" aria-expanded="false" aria-controls="collapseSearch">
                🎵 Gyorskereső / Dal kiválasztása
            </button>
        </h2>
        <div id="collapseSearch" class="accordion-collapse collapse" aria-labelledby="headingSearch" data-bs-parent="#searchAccordion">
            <div class="accordion-body bg-dark text-white">
                
                <!-- 1. Szint: ABC Gombok (Dalok kezdőbetűi) -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-warning small">1. Válassz kezdőbetűt (Dal cím alapján):</label>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach($abc as $char): 
                            $is_active = in_array($char, $active_letters); ?>
                            <button type="button" class="btn btn-sm <?= $is_active ? 'btn-outline-warning' : 'btn-outline-secondary disabled' ?> abc-filter-btn" data-letter="<?= $char ?>"><?= $char ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Fixen megjelenő két legördülő menü egy sorban vagy egymás alatt -->
                <div class="row g-2">
                    <!-- 2. Szint: Dal választó -->
                    <div class="col-md-6" id="songSelectWrapper">
                        <label for="search_song_select" class="form-label fw-bold text-warning small">2. Válassz dalt:</label>
                        <select id="search_song_select" class="form-select bg-secondary text-white border-0" disabled>
                            <option value="">-- Előbb válassz betűt --</option>
                        </select>
                    </div>

                    <!-- 3. Szint: Előadó információ (Csak olvasható / Megjelenítő) -->
                    <div class="col-md-6" id="artistSelectWrapper">
                        <label for="search_artist_display" class="form-label fw-bold text-warning small">3. Előadó / Zeneszerző:</label>
                        <input type="text" id="search_artist_display" class="form-control bg-secondary text-white border-0" readonly placeholder="-- Automatikusan kitöltődik --">
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
