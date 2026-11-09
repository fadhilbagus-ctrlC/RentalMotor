<?php
session_start();
session_unset();
session_destroy();

// Hapus cookie session jika ada agar logout bersih
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"] ?? '/',
        $params["domain"] ?? '',
        $params["secure"] ?? false,
        $params["httponly"] ?? false
    );
}

// Cegah browser / proxy meng-cache hasil logout (fix nyangkut ke login.php lama)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect: default ke halaman user sebagai guest.
// Tetap dukung ?redirect=login bila suatu saat dibutuhkan.
$redirect = $_GET['redirect'] ?? '';
if ($redirect === 'login') {
    header("location: login.php", true, 303);
} else {
    // URL relatif + query logged_out=1 agar browser tidak memakai cache 302 lama
    header("location: ../user/index.php?logged_out=1", true, 303);
}
exit();
?>