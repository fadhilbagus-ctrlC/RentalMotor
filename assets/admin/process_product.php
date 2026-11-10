<?php

session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login/login.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$allowed_status = ['available', 'rented', 'maintenance'];

function upload_product_image(array $file): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Upload gambar gagal (kode ' . (int) $file['error'] . ')'];
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        return ['ok' => false, 'msg' => 'Format gambar harus jpg, jpeg, png, atau webp'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'msg' => 'Ukuran gambar maksimal 2MB'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'msg' => 'File bukan gambar yang valid'];
    }

    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = 'motor_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'msg' => 'Gagal menyimpan file gambar'];
    }

    return ['ok' => true, 'path' => 'uploads/' . $filename];
}

function delete_product_image(?string $image): void {
    if (empty($image)) {
        return;
    }
    $file = __DIR__ . '/../' . $image;
    if (is_file($file)) {
        @unlink($file);
    }
}

function redirect_products(string $type, string $text): void {
    $_SESSION['msg'] = ['type' => $type, 'text' => $text];
    header("location: index.php?page=products");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['csrf_token'] ?? null)) {
    redirect_products('error', 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.');
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $brand         = trim($_POST['brand'] ?? '');
    $type          = trim($_POST['type'] ?? '');
    $price_per_day = intval($_POST['price_per_day'] ?? 0);
    $price_per_week = intval($_POST['price_per_week'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $status        = $_POST['status'] ?? 'available';

    if ($name === '' || $brand === '' || $type === '') {
        redirect_products('error', 'Nama, brand, dan tipe wajib diisi');
    }
    if ($price_per_day <= 0) {
        redirect_products('error', 'Harga per hari harus lebih dari 0');
    }
    if ($price_per_week < 0) {
        redirect_products('error', 'Harga per minggu tidak valid');
    }
    if (!in_array($status, $allowed_status, true)) {
        redirect_products('error', 'Status tidak valid');
    }

    $upload = upload_product_image($_FILES['image'] ?? []);
    if (!$upload['ok']) {
        redirect_products('error', $upload['msg']);
    }
    $image = $upload['path'] ?? 'default-motor.jpg';

    $stmt = $conn->prepare("
        INSERT INTO products (name, brand, type, price_per_day, price_per_week, image, description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssiisss", $name, $brand, $type, $price_per_day, $price_per_week, $image, $description, $status);
    $stmt->execute();
    $stmt->close();

    redirect_products('success', 'Motor berhasil ditambahkan');
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = intval($_POST['id'] ?? 0);
    $name          = trim($_POST['name'] ?? '');
    $brand         = trim($_POST['brand'] ?? '');
    $type          = trim($_POST['type'] ?? '');
    $price_per_day = intval($_POST['price_per_day'] ?? 0);
    $price_per_week = intval($_POST['price_per_week'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $status        = $_POST['status'] ?? 'available';

    if ($id <= 0 || $name === '' || $brand === '' || $type === '') {
        redirect_products('error', 'Data tidak lengkap');
    }
    if ($price_per_day <= 0) {
        redirect_products('error', 'Harga per hari harus lebih dari 0');
    }
    if ($price_per_week < 0) {
        redirect_products('error', 'Harga per minggu tidak valid');
    }
    if (!in_array($status, $allowed_status, true)) {
        redirect_products('error', 'Status tidak valid');
    }

    $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$old) {
        redirect_products('error', 'Motor tidak ditemukan');
    }

    $image = $old['image'];
    $upload = upload_product_image($_FILES['image'] ?? []);
    if (!$upload['ok']) {
        redirect_products('error', $upload['msg']);
    }
    if ($upload['path'] !== null) {
        delete_product_image($old['image']);
        $image = $upload['path'];
    }

    $stmt = $conn->prepare("
        UPDATE products
        SET name=?, brand=?, type=?, price_per_day=?, price_per_week=?, image=?, description=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("sssiissss", $name, $brand, $type, $price_per_day, $price_per_week, $image, $description, $status, $id);
    $stmt->execute();
    $stmt->close();

    redirect_products('success', 'Motor berhasil diupdate');
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_products('error', 'Metode hapus tidak valid. Gunakan tombol Hapus.');
    }
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_products('error', 'ID motor tidak valid');
    }

    $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        redirect_products('error', 'Motor tidak ditemukan');
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    delete_product_image($row['image']);

    redirect_products('success', 'Motor berhasil dihapus');
}

header("location: index.php?page=products");
exit();
?>