<?php $mysqli->set_charset("utf8mb4"); ?>

<!-- GOOGLE FONTS BEEMELÉSE: A JetBrains Mono a legmodernebb, legolvashatóbb sans-serif jellegű monospaced betűtípus -->
<style>
    @import url('https://googleapis.com');

    /* Modern, tiszta prompter alapbetűtípus */
    .onsong-font {
        font-family: 'JetBrains Mono', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace !important;
        letter-spacing: -0.2px;
    }

    /* AKKORDOK STÍLUSÁNAK ÉS SORTÁVOLSÁGÁNAK FIXÁLÁSA */
    
    /* 1. Az akkordokat tartalmazó elemek (pl. .chord vagy amivé a JS alakítja a [Am]-t) */
    .chord, .chord-line {
        color: #0dcaf0 !important; /* Visszaállítva a korábbi diszkrét világoskékre */
        font-weight: bold;
        height: 1.2em; /* Fix magasság az akkordsornak */
        margin-bottom: -0.2em; /* Finom igazítás, hogy közelebb üljön a szöveghez */
        display: inline-block;
    }

    /* 2. A teljes szövegsorok blokkja (Akkord + Szöveg együtt) */
    #prompterOutput, #editorPreviewOutput {
        line-height: 2.2 !important; /* Emelt, fix sortávolság, hogy kényelmesen elférjen az akkord a szöveg felett */
    }

    /* 3. Az üres vagy csak szöveges sorok kényszerítése, hogy ugyanakkora helyet foglaljanak */
    #prompterOutput div, #editorPreviewOutput div,
    .prompter-row {
        min-height: 2.2em; /* Ha nincs akkord, a sor függőleges helyfoglalása akkor sem csökken le */
        display: block;
    }
</style>


<div class="container-fluid py-3 bg-black text-white min-vh-100" id="liveMainContainer">
    
    <!-- Felső Harmonika Gyorskereső -->
    <?php include(DOC_ROOT.'/php/include/search_panel.php'); ?>

    <!-- VEZÉRLŐSÁV: Megkapta a sticky-top-control osztályt az ottmaradáshoz -->
<div class="row pt-1 bg-secondary align-items-center bg-black sticky-top sticky-top-control border-bottom border-dark" style="z-index: 1010;">
    <div class="col-md-6 d-flex align-items-center gap-3 flex-wrap">
        <button id="toggleViewBtn" class="btn btn-success fw-bold">📝 Szerkesztés</button>
        
        <div class="form-check form-switch m-0" id="followSwitchWrapper">
            <input class="form-check-input" type="checkbox" id="followBandSwitch" checked>
            <label class="form-check-label fw-bold text-warning" for="followBandSwitch">🔗 Zenekar követése</label>
        </div>

        <!-- MODOSÍTVA: Kivettük a 'disabled' és 'text-white-50' osztályokat, hogy kattintható és jól látható legyen -->
        <button type="button" id="btnScrollSync" class="btn btn-sm btn-dark border-secondary text-white fw-bold ms-2">✓ Szinkronban</button>

        <div id="transposeWrapper" class="d-none btn-group">
            <button type="button" class="btn btn-outline-light fw-bold" id="btnTransDown">♭ (-1)</button>
            <button type="button" class="btn btn-outline-light fw-bold" id="btnTransUp">♯ (+1)</button>
        </div>
    </div>

    <div class="col-md-6" id="sliderWrapper">
        <div class="d-flex align-items-center gap-2">
            <span class="small fw-bold text-white-50">Betűméret:</span>
            <input type="range" class="form-range" id="fontSizeSlider" min="14" max="40" value="20">
        </div>
    </div>
</div>



    <!-- INFÓ SÁV -->
    <div class="mb-3 border-bottom border-secondary pb-2">
        <h2 id="liveDisplayTitle" class="m-0 text-warning fw-bold">Nincs dal kiválasztva</h2>
        <p id="liveDisplayArtist" class="m-0 text-white-50 fs-5"></p>
    </div>

    <!-- A: PROMPTER NÉZET -->
    <div id="livePrompterContainer" class="p-3 bg-black rounded shadow" style="line-height: 1.8; letter-spacing: 0.5px;">
        <div id="prompterOutput" class="onsong-font" style="font-size: 20px; white-space: pre-wrap;"></div>
    </div>



    <!-- B: KÉTABLAKOS SZERKESZTŐ NÉZET -->
    <div id="liveEditorContainer" class="d-none">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="bg-black p-3 rounded h-100 d-flex flex-column" id="leftScrollBox" style="max-height: 65vh; overflow-y: auto;">
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary fs-6">Szerkesztési mód</span>
                        <button type="button" id="btnCancelEditSong" class="btn btn-sm btn-outline-danger fw-bold">❌ Mégse / Dal elvetése</button>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-white-50 m-0">Dal címe</label>
                        <input type="text" id="editTitle" class="form-control bg-dark text-white border-secondary">
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small text-white-50 m-0">Előadó</label>
                        <input type="text" id="editArtist" class="form-control bg-dark text-white border-secondary">
                    </div>

                    <div class="mb-2 flex-grow-1">
                        <label class="form-label small text-white-50 m-0">Szöveg + Akkordok szögletes zárójelben (pl: [Am]Szöveg)</label>
                        <textarea id="editLyrics" class="form-control bg-dark text-white border-secondary h-100 onsong-font" rows="12" style="resize: none;"></textarea>
                    </div>
                </div>
            </div>

            <!-- Jobb ablak: Élő előnézet -->
            <div class="col-md-6">
                <div class="bg-black p-3 rounded border border-secondary" id="rightScrollBox" style="max-height: 65vh; overflow-y: auto;">
                    <span class="badge bg-danger mb-2">ÉLŐ ELŐNÉZET</span>
                    <div id="editorPreviewOutput" class="onsong-font" style="font-size: 18px; white-space: pre-wrap; line-height: 1.8;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
  $(document).on('click', '.prompter-row', function() {
    let syncBtn = $('#btnScrollSync');
    
    // Szöveg átírása és a sárga figyelmeztető szín (btn-warning) beállítása
    syncBtn.text('⚠ Irányítás átadása');
    syncBtn.removeClass('btn-dark text-white').addClass('btn-warning text-dark');
    
    // Itt futhat a meglévő logikád (pl. görgetés vagy socket küldés a zenekarnak)
});  
</script>