<?php

if (!$is_logged_in) {
?>
<div class="container">
    <h2 class="section-title">Booking Saya</h2>
    <div class="empty-state">
        <div class="icon">&#128203;</div>
        <h3>Silahkan login terlebih dahulu, untuk melanjutkan pemesanan.</h3>
        <p>Anda harus login untuk melihat riwayat booking.</p>
        <a href="../login/login.php" class="btn btn-primary">&#128274; Login Sekarang</a>
    </div>
</div>
<?php
    return;
}

$user_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT b.*, p.name, p.brand, p.image, p.price_per_day
    FROM bookings b
    JOIN products p ON b.product_id = p.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
if ($stmt === false) {
    error_log("riwayat prepare failed: " . $conn->error);
    echo '<div class="container"><div class="alert alert-error">Gagal memuat riwayat booking.</div></div>';
    return;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result ? $result : null;
if ($bookings === null) {
    echo '<div class="container"><div class="alert alert-error">Gagal memuat riwayat booking.</div></div>';
    $stmt->close();
    return;
}

$stmt2 = $conn->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('confirmed','active') THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM bookings WHERE user_id = ?
");
if ($stmt2 === false) {
    error_log("riwayat stat prepare failed: " . $conn->error);
    $stat = ['total' => 0, 'active' => 0, 'completed' => 0, 'pending' => 0];
} else {
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $stat_result = $stmt2->get_result();
    $stat = $stat_result ? $stat_result->fetch_assoc() : null;
    if (!is_array($stat)) {
        $stat = ['total' => 0, 'active' => 0, 'completed' => 0, 'pending' => 0];
    }
    $stmt2->close();
}
?>

<div class="container">
    <h2 class="section-title">Booking Saya</h2>

    <div class="stat-cards" id="statCards">
        <button type="button" class="stat-card is-active" data-filter="all" aria-pressed="true">
            <div class="stat-icon blue">&#128203;</div>
            <div class="stat-info">
                <h3><?= (int) ($stat['total'] ?? 0) ?></h3>
                <p>Total Booking</p>
            </div>
        </button>
        <button type="button" class="stat-card" data-filter="pending" aria-pressed="false">
            <div class="stat-icon orange">&#9203;</div>
            <div class="stat-info">
                <h3><?= (int) ($stat['pending'] ?? 0) ?></h3>
                <p>Menunggu</p>
            </div>
        </button>
        <button type="button" class="stat-card" data-filter="booked" aria-pressed="false">
            <div class="stat-icon teal">&#128338;</div>
            <div class="stat-info">
                <h3><?= (int) ($stat['active'] ?? 0) ?></h3>
                <p>Sudah dibooking</p>
            </div>
        </button>
        <button type="button" class="stat-card" data-filter="completed" aria-pressed="false">
            <div class="stat-icon green">&#10004;</div>
            <div class="stat-info">
                <h3><?= (int) ($stat['completed'] ?? 0) ?></h3>
                <p>Selesai</p>
            </div>
        </button>
    </div>
    <p class="filter-info" id="filterInfo" aria-live="polite">Menampilkan semua booking.</p>

    <?php if ($bookings->num_rows > 0): ?>
    <div class="table-wrapper">
        <table id="bookingTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Motor</th>
                    <th>Dipesan</th>
                    <th>Ambil - Kembali</th>
                    <th>Jumlah Hari</th>
                    <th>Total + Denda</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $bookings->fetch_assoc()): ?>
                <tr data-status="<?= htmlspecialchars($row['status']) ?>">
                    <td><?= $i++ ?></td>
                    <td>
                        <div class="motor-cell">
                            <div class="motor-thumb">
                                <?php if (!empty($row['image']) && file_exists(__DIR__ . '/../' . $row['image'])): ?>
                                    <img src="../<?= htmlspecialchars($row['image']) ?>" alt="">
                                <?php else: ?>
                                    <div class="placeholder-img"></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($row['name']) ?></strong>
                                <br><small style="color:#888;"><?= htmlspecialchars($row['brand']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= tgl_jam_id($row['created_at'] ?? $row['booking_date']) ?><br><small style="color:#888;">Ambil <?= jam_id($row['created_at'] ?? null) ?></small></td>
                    <td>
                        <?= date('d M Y', strtotime($row['pickup_date'])) ?> <?= jam_id($row['created_at'] ?? null) ?> -
                        <?= date('d M Y', strtotime($row['return_date'])) ?> <?= str_replace(':', '.', RETURN_DEADLINE_TIME) ?>
                        <br><small style="color:#888;">Batas <?= str_replace(':', '.', RETURN_DEADLINE_TIME) ?></small>
                    </td>
                    <td><strong><?= $row['total_days'] ?> hari</strong></td>
                    <td><strong style="color:#7494ec;">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></strong><?php $fine = (int) ($row['fine_amount'] ?? 0); if ($fine > 0): ?><br><small style="color:#c0392b;">+ Denda Rp <?= number_format($fine, 0, ',', '.') ?> (<?= (int) ($row['late_minutes'] ?? 0) ?> mnt)</small><?php endif; ?></td>
                    <td>
                        <?php
                        $pay_labels = ['dana' => 'Dana', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'ovo' => 'OVO', 'qris' => 'QRIS'];
                        if (!empty($row['payment_method'])):
                        ?>
                            <strong><?= $pay_labels[$row['payment_method']] ?? htmlspecialchars($row['payment_method']) ?></strong>
                            <?php if (!empty($row['payment_number'])): ?>
                                <br><small style="color:#888;"><?= htmlspecialchars($row['payment_number']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($row['payment_ref'])): ?>
                                <br><small style="color:#aaa;"><?= htmlspecialchars($row['payment_ref']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <small style="color:#aaa;">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $status_class = [
                            'pending' => 'badge-pending',
                            'confirmed' => 'badge-confirmed',
                            'active' => 'badge-active',
                            'completed' => 'badge-completed',
                            'cancelled' => 'badge-cancelled'
                        ];
                        $status_label = [
                            'pending' => 'Menunggu',
                            'confirmed' => 'Dikonfirmasi',
                            'active' => 'Sedang Disewa',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan'
                        ];
                        ?>
                        <span class="badge <?= $status_class[$row['status']] ?? '' ?>">
                            <?= $status_label[$row['status']] ?? ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <a href="index.php?page=checkout&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Bayar</a>
                        <?php elseif (in_array($row['status'], ['confirmed', 'active', 'completed'], true)): ?>
                            <a href="index.php?page=struk&id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Struk</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <tr class="filter-empty" hidden>
                    <td colspan="9">Tidak ada booking dengan status ini.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="icon">&#128203;</div>
        <h3>Belum Ada Booking</h3>
        <p>Anda belum memiliki riwayat penyewaan motor.</p>
        <a href="index.php?page=katalog" class="btn btn-primary">Sewa Motor Sekarang</a>
    </div>
    <?php endif; ?>
</div>
