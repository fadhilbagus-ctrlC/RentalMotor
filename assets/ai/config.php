<?php
/**
 * Konfigurasi AI Assistant MotorKu (AgentRouter).
 *
 * API key HANYA dipakai di server. Jangan pernah mengirim key ini ke browser.
 * Key dibaca dari environment variable AGENTROUTER_API_KEY, atau file lokal
 * C:\Laragon\etc\ai_secrets.php (development saja, TIDAK dalam repo).
 */

if (!defined('AI_API_BASE')) define('AI_API_BASE', 'https://agentrouter.org/v1');
if (!defined('AI_MODEL')) define('AI_MODEL', 'gpt-6-astra');

$ai_key = getenv('AGENTROUTER_API_KEY');
if (!is_string($ai_key) || trim($ai_key) === '') {
    // Muat secret lokal bila env belum diset (dev only, file di luar docroot & repo).
    $local_ai_secrets = 'C:\\Laragon\\etc\\ai_secrets.php';
    if (is_file($local_ai_secrets)) {
        require_once $local_ai_secrets;
        $ai_key = defined('AGENTROUTER_API_KEY') ? AGENTROUTER_API_KEY : '';
    }
}
if (!defined('AI_API_KEY')) define('AI_API_KEY', trim((string) $ai_key));

// Batas pemakaian agar kuota tidak tersedot.
if (!defined('AI_MAX_OUTPUT_TOKENS')) define('AI_MAX_OUTPUT_TOKENS', 2500);
if (!defined('AI_MAX_INPUT_CHARS')) define('AI_MAX_INPUT_CHARS', 1000);
if (!defined('AI_MAX_HISTORY_MESSAGES')) define('AI_MAX_HISTORY_MESSAGES', 8);
if (!defined('AI_RATE_LIMIT')) define('AI_RATE_LIMIT', 20);       // pesan
if (!defined('AI_RATE_WINDOW')) define('AI_RATE_WINDOW', 600);    // per 10 menit
if (!defined('AI_TIMEOUT_SECONDS')) define('AI_TIMEOUT_SECONDS', 120);
