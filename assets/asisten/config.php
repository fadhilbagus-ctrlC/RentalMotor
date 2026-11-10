<?php

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

if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', trim((string) $ai_gemini_key) !== '' ? 'gemini' : 'agentrouter');
}

if (!defined('AI_API_BASE')) {
    define('AI_API_BASE', AI_PROVIDER === 'gemini'
        ? 'https://generativelanguage.googleapis.com/v1beta/openai'
        : 'https://agentrouter.org/v1');
}
if (!defined('AI_MODEL')) {

    define('AI_MODEL', AI_PROVIDER === 'gemini' ? 'gemini-3.6-flash' : 'gpt-6-astra');
}
if (!defined('AI_API_KEY')) {
    define('AI_API_KEY', trim((string) (AI_PROVIDER === 'gemini' ? $ai_gemini_key : $ai_router_key)));
}

if (!defined('AI_MAX_OUTPUT_TOKENS')) define('AI_MAX_OUTPUT_TOKENS', 2500);
if (!defined('AI_MAX_INPUT_CHARS')) define('AI_MAX_INPUT_CHARS', 1000);
if (!defined('AI_MAX_HISTORY_MESSAGES')) define('AI_MAX_HISTORY_MESSAGES', 8);
if (!defined('AI_RATE_LIMIT')) define('AI_RATE_LIMIT', 20);
if (!defined('AI_RATE_WINDOW')) define('AI_RATE_WINDOW', 600);
if (!defined('AI_TIMEOUT_SECONDS')) define('AI_TIMEOUT_SECONDS', 120);
