<?php
/**
 * Admin Settings
 * Manage portal configurations.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getPortalDB();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meetingRecordingsUrl = $_POST['meeting_recordings_url'] ?? '#';
    
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = 'meeting_recordings_url'");
    $stmt->execute(['val' => $meetingRecordingsUrl]);
    
    $message = "Settings updated successfully.";
}

// Fetch current setting
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'meeting_recordings_url'");
$stmt->execute();
$currentUrl = $stmt->fetchColumn() ?: '#';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .settings-form {
            max-width: 600px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .alert-success {
            padding: 1rem;
            background-color: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <?php $activePage = 'settings'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>Settings</h1>
            </div>
            <div class="admin-topbar-actions">
                <span style="font-size:0.85rem;color:var(--admin-text-muted)">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>
        </div>

        <div class="admin-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Portal Configurations</h2>
                </div>
                <div style="padding:1.5rem;">
                    <?php if ($message): ?>
                        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="settings.php" class="settings-form">
                        <div class="form-group">
                            <label for="meeting_recordings_url">Meeting Recordings Google Drive URL</label>
                            <input type="text" id="meeting_recordings_url" name="meeting_recordings_url" class="form-control" value="<?= htmlspecialchars($currentUrl) ?>" required>
                        </div>
                        
                        <div style="margin-top:1.5rem">
                            <button type="submit" class="btn-admin btn-admin-primary">Save Settings</button>
                        </div>
                    </form>

                    <hr style="margin:2rem 0;border:0;border-top:1px solid var(--admin-border)">

                    <div style="display:flex;justify-content:space-between;align-items:center;background:#f8fafc;padding:1.25rem;border-radius:8px;border:1px solid var(--admin-border)">
                        <div>
                            <h3 style="font-size:1rem;font-weight:700;color:var(--admin-navy);margin-bottom:0.25rem">Centers of Excellence Content</h3>
                            <p style="font-size:0.85rem;color:var(--admin-text-muted);margin:0">Customize National and Regional COEs overview paragraphs and priority challenges text.</p>
                        </div>
                        <a href="coes.php?action=content" class="btn-admin btn-admin-primary" style="background:#040484;white-space:nowrap">Manage COE Content &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
