<?php
$mysqli->set_charset("utf8mb4");

// Segédfüggvény az ékezetes kezdőbetűk normál betűvé alakításához a csoportosításnál
function tisztitKezdobetu($szoveg) {
    $elso_betu = mb_strtoupper(mb_substr($szoveg, 0, 1, 'UTF-8'), 'UTF-8');
    
    $keres =  ['Á', 'É', 'Í', 'Ó', 'Ö', 'Ő', 'Ú', 'Ü', 'Ű'];
    $cserel = ['A', 'E', 'I', 'O', 'O', 'O', 'U', 'U', 'U'];
    
    return str_replace($keres, $cserel, $elso_betu);
}

// 1. Dalok csoportosítása
$res_title = $mysqli->query("SELECT id, dalnev, eloado FROM dalok WHERE dalnev != 'Új dal cím' ORDER BY dalnev ASC");
$songs_by_title = [];
while($row = $res_title->fetch_assoc()) {
    $letter = tisztitKezdobetu($row['dalnev']);
    $songs_by_title[$letter][] = $row;
}

// 2. Előadók csoportosítása (A rendezés maradt előadó szerint, de a megjelenítés változik)
$res_artist = $mysqli->query("SELECT id, dalnev, eloado FROM dalok WHERE dalnev != 'Új dal cím' ORDER BY eloado ASC, dalnev ASC");
$songs_by_artist = [];
while($row = $res_artist->fetch_assoc()) {
    $letter = tisztitKezdobetu($row['eloado']);
    $songs_by_artist[$letter][] = $row;
}

$abc = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];
?>


<div class="container py-4">
    
    <!-- FIX FEJLÉC PANEL -->
    <div class="sticky-top bg-black text-white pt-2 pb-3 px-3 rounded shadow-sm mb-4 border border-secondary" style="top: 0; z-index: 1020;">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap g-2">
            <!-- A váltógombok megkapták a btn-outline-info osztályt, így aktív állapotban kékek lesznek -->
            <div class="btn-group text-info-toggle-group" role="group">
                <input type="radio" class="btn-check" name="listSort" id="sortTitle" checked>
                <label class="btn btn-sm btn-outline-info text-white" for="sortTitle">Dalok szerint</label>
                <input type="radio" class="btn-check" name="listSort" id="sortArtist">
                <label class="btn btn-sm btn-outline-info text-white" for="sortArtist">Előadók szerint</label>
            </div>
            <a href="/live" class="btn btn-sm btn-info bg-opacity-75 text-dark fw-bold border-0">📺 Élő nézet</a>
        </div>

        <!-- Címek szerinti ABC gomsor -->
        <div id="abcTitleRow" class="d-flex flex-wrap gap-1 mt-2">
            <span class="small text-white-50 w-100 d-block mb-1 fw-bold">Ugrás betűhöz (Dalok):</span>
            <?php foreach($abc as $char): 
                $has_data = isset($songs_by_title[$char]); ?>
                <button type="button" class="btn btn-xs <?= $has_data ? 'btn-outline-info text-info fw-bold abc-scroll-trigger' : 'btn-outline-secondary text-muted disabled' ?>" data-target="anchor-title-<?= $char ?>"><?= $char ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Előadók szerinti ABC gomsor (Alapból rejtve) -->
        <div id="abcArtistRow" class="d-flex flex-wrap gap-1 mt-2 d-none">
            <span class="small text-white-50 w-100 d-block mb-1 fw-bold">Ugrás betűhöz (Előadók):</span>
            <?php foreach($abc as $char): 
                $has_data = isset($songs_by_artist[$char]); ?>
                <button type="button" class="btn btn-xs <?= $has_data ? 'btn-outline-info text-info fw-bold abc-scroll-trigger' : 'btn-outline-secondary text-muted disabled' ?>" data-target="anchor-artist-<?= $char ?>"><?= $char ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CÍM SZERINTI SZEKCIÓ -->
    <div id="boxTitle">
        <?php foreach($songs_by_title as $letter => $rows): ?>
            <div id="anchor-title-<?= $letter ?>" class="scroll-target-block">
                <h3 class="bg-info bg-opacity-50 text-white p-2 rounded mb-2 mt-3 fw-bold border border-info border-opacity-25"><?= $letter ?></h3>
                <div class="list-group mb-3 shadow-sm list-group-dark-custom">
                    <?php foreach($rows as $song): ?>
                        <button class="list-group-item list-group-item-action d-flex justify-content-start align-items-center item-select-trigger bg-dark border-secondary text-white" data-id="<?= $song['id'] ?>">
                            <span class="fw-bold text-white me-2"><?= htmlspecialchars($song['dalnev']) ?></span>
                            <span class="text-white-50 small me-2">|</span>
                            <span class="text-white-50 small"><?= htmlspecialchars($song['eloado']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ELŐADÓ SZERINTI SZEKCIÓ -->
    <div id="boxArtist" class="d-none">
        <?php foreach($songs_by_artist as $letter => $rows): ?>
            <div id="anchor-artist-<?= $letter ?>" class="scroll-target-block">
                <h3 class="bg-info bg-opacity-50 text-white p-2 rounded mb-2 mt-3 fw-bold border border-info border-opacity-25"><?= $letter ?></h3>
                <div class="list-group mb-3 shadow-sm list-group-dark-custom">
                    <?php foreach($rows as $song): ?>
                        <!-- Átalakítva: Most már itt is a dalnév van elöl, utána az előadó, pontosan ugyanúgy -->
                        <button class="list-group-item list-group-item-action d-flex justify-content-start align-items-center item-select-trigger bg-dark border-secondary text-white" data-id="<?= $song['id'] ?>">
                            <span class="fw-bold text-white me-2"><?= htmlspecialchars($song['dalnev']) ?></span>
                            <span class="text-white-50 small me-2">|</span>
                            <!-- Az előadó neve itt is megkapta a text-white-50 színt a tökéletes egységért -->
                            <span class="text-white-50 small"><?= htmlspecialchars($song['eloado']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
  html {
    scroll-padding-top: 190px;
    scroll-behavior: smooth;
  }

  /* Listaelemek aktív stílusa */
  .list-group-dark-custom .list-group-item-action:active,
  .list-group-dark-custom .list-group-item-action:focus {
      background-color: #343a40 !important;
      color: #fff !important;
  }
  
  /* ABC gombok aktív érintési stílusa */
  .btn-outline-info:active, .btn-outline-info:focus {
      background-color: rgba(13, 202, 240, 0.2) !important;
      color: #0dcaf0 !important;
  }

  /* Váltógombok egyedi CSS-e: ha be vannak jelölve, fekete legyen a szövegük az élénk kék háttéren */
  .text-info-toggle-group .btn-check:checked + .btn-outline-info {
      background-color: #0dcaf0 !important;
      color: #000000 !important;
      font-weight: bold;
  }
</style>
