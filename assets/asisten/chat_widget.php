<?php
require_once __DIR__ . '/../config.php';
?>
<div class="ai-chat" id="aiChat"
     data-endpoint="../asisten/chat.php"
     data-store-lat="<?= htmlspecialchars((string) STORE_LAT, ENT_QUOTES) ?>"
     data-store-lng="<?= htmlspecialchars((string) STORE_LNG, ENT_QUOTES) ?>"
     data-store-name="<?= htmlspecialchars(STORE_NAME, ENT_QUOTES) ?>"
     data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

    <button type="button" class="ai-chat-toggle" id="aiChatToggle"
            aria-label="Buka asisten AI MotorKu" aria-expanded="false" aria-controls="aiChatPanel">
        <svg class="ai-chat-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H7l-4 3v-6.2A8.5 8.5 0 0 1 3 11.5 8.5 8.5 0 0 1 11.5 3h1A8.5 8.5 0 0 1 21 11.5z"/>
            <path d="M8 10h8M8 14h5"/>
        </svg>
        <svg class="ai-chat-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18"/>
        </svg>
        <span class="ai-chat-dot" aria-hidden="true"></span>
    </button>

    <section class="ai-chat-panel" id="aiChatPanel" aria-label="Asisten AI MotorKu" hidden>
        <header class="ai-chat-header">
            <div class="ai-chat-avatar" aria-hidden="true">AI</div>
            <div class="ai-chat-title">
            <strong>Asisten Motorku</strong>
                <span>Tanya seputar sewa motor</span>
            </div>
            <button type="button" class="ai-chat-close" id="aiChatClose" aria-label="Tutup asisten AI">&times;</button>
        </header>

        <div class="ai-chat-messages" id="aiChatMessages" role="log" aria-live="polite">
            <div class="ai-msg ai-msg-bot">Halo! Saya asisten AI MotorKu. Mau tanya harga, cara booking, atau jam operasional?</div>
            <div class="ai-quick-replies" id="aiQuickReplies">
                <button type="button" data-quick="Berapa harga sewa motor?">Harga sewa</button>
                <button type="button" data-quick="Bagaimana cara booking motor?">Cara booking</button>
                <button type="button" data-quick="Jam operasional MotorKu kapan?">Jam operasional</button>
                <button type="button" data-quick="Metode pembayaran apa saja yang tersedia?">Pembayaran</button>
                <button type="button" data-quick="Di mana lokasi pengambilan MotorKu?">Lokasi</button>
            </div>
        </div>

        <div class="ai-chat-locbar" aria-live="polite">
            <button type="button" class="ai-chat-locbtn" id="aiChatLocation" aria-pressed="false"
                    title="Bagikan lokasi Anda untuk melihat perkiraan jarak ke toko">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <span id="aiChatLocationText">Lokasi saya</span>
            </button>
        </div>

        <form class="ai-chat-form" id="aiChatForm">
            <textarea id="aiChatInput" rows="1" maxlength="1000"
                      placeholder="Tulis pertanyaan Anda..." aria-label="Pesan untuk asisten AI"></textarea>
            <button type="submit" id="aiChatSend" aria-label="Kirim pesan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
            </button>
        </form>
        <p class="ai-chat-note">Jawaban AI bisa kurang tepat. Lokasi hanya dikirim setelah Anda menekan tombol lokasi.</p>
    </section>
</div>
