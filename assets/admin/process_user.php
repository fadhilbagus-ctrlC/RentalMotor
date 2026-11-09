<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login/login.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Validasi CSRF untuk semua aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.'];
    header("location: index.php?page=users");
    exit();
}

// ADD USER
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $raw_password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $raw_password === '') {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Nama, email, dan password wajib diisi'];
        header("location: index.php?page=users");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Format email tidak valid'];
        header("location: index.php?page=users");
        exit();
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Role tidak valid'];
        header("location: index.php?page=users");
        exit();
    }

    if (strlen($raw_password) < 8) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Password minimal 8 karakter'];
        header("location: index.php?page=users");
        exit();
    }

    $password = password_hash($raw_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT email FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $check = $stmt->get_result();
    if ($check->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Email sudah terdaftar'];
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        $stmt->execute();
        $stmt->close();
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'User berhasil ditambahkan'];
    }
    header("location: index.php?page=users");
    exit();
}

// EDIT USER
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if ($id <= 0 || $name === '' || $email === '') {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Data tidak lengkap'];
        header("location: index.php?page=users");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Format email tidak valid'];
        header("location: index.php?page=users");
        exit();
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Role tidak valid'];
        header("location: index.php?page=users");
        exit();
    }

    $stmt = $conn->prepare("SELECT email FROM users WHERE email=? AND id!=?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $check = $stmt->get_result();
    if ($check->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Email sudah digunakan user lain'];
    } else {
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Password minimal 8 karakter'];
                $stmt->close();
                header("location: index.php?page=users");
                exit();
            }
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $role, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $role, $id);
        }
        $stmt->execute();
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'User berhasil diupdate'];
    }
    $stmt->close();
    header("location: index.php?page=users");
    exit();
}

// DELETE USER (hanya via POST, GET lama tetap didukung tapi ditolak jika bukan POST)
if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Metode hapus tidak valid. Gunakan tombol Hapus.'];
        header("location: index.php?page=users");
        exit();
    }
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'ID user tidak valid'];
        header("location: index.php?page=users");
        exit();
    }
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'User berhasil dihapus'];
    } else {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Tidak bisa menghapus akun sendiri'];
    }
    header("location: index.php?page=users");
    exit();
}

header("location: index.php");
exit();
?>
