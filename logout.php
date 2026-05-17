<?php
require_once __DIR__ . '/admin/modules/Auth.php';

Auth::logout();

$redirect = $_GET['redirect'] ?? '/';
if (!is_string($redirect) || !str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
    $redirect = '/';
}

header('Location: ' . $redirect);
exit;
?>
