<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Silahkan login terlebih dahulu, untuk melanjutkan pemesanan.'];
    header("location: index.php?page=katalog");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.'];
    $back = in_array($action, ['confirm', 'cancel'], true) ? 'index.php?page=riwayat' : 'index.php?page=katalog';
    header("location: $back");
    exit();
}

function db_prepare_or_redirect($conn, string $sql, string $redirect) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("process_booking prepare failed: " . $conn->error . " | SQL: " . $sql);
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Terjadi kesalahan database. Silakan coba lagi.'];
        header("location: $redirect");
        exit();
    }
    return $stmt;
}

switch ($action) {
    case 'add':

        $product_id = intval($_POST['product_id'] ?? 0);
        $pickup_date = trim($_POST['pickup_date'] ?? '');
        $return_date = trim($_POST['return_date'] ?? '');

        $today = date('Y-m-d');

        if (empty($pickup_date) || empty($return_date) || $product_id <= 0) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Semua field harus diisi.'];
            header("location: index.php?page=detail&id=$product_id");
            exit();
        }

        $now_time = date('H:i');
        if ($now_time < OPERATIONAL_OPEN || $now_time > OPERATIONAL_CLOSE) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Booking hanya bisa dilakukan pada jam operasional 05.00 - 23.00 WIB. Sekarang ' . date('H.i') . '.'];
            header("location: index.php?page=detail&id=$product_id");
            exit();
        }

        $d_pickup = DateTime::createFromFormat('Y-m-d', $pickup_date);
        $d_return = DateTime::createFromFormat('Y-m-d', $return_date);
        if (!$d_pickup || !$d_return || $d_pickup->format('Y-m-d') !== $pickup_date || $d_return->format('Y-m-d') !== $return_date) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Format tanggal tidak valid.'];
            header("location: index.php?page=detail&id=$product_id");
            exit();
        }

        if ($pickup_date < $today) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Tanggal ambil tidak boleh di masa lalu.'];
            header("location: index.php?page=detail&id=$product_id");
            exit();
        }

        if ($return_date <= $pickup_date) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Tanggal kembali harus setelah tanggal ambil.'];
            header("location: index.php?page=detail&id=$product_id");
            exit();
        }

        $d1 = new DateTime($pickup_date);
        $d2 = new DateTime($return_date);
        $total_days = (int) $d2->diff($d1)->days;

        if ($total_days < 1) {
            $total_days = 1;
        }

        $stmt = db_prepare_or_redirect($conn, "SELECT status, price_per_day FROM products WHERE id=?", "index.php?page=katalog");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_result = $stmt->get_result();
        $product = $product_result ? $product_result->fetch_assoc() : null;
        $stmt->close();

        if (!$product || $product['status'] !== 'available') {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Motor tidak tersedia.'];
            header("location: index.php?page=katalog");
            exit();
        }

        $price_per_day = (int) $product['price_per_day'];
        $total_price = $total_days * $price_per_day;

        $stmt = db_prepare_or_redirect($conn, "
            SELECT id FROM bookings
            WHERE product_id = ?
              AND status IN ('pending','confirmed','active')
              AND NOT (return_date <= ? OR pickup_date >= ?)
            LIMIT 1
        ", "index.php?page=detail&id=$product_id");
        $stmt->bind_param("iss", $product_id, $pickup_date, $return_date);
        $stmt->execute();
        $overlap_result = $stmt->get_result();
        $overlap = $overlap_result ? $overlap_result->fetch_assoc() : null;
        $stmt->close();

        if ($overlap) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Motor sudah dibooking pada tanggal tersebut. Pilih tanggal lain.'];
            header("location: index.php?page=detail&id=$product_id");
            exit();
        }

        $stmt = db_prepare_or_redirect($conn, "
            INSERT INTO bookings (user_id, product_id, booking_date, pickup_date, return_date, total_days, total_price, status)
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 'pending')
        ", "index.php?page=detail&id=$product_id");
        $stmt->bind_param("iissii", $_SESSION['user_id'], $product_id, $pickup_date, $return_date, $total_days, $total_price);
        $stmt->execute();
        $booking_id = $stmt->insert_id;
        $stmt->close();

        $_SESSION['msg'] = ['type' => 'success', 'text' => "Booking #$booking_id berhasil dibuat! Silakan lakukan pembayaran."];
        header("location: index.php?page=checkout&id=$booking_id");
        exit();
        break;

    case 'confirm':

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $payment_method = strtolower(trim($_POST['payment_method'] ?? ''));
        $payment_number = trim($_POST['payment_number'] ?? '');
        $allowed_payments = ['dana', 'gopay', 'shopeepay', 'ovo', 'qris'];
        $payment_labels = ['dana' => 'Dana', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'ovo' => 'OVO', 'qris' => 'QRIS'];

        if (!in_array($payment_method, $allowed_payments, true)) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Pilih metode pembayaran yang valid (Dana, GoPay, ShopeePay, OVO, QRIS).'];
            header("location: index.php?page=checkout&id=$booking_id");
            exit();
        }

        if ($payment_number === '' || strlen($payment_number) > 50) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Nomor e-wallet wajib diisi (maksimal 50 karakter).'];
            header("location: index.php?page=checkout&id=$booking_id");
            exit();
        }

        $stmt = db_prepare_or_redirect($conn, "
            SELECT b.*, p.status as product_status, p.name AS p_name
            FROM bookings b
            JOIN products p ON b.product_id = p.id
            WHERE b.id=? AND b.user_id=? AND b.status='pending'
        ", "index.php?page=riwayat");
        $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $stmt->execute();
        $booking_result = $stmt->get_result();
        $booking = $booking_result ? $booking_result->fetch_assoc() : null;
        $stmt->close();

        if (!$booking) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Booking tidak ditemukan atau sudah diproses.'];
            header("location: index.php?page=riwayat");
            exit();
        }

        require_once __DIR__ . '/payment_gateway.php';

        if (!empty($booking['payment_ref'])) {
            $existing = xendit_invoice_by_ref($booking['payment_ref']);
            if (is_array($existing)) {
                $existing_status = strtoupper((string) ($existing['status'] ?? ''));
                if (in_array($existing_status, ['PAID', 'SETTLED'], true)) {
                    $existing_paid = (int) ($existing['paid_amount'] ?? $existing['amount'] ?? 0);
                    if ($existing_paid >= (int) $booking['total_price']) {
                        $paid_at = null;
                        if (!empty($existing['paid_at'])) {
                            $ts = strtotime($existing['paid_at']);
                            if ($ts !== false) {
                                $paid_at = date('Y-m-d H:i:s', $ts);
                            }
                        }
                        confirm_booking_paid($conn, (int) $booking['id'], (int) $booking['product_id'], $paid_at);
                        $_SESSION['msg'] = ['type' => 'success', 'text' => "Pembayaran booking #{$booking['id']} sudah lunas. Struk tersedia di bawah."];
                        header("location: index.php?page=struk&id=" . (int) $booking['id']);
                        exit();
                    }
                } elseif ($existing_status === 'PENDING' && !empty($booking['payment_url'])) {
                    $_SESSION['msg'] = ['type' => 'info', 'text' => 'Tagihan pembayaran sudah dibuat. Silakan selesaikan pembayaran.'];
                    header("location: " . $booking['payment_url']);
                    exit();
                }
            } elseif (!empty($booking['payment_url'])) {

                $_SESSION['msg'] = ['type' => 'info', 'text' => 'Tagihan pembayaran sudah dibuat. Silakan selesaikan pembayaran.'];
                header("location: " . $booking['payment_url']);
                exit();
            } else {
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Gagal memverifikasi tagihan pembayaran. Silakan coba lagi.'];
                header("location: index.php?page=checkout&id=$booking_id");
                exit();
            }
        }

        $stmt = db_prepare_or_redirect($conn, "
            SELECT id FROM bookings
            WHERE product_id = ?
              AND id != ?
              AND status IN ('pending','confirmed','active')
              AND NOT (return_date <= ? OR pickup_date >= ?)
            LIMIT 1
        ", "index.php?page=riwayat");
        $stmt->bind_param("iiss", $booking['product_id'], $booking_id, $booking['pickup_date'], $booking['return_date']);
        $stmt->execute();
        $overlap_result2 = $stmt->get_result();
        $overlap = $overlap_result2 ? $overlap_result2->fetch_assoc() : null;
        $stmt->close();

        if ($overlap) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Motor sudah dibooking pada tanggal tersebut oleh booking lain.'];
            header("location: index.php?page=riwayat");
            exit();
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/pkk/assets/user'), '/');
        $customer = ['name' => $_SESSION['name'] ?? 'Penyewa', 'email' => $_SESSION['email'] ?? null];
        $charge = create_ewallet_charge(
            $booking, $payment_method, $payment_number, $customer,
            $base . '/index.php?page=bayar_kembali',
            $base . '/index.php?page=checkout&id=' . $booking_id
        );
        if (isset($charge['error']) || empty($charge['url'])) {
            error_log('confirm create invoice failed for booking ' . $booking_id . ': ' . ($charge['error'] ?? 'no url'));
            $_SESSION['msg'] = ['type' => 'error', 'text' => $charge['error'] ?? 'Gagal membuat tagihan pembayaran. Silakan coba lagi.'];
            header("location: index.php?page=checkout&id=$booking_id");
            exit();
        }
        $payment_ref = $charge['ref'];
        $payment_url = $charge['url'];
        $old_ref = (string) ($booking['payment_ref'] ?? '');

        $stmt = db_prepare_or_redirect($conn, "UPDATE bookings SET payment_method=?, payment_number=?, payment_ref=?, payment_url=? WHERE id=? AND status='pending' AND (payment_ref IS NULL OR payment_ref=?)", "index.php?page=riwayat");
        $stmt->bind_param("ssssis", $payment_method, $payment_number, $payment_ref, $payment_url, $booking_id, $old_ref);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();

        if ($updated === 0) {

            $stmt = db_prepare_or_redirect($conn, "SELECT payment_url FROM bookings WHERE id=? AND user_id=?", "index.php?page=riwayat");
            $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
            $stmt->execute();
            $race_result = $stmt->get_result();
            $race = $race_result ? $race_result->fetch_assoc() : null;
            $stmt->close();
            if (!empty($race['payment_url'])) {
                $_SESSION['msg'] = ['type' => 'info', 'text' => 'Tagihan pembayaran sudah dibuat. Silakan selesaikan pembayaran.'];
                header("location: " . $race['payment_url']);
                exit();
            }
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Gagal menyimpan tagihan. Silakan coba lagi.'];
            header("location: index.php?page=checkout&id=$booking_id");
            exit();
        }

        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Tagihan dibuat! Selesaikan pembayaran di halaman Xendit.'];
        header("location: $payment_url");
        exit();
        break;

    case 'cancel':

        $booking_id = intval($_POST['booking_id'] ?? 0);

        $stmt = db_prepare_or_redirect($conn, "
            SELECT * FROM bookings
            WHERE id=? AND user_id=? AND status IN ('pending','confirmed')
        ", "index.php?page=riwayat");
        $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $stmt->execute();
        $booking_result2 = $stmt->get_result();
        $booking = $booking_result2 ? $booking_result2->fetch_assoc() : null;
        $stmt->close();

        if (!$booking) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Booking tidak bisa dibatalkan.'];
            header("location: index.php?page=riwayat");
            exit();
        }

        $stmt = db_prepare_or_redirect($conn, "UPDATE bookings SET status='cancelled' WHERE id=?", "index.php?page=riwayat");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        $stmt = db_prepare_or_redirect($conn, "
            SELECT id FROM bookings
            WHERE product_id = ? AND id != ? AND status IN ('pending','confirmed','active')
            LIMIT 1
        ", "index.php?page=riwayat");
        $stmt->bind_param("ii", $booking['product_id'], $booking_id);
        $stmt->execute();
        $other_result = $stmt->get_result();
        $other_active = $other_result ? $other_result->fetch_assoc() : null;
        $stmt->close();

        if (!$other_active) {
            $stmt = db_prepare_or_redirect($conn, "UPDATE products SET status='available' WHERE id=?", "index.php?page=riwayat");
            $stmt->bind_param("i", $booking['product_id']);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION['msg'] = ['type' => 'success', 'text' => "Booking #$booking_id berhasil dibatalkan."];
        header("location: index.php?page=riwayat");
        exit();
        break;

    default:
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Aksi tidak valid.'];
        header("location: index.php?page=katalog");
        exit();
}
