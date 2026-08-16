<?php
/**
 * Admin Dashboard
 * Shows overview stats and quick links to CRUD sections.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getPortalDB();
    $repoCount = (int)$pdo->query("SELECT COUNT(*) FROM repositories")->fetchColumn();
    $memoCount = (int)$pdo->query("SELECT COUNT(*) FROM memorandums")->fetchColumn();
    $coeCount  = (int)$pdo->query("SELECT COUNT(*) FROM centers_of_excellence")->fetchColumn();
} catch (PDOException $e) {
    $repoCount = 0;
    $memoCount = 0;
    $coeCount  = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <?php $activePage = 'dashboard'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>Dashboard</h1>
            </div>
            <div class="admin-topbar-actions">
                <span style="font-size:0.85rem;color:var(--admin-text-muted)">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>
        </div>

        <div class="admin-content">

            <!-- Stat Cards -->
            <div class="admin-stats">
                <a href="repositories.php" class="admin-stat-card" style="text-decoration:none;color:inherit">
                    <div class="admin-stat-icon repos">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    </div>
                    <div class="admin-stat-info">
                        <h3><?= $repoCount ?></h3>
                        <p>Repository Documents</p>
                    </div>
                </a>

                <a href="memorandums.php" class="admin-stat-card" style="text-decoration:none;color:inherit">
                    <div class="admin-stat-icon memos">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <div class="admin-stat-info">
                        <h3><?= $memoCount ?></h3>
                        <p>Office Memorandums</p>
                    </div>
                </a>

                <a href="coes.php" class="admin-stat-card" style="text-decoration:none;color:inherit">
                    <div class="admin-stat-icon coes">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path></svg>
                    </div>
                    <div class="admin-stat-info">
                        <h3><?= $coeCount ?></h3>
                        <p>Centers of Excellence</p>
                    </div>
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div style="padding:1.5rem;display:flex;flex-wrap:wrap;gap:0.75rem">
                    <a href="repositories.php?action=add" class="btn-admin btn-admin-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Repository Document
                    </a>
                    <a href="memorandums.php?action=add" class="btn-admin btn-admin-gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Memorandum
                    </a>
                    <a href="coes.php?action=add" class="btn-admin btn-admin-primary" style="background:#059669">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Center of Excellence
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
