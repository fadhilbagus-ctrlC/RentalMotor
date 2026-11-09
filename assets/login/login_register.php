<?php

session_start();
require_once __DIR__ . '/../config.php';

// Validasi CSRF untuk semua POST (login & register)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['csrf_token'] ?? null)) {
    $form = isset($_POST['register']) ? 'register' : 'login';
    $_SESSION[$form . '_error'] = 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.';
    $_SESSION['active_form'] = $form;
    header("location: login.php");
    exit();
}

if(isset($_POST['register'])){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $raw_password = $_POST['password'] ?? '';
    $role = 'user';

    if ($name === '' || $email === '' || $raw_password === '') {
        $_SESSION['register_error'] = 'Semua field wajib diisi';
        $_SESSION['active_form'] = 'register';
        header("location: login.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Format email tidak valid';
        $_SESSION['active_form'] = 'register';
        header("location: login.php");
        exit();
    }

    if (strlen($raw_password) < 8) {
        $_SESSION['register_error'] = 'Password minimal 8 karakter';
        $_SESSION['active_form'] = 'register';
        header("location: login.php");
        exit();
    }

    $password = password_hash($raw_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT email FROM users WHERE email=?");
    if ($stmt === false) {
        error_log("register check prepare failed: " . $conn->error);
        $_SESSION['register_error'] = 'Terjadi kesalahan database. Silakan coba lagi.';
        $_SESSION['active_form'] = 'register';
        header("location: login.php");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $checkemail = $stmt->get_result();
    if($checkemail && $checkemail->num_rows > 0){
        $_SESSION['register_error'] = 'Email is already registered';
        $_SESSION['active_form'] = 'register';
        $stmt->close();
    }
    else{
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            error_log("register insert prepare failed: " . $conn->error);
            $_SESSION['register_error'] = 'Terjadi kesalahan database. Silakan coba lagi.';
            $_SESSION['active_form'] = 'register';
            header("location: login.php");
            exit();
        }
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        $stmt->execute();
        $stmt->close();
    }

    header("location: login.php");
    exit(); 
}

if (isset($_POST['login'])){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Email dan password wajib diisi';
        $_SESSION['active_form'] = 'login';
        header("location: login.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if ($stmt === false) {
        error_log("login prepare failed: " . $conn->error);
        $_SESSION['login_error'] = 'Terjadi kesalahan database. Silakan coba lagi.';
        $_SESSION['active_form'] = 'login';
        header("location: login.php");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Otomatis sesuai role di database (tanpa pilih manual)
            if($user['role'] === 'admin') {
                header("location: ../admin/index.php");
            }
            else{
                header("location: ../user/index.php");
            }
            $stmt->close();
            exit();
        }
    }
    $stmt->close();

    $_SESSION['login_error'] = 'Incorrect email or password';
    $_SESSION['active_form'] = 'login';
    header("location: login.php");
    exit();
}
?>