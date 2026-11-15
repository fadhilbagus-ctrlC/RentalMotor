<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("location: ../admin/index.php");
        exit();
    } elseif ($_SESSION['role'] === 'user') {
        header("location: ../user/index.php");
        exit();
    }
}

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeform = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showerror($error) {
    return !empty($error) ? "<p class='error-message'>" . htmlspecialchars($error) . "</p>" : '';
}

function isactiveform($formname, $activeform) {
    return ($formname === $activeform) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Login your account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box login <?= isactiveform('login', $activeform); ?>" id="login-form">
            <form action="login_register.php" method="post">
                <?= csrf_field() ?>
                <h1>Login</h1>
                <?= showerror($errors['login']);?>
                <?php if (($_GET['reset'] ?? '') === 'success'): ?>
                <p class='error-message' style="color:#1a9e54;">Password berhasil diubah, silakan login.</p>
                <?php endif; ?>
                <div class="input-box">
                    <input type="text" name="email" placeholder="Email" required>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="forgot-link">
                    <a href="forgot.php">Forgot password?</a>
                </div>
                <button type="submit" class="btn" name="login">Login</button>
            </form>
        </div>
        <div class="form-box register <?= isactiveform('register', $activeform); ?>" id="register-form">
            <form action="login_register.php" method="post">
                <?= csrf_field() ?>
                <h1>Registration</h1>
                <?= showerror($errors['register']);?>
                <div class="input-box">
                    <input type="text" name="name" placeholder="Name" required>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn" name="register">Register</button>
            </form>
        </div>
        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <a href="../user/index.php" class="back-btn">&#8592; Kembali</a>
                <h1>Welcome</h1>
                <p>Don't have an account?</p>
                <button class="btn register-btn">Register</button>
            </div>
            <div class="toggle-panel toggle-right">
                <h1>Regist your account</h1>
                <p>Do you have an account?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>
    <script src="login.js"></script>
</body>
</html>