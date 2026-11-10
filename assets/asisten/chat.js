(function () {
    'use strict';

    var root = document.getElementById('aiChat');
    if (!root) return;

    var toggle = document.getElementById('aiChatToggle');
    var panel = document.getElementById('aiChatPanel');
    var closeBtn = document.getElementById('aiChatClose');
    var messagesEl = document.getElementById('aiChatMessages');
    var form = document.getElementById('aiChatForm');
    var input = document.getElementById('aiChatInput');
    var sendBtn = document.getElementById('aiChatSend');
    var quickReplies = document.getElementById('aiQuickReplies');
    var locationBtn = document.getElementById('aiChatLocation');
    var locationText = document.getElementById('aiChatLocationText');
    var endpoint = root.getAttribute('data-endpoint');
    var csrf = root.getAttribute('data-csrf') || '';
    var storeLat = parseFloat(root.getAttribute('data-store-lat'));
    var storeLng = parseFloat(root.getAttribute('data-store-lng'));
    var storeName = root.getAttribute('data-store-name') || 'MotorKu Yogyakarta';

    var busy = false;
    var typingEl = null;
    var userLocation = null;
    var locationBusy = false;

    function openChat() {
        panel.hidden = false;
        root.classList.add('open', 'seen');
        toggle.setAttribute('aria-expanded', 'true');
        window.setTimeout(function () { input.focus(); }, 60);
        scrollToBottom();
    }

    function closeChat() {
        panel.hidden = true;
        root.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function addMessage(text, kind) {
        var el = document.createElement('div');
        el.className = 'ai-msg ai-msg-' + kind;
        el.textContent = text;
        messagesEl.appendChild(el);
        scrollToBottom();
        return el;
    }

    function showTyping() {
        typingEl = document.createElement('div');
        typingEl.className = 'ai-msg ai-msg-bot ai-msg-typing';
        typingEl.setAttribute('aria-label', 'AI sedang mengetik');
        for (var i = 0; i < 3; i++) typingEl.appendChild(document.createElement('span'));
        messagesEl.appendChild(typingEl);
        scrollToBottom();
    }

    function hideTyping() {
        if (typingEl && typingEl.parentNode) typingEl.parentNode.removeChild(typingEl);
        typingEl = null;
    }

    function setBusy(value) {
        busy = value;
        sendBtn.disabled = value;
        input.disabled = value;
    }

    function autoGrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 92) + 'px';
    }

    function removeQuickReplies() {
        if (quickReplies && quickReplies.parentNode) quickReplies.parentNode.removeChild(quickReplies);
        quickReplies = null;
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
        var earth = 6371;
        var toRad = Math.PI / 180;
        var dLat = (lat2 - lat1) * toRad;
        var dLng = (lng2 - lng1) * toRad;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(lat1 * toRad) * Math.cos(lat2 * toRad)
            * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        a = Math.min(1, Math.max(0, a));
        return earth * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function formatKm(km) {
        return Number(km).toLocaleString('id-ID', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        });
    }

    function setLocationState(state, text) {
        if (!locationBtn || !locationText) return;
        locationBtn.classList.remove('active', 'error');
        locationBtn.setAttribute('aria-pressed', 'false');
        if (state === 'active') {
            locationBtn.classList.add('active');
            locationBtn.setAttribute('aria-pressed', 'true');
        } else if (state === 'error') {
            locationBtn.classList.add('error');
        }
        locationText.textContent = text;
    }

    function requestLocation() {
        if (!locationBtn || locationBusy) return;
        if (!('geolocation' in navigator)) {
            setLocationState('error', 'Lokasi tidak didukung');
            addMessage('Browser Anda tidak mendukung deteksi lokasi. Anda tetap bisa bertanya alamat lewat chat.', 'error');
            return;
        }
        if (!window.isSecureContext) {
            setLocationState('error', 'Lokasi tidak aktif');
            addMessage('Deteksi lokasi hanya bisa di HTTPS atau localhost. Buka situs lewat alamat tersebut lalu coba lagi.', 'error');
            return;
        }

        var canCalculate = isFinite(storeLat) && isFinite(storeLng);
        locationBusy = true;
        locationBtn.disabled = true;
        setLocationState('idle', 'Mendeteksi lokasi...');

        navigator.geolocation.getCurrentPosition(function (pos) {
            userLocation = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            };
            locationBusy = false;
            locationBtn.disabled = false;

            if (canCalculate) {
                var km = haversineKm(userLocation.lat, userLocation.lng, storeLat, storeLng);
                setLocationState('active', 'Sekitar ' + formatKm(km) + ' km dari toko');
                addMessage('Lokasi terdeteksi. Perkiraan jarak garis lurus ke ' + storeName
                    + ' sekitar ' + formatKm(km) + ' km. Tanya "lokasi" kalau mau alamat lengkapnya.', 'bot');
            } else {
                setLocationState('active', 'Lokasi terdeteksi');
            }
        }, function (err) {
            locationBusy = false;
            locationBtn.disabled = false;
            userLocation = null;
            var message = 'Lokasi tidak dapat diambil. Anda tetap bisa bertanya alamat lewat chat.';
            if (err && err.code === 1) {
                message = 'Izin lokasi ditolak. Anda tetap bisa bertanya alamat lewat chat.';
            } else if (err && err.code === 2) {
                message = 'Lokasi tidak tersedia saat ini. Coba lagi atau tanya alamat lewat chat.';
            } else if (err && err.code === 3) {
                message = 'Deteksi lokasi terlalu lama. Coba lagi atau tanya alamat lewat chat.';
            }
            setLocationState('error', 'Lokasi tidak aktif');
            addMessage(message, 'error');
        }, {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 300000
        });
    }

    function send(message) {
        message = String(message || '').trim();
        if (!message || busy) return;

        removeQuickReplies();
        addMessage(message, 'user');
        input.value = '';
        autoGrow();
        setBusy(true);
        showTyping();

        var payload = { message: message };
        if (userLocation) payload.location = userLocation;

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf
            },
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.json().catch(function () { return null; }).then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (result) {
                hideTyping();
                if (result.ok && result.data && result.data.ok) {
                    if (typeof result.data.distance_km === 'number' && userLocation) {
                        setLocationState('active', 'Sekitar ' + formatKm(result.data.distance_km) + ' km dari toko');
                    }
                    addMessage(result.data.reply, 'bot');
                } else if (result.status === 419) {
                    addMessage('Sesi halaman sudah kedaluwarsa. Silakan muat ulang halaman lalu coba lagi.', 'error');
                } else {
                    var msg = (result.data && result.data.error) || 'Maaf, terjadi kendala. Silakan coba lagi.';
                    addMessage(msg, 'error');
                }
            })
            .catch(function () {
                hideTyping();
                addMessage('Tidak dapat terhubung ke layanan AI. Periksa koneksi internet Anda lalu coba lagi.', 'error');
            })
            .then(function () {
                setBusy(false);
                input.focus();
            });
    }

    toggle.addEventListener('click', function () {
        if (panel.hidden) openChat(); else closeChat();
    });

    closeBtn.addEventListener('click', closeChat);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) closeChat();
    });

    if (locationBtn) locationBtn.addEventListener('click', requestLocation);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send(input.value);
    });

    input.addEventListener('input', autoGrow);

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send(input.value);
        }
    });

    messagesEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-quick]');
        if (btn) send(btn.getAttribute('data-quick'));
    });
})();
