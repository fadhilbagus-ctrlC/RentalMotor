<?php
// Query motor tersedia (prepared statement, anti SQL injection)
$search = trim($_GET['search'] ?? '');
$filter_brand = trim($_GET['brand'] ?? '');
$filter_type = trim($_GET['type'] ?? '');

$where = "WHERE status = 'available'";
$params = [];
$bind_types = "";

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR brand LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $bind_types .= "ss";
}
if (!empty($filter_brand)) {
    $where .= " AND brand = ?";
    $params[] = $filter_brand;
    $bind_types .= "s";
}
if (!empty($filter_type)) {
    $where .= " AND type = ?";
    $params[] = $filter_type;
    $bind_types .= "s";
}

$stmt = $conn->prepare("SELECT * FROM products $where ORDER BY created_at DESC");
if ($stmt === false) {
    error_log("katalog prepare failed: " . $conn->error);
    $products = false;
    $brands = false;
    $types = false;
} else {
    if (!empty($params)) {
        $stmt->bind_param($bind_types, ...$params);
    }
    $stmt->execute();
    $products = $stmt->get_result();

    // Get brands & types for filter
    $brands = $conn->query("SELECT DISTINCT brand FROM products WHERE status='available' ORDER BY brand");
    $types = $conn->query("SELECT DISTINCT type FROM products WHERE status='available' ORDER BY type");
    if ($brands === false) {
        error_log("katalog brands query failed: " . $conn->error);
    }
    if ($types === false) {
        error_log("katalog types query failed: " . $conn->error);
    }
}
if ($products === false) {
    echo '<div class="container"><div class="alert alert-error">Gagal memuat data motor. Silakan coba lagi.</div></div>';
    return;
}
?>

<!-- HERO -->
<section class="hero-shell">
    <div class="hero-banner">
        <div class="hero-copy">
            <h1>Sewa <span>Motor</span> Mudah &amp; Terpercaya</h1>
            <p>Pilihan motor terbaik untuk perjalanan Anda. Harga terjangkau, proses cepat.</p>
            <div class="hero-deal-pill">
                <a href="#motor-tersedia" class="hero-cta">Lihat Motor</a>
                <span class="hero-hours">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    05.00 &ndash; 23.00 WIB
                </span>
            </div>
        </div>
    </div>

    <div class="hero-panel">
        <nav class="hero-tabs" aria-label="Menu halaman">
            <a href="index.php?page=katalog" class="hero-tab active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                <span>Katalog</span>
            </a>
            <a href="index.php?page=riwayat" class="hero-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4a3 3 0 0 1 6 0"/><path d="M9 11h6M9 15h6"/></svg>
                <span>Booking Saya</span>
            </a>
            <a href="index.php?page=profile" class="hero-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                <span>Profil</span>
            </a>
            <?php if ($is_logged_in): ?>
            <a href="../login/logout.php" class="hero-tab" onclick="this.href = '../login/logout.php?nocache=' + Date.now();">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/></svg>
                <span>Logout</span>
            </a>
            <?php else: ?>
            <a href="../login/login.php" class="hero-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <span>Login</span>
            </a>
            <?php endif; ?>
        </nav>

        <form class="hero-search" method="GET" action="index.php">
            <input type="hidden" name="page" value="katalog">
            <div class="hero-field">
                <span class="hero-field-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5h4l3-7h3l3 7"/><path d="M9 10.5h5"/></svg>
                </span>
                <input type="text" name="search" placeholder="Cari motor atau merek..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="hero-field">
                <span class="hero-field-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l8-8h7a2 2 0 0 1 2 2v7l-8 8-9-9z"/><circle cx="16" cy="8" r="1.5"/></svg>
                </span>
                <select name="brand">
                    <option value="">Semua Merek</option>
                    <?php if ($brands !== false) while ($b = $brands->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $filter_brand === $b['brand'] ? 'selected' : '' ?>><?= htmlspecialchars($b['brand']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="hero-field">
                <span class="hero-field-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="2" fill="#fff"/><circle cx="16" cy="12" r="2" fill="#fff"/><circle cx="10" cy="17" r="2" fill="#fff"/></svg>
                </span>
                <select name="type">
                    <option value="">Semua Tipe</option>
                    <?php if ($types !== false) while ($t = $types->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($t['type']) ?>" <?= $filter_type === $t['type'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="hero-search-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                <span>Cari</span>
            </button>
        </form>

        <p class="hero-signin">
            <?php if ($is_logged_in): ?>
                Selamat datang kembali, <strong><?= htmlspecialchars($user['name'] ?? 'Member') ?></strong>. <a href="index.php?page=riwayat">Lihat booking Anda</a>
            <?php else: ?>
                <a href="../login/login.php">Login atau daftar</a> untuk pengalaman sewa yang lebih mudah.
            <?php endif; ?>
        </p>
    </div>
</section>

<div class="container">
    <!-- PRODUCT GRID -->
    <h2 class="section-title" id="motor-tersedia">Motor Tersedia</h2>

    <?php if ($products->num_rows > 0): ?>
    <div class="product-grid">
        <?php while ($p = $products->fetch_assoc()): ?>
        <div class="product-card">
            <div class="product-image">
                <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../' . $p['image'])): ?>
                    <img src="../<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <?php else: ?>
                    <div class="placeholder-img"></div>
                <?php endif; ?>
                <span class="status-badge available">Tersedia</span>
            </div>
            <div class="product-info">
                <div class="brand"><?= htmlspecialchars($p['brand']) ?></div>
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <div class="price">
                    <span class="amount">Rp <?= number_format($p['price_per_day'], 0, ',', '.') ?></span>
                    <span class="unit">/hari</span>
                </div>
                <a href="index.php?page=detail&id=<?= $p['id'] ?>" class="btn-rent">Sewa Sekarang</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="icon">&#128666;</div>
        <h3>Tidak Ada Motor</h3>
        <p>Motor tidak ditemukan atau sedang tidak tersedia.</p>
    </div>
    <?php endif; ?>
</div>
