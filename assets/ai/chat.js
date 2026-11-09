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
    var endpoint = root.getAttribute('data-endpoint');
    var csrf = root.getAttribute('data-csrf') || '';

    var busy = false;
    var typingEl = null;

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

    function send(message) {
        message = String(message || '').trim();
        if (!message || busy) return;

        removeQuickReplies();
        addMessage(message, 'user');
        input.value = '';
        autoGrow();
        setBusy(true);
        showTyping();

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf
            },
            body: JSON.stringify({ message: message })
        })
            .then(function (res) {
                return res.json().catch(function () { return null; }).then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (result) {
                hideTyping();
                if (result.ok && result.data && result.data.ok) {
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
