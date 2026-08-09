<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' - CR Portal' : 'CR Portal' ?></title>
<link rel="stylesheet" href="/cr_portal/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
    <header class="topbar">
        <span class="topbar-title"><?= isset($pageTitle) ? clean($pageTitle) : 'CR Portal' ?></span>
        <div class="topbar-right">
            <a href="/cr_portal/groups.php" class="topbar-icon" title="Project Groups">📁</a>
            <a href="/cr_portal/students.php" class="topbar-icon" title="Manage Students">👥</a>
            <a href="/cr_portal/auth/logout.php" class="topbar-icon" title="Logout" data-confirm="Log out of your account?">🚪</a>
            <?php if (isset($_SESSION['section_name'])): ?>
                <span class="topbar-section"><?= clean($_SESSION['section_name']) ?></span>
            <?php endif; ?>
        </div>
    </header>
    <main class="content">