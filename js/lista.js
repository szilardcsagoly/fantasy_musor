$(function () {
    const $availableList = $('#availableSongs');
    const $alphabetFilter = $('#alphabetFilter');
    const $playlist = $('#playlistPreview');
    const $playlistItems = $('#playlistItems');
    const $countBadge = $('#playlistCount');
    const allSongs = $availableList.find('.draggable-song').map(function () {
        return $(this);
    }).get();
    const storageKey = 'fantaPlaylistState';

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
        const items = $playlistItems.find('.playlist-item').map(function () {
            return {
                id: $(this).data('song-id'),
                title: $(this).find('.fw-bold').first().text(),
                artist: $(this).find('.small.text-white-50').first().text()
            };
        }).get();

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

    function loadPlaylistState() {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            try {
                const items = JSON.parse(saved);
                if (Array.isArray(items) && items.length) {
                    $playlistItems.find('.playlist-item, .playlist-empty').remove();
                    items.forEach(function (item) {
                        $playlistItems.append(buildPlaylistItem(item.id, item.title, item.artist));
                    });
                    updatePlaylistCount();
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

            $playlistItems.find('.playlist-item, .playlist-empty').remove();
            items.forEach(function (item) {
                $playlistItems.append(buildPlaylistItem(item.id, item.title, item.artist));
            });
            localStorage.setItem(storageKey, JSON.stringify(items));
            updatePlaylistCount();
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
        let target = null;

        $availableList.find('.draggable-song').each(function () {
            const $item = $(this);
            const title = ($item.data('title') || '').toUpperCase();
            if (title.charAt(0) === normalized) {
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
        $playlistItems.find('.playlist-item').remove();
        renderEmptyState();
        updatePlaylistCount();
        savePlaylistState();
    });

    loadPlaylistState();
    updatePlaylistCount();
});
