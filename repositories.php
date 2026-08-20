<?php
/**
 * QPTEO Portal — Repositories Page
 * Displays documents filtered by document_type and file_type.
 */
require_once __DIR__ . '/config/database.php';

// Valid Document Types — Sorted by frequency of use
$validDocTypes = [
    'presentations'              => 'Presentations',
    'reports'                    => 'Reports',
    'checklists'                 => 'Checklists',
    'concept_papers'             => 'Concept Papers',
    'briefers'                   => 'Briefers',
    'session_guides'             => 'Session Guides',
    'accomplishment_reports'     => 'Accomplishment Reports',
    'leave_forms'                => 'Leave Forms',
    'proposals'                  => 'Proposals',
    'program_completion_reports' => 'Program Completion Reports',
    'monitoring_evaluation'      => 'Monitoring and Evaluation Results',
    'qpteo_office_meetings'      => 'QPTEO Office Meetings',
    'execom_meetings'            => 'ExeCom Meetings',
    'other_meetings'             => 'Other Meetings',
    'cmos'                       => 'CHED Memorandum Orders (CMOs)',
    'psgs'                       => 'Policies, Standards and Guidelines (PSGs)',
    'ppst'                       => 'Philippine Professional Standards for Teachers (PPST)',
    'policies'                   => 'Policies',
    'guidelines'                 => 'Guidelines',
    'rite'                       => 'Research Initiatives in Teacher Education (RITE)',
    'others'                     => 'Others',
];

// Valid File Types with display labels, icon keys, and CSS badge classes
$validFileTypes = [
    'pdf'    => ['label' => 'PDF',    'icon' => 'pdf',    'class' => 'file-badge-pdf'],
    'docs'   => ['label' => 'DOCX',   'icon' => 'docs',   'class' => 'file-badge-docs'],
    'sheets' => ['label' => 'XLSX',   'icon' => 'sheets', 'class' => 'file-badge-sheets'],
    'slides' => ['label' => 'PPTX',   'icon' => 'slides', 'class' => 'file-badge-slides'],
    'folder' => ['label' => 'Folder', 'icon' => 'folder', 'class' => 'file-badge-folder'],
    'others' => ['label' => 'Others', 'icon' => 'others', 'class' => 'file-badge-others'],
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

// Counts initialization
$docCounts = array_fill_keys(array_keys($validDocTypes), 0);
$fileTypeCounts = array_fill_keys(array_keys($validFileTypes), 0);
$totalAllDocs = 0;

try {
    $pdo = getPortalDB();

    // Fetch counts per Document Type
    $docCountStmt = $pdo->query("SELECT COALESCE(NULLIF(document_type, ''), category) AS doc_key, COUNT(*) AS cnt FROM repositories GROUP BY doc_key");
    if ($docCountStmt) {
        while ($row = $docCountStmt->fetch(PDO::FETCH_ASSOC)) {
            $dk = $row['doc_key'];
            if (isset($docCounts[$dk])) {
                $docCounts[$dk] = (int)$row['cnt'];
            }
            $totalAllDocs += (int)$row['cnt'];
        }
    }

    // Fetch counts per File Type
    $fCountStmt = $pdo->query("SELECT file_type, COUNT(*) AS cnt FROM repositories GROUP BY file_type");
    if ($fCountStmt) {
        while ($row = $fCountStmt->fetch(PDO::FETCH_ASSOC)) {
            $fk = $row['file_type'];
            if (isset($fileTypeCounts[$fk])) {
                $fileTypeCounts[$fk] = (int)$row['cnt'];
            }
        }
    }

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

// Group active categories (count > 0) and zero-count categories
$activeDocTypes = [];
$emptyDocTypes = [];
foreach ($validDocTypes as $k => $label) {
    $c = $docCounts[$k] ?? 0;
    if ($c > 0) {
        $activeDocTypes[$k] = $label;
    } else {
        $emptyDocTypes[$k] = $label;
    }
}

// Group active file types (count > 0) and zero-count file types
$activeFileTypes = [];
$emptyFileTypes = [];
foreach ($validFileTypes as $k => $meta) {
    $c = $fileTypeCounts[$k] ?? 0;
    if ($c > 0) {
        $activeFileTypes[$k] = $meta;
    } else {
        $emptyFileTypes[$k] = $meta;
    }
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

// Helper: File type SVG icon
function getFileBadgeIcon($fileTypeKey, $size = 14) {
    switch ($fileTypeKey) {
        case 'pdf':
            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>';
        case 'docs':
            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>';
        case 'sheets':
            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><line x1="12" y1="9" x2="12" y2="21"></line></svg>';
        case 'slides':
            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>';
        case 'folder':
            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>';
        default:
            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';
    }
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
            <div class="portal-section-header repo-section-header">
                <h1 class="portal-section-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="portal-section-subtitle">Browse documents by type.</p>
            </div>

            <!-- Single Row Dual-Dropdown Filter Bar with Custom Bottom Sheets -->
            <div class="repo-filter-row">
                <div class="repo-select-wrapper">
                    <button type="button" class="portal-picker-btn <?= $category ? 'is-active' : '' ?>" id="pickerBtnDocType" onclick="openBottomSheet('sheetDocType')" aria-haspopup="dialog" aria-expanded="false" aria-label="Select Document Type">
                        <span class="portal-picker-label"><?= htmlspecialchars($categoryLabel ?: "Document Type ({$totalAllDocs})") ?></span>
                        <svg class="portal-picker-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>

                <div class="repo-select-wrapper">
                    <button type="button" class="portal-picker-btn <?= $fileTypeFilter ? 'is-active' : '' ?>" id="pickerBtnFileType" onclick="openBottomSheet('sheetFileType')" aria-haspopup="dialog" aria-expanded="false" aria-label="Select File Format">
                        <span class="portal-picker-label"><?= htmlspecialchars($fileTypeInfo ? $fileTypeInfo['label'] : "File Type ({$totalAllDocs})") ?></span>
                        <svg class="portal-picker-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>

                <?php if ($category || $fileTypeFilter): ?>
                    <a href="repositories.php<?= $search ? '?search=' . urlencode($search) : '' ?>" class="repo-reset-btn" title="Reset filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Document Type Custom Bottom Sheet -->
            <div class="portal-bottom-sheet" id="sheetDocType" role="dialog" aria-modal="true" aria-labelledby="sheetDocTypeTitle">
                <div class="portal-sheet-backdrop" onclick="closeBottomSheet('sheetDocType')"></div>
                <div class="portal-sheet-panel">
                    <div class="portal-sheet-handle"></div>
                    
                    <div class="portal-sheet-header">
                        <h3 class="portal-sheet-title" id="sheetDocTypeTitle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            Document Type
                        </h3>
                        <button type="button" class="portal-sheet-close" onclick="closeBottomSheet('sheetDocType')" aria-label="Close sheet">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div class="portal-sheet-search-wrap">
                        <svg class="portal-sheet-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                        <input type="text" class="portal-sheet-search-input" placeholder="Search document types..." aria-label="Search document types">
                        <button type="button" class="portal-sheet-search-clear" aria-label="Clear search">&times;</button>
                    </div>

                    <div class="portal-sheet-body">
                        <!-- All Document Types Option -->
                        <button type="button" class="portal-sheet-item <?= empty($category) ? 'is-selected' : '' ?>" onclick="selectDocType('')" data-label="All Document Types">
                            <span class="portal-sheet-item-label">All Document Types</span>
                            <span class="portal-sheet-item-meta">
                                <span class="portal-sheet-pill"><?= $totalAllDocs ?></span>
                                <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </button>

                        <?php if (!empty($activeDocTypes)): ?>
                            <div class="portal-sheet-group">
                                <div class="portal-sheet-group-title">Available Documents</div>
                                <?php foreach ($activeDocTypes as $key => $label): ?>
                                    <button type="button" class="portal-sheet-item <?= $category === $key ? 'is-selected' : '' ?>" onclick="selectDocType('<?= htmlspecialchars($key) ?>')" data-label="<?= htmlspecialchars($label) ?>">
                                        <span class="portal-sheet-item-label"><?= htmlspecialchars($label) ?></span>
                                        <span class="portal-sheet-item-meta">
                                            <span class="portal-sheet-pill"><?= $docCounts[$key] ?? 0 ?></span>
                                            <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($emptyDocTypes)): ?>
                            <div class="portal-sheet-group">
                                <div class="portal-sheet-group-title">Other Categories</div>
                                <?php foreach ($emptyDocTypes as $key => $label): ?>
                                    <button type="button" class="portal-sheet-item <?= $category === $key ? 'is-selected' : '' ?>" onclick="selectDocType('<?= htmlspecialchars($key) ?>')" data-label="<?= htmlspecialchars($label) ?>">
                                        <span class="portal-sheet-item-label"><?= htmlspecialchars($label) ?></span>
                                        <span class="portal-sheet-item-meta">
                                            <span class="portal-sheet-pill">0</span>
                                            <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="portal-sheet-empty">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            <p>No matching document types found</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Format Custom Bottom Sheet -->
            <div class="portal-bottom-sheet" id="sheetFileType" role="dialog" aria-modal="true" aria-labelledby="sheetFileTypeTitle">
                <div class="portal-sheet-backdrop" onclick="closeBottomSheet('sheetFileType')"></div>
                <div class="portal-sheet-panel">
                    <div class="portal-sheet-handle"></div>
                    
                    <div class="portal-sheet-header">
                        <h3 class="portal-sheet-title" id="sheetFileTypeTitle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                            File Format
                        </h3>
                        <button type="button" class="portal-sheet-close" onclick="closeBottomSheet('sheetFileType')" aria-label="Close sheet">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div class="portal-sheet-search-wrap">
                        <svg class="portal-sheet-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                        <input type="text" class="portal-sheet-search-input" placeholder="Search file formats..." aria-label="Search file formats">
                        <button type="button" class="portal-sheet-search-clear" aria-label="Clear search">&times;</button>
                    </div>

                    <div class="portal-sheet-body">
                        <!-- All File Types Option -->
                        <button type="button" class="portal-sheet-item <?= empty($fileTypeFilter) ? 'is-selected' : '' ?>" onclick="selectFileType('')" data-label="All File Formats">
                            <span class="portal-sheet-item-label">All File Formats</span>
                            <span class="portal-sheet-item-meta">
                                <span class="portal-sheet-pill"><?= $totalAllDocs ?></span>
                                <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </button>

                        <?php if (!empty($activeFileTypes)): ?>
                            <div class="portal-sheet-group">
                                <div class="portal-sheet-group-title">Available Formats</div>
                                <?php foreach ($activeFileTypes as $fKey => $fMeta): ?>
                                    <button type="button" class="portal-sheet-item <?= $fileTypeFilter === $fKey ? 'is-selected' : '' ?>" onclick="selectFileType('<?= htmlspecialchars($fKey) ?>')" data-label="<?= htmlspecialchars($fMeta['label']) ?>">
                                        <span class="portal-sheet-item-label"><?= htmlspecialchars($fMeta['label']) ?></span>
                                        <span class="portal-sheet-item-meta">
                                            <span class="portal-sheet-pill"><?= $fileTypeCounts[$fKey] ?? 0 ?></span>
                                            <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($emptyFileTypes)): ?>
                            <div class="portal-sheet-group">
                                <div class="portal-sheet-group-title">Other Formats</div>
                                <?php foreach ($emptyFileTypes as $fKey => $fMeta): ?>
                                    <button type="button" class="portal-sheet-item <?= $fileTypeFilter === $fKey ? 'is-selected' : '' ?>" onclick="selectFileType('<?= htmlspecialchars($fKey) ?>')" data-label="<?= htmlspecialchars($fMeta['label']) ?>">
                                        <span class="portal-sheet-item-label"><?= htmlspecialchars($fMeta['label']) ?></span>
                                        <span class="portal-sheet-item-meta">
                                            <span class="portal-sheet-pill">0</span>
                                            <svg class="portal-sheet-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="portal-sheet-empty">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            <p>No matching file formats found</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Preview / Table Area -->
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
                    <p>There are no documents matching your selection. Try selecting another document type or clearing filters.</p>
                </div>
            <?php else: ?>
                
                <!-- MOBILE VIEW: Dedicated Document Preview Card List -->
                <div class="repo-mobile-results">
                    <div class="repo-mobile-results-header">
                        <div class="repo-mobile-results-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--portal-navy)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            <span>Documents (<?= $totalRecords ?>)</span>
                        </div>
                        <div class="repo-mobile-search-bar">
                            <input type="text" id="repoSearchMobile" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearchMobile()">
                            <button type="button" onclick="applySearchMobile()" aria-label="Search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="repo-mobile-card-list">
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
                            <div class="repo-mobile-doc-card">
                                <div class="repo-doc-card-top">
                                    <div class="repo-doc-icon-badge <?= $fileTypeMeta['class'] ?>">
                                        <?= getFileBadgeIcon($fileTypeKey, 20) ?>
                                    </div>
                                    <div class="repo-doc-card-main">
                                        <a href="javascript:void(0)" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['title'])) ?>', '<?= $previewType ?>', '<?= htmlspecialchars(addslashes($docTypeLabel)) ?>', '<?= htmlspecialchars(addslashes($fileTypeMeta['label'])) ?>')" class="repo-doc-title">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </a>
                                        <div class="repo-doc-meta-row">
                                            <span class="repo-doc-cat-tag"><?= htmlspecialchars($docTypeLabel) ?></span>
                                            <span class="repo-doc-meta-dot">&bull;</span>
                                            <span class="repo-doc-date"><?= date('M d, Y', strtotime($row['date_uploaded'])) ?></span>
                                            <?php if ($row['file_size']): ?>
                                                <span class="repo-doc-meta-dot">&bull;</span>
                                                <span class="repo-doc-size"><?= formatFileSize($row['file_size']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($row['description']): ?>
                                    <p class="repo-doc-desc"><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 110, '…')) ?></p>
                                <?php endif; ?>

                                <div class="repo-doc-card-actions">
                                    <button type="button" class="repo-doc-action-btn preview" onclick="openMediaModal('<?= htmlspecialchars($pubUrl) ?>', '<?= htmlspecialchars(addslashes($row['title'])) ?>', '<?= $previewType ?>', '<?= htmlspecialchars(addslashes($docTypeLabel)) ?>', '<?= htmlspecialchars(addslashes($fileTypeMeta['label'])) ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <span>Preview</span>
                                    </button>
                                    <a href="<?= htmlspecialchars($pubUrl) ?>" class="repo-doc-action-btn download" target="_blank" <?= ($isExternal || $isVideo) ? '' : 'download' ?>>
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
                                        <span><?= $isExternal ? 'Open' : 'Download' ?></span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mobile Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="portal-pagination" style="margin-top: 1rem;">
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

                <!-- DESKTOP VIEW: Data Table -->
                <div class="portal-table-wrapper repo-desktop-table">
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
        let currentCategory = '<?= htmlspecialchars(addslashes($category)) ?>';
        let currentFileType = '<?= htmlspecialchars(addslashes($fileTypeFilter)) ?>';

        function selectDocType(docType) {
            currentCategory = docType;
            closeBottomSheet('sheetDocType');
            navigateToFilters();
        }

        function selectFileType(fileType) {
            currentFileType = fileType;
            closeBottomSheet('sheetFileType');
            navigateToFilters();
        }

        function navigateToFilters() {
            const searchInput = document.getElementById('repoSearchMobile') || document.getElementById('repoSearch');
            const searchVal = searchInput ? searchInput.value.trim() : '';

            const url = new URL(window.location.origin + window.location.pathname);
            if (currentCategory) url.searchParams.set('category', currentCategory);
            if (currentFileType) url.searchParams.set('file_type', currentFileType);
            if (searchVal) url.searchParams.set('search', searchVal);

            window.location.href = url.toString();
        }

        function applySearch() {
            const val = document.getElementById('repoSearch').value.trim();
            const url = new URL(window.location.origin + window.location.pathname);
            if (currentCategory) url.searchParams.set('category', currentCategory);
            if (currentFileType) url.searchParams.set('file_type', currentFileType);
            if (val) url.searchParams.set('search', val);

            window.location.href = url.toString();
        }

        function applySearchMobile() {
            const val = document.getElementById('repoSearchMobile').value.trim();
            const url = new URL(window.location.origin + window.location.pathname);
            if (currentCategory) url.searchParams.set('category', currentCategory);
            if (currentFileType) url.searchParams.set('file_type', currentFileType);
            if (val) url.searchParams.set('search', val);

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
