<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['cr_id'])) {
    redirect('/cr_portal/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sectionName = trim($_POST['section_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $sectionName === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM cr WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO cr (name, email, password, section_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $sectionName]);

            $_SESSION['cr_id'] = $pdo->lastInsertId();
            $_SESSION['cr_name'] = $name;
            $_SESSION['section_name'] = $sectionName;
            redirect('/cr_portal/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Register - CR Portal</title>
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
            <div class="authpage-card-title">Create your account</div>
            <div class="authpage-card-sub">Set up your class in a couple minutes</div>

            <?php if ($error): ?>
                <div class="authpage-error"><?= clean($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="authpage-form">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Your name" value="<?= isset($_POST['name']) ? clean($_POST['name']) : '' ?>" required>

                <label>Email</label>
                <input type="email" name="email" placeholder="you@university.edu" value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" required>

                <label>Section</label>
                <input type="text" name="section_name" placeholder="e.g. BSCS-F24-A" value="<?= isset($_POST['section_name']) ? clean($_POST['section_name']) : '' ?>" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="At least 6 characters" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter password" required>

                <button type="submit" class="authpage-btn">Create Account</button>
            </form>

            <p class="authpage-switch">Already have an account? <a href="login.php">Login</a></p>
        </div>

    </div>
</div>
</body>
</html>