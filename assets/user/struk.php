<?php
// Struk laporan hasil pembelian - login wajib, hanya pemilik, status non-pending (lunas)
if (!$is_logged_in) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Silahkan login terlebih dahulu untuk melihat struk.'];
    header("location: index.php?page=katalog");
    exit();
}

$struk_id = intval($_GET['id'] ?? 0);
if ($struk_id <= 0) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'ID booking tidak valid.'];
    header("location: index.php?page=riwayat");
    exit();
}

$stmt = $conn->prepare("
    SELECT b.*, p.name, p.brand, p.type, p.price_per_day,
           u.name AS user_name, u.email AS user_email
    FROM bookings b
    JOIN products p ON b.product_id = p.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id=? AND b.user_id=? AND b.status IN ('confirmed','active','completed')
");
if ($stmt === false) {
    error_log("struk prepare failed: " . $conn->error);
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Gagal memuat struk.'];
    header("location: index.php?page=riwayat");
    exit();
}
$stmt->bind_param("ii", $struk_id, $_SESSION['user_id']);
$stmt->execute();
$struk_result = $stmt->get_result();
$struk = $struk_result ? $struk_result->fetch_assoc() : null;
$stmt->close();

if (!$struk) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Struk tidak ditemukan. Struk hanya tersedia untuk booking yang sudah dibayar.'];
    header("location: index.php?page=riwayat");
    exit();
}

$pay_labels = ['dana' => 'Dana', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'ovo' => 'OVO', 'qris' => 'QRIS'];
$pay_label = $pay_labels[$struk['payment_method'] ?? ''] ?? ($struk['payment_method'] ? htmlspecialchars($struk['payment_method']) : '-');
$status_labels = ['confirmed' => 'Lunas (Dikonfirmasi)', 'active' => 'Lunas (Sedang Disewa)', 'completed' => 'Lunas (Selesai)'];
$status_label = $status_labels[$struk['status']] ?? ucfirst($struk['status']);
$struk_no = 'STRUK-' . str_pad((string) $struk['id'], 6, '0', STR_PAD_LEFT);
$verify_code = strtoupper(substr(md5($struk['id'] . '|' . $struk['user_id'] . '|' . $struk['created_at']), 0, 8));
$print_date = date('d M Y H:i');
?>
<style>
.struk-paper { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 28px 30px; }
.struk-paper h2 { margin: 0 0 4px; }
.struk-head { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 14px; margin-bottom: 16px; }
.struk-head .brand { font-size: 22px; font-weight: 800; }
.struk-head .sub { font-size: 12px; color: #888; }
.struk-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.struk-table td { padding: 7px 4px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
.struk-table td.k { color: #666; width: 42%; }
.struk-total td { font-weight: 800; font-size: 16px; border-top: 2px solid #333; border-bottom: none; }
.struk-foot { margin-top: 16px; font-size: 12px; color: #888; text-align: center; }
.struk-actions { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
@media print {
    .navbar, .footer, .struk-actions, .alert, .breadcrumb-print-hide { display: none !important; }
    .page-content { margin: 0 !important; padding: 0 !important; }
    .struk-paper { border: none; border-radius: 0; max-width: 100%; padding: 0; }
    body { background: #fff !important; }
}
</style>

<div class="container">
    <p class="breadcrumb-print-hide" style="margin-bottom:20px; font-size:14px;">
        <a href="index.php?page=riwayat" style="color:#7494ec; text-decoration:none;">&#8592; Kembali ke Riwayat</a>
    </p>

    <div class="struk-paper">
        <div class="struk-head">
            <div class="brand">MotorKu</div>
            <div class="sub">Rental Motor Terpercaya</div>
            <h2 style="margin-top:10px;">Struk Pembayaran</h2>
            <div class="sub"><?= htmlspecialchars($struk_no) ?> &bull; <?= htmlspecialchars($print_date) ?></div>
        </div>

        <table class="struk-table">
            <tr><td class="k">No. Struk</td><td><strong><?= htmlspecialchars($struk_no) ?></strong></td></tr>
            <tr><td class="k">Waktu Dipesan</td><td><?= tgl_jam_id($struk['created_at'] ?? $struk['booking_date']) ?></td></tr>
            <tr><td class="k">Penyewa</td><td><?= htmlspecialchars($struk['user_name']) ?><br><small style="color:#888;"><?= htmlspecialchars($struk['user_email']) ?></small></td></tr>
            <tr><td class="k">Motor</td><td><strong><?= htmlspecialchars($struk['name']) ?></strong><br><small style="color:#888;"><?= htmlspecialchars($struk['brand']) ?> &bull; <?= htmlspecialchars($struk['type']) ?></small></td></tr>
            <tr><td class="k">Tanggal Ambil</td><td><?= date('d M Y', strtotime($struk['pickup_date'])) ?> <?= jam_id($struk['created_at'] ?? null) ?></td></tr>
            <tr><td class="k">Batas Kembali</td><td><?= date('d M Y', strtotime($struk['return_date'])) ?> <?= str_replace(':', '.', RETURN_DEADLINE_TIME) ?> <br><small style="color:#888;">Operasional 05.00 - 23.00 WIB</small></td></tr>
            <tr><td class="k">Jumlah Hari</td><td><?= (int) $struk['total_days'] ?> hari</td></tr>
            <tr><td class="k">Harga per Hari</td><td>Rp <?= number_format($struk['price_per_day'], 0, ',', '.') ?></td></tr>
            <tr class="struk-total"><td class="k">Total Dibayar</td><td>Rp <?= number_format($struk['total_price'], 0, ',', '.') ?></td></tr>
            <?php $s_fine = (int) ($struk['fine_amount'] ?? 0); if ($s_fine > 0): ?>
            <tr><td class="k">Keterlambatan</td><td><?= (int) ($struk['late_minutes'] ?? 0) ?> menit (dikembalikan <?= !empty($struk['actual_return_at']) ? tgl_jam_id($struk['actual_return_at']) : '-' ?>)</td></tr>
            <tr class="struk-total"><td class="k">Denda</td><td>Rp <?= number_format($s_fine, 0, ',', '.') ?></td></tr>
            <tr class="struk-total"><td class="k">Total + Denda</td><td>Rp <?= number_format((int) $struk['total_price'] + $s_fine, 0, ',', '.') ?></td></tr>
            <?php endif; ?>
            <tr><td class="k">Metode Pembayaran</td><td><strong><?= htmlspecialchars($pay_label) ?></strong></td></tr>
            <tr><td class="k">Nomor E-Wallet</td><td><?= htmlspecialchars($struk['payment_number'] ?? '-') ?></td></tr>
            <?php if (!empty($struk['payment_ref'])): ?>
            <tr><td class="k">No. Referensi</td><td><code><?= htmlspecialchars($struk['payment_ref']) ?></code></td></tr>
            <?php endif; ?>
            <tr><td class="k">Status</td><td><span class="badge badge-confirmed"><?= htmlspecialchars($status_label) ?></span></td></tr>
            <tr><td class="k">Kode Verifikasi</td><td><code><?= htmlspecialchars($verify_code) ?></code></td></tr>
        </table>

        <div class="struk-foot">
            Simpan struk ini sebagai bukti pembayaran yang sah.<br>Terima kasih telah menyewa di MotorKu.
        </div>

        <div class="struk-actions">
            <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
            <a href="index.php?page=riwayat" class="btn btn-outline btn-lg">Kembali</a>
        </div>
    </div>
</div>
