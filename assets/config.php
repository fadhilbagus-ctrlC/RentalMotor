<?php

date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$password = "";
$database = "login_db";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+07:00'");

$users = $user;

if (!defined('OPERATIONAL_OPEN')) define('OPERATIONAL_OPEN', '05:00');
if (!defined('OPERATIONAL_CLOSE')) define('OPERATIONAL_CLOSE', '23:00');
if (!defined('RETURN_DEADLINE_TIME')) define('RETURN_DEADLINE_TIME', '23:00');
if (!defined('FINE_PER_20MIN')) define('FINE_PER_20MIN', 10000);
if (!defined('FINE_BLOCK_MINUTES')) define('FINE_BLOCK_MINUTES', 20);

if (!defined('STORE_NAME')) define('STORE_NAME', 'MotorKu Yogyakarta');
if (!defined('STORE_ADDRESS')) define('STORE_ADDRESS', 'Jl. Malioboro No. 88, Suryatmajan, Danurejan, Kota Yogyakarta, DIY 55213');
if (!defined('STORE_LAT')) define('STORE_LAT', -7.79325);
if (!defined('STORE_LNG')) define('STORE_LNG', 110.36578);

if (!function_exists('tgl_jam_id')) {
    function tgl_jam_id($datetime): string {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === '0000-00-00') return '-';
        $ts = strtotime($datetime);
        if ($ts === false) return '-';
        return date('d M Y H.i', $ts);
    }
}

if (!function_exists('jam_id')) {
    function jam_id($datetime): string {
        if (empty($datetime)) return '-';
        $ts = strtotime($datetime);
        if ($ts === false) return '-';
        return date('H.i', $ts);
    }
}

if (!function_exists('hitung_denda')) {
    function hitung_denda(int $late_minutes): int {
        if ($late_minutes <= 0) return 0;
        return (int) (ceil($late_minutes / FINE_BLOCK_MINUTES) * FINE_PER_20MIN);
    }
}

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