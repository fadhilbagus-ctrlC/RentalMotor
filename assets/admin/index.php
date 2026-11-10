<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login/login.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'users', 'products', 'bookings', 'profile'];
if (!in_array($page, $allowed_pages, true)) {
    $page = 'dashboard';
}

function stat_count($conn, string $sql): int {
    $res = $conn->query($sql);
    if ($res === false) {
        error_log("admin stat failed: " . $conn->error . " | SQL: " . $sql);
        return 0;
    }
    $row = $res->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function admin_range_stats(mysqli $conn, string $start_dt, string $end_dt): array {
    $empty = ['total' => 0, 'revenue' => 0.0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0];
    $sql = "
        SELECT COUNT(*) AS total,
               COALESCE(SUM(CASE WHEN status IN ('confirmed','active','completed') THEN total_price + COALESCE(fine_amount, 0) ELSE 0 END), 0) AS revenue,
               COALESCE(SUM(status = 'pending'), 0) AS pending,
               COALESCE(SUM(status = 'completed'), 0) AS completed,
               COALESCE(SUM(status = 'cancelled'), 0) AS cancelled
        FROM bookings
        WHERE created_at BETWEEN ? AND ?
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("admin range stats prepare failed: " . $conn->error);
        return $empty;
    }
    $stmt->bind_param("ss", $start_dt, $end_dt);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!is_array($row)) {
        return $empty;
    }
    return [
        'total' => (int) ($row['total'] ?? 0),
        'revenue' => (float) ($row['revenue'] ?? 0),
        'pending' => (int) ($row['pending'] ?? 0),
        'completed' => (int) ($row['completed'] ?? 0),
        'cancelled' => (int) ($row['cancelled'] ?? 0),
    ];
}

function admin_nice_max(float $value): float {
    if ($value <= 0) {
        return 0;
    }
    $value *= 1.1;
    $exponent = floor(log10($value));
    $base = pow(10, $exponent);
    $fraction = $value / $base;
    if ($fraction <= 1) {
        $nice = 1;
    } elseif ($fraction <= 2) {
        $nice = 2;
    } elseif ($fraction <= 2.5) {
        $nice = 2.5;
    } elseif ($fraction <= 5) {
        $nice = 5;
    } else {
        $nice = 10;
    }
    return $nice * $base;
}

function admin_short_rupiah(float $value): string {
    if ($value >= 1000000000) {
        $formatted = number_format($value / 1000000000, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',') . ' M';
    }
    if ($value >= 1000000) {
        $formatted = number_format($value / 1000000, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',') . ' jt';
    }
    if ($value >= 1000) {
        return number_format($value / 1000, 0, ',', '.') . ' rb';
    }
    return number_format($value, 0, ',', '.');
}

function admin_chart_geometry(array $series, float $axis_max, array $config): array {
    $w = (int) $config['w'];
    $h = (int) $config['h'];
    $pad_l = (int) $config['pad_l'];
    $pad_r = (int) $config['pad_r'];
    $pad_t = (int) $config['pad_t'];
    $pad_b = (int) $config['pad_b'];
    $max_labels = max(2, (int) ($config['max_labels'] ?? 6));
    $show_y = (bool) ($config['show_y'] ?? true);

    $count = count($series);
    $plot_w = max(1, $w - $pad_l - $pad_r);
    $plot_h = max(1, $h - $pad_t - $pad_b);
    $baseline = $pad_t + $plot_h;

    $points = [];
    foreach ($series as $index => $point) {
        $ratio = $count > 1 ? $index / ($count - 1) : 0.5;
        $x = $pad_l + ($ratio * $plot_w);
        $y = $axis_max > 0
            ? $baseline - (($point['value'] / $axis_max) * $plot_h)
            : $baseline;
        $points[] = array_merge($point, ['x' => $x, 'y' => $y]);
    }

    $line_path = '';
    foreach ($points as $index => $point) {
        $line_path .= ($index === 0 ? 'M' : ' L') . round($point['x'], 2) . ' ' . round($point['y'], 2);
    }

    $area_path = '';
    if ($points) {
        $first = $points[0];
        $last = $points[$count - 1];
        $area_path = $line_path
            . ' L' . round($last['x'], 2) . ' ' . $baseline
            . ' L' . round($first['x'], 2) . ' ' . $baseline . ' Z';
    }

    $grid = [];
    for ($i = 0; $i <= 4; $i++) {
        $ratio = $i / 4;
        $grid[] = [
            'y' => $pad_t + ($ratio * $plot_h),
            'label' => $show_y ? admin_short_rupiah($axis_max * (1 - $ratio)) : '',
        ];
    }

    $label_step = $count > $max_labels ? (int) ceil($count / $max_labels) : 1;
    $label_indexes = [];
    for ($i = 0; $i < $count; $i += $label_step) {
        $label_indexes[] = $i;
    }
    if ($count > 0 && end($label_indexes) !== $count - 1) {
        $label_indexes[] = $count - 1;
    }

    $x_labels = [];
    foreach ($label_indexes as $index) {
        $point = $points[$index];
        $x_labels[] = [
            'x' => $point['x'],
            'y' => $h - 16,
            'label' => $point['short'],
            'anchor' => $index === 0 ? 'start' : ($index === $count - 1 ? 'end' : 'middle'),
        ];
    }

    return [
        'w' => $w,
        'h' => $h,
        'pad_l' => $pad_l,
        'pad_r' => $pad_r,
        'count' => $count,
        'points' => $points,
        'line_path' => $line_path,
        'area_path' => $area_path,
        'grid' => $grid,
        'x_labels' => $x_labels,
    ];
}

function admin_chart_svg(array $geo, string $variant, string $gradient_id, string $aria_label, bool $show_y, bool $dense): string {
    $w = (int) $geo['w'];
    $h = (int) $geo['h'];
    $dot_r = $variant === 'mobile' ? 3 : 4;
    $hit_r = $variant === 'mobile' ? 16 : 14;
    $class = 'chart-svg chart-' . $variant . ($dense ? ' chart-dense' : '');

    $out = '<svg class="' . $class . '" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . htmlspecialchars($aria_label) . '">';
    $out .= '<defs><linearGradient id="' . $gradient_id . '" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0%" stop-color="#2563EB" stop-opacity="0.20"/>'
        . '<stop offset="100%" stop-color="#2563EB" stop-opacity="0.01"/>'
        . '</linearGradient></defs>';

    foreach ($geo['grid'] as $grid) {
        $out .= '<line class="chart-grid-line" x1="' . (int) $geo['pad_l'] . '" y1="' . round($grid['y'], 2) . '"'
            . ' x2="' . ($w - (int) $geo['pad_r']) . '" y2="' . round($grid['y'], 2) . '"/>';
        if ($show_y) {
            $out .= '<text class="chart-axis-label" x="' . ((int) $geo['pad_l'] - 12) . '" y="' . round($grid['y'] + 4, 2) . '" text-anchor="end">'
                . htmlspecialchars($grid['label']) . '</text>';
        }
    }

    foreach ($geo['x_labels'] as $label) {
        $out .= '<text class="chart-axis-label" x="' . round($label['x'], 2) . '" y="' . (int) $label['y'] . '" text-anchor="' . $label['anchor'] . '">'
            . htmlspecialchars($label['label']) . '</text>';
    }

    $out .= '<path class="chart-area" d="' . $geo['area_path'] . '" fill="url(#' . $gradient_id . ')"/>';
    $out .= '<path class="chart-line" d="' . $geo['line_path'] . '" pathLength="1"/>';

    foreach ($geo['points'] as $point) {
        $out .= '<circle class="chart-hit" cx="' . round($point['x'], 2) . '" cy="' . round($point['y'], 2) . '" r="' . $hit_r . '" tabindex="0"'
            . ' data-label="' . htmlspecialchars($point['full']) . '"'
            . ' data-value="Rp ' . number_format($point['value'], 0, ',', '.') . '"'
            . ' data-xpct="' . round(($point['x'] / $w) * 100, 2) . '"'
            . ' data-ypct="' . round(($point['y'] / $h) * 100, 2) . '">'
            . '<title>' . htmlspecialchars($point['full'] . ': Rp ' . number_format($point['value'], 0, ',', '.')) . '</title>'
            . '</circle>';
        $out .= '<circle class="chart-dot" cx="' . round($point['x'], 2) . '" cy="' . round($point['y'], 2) . '" r="' . $dot_r . '"/>';
    }

    $out .= '</svg>';
    return $out;
}

function admin_initials(string $name): string {
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $first = function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
        $initials .= strtoupper($first);
    }
    return $initials !== '' ? $initials : '?';
}

function admin_avatar_tone(string $name): int {
    return (abs((int) crc32($name)) % 5) + 1;
}

function admin_icon(string $name, string $class = 'icon'): string {
    $icons = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'bookings' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        'products' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'profile' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'chart' => '<line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/>',
        'scooter' => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg class="' . htmlspecialchars($class) . '" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

$bulan_short = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$bulan_full = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$hari_short = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

$today = new DateTimeImmutable('today');
$range_options = [
    '7' => '7 hari terakhir',
    '30' => '30 hari terakhir',
    'month' => 'Bulan ini',
    '12m' => '12 bulan terakhir',
];
$range = $_GET['range'] ?? 'month';
if (!isset($range_options[$range])) {
    $range = 'month';
}

switch ($range) {
    case '7':
        $range_start = $today->modify('-6 days');
        $range_end = $today;
        break;
    case '30':
        $range_start = $today->modify('-29 days');
        $range_end = $today;
        break;
    case '12m':
        $range_start = $today->modify('first day of -11 months');
        $range_end = $today;
        break;
    case 'month':
    default:
        $range_start = $today->modify('first day of this month');
        $range_end = $today;
        break;
}

$range_start_dt = $range_start->format('Y-m-d') . ' 00:00:00';
$range_end_dt = $range_end->format('Y-m-d') . ' 23:59:59';
$range_days = (int) $range_start->diff($range_end)->days + 1;
$previous_end = $range_start->modify('-1 day');
$previous_start = $previous_end->modify('-' . max(0, $range_days - 1) . ' days');

$range_label = $range_start->format('d') . ' ' . $bulan_short[(int) $range_start->format('n')] . ' ' . $range_start->format('Y')
    . ' – ' . $range_end->format('d') . ' ' . $bulan_short[(int) $range_end->format('n')] . ' ' . $range_end->format('Y');

$range_stat = admin_range_stats($conn, $range_start_dt, $range_end_dt);
$previous_stat = admin_range_stats(
    $conn,
    $previous_start->format('Y-m-d') . ' 00:00:00',
    $previous_end->format('Y-m-d') . ' 23:59:59'
);
$revenue_change = null;
if ($previous_stat['revenue'] > 0) {
    $revenue_change = (($range_stat['revenue'] - $previous_stat['revenue']) / $previous_stat['revenue']) * 100;
}
$average_revenue = $range_stat['total'] > 0 ? $range_stat['revenue'] / $range_stat['total'] : 0;

$pending_total = stat_count($conn, "SELECT COUNT(*) AS total FROM bookings WHERE status='pending'");

$chart_monthly = ($range === '12m');
$chart_series = [];
$cursor = $chart_monthly ? $range_start->modify('first day of this month') : $range_start;
while ($cursor <= $range_end) {
    if ($chart_monthly) {
        $key = $cursor->format('Y-m');
        $chart_series[$key] = [
            'short' => $bulan_short[(int) $cursor->format('n')] . " '" . $cursor->format('y'),
            'full' => $bulan_full[(int) $cursor->format('n')] . ' ' . $cursor->format('Y'),
            'value' => 0.0,
        ];
        $cursor = $cursor->modify('+1 month');
    } else {
        $key = $cursor->format('Y-m-d');
        $chart_series[$key] = [
            'short' => $cursor->format('j') . ' ' . $bulan_short[(int) $cursor->format('n')],
            'full' => $hari_short[(int) $cursor->format('w')] . ', ' . $cursor->format('j') . ' '
                . $bulan_full[(int) $cursor->format('n')] . ' ' . $cursor->format('Y'),
            'value' => 0.0,
        ];
        $cursor = $cursor->modify('+1 day');
    }
}

$bucket_expression = $chart_monthly ? "DATE_FORMAT(created_at, '%Y-%m')" : "DATE(created_at)";
$chart_sql = "
    SELECT $bucket_expression AS bucket,
           COALESCE(SUM(total_price + COALESCE(fine_amount, 0)), 0) AS revenue
    FROM bookings
    WHERE created_at BETWEEN ? AND ?
      AND status IN ('confirmed','active','completed')
    GROUP BY bucket
";
$chart_stmt = $conn->prepare($chart_sql);
if ($chart_stmt === false) {
    error_log("admin chart prepare failed: " . $conn->error);
} else {
    $chart_stmt->bind_param("ss", $range_start_dt, $range_end_dt);
    $chart_stmt->execute();
    $chart_result = $chart_stmt->get_result();
    if ($chart_result) {
        while ($row = $chart_result->fetch_assoc()) {
            if (isset($chart_series[$row['bucket']])) {
                $chart_series[$row['bucket']]['value'] = (float) $row['revenue'];
            }
        }
    }
    $chart_stmt->close();
}
$chart_series = array_values($chart_series);
$chart_count = count($chart_series);
$chart_total = array_sum(array_column($chart_series, 'value'));
$chart_max_value = 0.0;
$chart_peak = ['short' => '-', 'full' => '-', 'value' => 0.0];
foreach ($chart_series as $point) {
    if ($point['value'] > $chart_max_value) {
        $chart_max_value = $point['value'];
        $chart_peak = $point;
    }
}
$chart_average = $chart_count > 0 ? $chart_total / $chart_count : 0;
$chart_axis_max = admin_nice_max($chart_max_value);

$chart_dense = $chart_count > 14;
$chart_desktop = admin_chart_geometry($chart_series, $chart_axis_max, [
    'w' => 1000,
    'h' => 320,
    'pad_l' => 82,
    'pad_r' => 24,
    'pad_t' => 22,
    'pad_b' => 46,
    'max_labels' => 6,
    'show_y' => true,
]);
$chart_mobile = admin_chart_geometry($chart_series, $chart_axis_max, [
    'w' => 360,
    'h' => 260,
    'pad_l' => 14,
    'pad_r' => 12,
    'pad_t' => 16,
    'pad_b' => 36,
    'max_labels' => 4,
    'show_y' => false,
]);

$top_products = [];
$product_stmt = $conn->prepare("
    SELECT p.name, p.brand,
           COUNT(b.id) AS total,
           COALESCE(SUM(CASE WHEN b.status IN ('confirmed','active','completed') THEN b.total_price + COALESCE(b.fine_amount, 0) ELSE 0 END), 0) AS revenue
    FROM bookings b
    JOIN products p ON p.id = b.product_id
    WHERE b.created_at BETWEEN ? AND ?
      AND b.status <> 'cancelled'
    GROUP BY p.id, p.name, p.brand
    ORDER BY total DESC, revenue DESC
    LIMIT 5
");
if ($product_stmt === false) {
    error_log("admin top product prepare failed: " . $conn->error);
} else {
    $product_stmt->bind_param("ss", $range_start_dt, $range_end_dt);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    if ($product_result) {
        while ($row = $product_result->fetch_assoc()) {
            $top_products[] = $row;
        }
    }
    $product_stmt->close();
}
$top_product_max = 0;
foreach ($top_products as $product) {
    $top_product_max = max($top_product_max, (int) $product['total']);
}

$latest_bookings = [];
$latest_stmt = $conn->prepare("
    SELECT b.id, b.status, b.created_at, b.pickup_date, b.total_price, COALESCE(b.fine_amount, 0) AS fine_amount,
           u.name AS user_name, p.name AS product_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN products p ON p.id = b.product_id
    WHERE b.created_at BETWEEN ? AND ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
if ($latest_stmt === false) {
    error_log("admin latest booking prepare failed: " . $conn->error);
} else {
    $latest_stmt->bind_param("ss", $range_start_dt, $range_end_dt);
    $latest_stmt->execute();
    $latest_result = $latest_stmt->get_result();
    if ($latest_result) {
        while ($row = $latest_result->fetch_assoc()) {
            $latest_bookings[] = $row;
        }
    }
    $latest_stmt->close();
}

$booking_label = [
    'pending' => 'Menunggu',
    'confirmed' => 'Dikonfirmasi',
    'active' => 'Sedang Disewa',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
if ($users === false) {
    error_log("admin users query failed: " . $conn->error);
}

$profile = null;
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
if ($stmt !== false) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $profile_result = $stmt->get_result();
    $profile = $profile_result ? $profile_result->fetch_assoc() : null;
    $stmt->close();
} else {
    error_log("admin profile prepare failed: " . $conn->error);
}
if (!is_array($profile)) {
    $profile = ['name' => $_SESSION['name'] ?? 'Admin', 'email' => ''];
}

$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

$page_titles = [
    'dashboard' => 'Dashboard Penyewaan',
    'users' => 'Pelanggan',
    'products' => 'Produk Motor',
    'bookings' => 'Pesanan',
    'profile' => 'Profil Saya',
];

$admin_photo = (!empty($profile['profile_photo']) && is_file(__DIR__ . '/../' . $profile['profile_photo']))
    ? '../' . $profile['profile_photo']
    : null;
$admin_initial = strtoupper(substr($profile['name'] ?: 'A', 0, 1));
$range_unit = $chart_monthly ? 'bulan' : 'hari';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_titles[$page] ?? 'Admin') ?> - MotorKu</title>
    <link rel="stylesheet" href="dashboard.css?v=<?= filemtime(__DIR__ . '/dashboard.css') ?>">
</head>
<body>

<aside class="sidebar">
    <a href="index.php?page=dashboard" class="sidebar-brand">
        <span class="brand-mark"><?= admin_icon('scooter') ?></span>
        <span class="brand-text">
            <strong>MotorKu</strong>
            <small>Admin Panel</small>
        </span>
    </a>

    <nav class="sidebar-nav" aria-label="Navigasi admin">
        <p class="sidebar-label">Operasional</p>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>" <?= $page === 'dashboard' ? 'aria-current="page"' : '' ?>>
                    <span class="menu-icon"><?= admin_icon('dashboard') ?></span>
                    <span>Ringkasan</span>
                </a>
            </li>
            <li>
                <a href="index.php?page=bookings" class="<?= $page === 'bookings' ? 'active' : '' ?>" <?= $page === 'bookings' ? 'aria-current="page"' : '' ?>>
                    <span class="menu-icon"><?= admin_icon('bookings') ?></span>
                    <span>Pesanan</span>
                    <?php if ($pending_total > 0): ?><span class="menu-badge"><?= (int) $pending_total ?></span><?php endif; ?>
                </a>
            </li>
            <li>
                <a href="index.php?page=products" class="<?= $page === 'products' ? 'active' : '' ?>" <?= $page === 'products' ? 'aria-current="page"' : '' ?>>
                    <span class="menu-icon"><?= admin_icon('products') ?></span>
                    <span>Produk</span>
                </a>
            </li>
        </ul>

        <p class="sidebar-label">Lainnya</p>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php?page=users" class="<?= $page === 'users' ? 'active' : '' ?>" <?= $page === 'users' ? 'aria-current="page"' : '' ?>>
                    <span class="menu-icon"><?= admin_icon('users') ?></span>
                    <span>Pelanggan</span>
                </a>
            </li>
            <li>
                <a href="index.php?page=profile" class="<?= $page === 'profile' ? 'active' : '' ?>" <?= $page === 'profile' ? 'aria-current="page"' : '' ?>>
                    <span class="menu-icon"><?= admin_icon('profile') ?></span>
                    <span>Profil</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?php if ($admin_photo): ?><img src="<?= htmlspecialchars($admin_photo) ?>" alt="Foto admin"><?php else: ?><?= htmlspecialchars($admin_initial) ?><?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <strong><?= htmlspecialchars($profile['name']) ?></strong>
                <span><?= htmlspecialchars($profile['email']) ?></span>
            </div>
            <a href="../login/logout.php" class="logout-btn" title="Logout">
                <?= admin_icon('logout') ?>
            </a>
        </div>
    </div>
</aside>

<main class="main-content">

    <header class="topbar">
        <h1><?= htmlspecialchars($page_titles[$page] ?? 'Admin') ?></h1>
        <div class="topbar-actions">
            <a href="index.php?page=bookings" class="icon-btn" aria-label="Pesanan menunggu" title="Pesanan menunggu">
                <?= admin_icon('bell') ?>
                <?php if ($pending_total > 0): ?><span class="icon-badge"><?= (int) $pending_total ?></span><?php endif; ?>
            </a>
            <a href="index.php?page=profile" class="topbar-user">
                <div class="avatar">
                    <?php if ($admin_photo): ?><img src="<?= htmlspecialchars($admin_photo) ?>" alt="Foto admin"><?php else: ?><?= htmlspecialchars($admin_initial) ?><?php endif; ?>
                </div>
                <div class="user-details">
                    <span class="name"><?= htmlspecialchars($profile['name']) ?></span>
                    <span class="role">Administrator</span>
                </div>
            </a>
        </div>
    </header>

    <div class="page-body">

        <?php if ($msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['text']) ?></div>
        <?php endif; ?>

        <?php if ($page === 'dashboard'): ?>

            <section class="dash-head">
                <div>
                    <h2>Ringkasan penyewaan</h2>
                    <p class="dash-sub"><?= $range_label ?> &middot; <?= number_format($range_stat['total'], 0, ',', '.') ?> penyewaan</p>
                </div>
                <div class="dash-actions">
                    <a href="export_laporan.php?range=<?= urlencode($range) ?>" class="btn btn-download">
                        <?= admin_icon('download') ?>
                        <span>Unduh laporan</span>
                    </a>
                    <form method="get" class="range-form">
                        <input type="hidden" name="page" value="dashboard">
                        <label class="range-select">
                            <?= admin_icon('calendar') ?>
                            <select name="range" onchange="this.form.submit()" aria-label="Pilih rentang laporan">
                                <?php foreach ($range_options as $value => $label): ?>
                                    <option value="<?= htmlspecialchars((string) $value) ?>" <?= $range === (string) $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <noscript><button type="submit" class="btn btn-primary btn-sm">Terapkan</button></noscript>
                    </form>
                </div>
            </section>

            <div class="summary-grid">
                <article class="summary-card">
                    <p class="summary-label">Total omzet</p>
                    <h3 class="summary-value">Rp <?= number_format($range_stat['revenue'], 0, ',', '.') ?></h3>
                    <p class="summary-meta">
                        <?php if ($revenue_change !== null): ?>
                            <span class="trend <?= $revenue_change >= 0 ? 'up' : 'down' ?>">
                                <?= $revenue_change >= 0 ? '&uarr;' : '&darr;' ?> <?= number_format(abs($revenue_change), 1, ',', '.') ?>%
                            </span>
                            <span>dibanding periode sebelumnya</span>
                        <?php else: ?>
                            <span class="trend flat">&mdash;</span>
                            <span>belum ada data pembanding</span>
                        <?php endif; ?>
                    </p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Jumlah penyewaan</p>
                    <h3 class="summary-value"><?= number_format($range_stat['total'], 0, ',', '.') ?></h3>
                    <p class="summary-meta">
                        <?php if ($range_stat['total'] > 0): ?>
                            <span><?= number_format($range_stat['pending'], 0, ',', '.') ?> menunggu</span>
                            <span>&middot;</span>
                            <span><?= number_format($range_stat['completed'], 0, ',', '.') ?> selesai</span>
                        <?php else: ?>
                            <span>Belum ada penyewaan pada periode ini</span>
                        <?php endif; ?>
                    </p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Rata-rata nilai penyewaan</p>
                    <h3 class="summary-value">Rp <?= number_format($average_revenue, 0, ',', '.') ?></h3>
                    <p class="summary-meta"><span>Dihitung dari omzet &divide; jumlah penyewaan</span></p>
                </article>
            </div>

            <section class="panel chart-panel">
                <div class="panel-head">
                    <div>
                        <h2>Tren omzet</h2>
                        <p>Pendapatan dari booking yang dikonfirmasi, aktif, dan selesai.</p>
                    </div>
                    <nav class="segmented" aria-label="Rentang grafik">
                        <a href="index.php?page=dashboard&amp;range=7" class="<?= $range === '7' ? 'active' : '' ?>">7 hari</a>
                        <a href="index.php?page=dashboard&amp;range=30" class="<?= $range === '30' ? 'active' : '' ?>">30 hari</a>
                        <a href="index.php?page=dashboard&amp;range=month" class="<?= $range === 'month' ? 'active' : '' ?>">Bulan ini</a>
                        <a href="index.php?page=dashboard&amp;range=12m" class="<?= $range === '12m' ? 'active' : '' ?>">12 bulan</a>
                    </nav>
                </div>

                <?php if ($chart_total <= 0): ?>
                    <div class="chart-empty">
                        <?= admin_icon('chart') ?>
                        <p>Belum ada omzet pada periode ini.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-wrap" id="omzetChart">
                        <?= admin_chart_svg($chart_desktop, 'desktop', 'chartFillDesktop', 'Grafik tren omzet dari ' . $range_label, true, $chart_dense) ?>
                        <?= admin_chart_svg($chart_mobile, 'mobile', 'chartFillMobile', 'Grafik tren omzet dari ' . $range_label, false, $chart_dense) ?>
                        <div class="chart-tooltip" id="chartTooltip" hidden></div>
                    </div>

                    <div class="chart-foot">
                        <p>
                            Rata-rata per <?= $range_unit ?> <strong>Rp <?= number_format($chart_average, 0, ',', '.') ?></strong>
                            <?php if ($chart_peak['value'] > 0): ?>
                                &middot; Omzet tertinggi <strong>Rp <?= number_format($chart_peak['value'], 0, ',', '.') ?></strong>
                                (<?= htmlspecialchars($chart_peak['short']) ?>)
                            <?php endif; ?>
                        </p>
                        <p class="chart-count">Menampilkan <?= $chart_count ?> titik data</p>
                    </div>
                <?php endif; ?>
            </section>

            <div class="dashboard-grid">

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2>Produk terlaris</h2>
                            <p>Berdasarkan jumlah penyewaan</p>
                        </div>
                        <a href="index.php?page=products" class="panel-link">Lihat semua</a>
                    </div>

                    <?php if ($top_products): ?>
                        <ol class="rank-list">
                            <?php foreach ($top_products as $index => $product): ?>
                                <?php $bar_width = $top_product_max > 0 ? ((int) $product['total'] / $top_product_max) * 100 : 0; ?>
                                <li class="rank-item">
                                    <span class="rank-no"><?= $index + 1 ?></span>
                                    <div class="rank-info">
                                        <div class="rank-line">
                                            <span class="rank-name" title="<?= htmlspecialchars($product['name']) ?>"><?= htmlspecialchars($product['name']) ?></span>
                                            <span class="rank-count"><?= (int) $product['total'] ?> sewa</span>
                                        </div>
                                        <div class="rank-bar"><span style="width: <?= round($bar_width, 1) ?>%"></span></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <div class="empty-panel">
                            <?= admin_icon('products') ?>
                            <p>Belum ada produk yang disewa pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2>Transaksi terbaru</h2>
                            <p>5 booking terakhir</p>
                        </div>
                        <a href="index.php?page=bookings" class="panel-link">Lihat semua</a>
                    </div>

                    <?php if ($latest_bookings): ?>
                        <ul class="tx-list">
                            <?php foreach ($latest_bookings as $booking): ?>
                                <?php
                                $tx_timestamp = !empty($booking['created_at']) ? strtotime($booking['created_at']) : strtotime($booking['pickup_date']);
                                $tx_date = date('d', $tx_timestamp) . ' ' . $bulan_short[(int) date('n', $tx_timestamp)];
                                $tx_total = (float) $booking['total_price'] + (float) $booking['fine_amount'];
                                ?>
                                <li class="tx-item">
                                    <div class="tx-avatar tone-<?= admin_avatar_tone($booking['user_name']) ?>">
                                        <?= htmlspecialchars(admin_initials($booking['user_name'])) ?>
                                    </div>
                                    <div class="tx-info">
                                        <strong><?= htmlspecialchars($booking['user_name']) ?></strong>
                                        <small>#<?= (int) $booking['id'] ?> &middot; <?= htmlspecialchars($booking['product_name']) ?> &middot; <?= htmlspecialchars($tx_date) ?></small>
                                    </div>
                                    <div class="tx-side">
                                        <span class="tx-amount">Rp <?= number_format($tx_total, 0, ',', '.') ?></span>
                                        <span class="badge badge-<?= htmlspecialchars($booking['status']) ?>"><?= htmlspecialchars($booking_label[$booking['status']] ?? ucfirst($booking['status'])) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-panel">
                            <?= admin_icon('bookings') ?>
                            <p>Belum ada transaksi pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

        <?php elseif ($page === 'users'): ?>
            <div class="content-panel">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h2 style="margin:0;border:0;padding:0;">Kelola Users</h2>
                    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">+ Tambah User</button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($users !== false) $users->data_seek(0);
                            $i = 1;
                            if ($users !== false) while ($row = $users->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($row['role']) ?>"><?= ucfirst(htmlspecialchars($row['role'])) ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-warning btn-sm" onclick="openEdit(<?= intval($row['id']) ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>')">Edit</button>
                                        <form action="process_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus user ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-overlay" id="addModal">
                <div class="modal">
                    <h2>Tambah User</h2>
                    <form action="process_user.php?action=add" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="name" required placeholder="Masukkan nama">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="Masukkan email">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required placeholder="Minimal 8 karakter">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-danger" onclick="document.getElementById('addModal').classList.remove('active')">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-overlay" id="editModal">
                <div class="modal">
                    <h2>Edit User</h2>
                    <form action="process_user.php?action=edit" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="name" id="edit_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label>Password (min 8 karakter, kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" placeholder="Biarkan kosong jika tidak diubah">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="edit_role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-danger" onclick="document.getElementById('editModal').classList.remove('active')">Batal</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($page === 'products'): ?>
            <?php include __DIR__ . '/products.php'; ?>

        <?php elseif ($page === 'bookings'): ?>
            <?php include __DIR__ . '/bookings.php'; ?>

        <?php elseif ($page === 'profile'): ?>
            <div class="content-panel">
                <h2>Profile Saya</h2>
                <div class="profile-section">
                    <div class="profile-card">
                        <?php $card_photo = (!empty($profile['profile_photo']) && is_file(__DIR__ . '/../' . $profile['profile_photo'])) ? '../' . $profile['profile_photo'] : null; ?>
                        <div class="profile-avatar"><?php if ($card_photo): ?><img src="<?= htmlspecialchars($card_photo) ?>" alt="Foto profile"><?php else: ?><?= strtoupper(substr($profile['name'], 0, 1)) ?><?php endif; ?></div>
                        <h3><?= htmlspecialchars($profile['name']) ?></h3>
                        <p><?= htmlspecialchars($profile['email']) ?></p>
                        <p style="margin-top:8px;"><span class="badge badge-admin">Administrator</span></p>
                    </div>
                    <div>
                        <form action="../process_profile.php" method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Foto Profile (jpg/png/webp, maks 2MB)</label>
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                                <?php if ($card_photo): ?>
                                <small class="text-muted">Foto saat ini sudah ada — pilih file baru untuk mengganti.</small>
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
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                        <div style="margin-top:16px;">
                            <a href="../user/index.php" class="btn btn-success">Switch to User</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
function openEdit(id, name, email, role) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('editModal').classList.add('active');
}

function openEditProduct(p) {
    document.getElementById('edit_product_id').value = p.id;
    document.getElementById('edit_product_name').value = p.name;
    document.getElementById('edit_product_brand').value = p.brand;
    document.getElementById('edit_product_type').value = p.type;
    document.getElementById('edit_product_status').value = p.status;
    document.getElementById('edit_product_day').value = p.price_per_day;
    document.getElementById('edit_product_week').value = p.price_per_week;
    document.getElementById('edit_product_description').value = p.description || '';
    document.getElementById('editProductModal').classList.add('active');
}

document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>
<script src="dashboard.js?v=<?= filemtime(__DIR__ . '/dashboard.js') ?>"></script>

</body>
</html>
