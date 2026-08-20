<?php
/**
 * QPTEO Portal — Memorandums Page (Issuances)
 * Displays QPTEO Office Memorandums in a searchable data table.
 */
require_once __DIR__ . '/config/database.php';

// Search and pagination
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = PORTAL_RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Sort
$allowedSorts = ['memo_number', 'subject', 'date_issued', 'issued_by', 'file_size'];
$sortCol = in_array($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'date_issued';
$sortDir = (strtolower($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';

try {
    $pdo = getPortalDB();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = '(memo_number LIKE :s1 OR subject LIKE :s2 OR description LIKE :s3 OR issued_by LIKE :s4)';
        $params[':s1'] = "%{$search}%";
        $params[':s2'] = "%{$search}%";
        $params[':s3'] = "%{$search}%";
        $params[':s4'] = "%{$search}%";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM memorandums {$whereClause}");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages   = max(1, ceil($totalRecords / $limit));

    // Fetch
    $dataSQL = "SELECT * FROM memorandums {$whereClause} ORDER BY {$sortCol} {$sortDir} LIMIT :limit OFFSET :offset";
    $dataStmt = $pdo->prepare($dataSQL);
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $records = $dataStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Memorandums query error: " . $e->getMessage());
    $records      = [];
    $totalRecords = 0;
    $totalPages   = 1;
}

// Helper: format file size
function formatFileSize($bytes) {
    if ($bytes === null || $bytes == 0) return '—';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

// Helper: build sort URL
function sortURL($col, $currentSort, $currentDir, $params) {
    $newDir = ($col === $currentSort && $currentDir === 'ASC') ? 'desc' : 'asc';
    $params['sort'] = $col;
    $params['dir']  = $newDir;
    return '?' . http_build_query($params);
}

// Helper: sort indicator
function sortIcon($col, $currentSort, $currentDir) {
    if ($col !== $currentSort) {
        return '<svg class="sort-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>';
    }
    if ($currentDir === 'ASC') {
        return '<svg class="sort-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 14l5-5 5 5"/></svg>';
    }
    return '<svg class="sort-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 10l5 5 5-5"/></svg>';
}

$baseParams = [];
if ($search) $baseParams['search'] = $search;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="QPTEO Office Memorandums — Browse and download official office memorandums and issuances.">
    <title>QPTEO Office Memorandums — QPTEO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php $activeNav = 'issuances'; include 'includes/navbar.php'; ?>

    <main class="portal-main">
        <div class="portal-section">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb portal-breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">QPTEO Office Memorandums</li>
                </ol>
            </nav>

            <!-- Section Header -->
            <div class="portal-section-header">
                <h1 class="portal-section-title">QPTEO Office Memorandums</h1>
                <p class="portal-section-subtitle">Official memorandums and issuances from the Quality Pre-Service Teacher Education Office.</p>
            </div>

            <?php if (empty($records) && $totalRecords === 0): ?>
                <!-- Empty State -->
                <div class="portal-empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <h3>No memorandums found</h3>
                    <p>There are no office memorandums available yet. Check back later.</p>
                </div>
            <?php else: ?>
                <!-- DESKTOP VIEW: Data Table (>= 769px) -->
                <div class="portal-table-wrapper memo-desktop-table">
                    <div class="portal-table-toolbar">
                        <div class="portal-search-box">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" id="memoSearch" placeholder="Search memorandums..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearch()">
                        </div>
                        <div class="portal-table-info">
                            Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> memorandum<?= $totalRecords !== 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <table class="portal-table" id="memoTable">
                        <thead>
                            <tr>
                                <th><a href="<?= sortURL('memo_number', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Memo No. <?= sortIcon('memo_number', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('subject', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Subject <?= sortIcon('subject', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('date_issued', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Date Issued <?= sortIcon('date_issued', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('issued_by', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Issued By <?= sortIcon('issued_by', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('file_size', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Size <?= sortIcon('file_size', $sortCol, $sortDir) ?></a></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row): 
                                $isEmpty = empty($row['file_path']);
                                $isExternal = preg_match('/^https?:\/\//i', $row['file_path']);
                                $pubUrl     = $isExternal ? $row['file_path'] : ltrim($row['file_path'], '/');
                                $ext        = strtolower(pathinfo(parse_url($pubUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                                
                                $previewType = 'pdf';
                                if ($isEmpty) $previewType = 'empty';
                                elseif ($ext === 'pdf') $previewType = 'pdf';
                                elseif (in_array($ext, ['ppt', 'pptx'])) $previewType = 'slides';
                                elseif (in_array($ext, ['doc', 'docx'])) $previewType = 'docs';
                                elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $previewType = 'sheets';
                                elseif ($isExternal && strpos($pubUrl, 'folder') !== false) $previewType = 'folder';
                                elseif ($isExternal) $previewType = 'link';
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['memo_number']) ?></strong></td>
                                    <td>
                                        <a href="javascript:void(0)" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['memo_number'] . ': ' . $row['subject'])) ?>', '<?= $previewType ?>', 'Memorandum')" style="color:var(--portal-navy); text-decoration:none; font-weight:700;" title="Click to preview memorandum">
                                            <?= htmlspecialchars($row['subject']) ?>
                                        </a>
                                        <?php if ($row['description']): ?>
                                            <br><small style="color:var(--portal-text-muted)"><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 120, '…')) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['date_issued'])) ?></td>
                                    <td><?= htmlspecialchars($row['issued_by'] ?? '—') ?></td>
                                    <td><?= formatFileSize($row['file_size']) ?></td>
                                    <td>
                                        <div style="display:flex;gap:0.4rem;align-items:center;">
                                            <button type="button" class="preview-btn" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['memo_number'] . ': ' . $row['subject'])) ?>', '<?= $previewType ?>', 'Memorandum')" title="Click to view preview">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                Preview
                                            </button>

                                            <?php if (!$isEmpty): ?>
                                                <a href="<?= htmlspecialchars($pubUrl) ?>" class="download-btn" target="_blank" <?= ($isExternal) ? '' : 'download' ?> title="<?= $isExternal ? 'Open Link' : 'Download File' ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <?php if ($isExternal): ?>
                                                             <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                                            <polyline points="15 3 21 3 21 9"></polyline>
                                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                                        <?php else: ?>
                                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                            <polyline points="7 10 12 15 17 10"></polyline>
                                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                                        <?php endif; ?>
                                                    </svg>
                                                    <?= $isExternal ? 'Open Link' : 'Download' ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="download-btn" style="opacity:0.5; cursor:not-allowed;" title="No File Attached">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                    Download
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Desktop Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="portal-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($baseParams, ['sort' => $sortCol, 'dir' => strtolower($sortDir), 'page' => $page - 1])) ?>">&laquo;</a>
                            <?php else: ?>
                                <span class="disabled">&laquo;</span>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($baseParams, ['sort' => $sortCol, 'dir' => strtolower($sortDir), 'page' => $i])) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($baseParams, ['sort' => $sortCol, 'dir' => strtolower($sortDir), 'page' => $page + 1])) ?>">&raquo;</a>
                            <?php else: ?>
                                <span class="disabled">&raquo;</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- MOBILE VIEW: Stacked Cards (< 768px) -->
                <div class="memo-mobile-results">
                    <div class="memo-mobile-toolbar">
                        <div class="portal-search-box memo-mobile-search">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" id="memoSearchMobile" placeholder="Search memorandums..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearchMobile()">
                        </div>

                        <div class="memo-mobile-sub-toolbar">
                            <div class="memo-mobile-count">
                                Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?>
                            </div>
                            
                            <?php
                            $currentSortKey = "{$sortCol}:" . strtolower($sortDir);
                            $sortOptions = [
                                'date_issued:desc' => 'Date (Newest)',
                                'date_issued:asc'  => 'Date (Oldest)',
                                'memo_number:asc'  => 'Memo No.',
                                'subject:asc'      => 'Subject',
                            ];
                            $activeSortLabel = $sortOptions[$currentSortKey] ?? 'Date (Newest)';
                            ?>
                            <div class="memo-sort-wrapper">
                                <button type="button" class="portal-picker-btn memo-sort-btn" onclick="openBottomSheet('sheetMemoSort')" aria-label="Sort memorandums">
                                    <span class="portal-picker-label"><?= htmlspecialchars($activeSortLabel) ?></span>
                                    <svg class="portal-picker-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Memo Sort Custom Bottom Sheet -->
                    <div class="portal-bottom-sheet" id="sheetMemoSort" role="dialog" aria-modal="true" aria-labelledby="sheetMemoSortTitle">
                        <div class="portal-sheet-backdrop" onclick="closeBottomSheet('sheetMemoSort')"></div>
                        <div class="portal-sheet-panel">
                            <div class="portal-sheet-handle"></div>
                            
                            <div class="portal-sheet-header">
                                <h3 class="portal-sheet-title" id="sheetMemoSortTitle">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M6 12h12M10 18h4"/></svg>
                                    Sort Memorandums
                                </h3>
                                <button type="button" class="portal-sheet-close" onclick="closeBottomSheet('sheetMemoSort')" aria-label="Close sheet">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                            </div>

                            <div class="portal-sheet-body">
                                <?php foreach ($sortOptions as $sKey => $sLabel): ?>
                                    <button type="button" class="portal-sheet-item <?= $currentSortKey === $sKey ? 'is-selected' : '' ?>" onclick="handleSortChange('<?= $sKey ?>')">
                                        <span class="portal-sheet-item-label"><?= htmlspecialchars($sLabel) ?></span>
                                        <span class="portal-sheet-item-meta">
                                            <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="memo-mobile-card-list">
                        <?php foreach ($records as $row): 
                            $isEmpty = empty($row['file_path']);
                            $isExternal = preg_match('/^https?:\/\//i', $row['file_path']);
                            $pubUrl     = $isExternal ? $row['file_path'] : ltrim($row['file_path'], '/');
                            $ext        = strtolower(pathinfo(parse_url($pubUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                            
                            $previewType = 'pdf';
                            if ($isEmpty) $previewType = 'empty';
                            elseif ($ext === 'pdf') $previewType = 'pdf';
                            elseif (in_array($ext, ['ppt', 'pptx'])) $previewType = 'slides';
                            elseif (in_array($ext, ['doc', 'docx'])) $previewType = 'docs';
                            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $previewType = 'sheets';
                            elseif ($isExternal && strpos($pubUrl, 'folder') !== false) $previewType = 'folder';
                            elseif ($isExternal) $previewType = 'link';
                        ?>
                            <div class="memo-mobile-card">
                                <div class="memo-card-meta">
                                    <span class="memo-card-no"><?= htmlspecialchars($row['memo_number']) ?></span>
                                    <span class="memo-card-date"><?= date('M d', strtotime($row['date_issued'])) ?></span>
                                </div>
                                <a href="javascript:void(0)" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['memo_number'] . ': ' . $row['subject'])) ?>', '<?= $previewType ?>', 'Memorandum')" class="memo-card-subject" title="Click to view memorandum">
                                    <?= htmlspecialchars($row['subject']) ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mobile Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="portal-pagination" style="margin-top: 1.25rem;">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($baseParams, ['sort' => $sortCol, 'dir' => strtolower($sortDir), 'page' => $page - 1])) ?>">&laquo;</a>
                            <?php else: ?>
                                <span class="disabled">&laquo;</span>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($baseParams, ['sort' => $sortCol, 'dir' => strtolower($sortDir), 'page' => $i])) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($baseParams, ['sort' => $sortCol, 'dir' => strtolower($sortDir), 'page' => $page + 1])) ?>">&raquo;</a>
                            <?php else: ?>
                                <span class="disabled">&raquo;</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
                    <h3 id="mediaModalTitle">Memorandum Preview</h3>
                    <span class="category-pill active" style="font-size:0.68rem;padding:0.15rem 0.5rem;background:#ffffff;color:#040484;">Memorandum</span>
                </div>
                <button type="button" class="media-modal-close" onclick="closeMediaModal()" title="Close">&times;</button>
            </div>
            <div class="media-modal-body" id="mediaModalBody">
                <!-- Dynamic Player / Viewer injected here -->
            </div>
            <div class="media-modal-footer">
                <a id="mediaModalDownload" href="#" class="portal-btn portal-btn-sm portal-btn-navy" download target="_blank">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.3rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download File
                </a>
                <button type="button" class="portal-btn portal-btn-sm portal-btn-outline" onclick="closeMediaModal()">Close Preview</button>
            </div>
        </div>
    </div>

    <script>
        function applySearch() {
            const val = document.getElementById('memoSearch').value.trim();
            const url = new URL(window.location.href);
            if (val) {
                url.searchParams.set('search', val);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function applySearchMobile() {
            const val = document.getElementById('memoSearchMobile').value.trim();
            const url = new URL(window.location.href);
            if (val) {
                url.searchParams.set('search', val);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function handleSortChange(sortVal) {
            if (!sortVal) return;
            const parts = sortVal.split(':');
            const url = new URL(window.location.href);
            url.searchParams.set('sort', parts[0]);
            if (parts[1]) {
                url.searchParams.set('dir', parts[1]);
            } else {
                url.searchParams.delete('dir');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function openMediaModal(url, title, type) {
            const modal = document.getElementById('mediaModal');
            const modalTitle = document.getElementById('mediaModalTitle');
            const modalBody = document.getElementById('mediaModalBody');
            const modalDl = document.getElementById('mediaModalDownload');

            modalTitle.textContent = title || 'Memorandum Preview';
            modalDl.href = url;
            modalDl.style.display = 'inline-flex';

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
            } else if (type === 'empty') {
                modalBody.innerHTML = `
                    <div class="doc-preview-card" style="padding: 3rem; text-align: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" style="width: 48px; height: 48px; margin-bottom: 1rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                        <h4 style="font-size:1.15rem;font-weight:700;margin-bottom:0.4rem;color:#374151;">No File Attached</h4>
                        <p style="color:#6b7280;font-size:0.88rem;">This memorandum was posted without an attached file.</p>
                    </div>`;
                modalDl.style.display = 'none';
            } else if (isExternal) {
                modalBody.innerHTML = `<iframe src="${embedUrl}" style="width:100%;height:100%;min-height:500px;border:none;"></iframe>`;
            } else {
                modalBody.innerHTML = `
                    <div class="doc-preview-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#040484" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <h4 style="font-size:1.15rem;font-weight:700;margin-bottom:0.4rem;color:#040484;">${title}</h4>
                        <p style="color:#6b7280;font-size:0.88rem;margin-bottom:1.25rem;">QPTEO Official Memorandum Document.</p>
                        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;justify-content:center;">
                            <a href="${url}" target="_blank" rel="noopener noreferrer" class="portal-btn portal-btn-navy" style="font-weight:600;padding:0.6rem 1.25rem;">
                                Open / Download File
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
