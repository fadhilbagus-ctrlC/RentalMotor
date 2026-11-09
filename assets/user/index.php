<?php
session_start();
require_once __DIR__ . '/../config.php';

// Cek status login (guest atau user)
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['role'] === 'user';

// Get user profile jika sudah login
$user = null;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    if ($stmt !== false) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    } else {
        error_log("index user lookup failed: " . $conn->error);
    }
}

// Flash message
$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

// Penanda baru saja logout (dari logout.php?logged_out=1) agar jelas bukan halaman login
if (!$msg && !$is_logged_in && ($_GET['logged_out'] ?? '') === '1') {
    $msg = ['type' => 'success', 'text' => 'Berhasil logout. Selamat datang sebagai Guest.'];
}

// Route pages
$page = $_GET['page'] ?? 'katalog';
$valid_pages = ['katalog', 'detail', 'checkout', 'riwayat', 'profile', 'struk', 'bayar_kembali'];
if (!in_array($page, $valid_pages)) {
    $page = 'katalog';
}

// Flash markup: overlay dari navbar di halaman katalog, alur normal di halaman lain
$flash_class = $page === 'katalog' ? 'flash-wrap' : 'flash-wrap flash-flow';
$flash_html = '';
if ($msg) {
    $flash_html = '<div class="' . $flash_class . '">'
        . '<div class="alert alert-' . htmlspecialchars($msg['type']) . '">'
        . htmlspecialchars($msg['text'])
        . '</div></div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MotorKu - Rental Motor</title>
    <link rel="stylesheet" href="user.css?v=<?= filemtime(__DIR__ . '/user.css') ?>">
    <link rel="stylesheet" href="../asisten/chat.css?v=<?= filemtime(__DIR__ . '/../asisten/chat.css') ?>">
</head>
<body class="page-<?= htmlspecialchars($page) ?>">

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <img src="../login/logo-white.png" alt="MotorKu" class="navbar-logo">
        <h2>MotorKu</h2>
        <span>Rental Motor</span>
    </a>
    <div class="navbar-user">
        <?php if ($is_logged_in && !empty($user)): ?>
        <a href="index.php?page=profile" class="navbar-profile-link" title="Lihat Profile Saya">
            <div class="user-details">
                <div class="name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="role">Member</div>
            </div>
            <div class="avatar"><?php $nav_photo = (!empty($user['profile_photo']) && is_file(__DIR__ . '/../' . $user['profile_photo'])) ? '../' . $user['profile_photo'] : null; ?><?php if ($nav_photo): ?><img src="<?= htmlspecialchars($nav_photo) ?>" alt="Foto"><?php else: ?><?= strtoupper(substr($user['name'], 0, 1)) ?><?php endif; ?></div>
        </a>
        <?php else: ?>
        <a href="index.php?page=profile" class="navbar-profile-link" title="Lihat Profile Pengunjung">
            <div class="user-details">
                <div class="name">Guest</div>
                <div class="role">Pengunjung</div>
            </div>
            <div class="avatar">G</div>
        </a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($msg && $page === 'katalog'): ?>
<div class="flash-anchor"><?= $flash_html ?></div>
<?php elseif ($msg): ?>
<?= $flash_html ?>
<?php endif; ?>

<!-- PAGE CONTENT -->
<div class="page-content">
<?php include __DIR__ . '/' . $page . '.php'; ?>
</div>

<!-- FOOTER -->
<footer class="footer">
    <p>&copy; MotorKu - Rental Motor Terpercaya</p>
</footer>

<?php include __DIR__ . '/../asisten/chat_widget.php'; ?>

<script src="user.js?v=<?= filemtime(__DIR__ . '/user.js') ?>"></script>
<script src="../asisten/chat.js?v=<?= filemtime(__DIR__ . '/../asisten/chat.js') ?>"></script>
</body>
</html>
