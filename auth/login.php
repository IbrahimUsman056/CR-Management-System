<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['cr_id'])) {
    redirect('/cr_portal/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cr WHERE email = ?");
        $stmt->execute([$email]);
        $cr = $stmt->fetch();

        if ($cr && password_verify($password, $cr['password'])) {
            $_SESSION['cr_id'] = $cr['id'];
            $_SESSION['cr_name'] = $cr['name'];
            $_SESSION['section_name'] = $cr['section_name'];
            redirect('/cr_portal/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Login - CR Portal</title>
<link rel="stylesheet" href="/cr_portal/assets/css/style.css">
</head>
<body>
<div class="authpage">
    <div class="authpage-inner">

        <div class="authpage-brand">
            <div class="authpage-badge">🎓</div>
            <div class="authpage-title">CR Portal</div>
            <div class="authpage-tagline">Everything a class rep needs, in one place</div>
        </div>

        <div class="authpage-features">
            <div class="authpage-feature">
                <span class="authpage-feature-icon">✅</span>
                <span class="authpage-feature-text">Mark attendance per course</span>
            </div>
            <div class="authpage-feature">
                <span class="authpage-feature-icon">📝</span>
                <span class="authpage-feature-text">Record quiz &amp; test marks</span>
            </div>
            <div class="authpage-feature">
                <span class="authpage-feature-icon">👥</span>
                <span class="authpage-feature-text">Manage your class roster</span>
            </div>
            <div class="authpage-feature">
                <span class="authpage-feature-icon">📁</span>
                <span class="authpage-feature-text">Organize project groups</span>
            </div>
            <div class="authpage-feature">
                <span class="authpage-feature-icon">📌</span>
                <span class="authpage-feature-text">Track what teachers need</span>
            </div>
            <div class="authpage-feature">
                <span class="authpage-feature-icon">📤</span>
                <span class="authpage-feature-text">Export clean reports</span>
            </div>
        </div>

        <div class="authpage-card">
            <div class="authpage-card-title">Welcome back</div>
            <div class="authpage-card-sub">Login to manage your class</div>

            <?php if ($error): ?>
                <div class="authpage-error"><?= clean($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="authpage-form">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@university.edu" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>

                <button type="submit" class="authpage-btn">Login</button>
            </form>

            <p class="authpage-switch">Don't have an account? <a href="register.php">Register</a></p>
        </div>

    </div>
</div>
</body>
</html>