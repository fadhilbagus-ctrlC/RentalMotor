<?php

date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$password = "";
$database = "login_db";

/*KONEK KE SQL*/
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+07:00'");

// Backward compatibility: $users dipakai di kode lama
$users = $user;

// Aturan operasional MotorKu (jam format H:i, tampil H.i dengan strip)
if (!defined('OPERATIONAL_OPEN')) define('OPERATIONAL_OPEN', '05:00');
if (!defined('OPERATIONAL_CLOSE')) define('OPERATIONAL_CLOSE', '23:00');
if (!defined('RETURN_DEADLINE_TIME')) define('RETURN_DEADLINE_TIME', '23:00');
if (!defined('FINE_PER_20MIN')) define('FINE_PER_20MIN', 10000);
if (!defined('FINE_BLOCK_MINUTES')) define('FINE_BLOCK_MINUTES', 20);

// Lokasi pengambilan motor (tampil di halaman checkout).
if (!defined('STORE_NAME')) define('STORE_NAME', 'MotorKu Yogyakarta');
if (!defined('STORE_ADDRESS')) define('STORE_ADDRESS', 'Jl. Malioboro No. 88, Suryatmajan, Danurejan, Kota Yogyakarta, DIY 55213');
if (!defined('STORE_MAPS_URL')) define('STORE_MAPS_URL', 'https://www.google.com/maps/search/?api=1&query=Jl.+Malioboro+No.+88+Yogyakarta');

// Helper: format tanggal Indonesia d M Y H.i (titik, bukan colon)
if (!function_exists('tgl_jam_id')) {
    function tgl_jam_id($datetime): string {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === '0000-00-00') return '-';
        $ts = strtotime($datetime);
        if ($ts === false) return '-';
        return date('d M Y H.i', $ts);
    }
}
// Helper: jam saja H.i
if (!function_exists('jam_id')) {
    function jam_id($datetime): string {
        if (empty($datetime)) return '-';
        $ts = strtotime($datetime);
        if ($ts === false) return '-';
        return date('H.i', $ts);
    }
}
// Helper: hitung denda dari menit telat
if (!function_exists('hitung_denda')) {
    function hitung_denda(int $late_minutes): int {
        if ($late_minutes <= 0) return 0;
        return (int) (ceil($late_minutes / FINE_BLOCK_MINUTES) * FINE_PER_20MIN);
    }
}

// CSRF: token per session, dipakai semua form POST.
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}
if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool {
        $expected = $_SESSION['csrf_token'] ?? '';
        return is_string($token) && $token !== '' && $expected !== '' && hash_equals($expected, $token);
    }
}

?>