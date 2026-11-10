<?php

if (!$is_logged_in) {
?>
<div class="container">
    <h2 class="section-title">Profile</h2>
    <div class="empty-state">
        <div class="profile-avatar" style="width:100px;height:100px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;font-weight:700;margin:0 auto 16px;">G</div>
        <h3>Guest</h3>
        <p>Silahkan login terlebih dahulu, untuk melanjutkan pemesanan.</p>
        <a href="../login/login.php" class="btn btn-primary btn-lg">&#128274; Login Sekarang</a>
    </div>
</div>
<?php
    return;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
if ($stmt === false) {
    error_log("profile prepare failed: " . $conn->error);
    echo '<div class="container"><div class="alert alert-error">Gagal memuat profile.</div></div>';
    return;
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$profile_result = $stmt->get_result();
$profile = $profile_result ? $profile_result->fetch_assoc() : null;
$stmt->close();
if (!$profile) {
    echo '<div class="container"><div class="alert alert-error">Profile tidak ditemukan.</div></div>';
    return;
}
?>

<div class="container">
    <h2 class="section-title">Profile Saya</h2>

    <div class="profile-layout">

        <div class="profile-card">
            <?php $ucard_photo = (!empty($profile['profile_photo']) && is_file(__DIR__ . '/../' . $profile['profile_photo'])) ? '../' . $profile['profile_photo'] : null; ?>
            <div class="profile-avatar"><?php if ($ucard_photo): ?><img src="<?= htmlspecialchars($ucard_photo) ?>" alt="Foto profile"><?php else: ?><?= strtoupper(substr($profile['name'], 0, 1)) ?><?php endif; ?></div>
            <h3><?= htmlspecialchars($profile['name']) ?></h3>
            <p><?= htmlspecialchars($profile['email']) ?></p>
            <p style="margin-top:8px;">
                <span class="badge badge-confirmed">Member</span>
            </p>
        </div>

        <div class="profile-form">
            <h2>Edit Profile</h2>
            <form action="../process_profile.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Foto Profile (jpg/png/webp, maks 2MB)</label>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                    <?php if ($ucard_photo): ?>
                    <small style="color:#888;">Foto saat ini sudah ada — pilih file baru untuk mengganti.</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Password Lama (wajib diisi bila mengganti password)</label>
                    <input type="password" name="old_password" placeholder="Masukkan password lama">
                </div>
                <div class="form-group">
                    <label>Password Baru (min 8 karakter, kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Biarkan kosong jika tidak diubah">
                </div>
                <div class="profile-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        &#10003; Update Profile
                    </button>
                    <a href="../login/logout.php" class="btn btn-danger btn-lg" onclick="this.href = '../login/logout.php?nocache=' + Date.now();">
                        &#10140; Logout
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
