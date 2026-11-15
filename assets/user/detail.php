<?php

$product_id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM products WHERE id=? AND status='available'");
if ($stmt === false) {
    error_log("detail prepare failed: " . $conn->error);
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Gagal memuat data motor.'];
    header("location: index.php?page=katalog");
    exit();
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$product) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Motor tidak ditemukan atau tidak tersedia.'];
    header("location: index.php?page=katalog");
    exit();
}

$today = date('Y-m-d');

$product_image_file = null;
if (!empty($product['image'])) {
    $candidates = [
        __DIR__ . '/../' . $product['image'],
        __DIR__ . '/' . $product['image'],
    ];
    foreach ($candidates as $c) {
        if (file_exists($c)) {
            $product_image_file = $c;
            break;
        }
    }
}
?>

<div class="container">

    <a href="index.php?page=katalog" class="detail-back-link">&#8592; Kembali ke Katalog</a>

    <div class="detail-layout">

        <div class="detail-image">
            <?php if ($product_image_file): ?>
                <img src="../<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div class="placeholder-img"></div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <span class="brand-tag"><?= htmlspecialchars($product['brand']) ?></span>
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <p class="type-tag">Tipe: <?= htmlspecialchars($product['type']) ?></p>

            <div class="price-section">
                <div class="price-row">
                    <span class="label">Harga per Hari</span>
                    <span class="value">Rp <?= number_format($product['price_per_day'], 0, ',', '.') ?></span>
                </div>
                <div class="price-row">
                    <span class="label">Harga per Minggu</span>
                    <span class="value">Rp <?= number_format($product['price_per_week'], 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="info-row">
                <span>Status: <span class="badge badge-available">Tersedia</span></span>
            </div>

            <div class="description">
                <h3>Deskripsi</h3>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
        </div>
    </div>

    <div class="booking-form">
        <h2>&#128197; Form Booking Sewa Motor</h2>
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px;">
            &#128336; Jam operasional 05.00 - 23.00 WIB. Jam ambil mengikuti jam saat booking dibuat.
            Batas kembali pukul 23.00 pada tanggal kembali.
        </div>
        <?php if (!$is_logged_in): ?>
        <div class="alert alert-info" style="text-align:center;padding:24px;">
            <p style="font-size:16px;margin-bottom:16px;">Silahkan login terlebih dahulu, untuk melanjutkan pemesanan.</p>
            <a href="../login/login.php" class="btn btn-primary btn-lg">&#128274; Login untuk Booking</a>
        </div>
        <?php else: ?>
        <form method="POST" action="process_booking.php" id="bookingForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="price_per_day" id="price_per_day" value="<?= $product['price_per_day'] ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Ambil</label>
                    <input type="date" name="pickup_date" id="pickup_date" min="<?= $today ?>" required onchange="hitungHarga()">
                </div>
                <div class="form-group">
                    <label>Tanggal Kembali</label>
                    <input type="date" name="return_date" id="return_date" min="<?= $today ?>" required onchange="hitungHarga()">
                </div>
            </div>

            <div class="booking-summary" id="bookingSummary">
                <h3>Ringkasan Booking</h3>
                <div class="summary-row">
                    <span class="label">Motor</span>
                    <span class="value"><?= htmlspecialchars($product['name']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Tanggal Ambil</span>
                    <span class="value" id="display_pickup">-</span>
                </div>
                <div class="summary-row">
                    <span class="label">Tanggal Kembali</span>
                    <span class="value" id="display_return">-</span>
                </div>
                <div class="summary-row">
                    <span class="label">Jumlah Hari</span>
                    <span class="value" id="display_days">0 hari</span>
                </div>
                <div class="summary-row">
                    <span class="label">Harga per Hari</span>
                    <span class="value">Rp <?= number_format($product['price_per_day'], 0, ',', '.') ?></span>
                </div>
                <div class="summary-row total">
                    <span class="label">Total Harga</span>
                    <span class="value" id="display_total">Rp 0</span>
                </div>
            </div>

            <div class="alert alert-warning" style="margin-top:16px;font-size:13px;">
                &#9888; Perhatian: pengembalian lewat pukul 23.00 WIB dikenakan denda Rp10.000 / 20 menit.
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary btn-lg btn-block" id="btnBooking">
                    &#128179; Booking Sekarang
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
