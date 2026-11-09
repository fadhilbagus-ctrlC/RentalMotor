<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("location: login/login.php");
    exit();
}

// Upload foto profile: return ['ok'=>true,'path'=>?string] (path null = tidak ada file baru).
// path relatif dari folder assets/, misal 'uploads/profiles/profile_14_....jpg'.
function upload_profile_photo(array $file, int $user_id): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Upload foto gagal (kode ' . (int) $file['error'] . ')'];
    }
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        return ['ok' => false, 'msg' => 'Format foto harus jpg, jpeg, png, atau webp'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'msg' => 'Ukuran foto maksimal 2MB'];
    }
    // Tolak file yang bukan gambar asli (anti file samaran).
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'msg' => 'File bukan gambar yang valid'];
    }
    $dir = __DIR__ . '/uploads/profiles';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = 'profile_' . $user_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'msg' => 'Gagal menyimpan file foto'];
    }
    return ['ok' => true, 'path' => 'uploads/profiles/' . $filename];
}

function delete_profile_photo(?string $photo): void {
    if (empty($photo)) {
        return;
    }
    // Keamanan: hanya hapus di dalam folder uploads/profiles.
    $base = realpath(__DIR__ . '/uploads/profiles');
    $file = realpath(__DIR__ . '/' . $photo);
    if ($base !== false && $file !== false && strpos($file, $base) === 0 && is_file($file)) {
        @unlink($file);
    }
}

function profile_prepare_or_fail($conn, string $sql) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("process_profile prepare failed: " . $conn->error . " | SQL: " . $sql);
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Terjadi kesalahan database. Silakan coba lagi.'];
        $redirect = ($_SESSION['role'] ?? '') === 'admin' ? 'admin/index.php?page=profile' : 'user/index.php?page=profile';
        header("location: $redirect");
        exit();
    }
    return $stmt;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.'];
    } elseif ($name === '' || $email === '') {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Nama dan email wajib diisi'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Format email tidak valid'];
    } else {
        $stmt = profile_prepare_or_fail($conn, "SELECT email, password, profile_photo FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $cur_result = $stmt->get_result();
        $current = $cur_result ? $cur_result->fetch_assoc() : null;
        $stmt->close();

        if (!$current) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Akun tidak ditemukan'];
        } else {
            $stmt = profile_prepare_or_fail($conn, "SELECT email FROM users WHERE email=? AND id!=?");
            $stmt->bind_param("si", $email, $id);
            $stmt->execute();
            $check = $stmt->get_result();
            $taken = $check ? $check->num_rows > 0 : false;
            $stmt->close();

            if ($taken) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Email sudah digunakan user lain'];
            } else {
                // Proses upload foto (opsional).
                $upload = upload_profile_photo($_FILES['photo'] ?? [], $id);
                if (!$upload['ok']) {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => $upload['msg']];
                } else {
                    $photo = $upload['path'] ?? $current['profile_photo'];
                    if ($upload['path'] !== null && !empty($current['profile_photo'])) {
                        delete_profile_photo($current['profile_photo']);
                    }
                    if (!empty($_POST['password'])) {
                        if (strlen($_POST['password']) < 8) {
                            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Password minimal 8 karakter'];
                            // Foto baru yang terlanjur terupload dibatalkan agar tidak yatim.
                            if ($upload['path'] !== null) {
                                delete_profile_photo($upload['path']);
                            }
                        } elseif (empty($_POST['old_password']) || !password_verify($_POST['old_password'], $current['password'])) {
                            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Password lama salah'];
                            // Foto baru yang terlanjur terupload dibatalkan agar tidak yatim.
                            if ($upload['path'] !== null) {
                                delete_profile_photo($upload['path']);
                            }
                        } else {
                            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                            $stmt = profile_prepare_or_fail($conn, "UPDATE users SET name=?, email=?, password=?, profile_photo=? WHERE id=?");
                            $stmt->bind_param("ssssi", $name, $email, $password, $photo, $id);
                            $stmt->execute();
                            $stmt->close();
                            $_SESSION['name'] = $name;
                            $_SESSION['email'] = $email;
                            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Profile berhasil diupdate'];
                        }
                    } else {
                        $stmt = profile_prepare_or_fail($conn, "UPDATE users SET name=?, email=?, profile_photo=? WHERE id=?");
                        $stmt->bind_param("sssi", $name, $email, $photo, $id);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Profile berhasil diupdate'];
                    }
                }
            }
        }
    }
}

$redirect = ($_SESSION['role'] ?? '') === 'admin' ? 'admin/index.php?page=profile' : 'user/index.php?page=profile';
header("location: $redirect");
exit();
?>
