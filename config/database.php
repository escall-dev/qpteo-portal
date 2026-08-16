<?php
/**
 * Database Configuration — QPTEO Portal
 * Shared connection for repositories, memorandums, COEs, and admin.
 */

// Set default timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

define('PORTAL_DB_HOST', 'localhost');
define('PORTAL_DB_NAME', 'u227964391_qpteo_portal');
define('PORTAL_DB_USER', 'u227964391_qpteo_portal');
define('PORTAL_DB_PASS', 'Qpteoportal1994');
define('PORTAL_DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection for the portal
 */
function getPortalDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . PORTAL_DB_HOST . ";dbname=" . PORTAL_DB_NAME . ";charset=" . PORTAL_DB_CHARSET;
            $pdo = new PDO($dsn, PORTAL_DB_USER, PORTAL_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Portal DB Connection Error: " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Portal application constants
// Increase runtime upload limits if supported
@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '128M');
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '300');

define('PORTAL_NAME', 'QPTEO Portal');
define('PORTAL_FULL_NAME', 'Quality Pre-Service Teacher Education Office');
define('PORTAL_BASE_URL', '/landing');
define('PORTAL_UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PORTAL_MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('PORTAL_ALLOWED_FILE_TYPES', [
    'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 
    'doc', 'docx', 'xls', 'xlsx', 'pptx', 'ppt', 
    'zip', 'rar', '7z', 
    'mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm', 'flv', 'mp3', 'wav'
]);
define('PORTAL_RECORDS_PER_PAGE', 15);
