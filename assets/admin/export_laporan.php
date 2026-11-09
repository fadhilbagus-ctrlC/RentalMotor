<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$allowed_ranges = ['7', '30', 'month', '12m'];
$range = $_GET['range'] ?? 'month';
if (!in_array($range, $allowed_ranges, true)) {
    $range = 'month';
}

$today = new DateTimeImmutable('today');
switch ($range) {
    case '7':
        $start = $today->modify('-6 days');
        $end = $today;
        break;
    case '30':
        $start = $today->modify('-29 days');
        $end = $today;
        break;
    case '12m':
        $start = $today->modify('first day of -11 months');
        $end = $today;
        break;
    case 'month':
    default:
        $start = $today->modify('first day of this month');
        $end = $today;
        break;
}

$start_dt = $start->format('Y-m-d') . ' 00:00:00';
$end_dt = $end->format('Y-m-d') . ' 23:59:59';
$filename = 'laporan-penyewaan-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv';

$sql = "
    SELECT b.id, b.created_at, u.name AS user_name, u.email,
           p.name AS product_name, p.brand,
           b.pickup_date, b.return_date, b.total_days, b.total_price,
           COALESCE(b.fine_amount, 0) AS fine_amount, b.status
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN products p ON p.id = b.product_id
    WHERE b.created_at BETWEEN ? AND ?
    ORDER BY b.created_at DESC
";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    exit('Gagal menyiapkan laporan.');
}
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$result = $stmt->get_result();

$status_labels = [
    'pending' => 'Menunggu',
    'confirmed' => 'Dikonfirmasi',
    'active' => 'Sedang Disewa',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
if ($output === false) {
    exit;
}

// BOM agar Excel membaca UTF-8 dengan benar
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [
    'ID Booking',
    'Tanggal Booking',
    'Nama Penyewa',
    'Email',
    'Motor',
    'Brand',
    'Tanggal Ambil',
    'Tanggal Kembali',
    'Jumlah Hari',
    'Total Sewa',
    'Denda',
    'Status',
]);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $created = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-';
        $pickup = !empty($row['pickup_date']) ? date('d/m/Y', strtotime($row['pickup_date'])) : '-';
        $return = !empty($row['return_date']) ? date('d/m/Y', strtotime($row['return_date'])) : '-';

        fputcsv($output, [
            (int) $row['id'],
            $created,
            $row['user_name'],
            $row['email'],
            $row['product_name'],
            $row['brand'],
            $pickup,
            $return,
            (int) $row['total_days'],
            (float) $row['total_price'],
            (float) $row['fine_amount'],
            $status_labels[$row['status']] ?? ucfirst($row['status']),
        ]);
    }
}

$stmt->close();
fclose($output);
exit;
