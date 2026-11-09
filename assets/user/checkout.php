<?php
// Checkout page - harus login dulu, tetap di index sebagai guest
if (!$is_logged_in) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Silahkan login terlebih dahulu, untuk melanjutkan pemesanan.'];
    header("location: index.php?page=katalog");
    exit();
}

// Tampilkan ringkasan booking sebelum konfirmasi
$booking_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT b.*, p.name, p.brand, p.type, p.image, p.price_per_day
    FROM bookings b
    JOIN products p ON b.product_id = p.id
    WHERE b.id=? AND b.user_id=? AND b.status='pending'
");
if ($stmt === false) {
    error_log("checkout prepare failed: " . $conn->error);
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Gagal memuat data booking.'];
    header("location: index.php?page=riwayat");
    exit();
}
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$booking) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Booking tidak ditemukan atau sudah diproses.'];
    header("location: index.php?page=riwayat");
    exit();
}
?>

<div class="container">
    <p style="margin-bottom:20px; font-size:14px;">
        <a href="index.php?page=riwayat" style="color:#7494ec; text-decoration:none;">&#8592; Kembali ke Riwayat</a>
    </p>

    <h2 class="section-title">Checkout Booking #<?= $booking['id'] ?></h2>

    <div class="checkout-layout">
        <!-- DETAIL BOOKING -->
        <div class="checkout-card">
            <h2>&#128666; Detail Motor</h2>
            <div class="checkout-item">
                <div class="item-image">
                    <?php if (!empty($booking['image']) && file_exists(__DIR__ . '/../' . $booking['image'])): ?>
                        <img src="../<?= htmlspecialchars($booking['image']) ?>" alt="<?= htmlspecialchars($booking['name']) ?>">
                    <?php else: ?>
                        <div class="placeholder-img"></div>
                    <?php endif; ?>
                </div>
                <div class="item-details">
                    <p style="font-size:12px;color:#888;text-transform:uppercase;"><?= htmlspecialchars($booking['brand']) ?></p>
                    <h3><?= htmlspecialchars($booking['name']) ?></h3>
                    <p>Tipe: <?= htmlspecialchars($booking['type']) ?></p>
                    <p class="price">Rp <?= number_format($booking['price_per_day'], 0, ',', '.') ?> /hari</p>
                </div>
            </div>

            <div style="margin-top:20px;">
                <div class="info-row" style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;">
                    <span style="width:20px;text-align:center;color:#7494ec;">&#128197;</span>
                    <span><strong>Waktu Dipesan:</strong> <?= tgl_jam_id($booking['created_at'] ?? null) ?></span>
                </div>
                <div class="info-row" style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;">
                    <span style="width:20px;text-align:center;color:#7494ec;">&#128197;</span>
                    <span><strong>Tanggal Ambil:</strong> <?= date('d M Y', strtotime($booking['pickup_date'])) ?> <?= jam_id($booking['created_at'] ?? null) ?></span>
                </div>
                <div class="info-row" style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;">
                    <span style="width:20px;text-align:center;color:#7494ec;">&#128197;</span>
                    <span><strong>Batas Kembali:</strong> <?= date('d M Y', strtotime($booking['return_date'])) ?> <?= str_replace(':', '.', RETURN_DEADLINE_TIME) ?> <small style="color:#888;">(Operasional 05.00 - 23.00 WIB)</small></span>
                </div>
            </div>
        </div>

        <!-- RINGKASAN HARGA -->
        <div class="checkout-card">
            <h2>&#128176; Ringkasan Harga</h2>

            <div class="booking-summary">
                <div class="summary-row">
                    <span class="label">Harga per Hari</span>
                    <span class="value">Rp <?= number_format($booking['price_per_day'], 0, ',', '.') ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Jumlah Hari</span>
                    <span class="value"><?= $booking['total_days'] ?> hari</span>
                </div>
                <div class="summary-row total">
                    <span class="label">Total Harga</span>
                    <span class="value">Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></span>
                </div>
            </div>

            <form method="POST" action="process_booking.php" style="margin-top:20px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                <h2 style="margin-bottom:12px;">&#128179; Metode Pembayaran</h2>
                <?php require_once __DIR__ . '/payment_gateway.php'; ?>
                <?php if (PAYMENT_MODE === 'xendit' && payment_is_configured()): ?>
                <p style="font-size:13px;color:#888;margin-bottom:12px;">Pilih e-wallet, masukkan nomor Anda, lalu klik Konfirmasi — Anda diarahkan ke <strong>halaman bayar Xendit</strong> sebesar <strong>Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></strong>. Struk terbit otomatis setelah lunas.</p>
                <?php else: ?>
                <p style="font-size:13px;color:#888;margin-bottom:12px;">Pilih salah satu e-wallet, bayar via aplikasinya sebesar <strong>Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></strong> dengan berita <strong>BOOKING-<?= $booking['id'] ?></strong>, lalu masukkan nomor e-wallet milik Anda dan konfirmasi.</p>
                <?php endif; ?>
                <?php
                $ewallets = [
                    'dana' => ['label' => 'Dana', 'app' => 'dana://', 'web' => 'https://link.dana.id/', 'guide' => 'Buka Dana → Kirim → masukkan nominal pas → cantumkan ID booking di catatan.'],
                    'gopay' => ['label' => 'GoPay', 'app' => 'gojek://gopay', 'web' => 'https://www.gojek.com/gopay/', 'guide' => 'Buka Gojek → GoPay → Bayar → masukkan nominal pas → cantumkan ID booking di catatan.'],
                    'shopeepay' => ['label' => 'ShopeePay', 'app' => 'shopeepay://', 'web' => 'https://shopee.co.id/', 'guide' => 'Buka Shopee → ShopeePay → Transfer → masukkan nominal pas → cantumkan ID booking di catatan.'],
                    'ovo' => ['label' => 'OVO', 'app' => 'ovo://', 'web' => 'https://www.ovo.id/', 'guide' => 'Buka OVO → Transfer → masukkan nominal pas → cantumkan ID booking di catatan.'],
                    'qris' => ['label' => 'QRIS', 'app' => '', 'web' => '', 'guide' => 'Pindai kode QRIS MotorKu dengan aplikasi e-wallet apapun → masukkan nominal pas → simpan bukti.'],
                ];
                ?>
                <div class="payment-methods" style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px;">
                    <?php foreach ($ewallets as $key => $w): ?>
                    <div style="padding:12px;border:1px solid #e0e0e0;border-radius:8px;font-size:14px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="radio" name="payment_method" value="<?= $key ?>" required>
                            <strong><?= $w['label'] ?></strong>
                        </label>
                        <p style="font-size:12px;color:#666;margin:8px 0;"><?= $w['guide'] ?></p>
                        <?php if ($w['app'] !== ''): ?>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="<?= $w['app'] ?>" class="btn btn-primary btn-sm">Buka aplikasi <?= $w['label'] ?></a>
                            <a href="<?= $w['web'] ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Belum punya? Unduh</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="payment_number">Nomor E-Wallet Anda</label>
                    <input type="text" name="payment_number" id="payment_number" placeholder="08xx-xxxx-xxxx" maxlength="50" required
                        style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
                </div>

                <button type="submit" class="btn btn-success btn-lg btn-block">
                    &#10003; Konfirmasi & Bayar
                </button>
            </form>

            <form method="POST" action="process_booking.php" style="margin-top:10px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <button type="submit" class="btn btn-outline btn-block" onclick="return confirm('Yakin batalkan booking ini?')">
                    Batalkan Booking
                </button>
            </form>
        </div>
    </div>
</div>
