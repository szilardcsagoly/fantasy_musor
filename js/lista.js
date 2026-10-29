$(function () {
    const $availableList = $('#availableSongs');
    const $alphabetFilter = $('#alphabetFilter');
    const $playlist = $('#playlistPreview');
    const $playlistItems = $('#playlistItems');
    const $countBadge = $('#playlistCount');
    const $savedPlaylistsSelect = $('#savedPlaylistsSelect');
    const $savePlaylistModal = $('#savePlaylistModal');
    const $newPlaylistName = $('#newPlaylistName');
    const $songSearch = $('#songSearch');
    const allSongs = $availableList.find('.draggable-song').map(function () {
        return $(this);
    }).get();
    const storageKey = 'fantaPlaylistState';
    const activeSavedPlaylistKey = 'fantaActiveSavedPlaylistId';
    const activeSavedPlaylistNameKey = 'fantaActiveSavedPlaylistName';
    let activeSavedPlaylistId = localStorage.getItem(activeSavedPlaylistKey);
    let activeSavedPlaylistName = localStorage.getItem(activeSavedPlaylistNameKey) || '';
    let activeSavedPlaylistSnapshot = null;

    function updatePlaylistCount() {
        const count = $playlistItems.find('.playlist-item').length;
        $countBadge.text(count + ' dal');
    }

    function renderEmptyState() {
        if ($playlistItems.find('.playlist-item').length === 0) {
            $playlistItems.append('<div class="list-group-item bg-dark border-secondary text-white text-center text-white-50 py-4 playlist-empty">Itt jelennek meg a kiválasztott dalok kártyaként.</div>');
        }
    }

    function savePlaylistState() {
        const items = getPlaylistItems();

        localStorage.setItem(storageKey, JSON.stringify(items));

        $.ajax({
            url: '/php/api/save_playlist_state.php',
            type: 'POST',
            data: { playlist: JSON.stringify(items) },
            dataType: 'json'
        }).fail(function () {
            console.warn('A lista állapota nem került elmentésre a szerverre.');
        });
    }

    function getPlaylistItems() {
        return $playlistItems.find('.playlist-item').map(function () {
            return {
                id: $(this).data('song-id'),
                title: $(this).find('.fw-bold').first().text(),
                artist: $(this).find('.small.text-white-50').first().text()
            };
        }).get();
    }

    function hasUnsavedPlaylistChanges() {
        const items = getPlaylistItems();
        if (!items.length && !activeSavedPlaylistSnapshot) {
            return false;
        }
        if (!activeSavedPlaylistId || activeSavedPlaylistSnapshot === null) {
            return items.length > 0 || Boolean(activeSavedPlaylistId);
        }
        return JSON.stringify(items) !== activeSavedPlaylistSnapshot;
    }

    function confirmDiscardPlaylistChanges() {
        if (!hasUnsavedPlaylistChanges()) {
            return true;
        }
        return window.confirm('A módosítások nincsenek elmentve a mentett listába. Biztosan elhagyod őket?');
    }

    function clearActiveSavedPlaylist() {
        activeSavedPlaylistId = null;
        activeSavedPlaylistName = '';
        activeSavedPlaylistSnapshot = null;
        localStorage.removeItem(activeSavedPlaylistKey);
        localStorage.removeItem(activeSavedPlaylistNameKey);
    }

    function normalizeSearchText(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('hu');
    }

    function filterAvailableSongs() {
        const query = normalizeSearchText($songSearch.val());
        let visibleCount = 0;

        allSongs.forEach(function ($song) {
            const title = normalizeSearchText($song.data('title'));
            const artist = normalizeSearchText($song.data('artist'));
            const matches = !query || title.includes(query) || artist.includes(query);
            $song.toggleClass('d-none', !matches);
            if (matches) {
                visibleCount++;
            }
        });

        $availableList.find('[data-letter-group]').each(function () {
            const hasVisibleSongs = $(this).nextUntil('[data-letter-group]').filter('.draggable-song').not('.d-none').length > 0;
            $(this).toggleClass('d-none', !hasVisibleSongs);
        });
        $('#songSearchEmpty').toggleClass('d-none', visibleCount > 0);
    }

    function renderPlaylist(items) {
        $playlistItems.find('.playlist-item, .playlist-empty').remove();
        items.forEach(function (item) {
            $playlistItems.append(buildPlaylistItem(item.id, item.title, item.artist));
        });
        renderEmptyState();
        updatePlaylistCount();
    }

    function loadSavedPlaylists(selectedId) {
        $.ajax({
            url: '/php/api/saved_playlists.php',
            type: 'GET',
            dataType: 'json'
        }).done(function (playlists) {
            $savedPlaylistsSelect.find('option:not(:first)').remove();
            playlists.forEach(function (playlist) {
                $('<option>').val(playlist.id).text(playlist.name).appendTo($savedPlaylistsSelect);
            });
            const rememberedId = selectedId || activeSavedPlaylistId;
            const hasRememberedPlaylist = rememberedId && $savedPlaylistsSelect.find('option[value="' + rememberedId + '"]').length;
            if (hasRememberedPlaylist) {
                $savedPlaylistsSelect.val(String(rememberedId));
                $.getJSON('/php/api/saved_playlists.php', { id: rememberedId }).done(function (playlist) {
                    activeSavedPlaylistId = String(playlist.id);
                    activeSavedPlaylistName = playlist.name;
                    activeSavedPlaylistSnapshot = JSON.stringify(playlist.playlist);
                    localStorage.setItem(activeSavedPlaylistKey, activeSavedPlaylistId);
                    localStorage.setItem(activeSavedPlaylistNameKey, activeSavedPlaylistName);
                }).fail(function () {
                    clearActiveSavedPlaylist();
                    $savedPlaylistsSelect.val('');
                });
            } else {
                clearActiveSavedPlaylist();
            }
            $('#deleteSavedPlaylistBtn').prop('disabled', !$savedPlaylistsSelect.val());
        });
    }

    function loadPlaylistState() {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            try {
                const items = JSON.parse(saved);
                if (Array.isArray(items) && items.length) {
                    renderPlaylist(items);
                    return;
                }
            } catch (e) {
                console.warn('Nem sikerült betölteni a helyi listát.', e);
            }
        }

        $.ajax({
            url: '/php/api/get_playlist_state.php',
            type: 'GET',
            dataType: 'json'
        }).done(function (items) {
            if (!Array.isArray(items) || !items.length) {
                return;
            }

            renderPlaylist(items);
            localStorage.setItem(storageKey, JSON.stringify(items));
        });
    }

    function openSavePlaylistModal() {
        $('#savePlaylistError').text('').addClass('d-none');
        $newPlaylistName.val('');

        if (activeSavedPlaylistId) {
            $('#savePlaylistPrompt').text('A(z) „' + activeSavedPlaylistName + '” listát frissíted, vagy új néven mented?');
            $('#newPlaylistNameGroup, #createNamedPlaylistBtn').addClass('d-none');
            $('#updateSavedPlaylistBtn, #startSaveAsNewBtn').removeClass('d-none');
        } else {
            $('#savePlaylistPrompt').text('Adj nevet az új mentett listának.');
            $('#newPlaylistNameGroup').removeClass('d-none');
            $('#updateSavedPlaylistBtn, #startSaveAsNewBtn').addClass('d-none');
            $('#createNamedPlaylistBtn').removeClass('d-none');
        }

        bootstrap.Modal.getOrCreateInstance($savePlaylistModal[0]).show();
    }

    function saveNamedPlaylist(action) {
        const data = {
            action: action,
            playlist: JSON.stringify(getPlaylistItems())
        };

        if (action === 'update') {
            data.id = activeSavedPlaylistId;
        } else {
            data.name = $newPlaylistName.val().trim();
            if (!data.name) {
                $('#savePlaylistError').text('Adj nevet a listának.').removeClass('d-none');
                $newPlaylistName.trigger('focus');
                return;
            }
        }

        $('#savePlaylistError').text('').addClass('d-none');
        $.ajax({
            url: '/php/api/saved_playlists.php',
            type: 'POST',
            data: data,
            dataType: 'json'
        }).done(function (result) {
            if (!result.success) {
                $('#savePlaylistError').text(result.message || 'A lista mentése nem sikerült.').removeClass('d-none');
                return;
            }

            activeSavedPlaylistId = String(result.id);
            activeSavedPlaylistName = result.name;
            activeSavedPlaylistSnapshot = JSON.stringify(getPlaylistItems());
            localStorage.setItem(activeSavedPlaylistKey, activeSavedPlaylistId);
            localStorage.setItem(activeSavedPlaylistNameKey, activeSavedPlaylistName);
            loadSavedPlaylists(activeSavedPlaylistId);
            $('#playlistSaveStatus').text('Mentve: ' + result.name);
            bootstrap.Modal.getOrCreateInstance($savePlaylistModal[0]).hide();
        }).fail(function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message;
            $('#savePlaylistError').text(message || 'A lista mentése nem sikerült.').removeClass('d-none');
        });
    }

    function buildPlaylistItem(songId, title, artist) {
        return $(
            '<div class="list-group-item bg-dark border-secondary text-white playlist-item d-flex justify-content-between align-items-center" data-song-id="' + songId + '">' +
            '<div class="d-flex align-items-center flex-grow-1">' +
            '<span class="playlist-handle me-3" aria-label="Húzás">⋮⋮</span>' +
            '<div class="flex-grow-1">' +
            '<div class="fw-bold">' + title + '</div>' +
            '<div class="small text-white-50">' + artist + '</div>' +
            '</div>' +
            '</div>' +
            '<button type="button" class="btn btn-outline-info btn-xs remove-song" title="Törlés" aria-label="Törlés">×</button>' +
            '</div>'
        );
    }

    function addSongToPlaylist(songId, title, artist) {
        if ($playlistItems.find('.playlist-item[data-song-id="' + songId + '"]').length) {
            return;
        }

        $playlistItems.find('.playlist-empty').remove();
        $playlistItems.append(buildPlaylistItem(songId, title, artist));
        updatePlaylistCount();
        savePlaylistState();
    }

    function scrollToLetter(letter) {
        const normalized = (letter || '').toUpperCase();
        if ($songSearch.val()) {
            $songSearch.val('');
            filterAvailableSongs();
        }
        let target = null;

        $availableList.find('.draggable-song').each(function () {
            const $item = $(this);
            const title = ($item.data('title') || '').toUpperCase();
            if (!$item.hasClass('d-none') && title.charAt(0) === normalized) {
                target = $item;
                return false;
            }
        });

        if (target) {
            const container = $availableList[0];
            const offset = $alphabetFilter.outerHeight() + 16;
            const targetTop = target[0].offsetTop - offset;
            $(container).stop(true, false).animate({ scrollTop: targetTop }, 260);
        }

        $alphabetFilter.find('button').removeClass('active');
        const $activeButton = $alphabetFilter.find('button[data-letter="' + normalized + '"]');
        if ($activeButton.length) {
            $activeButton.addClass('active');
        }
    }

    $alphabetFilter.on('click', 'button', function () {
        $alphabetFilter.find('button').removeClass('active');
        $(this).addClass('active');
        scrollToLetter($(this).data('letter'));
    });

    $songSearch.on('input', filterAvailableSongs);

    $(document).on('keydown', function (e) {
        const key = e.key.toUpperCase();
        if (!/^[A-ZÁÉÍÓÖŐÚÜŰ]$/.test(key)) {
            return;
        }

        if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
            return;
        }

        e.preventDefault();
        scrollToLetter(key);
    });

    $availableList.on('click', '.draggable-song', function () {
        const $item = $(this);
        addSongToPlaylist($item.data('song-id'), $item.data('title'), $item.data('artist'));
    });

    $availableList.on('dragstart', '.draggable-song', function (e) {
        const $item = $(this);
        const data = JSON.stringify({
            songId: $item.data('song-id'),
            title: $item.data('title'),
            artist: $item.data('artist')
        });
        e.originalEvent.dataTransfer.setData('application/x-song-data', data);
    });

    $playlist.on('dragover', function (e) {
        e.preventDefault();
    });

    $playlist.on('drop', function (e) {
        e.preventDefault();
        const data = e.originalEvent.dataTransfer.getData('application/x-song-data');
        if (!data) {
            return;
        }

        const song = JSON.parse(data);
        addSongToPlaylist(song.songId, song.title, song.artist);
    });

    $playlist.on('click', '.remove-song', function (e) {
        e.stopPropagation();
        const $item = $(this).closest('.playlist-item');
        $item.remove();
        updatePlaylistCount();
        if ($playlistItems.find('.playlist-item').length === 0) {
            renderEmptyState();
        }
        savePlaylistState();
    });

    if (typeof Sortable !== 'undefined') {
        new Sortable($playlistItems[0], {
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.playlist-handle',
            filter: '.remove-song',
            preventOnFilter: false,
            onEnd: function () {
                updatePlaylistCount();
                savePlaylistState();
            }
        });
    }

    $('#resetPlaylist').on('click', function () {
        if (!confirmDiscardPlaylistChanges()) {
            return;
        }
        clearActiveSavedPlaylist();
        $savedPlaylistsSelect.val('');
        $playlistItems.find('.playlist-item').remove();
        renderEmptyState();
        updatePlaylistCount();
        savePlaylistState();
    });

    $('#savePlaylistBtn').on('click', openSavePlaylistModal);

    $('#updateSavedPlaylistBtn').on('click', function () {
        saveNamedPlaylist('update');
    });

    $('#startSaveAsNewBtn').on('click', function () {
        $('#newPlaylistNameGroup').removeClass('d-none');
        $('#startSaveAsNewBtn').addClass('d-none');
        $('#createNamedPlaylistBtn').removeClass('d-none');
        $newPlaylistName.val(activeSavedPlaylistName);
        $newPlaylistName.trigger('focus');
    });

    $('#createNamedPlaylistBtn').on('click', function () {
        saveNamedPlaylist('create');
    });

    $savedPlaylistsSelect.on('change', function () {
        const playlistId = $(this).val();
        $('#deleteSavedPlaylistBtn').prop('disabled', !playlistId);
        if (!playlistId) {
            clearActiveSavedPlaylist();
            return;
        }

        if (!confirmDiscardPlaylistChanges()) {
            $(this).val(activeSavedPlaylistId || '');
            $('#deleteSavedPlaylistBtn').prop('disabled', !$savedPlaylistsSelect.val());
            return;
        }

        $savedPlaylistsSelect.prop('disabled', true);

        $.ajax({
            url: '/php/api/saved_playlists.php',
            type: 'GET',
            data: { id: playlistId },
            dataType: 'json'
        }).done(function (result) {
            renderPlaylist(result.playlist);
            activeSavedPlaylistId = String(result.id);
            activeSavedPlaylistName = result.name;
            activeSavedPlaylistSnapshot = JSON.stringify(result.playlist);
            localStorage.setItem(activeSavedPlaylistKey, activeSavedPlaylistId);
            localStorage.setItem(activeSavedPlaylistNameKey, activeSavedPlaylistName);
            savePlaylistState();
            $('#playlistSaveStatus').text('Betöltve: ' + result.name);
        }).fail(function () {
            $('#playlistSaveStatus').text('A mentett lista betöltése nem sikerült.');
            $savedPlaylistsSelect.val(activeSavedPlaylistId || '');
            $('#deleteSavedPlaylistBtn').prop('disabled', !$savedPlaylistsSelect.val());
        }).always(function () {
            $savedPlaylistsSelect.prop('disabled', false);
        });
    });

    $('#deleteSavedPlaylistBtn').on('click', function () {
        const playlistId = $savedPlaylistsSelect.val();
        if (!playlistId) {
            return;
        }

        const playlistName = $savedPlaylistsSelect.find('option:selected').text();
        if (!window.confirm('Biztosan törlöd a(z) „' + playlistName + '” mentett listát? A jelenlegi összeállítás megmarad.')) {
            return;
        }

        const $deleteButton = $(this).prop('disabled', true);
        $.ajax({
            url: '/php/api/saved_playlists.php',
            type: 'POST',
            data: { action: 'delete', id: playlistId },
            dataType: 'json'
        }).done(function (result) {
            if (!result.success) {
                $('#playlistSaveStatus').text(result.message || 'A lista törlése nem sikerült.');
                return;
            }

            if (activeSavedPlaylistId === String(playlistId)) {
                clearActiveSavedPlaylist();
            }
            $savedPlaylistsSelect.find('option[value="' + playlistId + '"]').remove();
            $savedPlaylistsSelect.val('');
            $('#playlistSaveStatus').text('Törölve: ' + playlistName + '. A jelenlegi összeállítás megmaradt.');
        }).fail(function () {
            $('#playlistSaveStatus').text('A lista törlése nem sikerült.');
        }).always(function () {
            $deleteButton.prop('disabled', !$savedPlaylistsSelect.val());
        });
    });

    $('#loadAllSongs').on('click', function () {
        if (!confirmDiscardPlaylistChanges()) {
            return;
        }
        $playlistItems.find('.playlist-item, .playlist-empty').remove();
        allSongs.forEach(function ($song) {
            $playlistItems.append(buildPlaylistItem(
                $song.data('song-id'),
                $song.data('title'),
                $song.data('artist')
            ));
        });

        renderEmptyState();
        updatePlaylistCount();
        savePlaylistState();
    });

    loadPlaylistState();
    loadSavedPlaylists();
    updatePlaylistCount();
});
