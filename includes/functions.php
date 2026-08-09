<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function requireLogin() {
    if (!isset($_SESSION['cr_id'])) {
        redirect('/cr_portal/auth/login.php');
    }
}

function currentCrId() {
    return $_SESSION['cr_id'] ?? null;
}

function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function isActive($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}