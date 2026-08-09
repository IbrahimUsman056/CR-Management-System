<?php
require_once 'includes/functions.php';
if (isset($_SESSION['cr_id'])) {
    redirect('/cr_portal/dashboard.php');
} else {
    redirect('/cr_portal/auth/login.php');
}