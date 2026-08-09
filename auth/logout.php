<?php
require_once '../includes/functions.php';
session_destroy();
redirect('/cr_portal/auth/login.php');