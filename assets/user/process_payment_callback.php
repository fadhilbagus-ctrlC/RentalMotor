<?php
// Webhook pembayaran Xendit (invoice). Daftarkan URL file ini di dashboard:
// Settings -> Developers -> Callbacks (pakai URL publik/ngrok saat development).
//
// Alur: Xendit POST {id, external_id, status, paid_at, ...} + header x-callback-token
//  -> verifikasi token -> status PAID: bookings confirmed + products rented (idempoten).
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/payment_gateway.php';

header('Content-Type: application/json; charset=utf-8');

if (PAYMENT_MODE !== 'xendit' || !payment_is_configured()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'payment gateway not configured']);
    exit();
}

$raw = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!verify_gateway_callback($raw, is_array($headers) ? $headers : [])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'invalid callback token']);
    exit();
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid payload']);
    exit();
}

$ref = trim($data['external_id'] ?? '');
$status = strtoupper(trim($data['status'] ?? ''));
if ($ref === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing external_id']);
    exit();
}

if ($status === 'PAID') {
    $stmt = $conn->prepare("SELECT id, product_id, status, total_price FROM bookings WHERE payment_ref=?");
    if ($stmt === false) {
        error_log('callback lookup prepare failed: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
        exit();
    }
    $stmt->bind_param('s', $ref);
    $stmt->execute();
    $r = $stmt->get_result();
    $bk = $r ? $r->fetch_assoc() : null;
    $stmt->close();
    if (!$bk) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'booking not found']);
        exit();
    }

    // Jangan percaya status dari body callback: ambil invoice resmi dari Xendit.
    $invoice_id = trim((string) ($data['id'] ?? ''));
    $inv = $invoice_id !== '' ? xendit_invoice_by_id($invoice_id) : xendit_invoice_by_ref($ref);
    if (!is_array($inv)) {
        error_log('callback verify invoice failed for ref ' . $ref);
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'invoice verification failed']);
        exit();
    }

    $inv_status = strtoupper((string) ($inv['status'] ?? ''));
    $inv_ref = (string) ($inv['external_id'] ?? '');
    $inv_id = (string) ($inv['id'] ?? '');
    $paid_amount = (int) ($inv['paid_amount'] ?? $inv['amount'] ?? 0);
    $expected_amount = (int) $bk['total_price'];

    $status_ok = in_array($inv_status, ['PAID', 'SETTLED'], true);
    $ref_ok = $inv_ref === $ref;
    $id_ok = $invoice_id === '' || $inv_id === $invoice_id;
    $amount_ok = $paid_amount >= $expected_amount;

    if (!$status_ok) {
        // Invoice belum tercatat lunas (bisa eventual consistency): minta Xendit retry.
        error_log('callback invoice not paid yet ref=' . $ref . ' status=' . $inv_status);
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'invoice not paid yet']);
        exit();
    }

    if (!$ref_ok || !$id_ok || !$amount_ok) {
        error_log('callback invoice mismatch ref=' . $ref
            . ' inv_id=' . $inv_id
            . ' status=' . $inv_status
            . ' inv_ref=' . $inv_ref
            . ' paid=' . $paid_amount
            . ' expected>=' . $expected_amount);
        http_response_code(200);
        echo json_encode(['ok' => false, 'error' => 'invoice verification mismatch']);
        exit();
    }

    $paid_at = null;
    if (!empty($inv['paid_at'])) {
        $ts = strtotime($inv['paid_at']);
        if ($ts !== false) {
            $paid_at = date('Y-m-d H:i:s', $ts);
        }
    }
    $changed = ($bk['status'] === 'pending')
        ? confirm_booking_paid($conn, (int) $bk['id'], (int) $bk['product_id'], $paid_at)
        : false;
    http_response_code(200);
    echo json_encode(['ok' => true, 'confirmed' => $changed]);
    exit();
}

// Status lain (PENDING/EXPIRED/dll): akui saja, tidak ada aksi.
http_response_code(200);
echo json_encode(['ok' => true, 'ignored_status' => $status]);
