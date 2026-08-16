<?php
/**
 * QPTEO Portal — Centers of Excellence Page
 * Displays Teacher Education Institutions designated as COEs in a card grid.
 */
require_once __DIR__ . '/config/database.php';

// Search
$search = trim($_GET['search'] ?? '');

try {
    $pdo = getPortalDB();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = '(institution_name LIKE :s1 OR region LIKE :s2 OR province LIKE :s3 OR description LIKE :s4)';
        $params[':s1'] = "%{$search}%";
        $params[':s2'] = "%{$search}%";
        $params[':s3'] = "%{$search}%";
        $params[':s4'] = "%{$search}%";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT * FROM centers_of_excellence {$whereClause} ORDER BY region ASC, institution_name ASC");
    $stmt->execute($params);
    $records = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("COEs query error: " . $e->getMessage());
    $records = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Teacher Education Centers of Excellence 2026 — Designated Teacher Education Institutions recognized for excellence.">
    <title>Teacher Education Centers of Excellence 2026 — QPTEO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php $activeNav = 'coes'; include 'includes/navbar.php'; ?>

    <main class="portal-main">
        <div class="portal-section">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb portal-breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Centers of Excellence 2026</li>
                </ol>
            </nav>

            <!-- Section Header -->
            <div class="portal-section-header">
                <h1 class="portal-section-title">Teacher Education Centers of Excellence 2026</h1>
                <p class="portal-section-subtitle">Teacher Education Institutions designated as Centers of Excellence by the Teacher Education Council.</p>
            </div>

            <!-- Search Bar -->
            <div style="max-width:400px;margin-bottom:1.5rem">
                <div class="portal-search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="coeSearch" placeholder="Search institutions..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearch()">
                </div>
            </div>

            <?php if (empty($records)): ?>
                <!-- Empty State -->
                <div class="portal-empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path>
                        <line x1="8" y1="14" x2="8" y2="17"></line>
                        <line x1="12" y1="14" x2="12" y2="17"></line>
                        <line x1="16" y1="14" x2="16" y2="17"></line>
                    </svg>
                    <h3>No institutions found</h3>
                    <p>There are no Centers of Excellence entries yet. Check back later.</p>
                </div>
            <?php else: ?>
                <!-- Results count -->
                <p style="font-size:0.85rem;color:var(--portal-text-muted);margin-bottom:1rem">
                    <?= count($records) ?> institution<?= count($records) !== 1 ? 's' : '' ?> found
                </p>

                <!-- Card Grid -->
                <div class="coe-grid">
                    <?php foreach ($records as $row): ?>
                        <div class="coe-card">
                            <!-- Logo / Image Area -->
                            <div class="coe-card-logo">
                                <?php if ($row['logo_path'] && file_exists(__DIR__ . '/' . $row['logo_path'])): ?>
                                    <img src="<?= htmlspecialchars($row['logo_path']) ?>" alt="<?= htmlspecialchars($row['institution_name']) ?> Logo">
                                <?php else: ?>
                                    <!-- Placeholder Icon -->
                                    <svg class="placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path>
                                        <line x1="8" y1="14" x2="8" y2="17"></line>
                                        <line x1="12" y1="14" x2="12" y2="17"></line>
                                        <line x1="16" y1="14" x2="16" y2="17"></line>
                                    </svg>
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
                            <div class="coe-card-body">
                                <h3 class="coe-card-name"><?= htmlspecialchars($row['institution_name']) ?></h3>

                                <div class="coe-card-meta">
                                    <?php if ($row['region']): ?>
                                        <span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <?= htmlspecialchars($row['region']) ?><?= $row['province'] ? ', ' . htmlspecialchars($row['province']) : '' ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($row['designation_date']): ?>
                                        <span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                            Designated: <?= date('M d, Y', strtotime($row['designation_date'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($row['contact_info']): ?>
                                        <span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                            <?= htmlspecialchars($row['contact_info']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($row['description']): ?>
                                    <p style="font-size:0.85rem;color:var(--portal-text-muted);margin-bottom:0.75rem;line-height:1.5">
                                        <?= htmlspecialchars(mb_strimwidth($row['description'], 0, 150, '…')) ?>
                                    </p>
                                <?php endif; ?>

                                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.85rem;padding-top:0.75rem;border-top:1px solid #f1f5f9;">
                                    <span class="coe-card-status <?= $row['status'] ?>">
                                        <svg viewBox="0 0 24 24" fill="currentColor" width="8" height="8"><circle cx="12" cy="12" r="6"/></svg>
                                        <?= ucfirst($row['status']) ?>
                                    </span>

                                    <?php if (!empty($row['doc_link'])): 
                                        $linkUrl = $row['doc_link'];
                                        $ext = strtolower(pathinfo(parse_url($linkUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                                        $previewType = ($ext === 'pdf') ? 'pdf' : 'link';
                                    ?>
                                        <div style="display:flex;gap:0.35rem;">
                                            <button type="button" class="preview-btn" onclick="openMediaModal('<?= htmlspecialchars($linkUrl) ?>', '<?= htmlspecialchars(addslashes($row['institution_name'])) ?>', '<?= $previewType ?>')" title="Click to view document/website preview">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                Preview
                                            </button>
                                            <a href="<?= htmlspecialchars($linkUrl) ?>" target="_blank" rel="noopener noreferrer" class="download-btn" title="Open Link">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                                Open Link
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Media Preview Modal -->
    <div id="mediaModal" class="media-modal-backdrop" onclick="if(event.target===this)closeMediaModal()">
        <div class="media-modal-content">
            <div class="media-modal-header">
                <div style="display:flex;align-items:center;gap:0.6rem;max-width:85%;">
                    <h3 id="mediaModalTitle">Institution Preview</h3>
                    <span class="category-pill active" style="font-size:0.68rem;padding:0.15rem 0.5rem;background:#ffffff;color:#040484;">Center of Excellence</span>
                </div>
                <button type="button" class="media-modal-close" onclick="closeMediaModal()" title="Close">&times;</button>
            </div>
            <div class="media-modal-body" id="mediaModalBody">
                <!-- Dynamic Player / Viewer injected here -->
            </div>
            <div class="media-modal-footer">
                <a id="mediaModalDownload" href="#" class="portal-btn portal-btn-sm portal-btn-navy" target="_blank" rel="noopener noreferrer">
                    Open Document Link
                </a>
                <button type="button" class="portal-btn portal-btn-sm portal-btn-outline" onclick="closeMediaModal()">Close Preview</button>
            </div>
        </div>
    </div>

    <script>
        function applySearch() {
            const val = document.getElementById('coeSearch').value.trim();
            const url = new URL(window.location.href);
            if (val) {
                url.searchParams.set('search', val);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }

        function openMediaModal(url, title, type) {
            const modal = document.getElementById('mediaModal');
            const modalTitle = document.getElementById('mediaModalTitle');
            const modalBody = document.getElementById('mediaModalBody');
            const modalDl = document.getElementById('mediaModalDownload');

            modalTitle.textContent = title || 'Institution Preview';
            modalDl.href = url;

            let isExternal = /^https?:\/\//i.test(url);
            let embedUrl = url;

            if (isExternal && url.includes('drive.google.com')) {
                if (url.includes('/view')) {
                    embedUrl = url.replace('/view', '/preview');
                } else if (!url.includes('/preview')) {
                    embedUrl = url + (url.includes('?') ? '&' : '?') + 'rm=embedded';
                }
            }

            if (type === 'pdf') {
                modalBody.innerHTML = `<iframe src="${embedUrl}#toolbar=1" style="width:100%;height:100%;min-height:500px;border:none;"></iframe>`;
            } else if (isExternal) {
                modalBody.innerHTML = `<iframe src="${embedUrl}" style="width:100%;height:100%;min-height:500px;border:none;"></iframe>`;
            } else {
                modalBody.innerHTML = `
                    <div class="doc-preview-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#040484" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <h4 style="font-size:1.15rem;font-weight:700;margin-bottom:0.4rem;color:#040484;">${title}</h4>
                        <p style="color:#6b7280;font-size:0.88rem;margin-bottom:1.25rem;">Center of Excellence Official Document / Website.</p>
                        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;justify-content:center;">
                            <a href="${url}" target="_blank" rel="noopener noreferrer" class="portal-btn portal-btn-navy" style="font-weight:600;padding:0.6rem 1.25rem;">
                                Open Document Location
                            </a>
                        </div>
                    </div>`;
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMediaModal() {
            const modal = document.getElementById('mediaModal');
            const modalBody = document.getElementById('mediaModalBody');
            modal.classList.remove('active');
            modalBody.innerHTML = '';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMediaModal();
        });
    </script>

</body>
</html>
