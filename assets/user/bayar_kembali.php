<?php
// Halaman kembali dari pembayaran Xendit (success_redirect_url).
// Mengecek status invoice terbaru via API; bila PAID -> konfirmasi + ke struk.
// Login wajib, hanya pemilik booking.
if (!$is_logged_in) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Silahkan login terlebih dahulu.'];
    header("location: index.php?page=katalog");
    exit();
}

require_once __DIR__ . '/payment_gateway.php';

$ref = trim($_GET['ref'] ?? '');
if ($ref === '') {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Referensi pembayaran tidak valid.'];
    header("location: index.php?page=riwayat");
    exit();
}

$stmt = $conn->prepare("
    SELECT b.*, p.name, p.brand, p.price_per_day
    FROM bookings b
    JOIN products p ON b.product_id = p.id
    WHERE b.payment_ref=? AND b.user_id=?
");
if ($stmt === false) {
    error_log("bayar_kembali prepare failed: " . $conn->error);
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Gagal memuat data pembayaran.'];
    header("location: index.php?page=riwayat");
    exit();
}
$stmt->bind_param("si", $ref, $_SESSION['user_id']);
$stmt->execute();
$bk_result = $stmt->get_result();
$bk = $bk_result ? $bk_result->fetch_assoc() : null;
$stmt->close();

if (!$bk) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Data pembayaran tidak ditemukan.'];
    header("location: index.php?page=riwayat");
    exit();
}

// Sudah lunas sebelumnya -> langsung struk.
if ($bk['status'] !== 'pending') {
    header("location: index.php?page=struk&id=" . (int) $bk['id']);
    exit();
}

$inv = xendit_invoice_by_ref($ref);
$inv_status = is_array($inv) ? strtoupper($inv['status'] ?? '') : '';

if ($inv_status === 'PAID') {
    $paid_at = null;
    if (!empty($inv['paid_at'])) {
        $ts = strtotime($inv['paid_at']);
        if ($ts !== false) {
            $paid_at = date('Y-m-d H:i:s', $ts);
        }
    }
    confirm_booking_paid($conn, (int) $bk['id'], (int) $bk['product_id'], $paid_at);
    $_SESSION['msg'] = ['type' => 'success', 'text' => "Pembayaran booking #{$bk['id']} berhasil! Struk tersedia di bawah."];
    header("location: index.php?page=struk&id=" . (int) $bk['id']);
    exit();
}

$pay_labels = ['dana' => 'Dana', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'ovo' => 'OVO', 'qris' => 'QRIS'];
$pay_label = $pay_labels[$bk['payment_method'] ?? ''] ?? '-';
?>
<div class="container">
    <p style="margin-bottom:20px; font-size:14px;">
        <a href="index.php?page=riwayat" style="color:#7494ec; text-decoration:none;">&#8592; Kembali ke Riwayat</a>
    </p>
    <h2 class="section-title">Menunggu Pembayaran</h2>
    <div class="checkout-card" style="max-width:640px;margin:0 auto;">
        <div class="booking-summary">
            <div class="summary-row"><span class="label">Booking</span><span class="value">#<?= (int) $bk['id'] ?> - <?= htmlspecialchars($bk['name']) ?></span></div>
            <div class="summary-row"><span class="label">Total</span><span class="value">Rp <?= number_format($bk['total_price'], 0, ',', '.') ?></span></div>
            <div class="summary-row"><span class="label">Metode</span><span class="value"><?= htmlspecialchars($pay_label) ?></span></div>
            <div class="summary-row"><span class="label">Status Invoice</span><span class="value"><?= htmlspecialchars($inv_status !== '' ? $inv_status : 'Tidak diketahui') ?></span></div>
        </div>
        <p style="font-size:13px;color:#888;margin-top:12px;">Pembayaran belum terdeteksi. Selesaikan di halaman Xendit, lalu klik cek status. Status juga terkonfirmasi otomatis via webhook.</p>
        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
            <?php if (!empty($bk['payment_url'])): ?>
            <a href="<?= htmlspecialchars($bk['payment_url']) ?>" class="btn btn-primary btn-lg">Lanjut Bayar</a>
            <?php endif; ?>
            <a href="index.php?page=bayar_kembali&ref=<?= urlencode($ref) ?>" class="btn btn-outline btn-lg">Cek Status</a>
        </div>
    </div>
</div>
