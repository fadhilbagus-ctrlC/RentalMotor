<?php
/**
 * Konfigurasi AI Assistant MotorKu.
 *
 * Provider yang didukung:
 *  - 'gemini'      : Google Gemini via endpoint OpenAI-compatible (dipakai bila key Gemini ada)
 *  - 'agentrouter' : AgentRouter / OpenAI Responses API (fallback otomatis)
 *
 * API key HANYA dipakai di server. Jangan pernah mengirim key ini ke browser.
 * Key dibaca dari environment variable GEMINI_API_KEY / AGENTROUTER_API_KEY,
 * atau file lokal C:\Laragon\etc\ai_secrets.php (development saja, TIDAK dalam repo).
 */

// Muat secret lokal bila ada (file di luar docroot & repo).
$local_ai_secrets = 'C:\\Laragon\\etc\\ai_secrets.php';
if (is_file($local_ai_secrets)) {
    require_once $local_ai_secrets;
}

$ai_gemini_key = getenv('GEMINI_API_KEY');
if (!is_string($ai_gemini_key) || trim($ai_gemini_key) === '') {
    $ai_gemini_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
}
$ai_router_key = getenv('AGENTROUTER_API_KEY');
if (!is_string($ai_router_key) || trim($ai_router_key) === '') {
    $ai_router_key = defined('AGENTROUTER_API_KEY') ? AGENTROUTER_API_KEY : '';
}

// Provider aktif: Gemini bila key tersedia, selain itu AgentRouter.
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', trim((string) $ai_gemini_key) !== '' ? 'gemini' : 'agentrouter');
}

if (!defined('AI_API_BASE')) {
    define('AI_API_BASE', AI_PROVIDER === 'gemini'
        ? 'https://generativelanguage.googleapis.com/v1beta/openai'
        : 'https://agentrouter.org/v1');
}
if (!defined('AI_MODEL')) {
    // Catatan: gemini-3.8-flash terlalu lambat untuk chat real-time (~70 detik/jawaban),
    // gemini-3.7-flash & model alias sempat hang. 3.6-flash stabil (~3-5 detik).
    define('AI_MODEL', AI_PROVIDER === 'gemini' ? 'gemini-3.6-flash' : 'gpt-6-astra');
}
if (!defined('AI_API_KEY')) {
    define('AI_API_KEY', trim((string) (AI_PROVIDER === 'gemini' ? $ai_gemini_key : $ai_router_key)));
}

// Batas pemakaian agar kuota tidak tersedot.
if (!defined('AI_MAX_OUTPUT_TOKENS')) define('AI_MAX_OUTPUT_TOKENS', 2500);
if (!defined('AI_MAX_INPUT_CHARS')) define('AI_MAX_INPUT_CHARS', 1000);
if (!defined('AI_MAX_HISTORY_MESSAGES')) define('AI_MAX_HISTORY_MESSAGES', 8);
if (!defined('AI_RATE_LIMIT')) define('AI_RATE_LIMIT', 20);       // pesan
if (!defined('AI_RATE_WINDOW')) define('AI_RATE_WINDOW', 600);    // per 10 menit
if (!defined('AI_TIMEOUT_SECONDS')) define('AI_TIMEOUT_SECONDS', 120);
