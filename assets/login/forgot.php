<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("location: ../admin/index.php");
    } else {
        header("location: ../user/index.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($email === '' || $old === '' || $new === '' || $confirm === '') {
        $error = 'Semua field wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($new) < 8) {
        $error = 'Password baru minimal 8 karakter';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password baru tidak sama';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email=?");
        if ($stmt === false) {
            error_log("forgot lookup prepare failed: " . $conn->error);
            $error = 'Terjadi kesalahan database. Silakan coba lagi.';
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$user) {
                $error = 'Email tidak terdaftar';
            } elseif (!password_verify($old, $user['password'])) {
                $error = 'Password lama salah';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                if ($stmt === false) {
                    error_log("forgot update prepare failed: " . $conn->error);
                    $error = 'Terjadi kesalahan database. Silakan coba lagi.';
                } else {
                    $stmt->bind_param("si", $hash, $user['id']);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['login_error'] = '';
                    $_SESSION['active_form'] = 'login';
                    header("location: login.php?reset=success");
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - MotorKu</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .forgot-side{position:absolute;left:0;top:0;width:50%;height:100%;background:#fff;border-right:1px solid #eee;display:flex;align-items:center;justify-content:center;color:#333;text-align:center;padding:40px;}
        .forgot-card{background:#fff;border:none;border-radius:20px;padding:28px 24px;max-width:300px;color:#333;}
        .forgot-card svg{width:180px;height:auto;display:block;margin:0 auto;}
        .forgot-back{position:absolute;top:15px;left:20px;z-index:10;background:#fff;color:#7494ec !important;text-decoration:none;font-size:15px;font-weight:600;padding:8px 16px;border:1px solid #7494ec;border-radius:8px;transition:all .3s;}
        .forgot-back:hover{background:#7494ec;color:#fff !important;}
        @media screen and (max-width:650px){.forgot-side{display:none;}}
    </style>
</head>
<body>
    <div class="container">
        <a href="login.php" class="forgot-back">&#8592; Kembali ke Login</a>
        <div class="forgot-side">
            <div class="forgot-card">
                <svg viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Gembok keamanan">
                    <path d="M60 112 L60 72 A40 40 0 0 1 140 72 L140 112" fill="none" stroke="#111" stroke-width="22"/>
                    <rect x="30" y="102" width="140" height="110" rx="22" fill="#fff" stroke="#111" stroke-width="6"/>
                    <circle cx="100" cy="146" r="14" fill="#fff" stroke="#111" stroke-width="5"/>
                    <rect x="92" y="152" width="16" height="38" rx="8" fill="#fff" stroke="#111" stroke-width="5"/>
                </svg>
            </div>
        </div>
        <div class="form-box login active" id="forgot-form">
            <form action="forgot.php" method="post">
                <?= csrf_field() ?>
                <h1>Lupa Password</h1>
                <p style="font-size:13px;color:#888;margin-bottom:12px;">Masukkan email, password lama, dan password baru yang diinginkan.</p>
                <?php if ($error !== ''): ?>
                <p class='error-message'><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="input-box">
                    <input type="password" name="old_password" placeholder="Password Lama" required>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password Baru (min 8 karakter)" required>
                </div>
                <div class="input-box">
                    <input type="password" name="password_confirm" placeholder="Konfirmasi Password Baru" required>
                </div>
                <button type="submit" class="btn">Ubah Password</button>
            </form>
        </div>
    </div>
</body>
</html>
