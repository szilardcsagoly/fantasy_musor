$(document).ready(function() {
    // ==========================================
    // SONGS.PHP LOGIKA
    // ==========================================
    if ($('input[name="listSort"]').length) {
        $('input[name="listSort"]').change(function() {
            if ($('#sortTitle').is(':checked')) {
                $('#boxTitle, #abcTitleRow').removeClass('d-none');
                $('#boxArtist, #abcArtistRow').addClass('d-none');
            } else {
                $('#boxTitle, #abcTitleRow').addClass('d-none');
                $('#boxArtist, #abcArtistRow').removeClass('d-none');
            }
        });
        $('.abc-scroll-trigger').click(function() {
            var targetId = $(this).data('target');
            var $targetElement = $('#' + targetId);
            if ($targetElement.length) {
                var stickyHeight = $('.sticky-top').outerHeight() || 110;
                var targetOffset = $targetElement.offset().top - stickyHeight - 10;
                $('html, body').animate({ scrollTop: targetOffset }, 300);
            }
        });
        $('.item-select-trigger').click(function() {
            var id = $(this).data('id');
            $.post('/php/api/set_active.php', { id: id }, function() {
                window.location.href = '/live/';
            });
        });
    }

    // ==========================================
    // ÚJ DAL GOMB VEZÉRLÉSE
    // ==========================================
    $('#navAddNewSongBtn').click(function(e) {
        e.preventDefault();
        $.getJSON('/php/api/add_new_song.php', function(res) {
            if (res.status === "success" && res.id > 0) {
                sessionStorage.setItem('forceEditorOpen', '1');
                window.location.href = '/live/';
            } else { alert("Hiba történt!"); }
        }).fail(function() { alert("A szerver nem érhető el!"); });
    });
    $(document).on('click', '#btnCancelEditSong', function() {
        activeSongId = 0; currentRawLyrics = ""; window.location.href = '/songs';
    });

    // ==========================================
    // LIVE.PHP INTELIGENS PROMPTER MOTOR
    // ==========================================
    if ($('#liveMainContainer').length) {
        var activeSongId = 0;
        var currentRawLyrics = "";
        var isEditorMode = false;
        var syncTimer = null;
        var saveTimeout = null;
        
        // JAVÍTOTT SZINKRON VÁLTOZÓK
        var isSyncLocked = false; 
        var lastServerLine = 0; 
        var iamTheKarmester = false; // ÚJ: True lesz, ha én bökök rá a sorokra, így védve vagyok a visszarángatástól!

        const scaleSharp = ["C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"];

        function parseChordPro(text) {
            var lines = text.split('\n'); var outputHtml = ""; var inRefren = false;
            const keywords = ['verse', 'chorus', 'bridge', 'intro', 'outro', 'solo', 'refrén', 'refren', 'szakasz'];
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim(); var lineIndex = i + 1;
                if (line === "") {
                    if (inRefren) { outputHtml += "</div>"; inRefren = false; }
                    outputHtml += "<div class='prompter-row' data-line='" + lineIndex + "'>\n</div>"; continue;
                }
                var sectionCheck = line.match(/^\[([^\]]+)\]$/);
                if (sectionCheck) {
                    var rawSectionText = line; var sectionContent = rawSectionText.replace('[','').replace(']','').toLowerCase();
                    if (inRefren) { outputHtml += "</div>"; inRefren = false; }
                    if (sectionContent.includes('chorus') || sectionContent.includes('refrén') || sectionContent.includes('refren')) {
                        outputHtml += "<div class='refren-block'><div class='prompter-row text-warning fw-bold my-2 fs-5' style='font-family: sans-serif;' data-line='" + lineIndex + "'>" + rawSectionText + "</div>";
                        inRefren = true;
                    } else {
                        outputHtml += "<div class='prompter-row text-info fw-bold my-2 fs-5 border-bottom border-secondary pb-1' style='font-family: sans-serif;' data-line='" + lineIndex + "'>" + rawSectionText + "</div>";
                    }
                    continue;
                }
                var parsedLine = "";
                if (lines[i].includes('[')) {
                    var segments = lines[i].split('['); parsedLine += segments.shift();
                    while (segments.length > 0) {
                        var nextPart = segments.shift(); var splitRight = nextPart.split(']');
                        var chord = splitRight.shift(); var textPart = splitRight.join(']');
                        var isSection = keywords.some(function(k) { return chord.toLowerCase().includes(k); });
                        if (isSection) { parsedLine += "<em class='text-muted'>(" + chord + ")</em>" + textPart; }
                        else { if(textPart === "") textPart = " "; parsedLine += "<span class='chord-line-container'><span class='chord-badge'>" + chord + "</span>" + textPart + "</span>"; }
                    }
                } else { parsedLine = lines[i]; }
                outputHtml += "<div class='prompter-row' data-line='" + lineIndex + "'>" + parsedLine + "</div>";
            }
            if (inRefren) outputHtml += "</div>"; return outputHtml;
        }

        function transposeChord(chord, steps) {
            return chord.replace(/([A-G][#b]?)/g, function(match) {
                var normalized = match;
                if (match === 'Db') normalized = 'C#'; if (match === 'Eb') normalized = 'D#';
                if (match === 'Gb') normalized = 'F#'; if (match === 'Ab') normalized = 'G#'; if (match === 'Bb') normalized = 'A#';
                var index = scaleSharp.indexOf(normalized); if (index === -1) return match;
                var newIndex = (index + steps) % 12; if (newIndex < 0) newIndex += 12; return scaleSharp[newIndex];
            });
        }

        function runTransposition(steps) {
            var text = $('#editLyrics').val();
            const keywords = ['verse', 'chorus', 'bridge', 'intro', 'outro', 'solo', 'refrén', 'refren', 'szakasz'];
            var segments = text.split('['); var transText = segments.shift();
            while (segments.length > 0) {
                var nextPart = segments.shift(); var splitRight = nextPart.split(']');
                var chord = splitRight.shift(); var textPart = splitRight.join(']');
                var isSection = keywords.some(function(k) { return chord.toLowerCase().includes(k); });
                if (isSection) { transText += '[' + chord + ']' + textPart; }
                else {
                    if (chord.includes('/')) {
                        var parts = chord.split('/');
                        var leftC = transposeChord(parts.shift(), steps); var rightC = transposeChord(parts.join('/'), steps);
                        transText += '[' + leftC + '/' + rightC + ']' + textPart;
                    } else { transText += '[' + transposeChord(chord, steps) + ']' + textPart; }
                }
            }
            $('#editLyrics').val(transText).trigger('input');
        }

        $('#btnTransUp').click(function() { runTransposition(1); });
        $('#btnTransDown').click(function() { runTransposition(-1); });


              // --- HARMONIKA MENÜ ---
        var localSongsCache = {};
        $('.abc-filter-btn').click(function() {
            var letter = $(this).data('letter');
            $('.abc-filter-btn').removeClass('btn-warning text-dark').addClass('btn-outline-warning');
            $(this).removeClass('btn-outline-warning').addClass('btn-warning text-dark');
            $.getJSON('/php/api/get_songs_by_letter.php', { letter: letter }, function(songs) {
                var sel = $('#search_song_select').html('<option value="">-- Válassz dalt --</option>').prop('disabled', false);
                $('#search_artist_display').val(''); localSongsCache = {};
                $.each(songs, function(i, song) { sel.append(new Option(song.dalnev, song.id)); localSongsCache[song.id] = song.eloado; });
            });
        });

        $('#search_song_select').change(function() {
            var id = $(this).val(); if(!id) { $('#search_artist_display').val(''); return; }
            var artistName = localSongsCache[id] ? localSongsCache[id] : ''; $('#search_artist_display').val(artistName);
            if($('#followBandSwitch').is(':checked') && !isEditorMode) {
                iamTheKarmester = true;
                $.post('/php/api/set_active.php', { id: id }, function() { loadSongDetails(id); });
            } else { loadSongDetails(id); }
            setTimeout(function() { $('#collapseSearch').collapse('hide'); }, 400);
        });

        // --- ADATBETÖLTŐ FÜGGVÉNY ---
        function loadSongDetails(id) {
            id = parseInt(id); if(isNaN(id) || id <= 0) return;
            $.getJSON('/php/api/get_songs.php', { id: id }, function(song) {
                if (song && song.dalszoveg !== undefined) {
                    activeSongId = id; currentRawLyrics = song.dalszoveg;
                    $('#liveDisplayTitle').text(song.dalnev); $('#liveDisplayArtist').text(song.eloado);
                    $('#prompterOutput').html(parseChordPro(song.dalszoveg));
                    if(!$('#editTitle').is(':focus')) $('#editTitle').val(song.dalnev);
                    if(!$('#editArtist').is(':focus')) $('#editArtist').val(song.eloado);
                    if(!$('#editLyrics').is(':focus')) $('#editLyrics').val(song.dalszoveg);
                    $('#editorPreviewOutput').html(parseChordPro(song.dalszoveg)); 
                    lastServerLine = 0; 
                    resetSyncState();
                    $('html, body').scrollTop(0); 
                }
            });
        }

        function resetSyncState() {
            isSyncLocked = false; 
            iamTheKarmester = false; // Alaphelyzetben követők vagyunk
            $('#btnScrollSync').removeClass('btn-warning text-dark').addClass('btn-outline-secondary disabled btn-dark text-white-50').text('✓ Szinkronban');
        }

        // --- OKOSÍTOTT BÖKŐS MOTOR ---
        $(document).on('click', '.prompter-row', function() {
            if (isEditorMode || !$('#followBandSwitch').is(':checked')) return;
            var lineNum = $(this).data('line'); if (lineNum === undefined) return;
            
            // Átvesszük az irányítást: én vagyok a Karmester!
            iamTheKarmester = true; 
            isSyncLocked = false; 

            $('.prompter-row').removeClass('active-stage-line'); 
            $(this).addClass('active-stage-line');
            
            // Élesítjük a sárga gombot a Karmesternek is, hogy vissza tudjon lépni követésbe!
            $('#btnScrollSync')
                .removeClass('btn-outline-secondary disabled btn-dark text-white-50')
                .addClass('btn-warning text-dark')
                .text('👋 Követés / Irányítás átadása');

            $.post('/php/api/set_scroll.php', { szazalek: lineNum }, function() {
                console.log("Sor elmentve: " + lineNum);
            });
        });

                // Manuális gördülés detektálása (Csak a csendes követőknél vált ki kizárást)
        $(window).on('wheel touchmove', function(e) {
            if ($(e.target).closest('.btn, .form-check-input, #searchAccordion, .prompter-row').length) return;

            if (!isEditorMode && $('#followBandSwitch').is(':checked') && !iamTheKarmester) {
                if (!isSyncLocked) {
                    isSyncLocked = true;
                    $('#btnScrollSync').removeClass('btn-outline-secondary disabled btn-dark text-white-50').addClass('btn-warning text-dark').text('🔄 Vissza a zenekarhoz');
                }
            }
        });

        // A MEGÚJULT SZINKRON GOMB KATTINTÁSA
        $('#btnScrollSync').click(function() {
            if ($(this).hasClass('disabled')) return; 
            
            // Akár Karmesterként, akár eltévedt Követőként nyomjuk meg: visszaállunk tiszta Slave követésbe!
            resetSyncState(); 
            if (lastServerLine > 0) {
                jumpToLine(lastServerLine);
            }
        });

        function jumpToLine(lineNum) {
            var $targetRow = $('.prompter-row[data-line="' + lineNum + '"]');
            if ($targetRow.length) {
                $('.prompter-row').removeClass('active-stage-line'); $targetRow.addClass('active-stage-line');
                var targetOffset = $targetRow.offset().top - 180; $('html, body').stop().animate({ scrollTop: targetOffset }, 500);
            }
        }

        // --- INTELLIGENS POLLING SZINKRON MOTOR ---
        function startSync() {
            syncTimer = setInterval(function() {
                if ($('#followBandSwitch').is(':checked') && !isEditorMode) {
                    $.getJSON('/php/api/get_active.php?_=' + new Date().getTime(), function(res) {
                        if (res && res.aktiv_dal_id > 0) {
                            
                            // 1. Ha a szerveren megváltozott a dal ID: Mindenki tiszta lappal követi!
                            if (res.aktiv_dal_id !== activeSongId) {
                                resetSyncState();
                                loadSongDetails(res.aktiv_dal_id);
                            } 
                            // 2. Ha ugyanaz a dal: A követőket odagörgetjük, a Karmestert békén hagyjuk
                            else if (res.aktiv_dal_id === activeSongId && !iamTheKarmester) {
                                lastServerLine = parseInt(res.scroll_szazalek);
                                if (!isSyncLocked && lastServerLine > 0) { jumpToLine(lastServerLine); }
                            }
                            // 3. Ha mi vagyunk a Karmester: csendben megjegyezzük a szerver állását a háttérben
                            else if (res.aktiv_dal_id === activeSongId && iamTheKarmester) {
                                lastServerLine = parseInt(res.scroll_szazalek);
                            }
                            
                        }
                    });
                }
            }, 2000);
        }
        startSync();

        $('#followBandSwitch').change(function() { if($(this).is(':checked')) { activeSongId = 0; resetSyncState(); } });
        $('#fontSizeSlider').on('input', function() { $('#prompterOutput').css('font-size', $(this).val() + 'px'); });

        function insertChordBrackets() {
            var lyricsField = document.getElementById('editLyrics');
            var start = lyricsField.selectionStart;
            var end = lyricsField.selectionEnd;
            var scrollTop = lyricsField.scrollTop;
            var selectedText = lyricsField.value.substring(start, end);
            lyricsField.value = lyricsField.value.substring(0, start) + '[' + selectedText + ']' + lyricsField.value.substring(end);
            lyricsField.focus();
            lyricsField.setSelectionRange(start + 1, end + 1);
            lyricsField.scrollTop = scrollTop;
            $(lyricsField).trigger('input');
            lyricsField.scrollTop = scrollTop;
        }

        function getChordSelection(lyricsField) {
            var text = lyricsField.value;
            var selectionStart = lyricsField.selectionStart;
            var selectionEnd = lyricsField.selectionEnd;
            var selectedText = text.substring(selectionStart, selectionEnd);
            if (/^\[[^\]\r\n]+\]$/.test(selectedText)) {
                return { start: selectionStart, end: selectionEnd };
            }

            var chordStart = text.lastIndexOf('[', selectionStart);
            var chordEnd = text.indexOf(']', selectionStart);
            var lineStart = text.lastIndexOf('\n', selectionStart - 1) + 1;
            var lineEnd = text.indexOf('\n', selectionStart);
            if (lineEnd === -1) lineEnd = text.length;
            if (chordStart < lineStart || chordEnd < chordStart || chordEnd >= lineEnd) {
                return null;
            }
            if (!/^\[[^\]\r\n]+\]$/.test(text.substring(chordStart, chordEnd + 1))) return null;
            return { start: chordStart, end: chordEnd + 1 };
        }

        function moveChordByCharacter(direction) {
            var lyricsField = document.getElementById('editLyrics');
            var chordSelection = getChordSelection(lyricsField);
            if (!chordSelection) return false;

            var text = lyricsField.value;
            var chord = text.substring(chordSelection.start, chordSelection.end);
            var textWithoutChord = text.substring(0, chordSelection.start) + text.substring(chordSelection.end);
            var targetPosition = direction < 0 ? chordSelection.start - 1 : chordSelection.start + 1;
            var adjacentCharacter = direction < 0 ? text.charAt(chordSelection.start - 1) : text.charAt(chordSelection.end);
            if (targetPosition < 0 || targetPosition > textWithoutChord.length || adjacentCharacter === '\n' || adjacentCharacter === '\r') return false;

            textWithoutChord = textWithoutChord.substring(0, targetPosition) + chord + textWithoutChord.substring(targetPosition);
            lyricsField.value = textWithoutChord;
            lyricsField.focus();
            lyricsField.setSelectionRange(targetPosition, targetPosition + chord.length);
            $(lyricsField).trigger('input');
            return true;
        }

        function isChordToken(token) {
            return /^[A-G](?:#|b)?(?:m|min|maj|dim|aug|sus|add)?\d*(?:\/[A-G](?:#|b)?)?$/.test(token);
        }

        function getChordTokens(line) {
            var tokens = [];
            var tokenMatch = /\S+/g;
            var match;
            while ((match = tokenMatch.exec(line)) !== null) {
                if (!isChordToken(match[0])) return [];
                tokens.push({ chord: match[0], position: match.index });
            }
            return tokens;
        }

        function convertAlignedChordsToChordPro(text) {
            var lines = text.split('\n');
            var convertedLines = [];
            var changed = false;

            for (var lineIndex = 0; lineIndex < lines.length; lineIndex++) {
                var chordTokens = getChordTokens(lines[lineIndex]);
                var lyricsLine = lines[lineIndex + 1];
                if (chordTokens.length > 0 && lyricsLine !== undefined && lyricsLine.trim() !== '') {
                    var convertedLyrics = lyricsLine;
                    var insertions = chordTokens.map(function(token) {
                        var position = Math.min(token.position, convertedLyrics.length);
                        while (position < convertedLyrics.length && /\s/.test(convertedLyrics.charAt(position))) position++;
                        while (position > 0 && !/\s/.test(convertedLyrics.charAt(position - 1))) position--;
                        return { chord: token.chord, position: position };
                    });

                    insertions.sort(function(first, second) { return second.position - first.position; });
                    insertions.forEach(function(insertion) {
                        convertedLyrics = convertedLyrics.substring(0, insertion.position) + '[' + insertion.chord + ']' + convertedLyrics.substring(insertion.position);
                    });
                    convertedLines.push(convertedLyrics);
                    lineIndex++;
                    changed = true;
                } else {
                    convertedLines.push(lines[lineIndex]);
                }
            }
            return { text: convertedLines.join('\n'), changed: changed };
        }

        $('#btnInsertChord').click(insertChordBrackets);
        $('#btnConvertChordPro').click(function() {
            var lyricsField = document.getElementById('editLyrics');
            var editorPanel = document.getElementById('leftScrollBox');
            var lyricsScrollTop = lyricsField.scrollTop;
            var panelScrollTop = editorPanel.scrollTop;
            var conversion = convertAlignedChordsToChordPro($('#editLyrics').val());
            if (!conversion.changed) {
                alert('Nem találtam átalakítható akkordsorokat.');
                return;
            }
            $('#editLyrics').val(conversion.text).trigger('input').focus();
            lyricsField.scrollTop = lyricsScrollTop;
            editorPanel.scrollTop = panelScrollTop;
        });
        $('#editLyrics').keydown(function(event) {
            if (event.ctrlKey && event.altKey && event.code === 'KeyD') {
                event.preventDefault();
                insertChordBrackets();
            } else if (event.ctrlKey && event.altKey && (event.code === 'ArrowLeft' || event.code === 'ArrowRight')) {
                if (moveChordByCharacter(event.code === 'ArrowLeft' ? -1 : 1)) event.preventDefault();
            }
        });

        $('#toggleViewBtn').click(function() {
            isEditorMode = !isEditorMode;
            if(isEditorMode) {
                $(this).text("📺 Élő nézet (Live)").removeClass("btn-success").addClass("btn-primary");
                $('#livePrompterContainer, #followSwitchWrapper, #sliderWrapper, #btnScrollSync').addClass('d-none');
                $('#liveEditorContainer, #transposeWrapper').removeClass('d-none');
            } else {
                $(this).text("📝 Szerkesztés").removeClass("btn-primary").addClass("btn-success");
                $('#livePrompterContainer, #followSwitchWrapper, #sliderWrapper, #btnScrollSync').removeClass('d-none');
                $('#liveEditorContainer, #transposeWrapper').addClass('d-none'); loadSongDetails(activeSongId);
            }
        });

        $('#editTitle, #editArtist, #editLyrics').on('input', function() {
            $('#editorPreviewOutput').html(parseChordPro($('#editLyrics').val()));
            if(activeSongId === 0) return;
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                var sendData = { id: activeSongId, dalnev: $('#editTitle').val(), eloado: $('#editArtist').val(), dalszoveg: $('#editLyrics').val() };
                $.post('/php/api/save_song_ajax.php', sendData, function() {
                    $('#liveDisplayTitle').text(sendData.dalnev); $('#liveDisplayArtist').text(sendData.eloado); currentRawLyrics = sendData.dalszoveg;
                });
            }, 500);
        });
    }
});
