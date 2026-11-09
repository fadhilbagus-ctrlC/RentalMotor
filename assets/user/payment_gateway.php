<?php
// Helper pembayaran e-wallet via Xendit Invoice API (Tahap 2 - aktif).
//
// Cara kerja:
//  - confirm (process_booking.php) memanggil create_ewallet_charge() -> invoice Xendit
//    dibuat, booking TETAP pending, user diarahkan ke invoice_url (halaman bayar Xendit).
//  - Setelah bayar, Xendit redirect browser ke halaman bayar_kembali + mengirim
//    webhook ke process_payment_callback.php -> booking confirmed + motor rented.
//  - Kunci dibaca dari env (XENDIT_SECRET_KEY, XENDIT_CALLBACK_TOKEN),
//    fallback file lokal C:\Laragon\etc\xendit_secrets.php (TIDAK di repo).
//
// Catatan keamanan: JANGAN commit secret key ke repo. Setelah pengujian,
// revoke + generate ulang key di dashboard Xendit bila key sempat terekspos.

define('PAYMENT_MODE', 'xendit'); // 'local' | 'xendit'
define('PAYMENT_METHODS', ['dana', 'gopay', 'shopeepay', 'ovo', 'qris']);
define('XENDIT_API_BASE', 'https://api.xendit.co');

// Muat secret lokal bila env belum diset (dev only, file di luar docroot & repo).
if (!getenv('XENDIT_SECRET_KEY') || !getenv('XENDIT_CALLBACK_TOKEN')) {
    $local_secrets = 'C:\\Laragon\\etc\\xendit_secrets.php';
    if (is_file($local_secrets)) {
        require_once $local_secrets;
    }
}

function payment_secret(): string {
    $key = getenv('XENDIT_SECRET_KEY');
    return is_string($key) ? $key : '';
}

function payment_is_configured(): bool {
    if (PAYMENT_MODE !== 'xendit') {
        return false;
    }
    return payment_secret() !== '' && function_exists('curl_init');
}

// Panggil API Xendit. Return ['http' => int, 'data' => mixed, 'error' => ?string].
function xendit_request(string $method, string $path, ?array $body = null): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, XENDIT_API_BASE . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERPWD, payment_secret() . ':');
    $headers = ['Content-Type: application/json'];
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? []));
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['http' => 0, 'data' => null, 'error' => 'curl: ' . $err];
    }
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($raw, true);
    return ['http' => $http, 'data' => $data, 'error' => $http >= 400 ? $raw : null];
}

// Petakan metode lokal -> channel invoice Xendit.
function xendit_channel_for(string $method): ?string {
    $map = ['dana' => 'DANA', 'gopay' => 'GOPAY', 'shopeepay' => 'SHOPEEPAY', 'ovo' => 'OVO', 'qris' => 'QRIS'];
    return $map[$method] ?? null;
}

// Buat tagihan invoice Xendit. Return ['ref'=>external_id, 'url'=>invoice_url, 'invoice_id'=>id]
// atau ['error'=>pesan] bila gagal.
function create_ewallet_charge(array $booking, string $method, string $number, array $customer, string $success_url, string $failure_url): array {
    if (!payment_is_configured()) {
        return ['error' => 'Gateway belum dikonfigurasi (secret key / curl).'];
    }
    $ref = 'MOTORKU-' . (int) $booking['id'] . '-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $payload = [
        'external_id' => $ref,
        'amount' => (int) $booking['total_price'],
        'description' => 'Sewa motor ' . ($booking['p_name'] ?? ('Booking #' . (int) $booking['id'])),
        'invoice_duration' => 86400,
        'customer' => [
            'given_names' => $customer['name'] ?? 'Penyewa',
            'email' => $customer['email'] ?? null,
            'mobile_number' => $number,
        ],
        'success_redirect_url' => $success_url . (strpos($success_url, '?') === false ? '?' : '&') . 'ref=' . urlencode($ref),
        'failure_redirect_url' => $failure_url,
        'currency' => 'IDR',
        'metadata' => ['booking_id' => (int) $booking['id'], 'method' => $method, 'number' => $number],
    ];
    $channel = xendit_channel_for($method);
    if ($channel !== null) {
        $payload['payment_methods'] = [$channel];
    }
    $res = xendit_request('POST', '/v2/invoices', $payload);
    // Channel tidak didukung akun ini -> coba lagi tanpa batasan channel.
    if (($res['http'] === 400) && isset($payload['payment_methods'])) {
        unset($payload['payment_methods']);
        $res = xendit_request('POST', '/v2/invoices', $payload);
    }
    if ($res['http'] < 200 || $res['http'] >= 300 || !is_array($res['data']) || empty($res['data']['invoice_url'])) {
        error_log('xendit create invoice failed: ' . ($res['error'] ?? 'unknown'));
        return ['error' => 'Gagal membuat tagihan pembayaran. Silakan coba lagi.'];
    }
    return ['ref' => $ref, 'url' => $res['data']['invoice_url'], 'invoice_id' => $res['data']['id'] ?? null];
}

// Ambil status invoice terbaru berdasarkan external_id. Return array invoice | null.
function xendit_invoice_by_ref(string $ref): ?array {
    $res = xendit_request('GET', '/v2/invoices?external_id=' . urlencode($ref) . '&limit=1');
    if ($res['http'] < 200 || $res['http'] >= 300 || !is_array($res['data'])) {
        return null;
    }
    $item = $res['data'][0] ?? (isset($res['data']['id']) ? $res['data'] : null);
    return is_array($item) ? $item : null;
}

// Ambil invoice resmi berdasarkan invoice id. Return array invoice | null.
function xendit_invoice_by_id(string $invoice_id): ?array {
    if ($invoice_id === '') {
        return null;
    }
    $res = xendit_request('GET', '/v2/invoices/' . rawurlencode($invoice_id));
    if ($res['http'] < 200 || $res['http'] >= 300 || !is_array($res['data'])) {
        return null;
    }
    return $res['data'];
}

// Konfirmasi booking yang sudah lunas (idempoten: hanya bila masih pending).
// Return true bila baris berubah menjadi confirmed.
function confirm_booking_paid($conn, int $booking_id, int $product_id, ?string $paid_at = null): bool {
    $stmt = $conn->prepare("UPDATE bookings SET status='confirmed', paid_at=? WHERE id=? AND status='pending'");
    if ($stmt === false) {
        error_log('confirm_booking_paid prepare failed: ' . $conn->error);
        return false;
    }
    $paid = $paid_at ?? date('Y-m-d H:i:s');
    $stmt->bind_param('si', $paid, $booking_id);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();
    if ($changed) {
        $stmt2 = $conn->prepare("UPDATE products SET status='rented' WHERE id=?");
        if ($stmt2 !== false) {
            $stmt2->bind_param('i', $product_id);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    return $changed;
}

// Verifikasi callback Xendit (dipakai process_payment_callback.php).
function verify_gateway_callback(string $raw_body, array $headers): bool {
    $token = getenv('XENDIT_CALLBACK_TOKEN');
    if (!is_string($token) || $token === '') {
        return false;
    }
    $lower = [];
    foreach ($headers as $k => $v) {
        $lower[strtolower((string) $k)] = $v;
    }
    $got = $lower['x-callback-token'] ?? '';
    return hash_equals($token, (string) $got);
}
