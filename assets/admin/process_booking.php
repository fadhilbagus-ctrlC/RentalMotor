<?php
// ============================================
// PROCESS BOOKING (Admin)
// Tanggung jawab: update status booking saja
// Alur valid: pending->confirmed/cancelled,
// confirmed->active/cancelled, active->completed/cancelled
// ============================================
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login/login.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function redirect_bookings(string $type, string $text): void {
    $_SESSION['msg'] = ['type' => $type, 'text' => $text];
    header("location: index.php?page=bookings");
    exit();
}

// Validasi CSRF untuk semua aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['csrf_token'] ?? null)) {
    redirect_bookings('error', 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_bookings('error', 'Metode tidak valid. Gunakan tombol aksi.');
}

$booking_id = intval($_POST['booking_id'] ?? $_POST['id'] ?? 0);
if ($booking_id <= 0) {
    redirect_bookings('error', 'ID booking tidak valid');
}

$stmt = $conn->prepare("SELECT * FROM bookings WHERE id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    redirect_bookings('error', 'Booking tidak ditemukan');
}

$current = $booking['status'];
$product_id = (int) $booking['product_id'];

// Tentukan status tujuan berdasarkan aksi
$target = '';
switch ($action) {
    case 'confirm':  // pending -> confirmed (motor jadi rented)
        if ($current !== 'pending') {
            redirect_bookings('error', 'Hanya booking pending yang bisa dikonfirmasi');
        }
        $target = 'confirmed';
        break;
    case 'activate': // confirmed -> active
        if ($current !== 'confirmed') {
            redirect_bookings('error', 'Hanya booking confirmed yang bisa diaktifkan');
        }
        $target = 'active';
        break;
    case 'complete': // active -> completed (motor kembali available)
        if ($current !== 'active') {
            redirect_bookings('error', 'Hanya booking active yang bisa diselesaikan');
        }
        $target = 'completed';
        break;
    case 'cancel':   // pending/confirmed/active -> cancelled
        if (!in_array($current, ['pending', 'confirmed', 'active'], true)) {
            redirect_bookings('error', 'Booking tidak bisa dibatalkan');
        }
        $target = 'cancelled';
        break;
    default:
        redirect_bookings('error', 'Aksi tidak valid');
}

// Update status booking (dengan hitungan denda bila complete)
if ($target === 'completed') {
    $actual = date('Y-m-d H:i:s');
    $deadline_str = ($booking['return_date'] ?? date('Y-m-d')) . ' ' . RETURN_DEADLINE_TIME . ':00';
    $late_minutes = max(0, (int) ((strtotime($actual) - strtotime($deadline_str)) / 60));
    $fine = hitung_denda($late_minutes);
    $stmt = $conn->prepare("UPDATE bookings SET status=?, actual_return_at=?, late_minutes=?, fine_amount=? WHERE id=?");
    $stmt->bind_param("ssiii", $target, $actual, $late_minutes, $fine, $booking_id);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $target, $booking_id);
    $stmt->execute();
    $stmt->close();
}

// Sinkronkan status motor
if ($target === 'confirmed' || $target === 'active') {
    $stmt = $conn->prepare("UPDATE products SET status='rented' WHERE id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
} elseif ($target === 'completed' || $target === 'cancelled') {
    // Kembalikan ke available hanya jika tidak ada booking aktif lain
    $stmt = $conn->prepare("
        SELECT id FROM bookings
        WHERE product_id=? AND id!=? AND status IN ('pending','confirmed','active')
        LIMIT 1
    ");
    $stmt->bind_param("ii", $product_id, $booking_id);
    $stmt->execute();
    $other = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$other) {
        $stmt = $conn->prepare("UPDATE products SET status='available' WHERE id=?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
    }
}

redirect_bookings('success', "Booking #$booking_id diubah menjadi $target" . (isset($fine) && $fine > 0 ? " (telat " . (int) $late_minutes . " menit, denda Rp" . number_format($fine, 0, ',', '.') . ")" : ""));
?>