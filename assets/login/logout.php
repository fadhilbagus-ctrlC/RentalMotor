<?php
session_start();
session_unset();
session_destroy();

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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$redirect = $_GET['redirect'] ?? '';
if ($redirect === 'login') {
    header("location: login.php", true, 303);
} else {

    header("location: ../user/index.php?logged_out=1", true, 303);
}
exit();
?>