<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
header('Location: ' . APP_URL . '/superadmin/dashboard.php');
exit;
