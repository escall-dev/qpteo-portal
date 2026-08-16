<?php
/**
 * Admin Auth Guard
 * Include at the top of every admin page (except login).
 * Redirects to login if not authenticated.
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['admin_last_regen']) || time() - $_SESSION['admin_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['admin_last_regen'] = time();
}
