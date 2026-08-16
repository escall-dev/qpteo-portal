<?php
/**
 * Admin Sidebar Navigation
 * Shared sidebar for all admin pages.
 * 
 * Expected: $activePage — string for active highlight
 */
$activePage = $activePage ?? '';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-brand">
        <img src="../branding/qpteo logo unfinalized-jukebox-bg-removed.png" alt="QPTEO" class="admin-sidebar-logo">
        <span>Admin Panel</span>
    </div>

    <nav class="admin-sidebar-nav">
        <a href="dashboard.php" class="admin-nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>" id="admin-nav-dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            <span>Dashboard</span>
        </a>
        <a href="repositories.php" class="admin-nav-item <?= $activePage === 'repositories' ? 'active' : '' ?>" id="admin-nav-repos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Repositories</span>
        </a>
        <a href="memorandums.php" class="admin-nav-item <?= $activePage === 'memorandums' ? 'active' : '' ?>" id="admin-nav-memos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            <span>Memorandums</span>
        </a>
        <a href="coes.php" class="admin-nav-item <?= $activePage === 'coes' ? 'active' : '' ?>" id="admin-nav-coes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path></svg>
            <span>Centers of Excellence</span>
        </a>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="../home.php" class="admin-nav-item" title="Back to Portal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
            <span>Back to Portal</span>
        </a>
        <a href="logout.php" class="admin-nav-item admin-nav-logout" id="admin-nav-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
