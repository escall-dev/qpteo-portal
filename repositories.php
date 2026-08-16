<?php
/**
 * QPTEO Portal — Repositories Page
 * Displays documents filtered by document_type and file_type.
 */
require_once __DIR__ . '/config/database.php';

// Valid Document Types (Image 1)
$validDocTypes = [
    'presentation'   => 'Presentation',
    'concept_paper'  => 'Concept Paper',
    'checklist'      => 'Checklist',
    'briefer'        => 'Briefer',
    'report'         => 'Report',
    'minutes'        => 'Minutes',
    'session_guides' => 'Session Guides',
    'others'         => 'Others',
];

// Valid File Types (Image 2) with display labels and CSS badge classes
$validFileTypes = [
    'slides' => ['label' => 'Slides', 'class' => 'file-badge-slides'],
    'docs'   => ['label' => 'Docs',   'class' => 'file-badge-docs'],
    'sheets' => ['label' => 'Sheets', 'class' => 'file-badge-sheets'],
    'folder' => ['label' => 'Folder', 'class' => 'file-badge-folder'],
    'pdf'    => ['label' => 'PDF',    'class' => 'file-badge-pdf'],
    'others' => ['label' => 'Others', 'class' => 'file-badge-others'],
];

// Backward compatibility alias for category
$category = $_GET['category'] ?? ($_GET['document_type'] ?? '');
// Normalize session_guidelines to session_guides
if ($category === 'session_guidelines') $category = 'session_guides';
$categoryLabel = $validDocTypes[$category] ?? null;

$fileTypeFilter = $_GET['file_type'] ?? '';
$fileTypeInfo   = $validFileTypes[$fileTypeFilter] ?? null;

// Search and pagination
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = PORTAL_RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Sort
$allowedSorts = ['title', 'date_uploaded', 'file_size', 'document_type', 'file_type'];
$sortCol = in_array($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'date_uploaded';
$sortDir = (strtolower($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';

try {
    $pdo = getPortalDB();

    // Build query
    $where  = [];
    $params = [];

    if ($categoryLabel !== null) {
        $where[]  = '(document_type = :doctype OR category = :category)';
        $params[':doctype']  = $category;
        $params[':category'] = $category;
    }

    if ($fileTypeInfo !== null) {
        $where[]  = 'file_type = :filetype';
        $params[':filetype'] = $fileTypeFilter;
    }

    if ($search !== '') {
        $where[]  = '(title LIKE :search OR description LIKE :search2 OR uploaded_by LIKE :search3)';
        $params[':search']  = "%{$search}%";
        $params[':search2'] = "%{$search}%";
        $params[':search3'] = "%{$search}%";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countSQL = "SELECT COUNT(*) FROM repositories {$whereClause}";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages   = max(1, ceil($totalRecords / $limit));

    // Fetch records
    $dataSQL = "SELECT * FROM repositories {$whereClause} ORDER BY {$sortCol} {$sortDir} LIMIT :limit OFFSET :offset";
    $dataStmt = $pdo->prepare($dataSQL);
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $records = $dataStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Repositories query error: " . $e->getMessage());
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

$pageTitle = $categoryLabel ? "Repositories — {$categoryLabel}" : "All Repositories";
$baseParams = [];
if ($category)       $baseParams['category']  = $category;
if ($fileTypeFilter) $baseParams['file_type'] = $fileTypeFilter;
if ($search)         $baseParams['search']    = $search;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="QPTEO Document Repositories — Browse and download <?= htmlspecialchars($categoryLabel ?? 'all') ?> documents.">
    <title><?= htmlspecialchars($pageTitle) ?> — QPTEO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php $activeNav = 'repositories'; include 'includes/navbar.php'; ?>

    <main class="portal-main">
        <div class="portal-section">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb portal-breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <?php if ($categoryLabel): ?>
                        <li class="breadcrumb-item"><a href="repositories.php">Repositories</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($categoryLabel) ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page">Repositories</li>
                    <?php endif; ?>
                </ol>
            </nav>

            <!-- Section Header -->
            <div class="portal-section-header">
                <h1 class="portal-section-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="portal-section-subtitle">Browse and download official QPTEO documents by Document Type and File Type.</p>
            </div>

            <!-- Filter Section: Document Types & File Types -->
            <div style="margin-bottom:1.5rem;display:flex;flex-direction:column;gap:0.75rem;">
                <!-- Document Type Filter Pills -->
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <span style="font-size:0.8rem;font-weight:700;color:var(--portal-navy);text-transform:uppercase;letter-spacing:0.04em;">Document Type:</span>
                    <?php 
                        $docParams = $baseParams; 
                        unset($docParams['category']);
                    ?>
                    <a href="repositories.php<?= $docParams ? '?' . http_build_query($docParams) : '' ?>" class="category-pill <?= !$category ? 'active' : '' ?>">All Types</a>
                    <?php foreach ($validDocTypes as $key => $label): ?>
                        <?php 
                            $p = array_merge($baseParams, ['category' => $key]);
                        ?>
                        <a href="repositories.php?<?= http_build_query($p) ?>" class="category-pill <?= $category === $key ? 'active' : '' ?>">
                            <?= htmlspecialchars($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- File Type Filter Badges (Image 2 Palette) -->
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <span style="font-size:0.8rem;font-weight:700;color:var(--portal-navy);text-transform:uppercase;letter-spacing:0.04em;">File Format:</span>
                    <?php 
                        $ftParams = $baseParams; 
                        unset($ftParams['file_type']);
                    ?>
                    <a href="repositories.php<?= $ftParams ? '?' . http_build_query($ftParams) : '' ?>" class="file-badge <?= !$fileTypeFilter ? 'file-badge-others' : '' ?>" style="text-decoration:none; opacity: <?= !$fileTypeFilter ? '1' : '0.6' ?>;">All Formats</a>
                    <?php foreach ($validFileTypes as $fKey => $fMeta): ?>
                        <?php 
                            $p = array_merge($baseParams, ['file_type' => $fKey]);
                            $isActive = ($fileTypeFilter === $fKey);
                        ?>
                        <a href="repositories.php?<?= http_build_query($p) ?>" class="file-badge <?= $fMeta['class'] ?>" style="text-decoration:none; transform: <?= $isActive ? 'scale(1.08)' : 'scale(1)' ?>; box-shadow: <?= $isActive ? '0 2px 6px rgba(0,0,0,0.15)' : 'none' ?>; outline: <?= $isActive ? '2px solid var(--portal-navy)' : 'none' ?>;">
                            <?= htmlspecialchars($fMeta['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
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
                    <h3>No documents found</h3>
                    <p>There are no documents matching your selection. Try clearing filters or searching for something else.</p>
                </div>
            <?php else: ?>
                <!-- Data Table -->
                <div class="portal-table-wrapper">
                    <!-- Toolbar -->
                    <div class="portal-table-toolbar">
                        <div class="portal-search-box">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" id="repoSearch" placeholder="Search documents..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearch()">
                        </div>
                        <div class="portal-table-info">
                            Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> document<?= $totalRecords !== 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <!-- Table -->
                    <table class="portal-table" id="repoTable">
                        <thead>
                            <tr>
                                <th><a href="<?= sortURL('title', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Title <?= sortIcon('title', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('document_type', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Document Type <?= sortIcon('document_type', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('file_type', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">File Format <?= sortIcon('file_type', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('date_uploaded', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Date Uploaded <?= sortIcon('date_uploaded', $sortCol, $sortDir) ?></a></th>
                                <th><a href="<?= sortURL('file_size', $sortCol, $sortDir, $baseParams) ?>" style="color:inherit;text-decoration:none">Size <?= sortIcon('file_size', $sortCol, $sortDir) ?></a></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row): 
                                $isExternal = preg_match('/^https?:\/\//i', $row['file_path']);
                                $pubUrl     = $isExternal ? $row['file_path'] : ltrim($row['file_path'], '/');

                                $docTypeKey = strtolower($row['document_type'] ?? $row['category'] ?? 'others');
                                $docTypeLabel = $validDocTypes[$docTypeKey] ?? ucfirst(str_replace('_', ' ', $docTypeKey));

                                $fileTypeKey  = strtolower($row['file_type'] ?? 'others');
                                $fileTypeMeta = $validFileTypes[$fileTypeKey] ?? $validFileTypes['others'];

                                $ext = strtolower(pathinfo(parse_url($pubUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                                $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'mkv', 'avi', 'wmv', 'flv'];
                                $audioExts = ['mp3', 'wav', 'aac', 'flac', 'm4a'];
                                $imgExts   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                                $isVideo = in_array($ext, $videoExts);
                                $isAudio = in_array($ext, $audioExts);
                                $isImage = in_array($ext, $imgExts);

                                $previewType = 'others';
                                if ($fileTypeKey === 'pdf' || $ext === 'pdf') $previewType = 'pdf';
                                elseif ($fileTypeKey === 'slides' || in_array($ext, ['ppt', 'pptx'])) $previewType = 'slides';
                                elseif ($fileTypeKey === 'docs' || in_array($ext, ['doc', 'docx'])) $previewType = 'docs';
                                elseif ($fileTypeKey === 'sheets' || in_array($ext, ['xls', 'xlsx', 'csv'])) $previewType = 'sheets';
                                elseif ($fileTypeKey === 'folder' || strpos($pubUrl, 'folder') !== false) $previewType = 'folder';
                                elseif ($isVideo) $previewType = 'video';
                                elseif ($isAudio) $previewType = 'audio';
                                elseif ($isImage) $previewType = 'image';
                                else $previewType = 'link';
                            ?>
                                <tr>
                                    <td>
                                        <a href="javascript:void(0)" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['title'])) ?>', '<?= $previewType ?>', '<?= htmlspecialchars(addslashes($docTypeLabel)) ?>', '<?= htmlspecialchars(addslashes($fileTypeMeta['label'])) ?>')" style="color:var(--portal-navy); text-decoration:none; font-weight:700;" title="Click to view file preview">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </a>
                                        <?php if ($row['description']): ?>
                                            <br><small style="color:var(--portal-text-muted)"><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 100, '…')) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="category-pill active" style="font-size:0.72rem;padding:0.2rem 0.6rem">
                                            <?= htmlspecialchars($docTypeLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="file-badge <?= $fileTypeMeta['class'] ?>">
                                            <?= htmlspecialchars($fileTypeMeta['label']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['date_uploaded'])) ?></td>
                                    <td><?= formatFileSize($row['file_size']) ?></td>
                                    <td>
                                        <div style="display:flex;gap:0.4rem;align-items:center;">
                                            <button type="button" class="preview-btn" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['title'])) ?>', '<?= $previewType ?>', '<?= htmlspecialchars(addslashes($docTypeLabel)) ?>', '<?= htmlspecialchars(addslashes($fileTypeMeta['label'])) ?>')" title="Click to view document preview">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                Preview
                                            </button>

                                            <a href="<?= htmlspecialchars($pubUrl) ?>" class="download-btn" target="_blank" <?= ($isExternal || $isVideo) ? '' : 'download' ?> title="Download or open link">
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
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
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
            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Media Preview Modal -->
    <div id="mediaModal" class="media-modal-backdrop" onclick="if(event.target===this)closeMediaModal()">
        <div class="media-modal-content">
            <div class="media-modal-header">
                <div style="display:flex;align-items:center;gap:0.6rem;max-width:85%;">
                    <h3 id="mediaModalTitle">Document Preview</h3>
                    <span id="mediaModalDocType" class="category-pill active" style="font-size:0.68rem;padding:0.15rem 0.5rem;background:#ffffff;color:#040484;">Document</span>
                    <span id="mediaModalFileType" class="file-badge file-badge-others" style="font-size:0.68rem;">Format</span>
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
            const val = document.getElementById('repoSearch').value.trim();
            const url = new URL(window.location.href);
            if (val) {
                url.searchParams.set('search', val);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function openMediaModal(url, title, type, docTypeLabel, fileTypeLabel) {
            const modal = document.getElementById('mediaModal');
            const modalTitle = document.getElementById('mediaModalTitle');
            const modalDocType = document.getElementById('mediaModalDocType');
            const modalFileType = document.getElementById('mediaModalFileType');
            const modalBody = document.getElementById('mediaModalBody');
            const modalDl = document.getElementById('mediaModalDownload');

            modalTitle.textContent = title || 'Document Preview';
            if (modalDocType && docTypeLabel) modalDocType.textContent = docTypeLabel;
            if (modalFileType && fileTypeLabel) {
                modalFileType.textContent = fileTypeLabel;
                var ftClass = 'file-badge-' + (fileTypeLabel.toLowerCase());
                modalFileType.className = 'file-badge ' + (['file-badge-slides','file-badge-docs','file-badge-sheets','file-badge-folder','file-badge-pdf'].includes(ftClass) ? ftClass : 'file-badge-others');
            }
            modalDl.href = url;

            let isExternal = /^https?:\/\//i.test(url);
            let embedUrl = url;

            // Handle Google Drive links
            if (isExternal && url.includes('drive.google.com')) {
                if (url.includes('/view')) {
                    embedUrl = url.replace('/view', '/preview');
                } else if (!url.includes('/preview')) {
                    embedUrl = url + (url.includes('?') ? '&' : '?') + 'rm=embedded';
                }
            }

            if (type === 'pdf') {
                modalBody.innerHTML = `<iframe src="${embedUrl}#toolbar=1" style="width:100%;height:100%;min-height:500px;border:none;"></iframe>`;
            } else if (type === 'video') {
                modalBody.innerHTML = `<video src="${url}" controls autoplay style="width:100%;max-height:75vh;"></video>`;
            } else if (type === 'audio') {
                modalBody.innerHTML = `<div style="padding:3rem 1.5rem;width:100%;text-align:center;"><audio src="${url}" controls autoplay style="width:100%;max-width:500px;"></audio></div>`;
            } else if (type === 'image') {
                modalBody.innerHTML = `<img src="${url}" style="max-height:75vh;object-fit:contain;" alt="${title}">`;
            } else if (type === 'folder' || (isExternal && url.includes('folder'))) {
                modalBody.innerHTML = `
                    <div class="doc-preview-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#edac36" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        <h4 style="font-size:1.15rem;font-weight:700;margin-bottom:0.4rem;color:#040484;">${title}</h4>
                        <p style="color:#6b7280;font-size:0.88rem;margin-bottom:1.25rem;">This entry is a shared folder repository containing multiple files.</p>
                        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;justify-content:center;">
                            <a href="${url}" target="_blank" rel="noopener noreferrer" class="portal-btn portal-btn-navy" style="font-weight:600;padding:0.6rem 1.25rem;">
                                Open Shared Folder Location
                            </a>
                        </div>
                    </div>`;
            } else if (isExternal) {
                modalBody.innerHTML = `<iframe src="${embedUrl}" style="width:100%;height:100%;min-height:500px;border:none;"></iframe>`;
            } else {
                // Local office document file (Slides, Docs, Sheets, ZIP)
                var iconColor = '#040484';
                if (type === 'slides') iconColor = '#d97706';
                else if (type === 'docs') iconColor = '#1d4ed8';
                else if (type === 'sheets') iconColor = '#15803d';

                modalBody.innerHTML = `
                    <div class="doc-preview-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <h4 style="font-size:1.15rem;font-weight:700;margin-bottom:0.4rem;color:#040484;">${title}</h4>
                        <p style="color:#6b7280;font-size:0.88rem;margin-bottom:1.25rem;">Official QPTEO ${docTypeLabel || 'Document'} (${fileTypeLabel || 'File'}).</p>
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

