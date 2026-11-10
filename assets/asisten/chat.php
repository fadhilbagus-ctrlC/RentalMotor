<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function ai_json(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    ai_json(405, ['ok' => false, 'error' => 'Metode tidak diizinkan.']);
}

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/ai_client.php';

@set_time_limit(AI_TIMEOUT_SECONDS + 30);

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    ai_json(400, ['ok' => false, 'error' => 'Format permintaan tidak valid.']);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? '');
if (!csrf_validate(is_string($token) ? $token : null)) {
    ai_json(419, ['ok' => false, 'error' => 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.']);
}

$message = trim((string) ($payload['message'] ?? ''));
$message = str_replace("\0", '', $message);
if ($message === '') {
    ai_json(422, ['ok' => false, 'error' => 'Pesan tidak boleh kosong.']);
}
if (mb_strlen($message) > AI_MAX_INPUT_CHARS) {
    ai_json(422, ['ok' => false, 'error' => 'Pesan terlalu panjang (maksimal ' . AI_MAX_INPUT_CHARS . ' karakter).']);
}

$user_location = null;
$raw_location = $payload['location'] ?? null;
if (is_array($raw_location)) {
    $lat = $raw_location['lat'] ?? null;
    $lng = $raw_location['lng'] ?? null;
    if (is_numeric($lat) && is_numeric($lng)) {
        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
            $user_location = ['lat' => $lat, 'lng' => $lng];
        }
    }
}

$distance_km = null;
if ($user_location !== null && defined('STORE_LAT') && defined('STORE_LNG')) {
    $distance_km = ai_distance_km($user_location['lat'], $user_location['lng'], STORE_LAT, STORE_LNG);
}

$now = time();
$times = $_SESSION['ai_chat_times'] ?? [];
if (!is_array($times)) $times = [];
$times = array_values(array_filter($times, static function ($t) use ($now) {
    return is_int($t) && $t > $now - AI_RATE_WINDOW;
}));
if (count($times) >= AI_RATE_LIMIT) {
    ai_json(429, ['ok' => false, 'error' => 'Terlalu banyak pesan. Coba lagi beberapa menit lagi.']);
}
$times[] = $now;
$_SESSION['ai_chat_times'] = $times;

$is_logged_in = isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'user';
$user_name = $is_logged_in ? ($_SESSION['name'] ?? null) : null;

$history = $_SESSION['ai_chat_history'] ?? [];
if (!is_array($history)) $history = [];

$result = ai_chat($conn, $message, $history, $is_logged_in, $user_name, $user_location);

if (!$result['ok']) {
    ai_json(503, ['ok' => false, 'error' => $result['error']]);
}

$history[] = ['role' => 'user', 'content' => $message];
$history[] = ['role' => 'assistant', 'content' => $result['text']];
if (count($history) > AI_MAX_HISTORY_MESSAGES) {
    $history = array_slice($history, -AI_MAX_HISTORY_MESSAGES);
}
$_SESSION['ai_chat_history'] = $history;

ai_json(200, [
    'ok' => true,
    'reply' => $result['text'],
    'remaining' => max(0, AI_RATE_LIMIT - count($times)),
    'distance_km' => $distance_km,
    'store' => [
        'name' => STORE_NAME,
        'address' => STORE_ADDRESS,
    ],
]);
