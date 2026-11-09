<?php
// ============================================
// BOOKINGS PAGE (partial, di-include index.php)
// Tanggung jawab: tampilan daftar booking saja
// Variabel tersedia: $conn
// ============================================

$filter_q = trim($_GET['q'] ?? '');
$filter_bstatus = trim($_GET['bstatus'] ?? '');
$page_num = max(1, intval($_GET['p'] ?? 1));
$per_page = 10;
$offset = ($page_num - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
$bind_types = "";

if ($filter_q !== '') {
    $where .= " AND (u.name LIKE ? OR p.name LIKE ?)";
    $like = "%" . $filter_q . "%";
    $params[] = $like;
    $params[] = $like;
    $bind_types .= "ss";
}
if ($filter_bstatus !== '' && in_array($filter_bstatus, ['pending', 'confirmed', 'active', 'completed', 'cancelled'], true)) {
    $where .= " AND b.status=?";
    $params[] = $filter_bstatus;
    $bind_types .= "s";
}

// Total baris untuk pagination
$stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN products p ON b.product_id = p.id
    $where
");
$total_rows = 0;
$total_pages = 1;
$bookings = false;
if ($stmt === false) {
    error_log("admin bookings count prepare failed: " . $conn->error);
} else {
    if (!empty($params)) {
        $stmt->bind_param($bind_types, ...$params);
    }
    $stmt->execute();
    $count_result = $stmt->get_result();
    $count_row = $count_result ? $count_result->fetch_assoc() : null;
    $total_rows = (int) ($count_row['total'] ?? 0);
    $stmt->close();
    $total_pages = max(1, (int) ceil($total_rows / $per_page));

    // Data halaman ini
    $stmt = $conn->prepare("
        SELECT b.*, u.name AS user_name, u.email AS user_email,
               p.name AS product_name, p.brand AS product_brand, p.image AS product_image
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN products p ON b.product_id = p.id
        $where
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    if ($stmt === false) {
        error_log("admin bookings data prepare failed: " . $conn->error);
    } else {
        $params_page = array_merge($params, [$per_page, $offset]);
        $bind_page = $bind_types . "ii";
        $stmt->bind_param($bind_page, ...$params_page);
        $stmt->execute();
        $data_result = $stmt->get_result();
        $bookings = $data_result ? $data_result : false;
    }
}

$booking_label = [
    'pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'active' => 'Sedang Disewa',
    'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'
];
?>

<div class="content-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <h2 style="margin:0;border:0;padding:0;">Kelola Booking (<?= $total_rows ?>)</h2>
    </div>

    <!-- FILTER -->
    <form method="GET" action="" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <input type="hidden" name="page" value="bookings">
        <input type="text" name="q" placeholder="Cari user/motor..." value="<?= htmlspecialchars($filter_q) ?>" style="padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
        <select name="bstatus" onchange="this.form.submit()" style="padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
            <option value="">Semua Status</option>
            <?php foreach ($booking_label as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filter_bstatus === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Motor</th>
                    <th>Dipesan</th>
                    <th>Ambil - Kembali</th>
                    <th>Total + Denda</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings !== false) while ($row = $bookings->fetch_assoc()): ?>
                <tr>
                    <td>#<?= intval($row['id']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['user_name']) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($row['user_email']) ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($row['product_brand']) ?></small>
                    </td>
                    <td><?= tgl_jam_id($row['created_at'] ?? null) ?></td>
                    <td>
                        <?= date('d M Y', strtotime($row['pickup_date'])) ?> <?= jam_id($row['created_at'] ?? null) ?> -
                        <?= date('d M Y', strtotime($row['return_date'])) ?> <?= str_replace(':', '.', RETURN_DEADLINE_TIME) ?>
                        <br><small class="text-muted"><?= intval($row['total_days']) ?> hari &bull; batas <?= str_replace(':', '.', RETURN_DEADLINE_TIME) ?></small>
                        <?php if (!empty($row['actual_return_at'])): ?><br><small class="text-muted">Kembali: <?= tgl_jam_id($row['actual_return_at']) ?></small><?php endif; ?>
                    </td>
                    <td><strong class="text-accent">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></strong><?php $af = (int) ($row['fine_amount'] ?? 0); if ($af > 0): ?><br><small class="text-danger">+ Rp <?= number_format($af, 0, ',', '.') ?> (<?= (int) ($row['late_minutes'] ?? 0) ?> mnt)</small><?php endif; ?></td>
                    <td>
                        <?php
                        $pay_labels_admin = ['dana' => 'Dana', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'ovo' => 'OVO', 'qris' => 'QRIS'];
                        if (!empty($row['payment_method'])):
                        ?>
                            <strong><?= $pay_labels_admin[$row['payment_method']] ?? htmlspecialchars($row['payment_method']) ?></strong>
                            <?php if (!empty($row['payment_number'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($row['payment_number']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($row['payment_ref'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($row['payment_ref']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= $booking_label[$row['status']] ?? ucfirst($row['status']) ?></span></td>
                    <td>
                        <div class="btn-group">
                            <?php if ($row['status'] === 'pending'): ?>
                                <form action="process_booking.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="confirm">
                                    <input type="hidden" name="booking_id" value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Konfirmasi</button>
                                </form>
                                <form action="process_booking.php" method="POST" style="display:inline;" onsubmit="return confirm('Batalkan booking ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="booking_id" value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Batal</button>
                                </form>
                            <?php elseif ($row['status'] === 'confirmed'): ?>
                                <form action="process_booking.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="booking_id" value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Aktifkan</button>
                                </form>
                                <form action="process_booking.php" method="POST" style="display:inline;" onsubmit="return confirm('Batalkan booking ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="booking_id" value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Batal</button>
                                </form>
                            <?php elseif ($row['status'] === 'active'): ?>
                                <form action="process_booking.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="booking_id" value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Selesaikan</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="index.php?page=bookings&q=<?= urlencode($filter_q) ?>&bstatus=<?= urlencode($filter_bstatus) ?>&p=<?= $p ?>"
               class="btn <?= $p === $page_num ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php if (isset($stmt) && $stmt !== false) $stmt->close(); ?>
