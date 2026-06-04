<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth;
Auth::start();
Auth::logout();
header('Location: ' . APP_URL . '/login.php');
exit;
