<?php
/**
 * Logout - CMS ChiapettaDev
 */

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/modules/Auth.php';

Auth::logout();

header('Location: /admin/login.php?logout=1');
exit;
?>
