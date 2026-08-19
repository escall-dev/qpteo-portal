<?php
/**
 * Admin — Repositories CRUD
 * List all, add, edit, and delete repository documents with Document Types and File Types.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/upload_helper.php';

$pdo = getPortalDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$msgType = '';

// Valid Document Types (Image 1)
$documentTypes = [
    'presentations'              => 'Presentations',
    'concept_papers'             => 'Concept Papers',
    'checklists'                 => 'Checklists',
    'briefers'                   => 'Briefers',
    'reports'                    => 'Reports',
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

// Valid File Types (Image 2) with display labels and CSS badge classes
$fileTypes = [
    'slides' => ['label' => 'Slides', 'class' => 'file-badge-slides'],
    'docs'   => ['label' => 'Docs',   'class' => 'file-badge-docs'],
    'sheets' => ['label' => 'Sheets', 'class' => 'file-badge-sheets'],
    'folder' => ['label' => 'Folder', 'class' => 'file-badge-folder'],
    'pdf'    => ['label' => 'PDF',    'class' => 'file-badge-pdf'],
    'others' => ['label' => 'Others', 'class' => 'file-badge-others'],
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $msg = 'The uploaded payload is too large. Maximum allowed size is 100MB.';
        $msgType = 'error';
    }
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $document_type = $_POST['document_type'] ?? ($_POST['category'] ?? 'others');
        if ($document_type === 'session_guidelines') $document_type = 'session_guides';
        $category      = $document_type;
        $file_type     = $_POST['file_type'] ?? '';
        $uploaded_by   = trim($_POST['uploaded_by'] ?? '');
        $externalLink  = trim($_POST['external_link'] ?? '');
        $editId        = (int)($_POST['edit_id'] ?? 0);

        if ($title === '') {
            $msg = 'Title is required.';
            $msgType = 'error';
        } else {
            $filePath = '';
            $fileSize = null;
            $detectedFtype = '';

            // Handle folder upload if provided
            if (isset($_FILES['folder_files']) && !empty($_FILES['folder_files']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/repositories/';
                $folderRes = handleFolderUpload($_FILES['folder_files'], $uploadDir, 'uploads/repositories/');
                if (!$folderRes['success']) {
                    $msg = $folderRes['error'];
                    $msgType = 'error';
                } else {
                    $filePath = $folderRes['filePath'];
                    $fileSize = $folderRes['fileSize'];
                    $detectedFtype = 'folder';
                }
            } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/repositories/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $origName = basename($_FILES['document_file']['name']);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $fileSize = $_FILES['document_file']['size'];

                if ($fileSize > PORTAL_MAX_FILE_SIZE) {
                    $msg = 'File is too large. Maximum size is 100MB.';
                    $msgType = 'error';
                } elseif (!in_array($ext, PORTAL_ALLOWED_FILE_TYPES)) {
                    $msg = 'File type not allowed.';
                    $msgType = 'error';
                } else {
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $origName);
                    if (move_uploaded_file($_FILES['document_file']['tmp_name'], $uploadDir . $safeName)) {
                        $filePath = 'uploads/repositories/' . $safeName;
                        if (in_array($ext, ['ppt', 'pptx'])) $detectedFtype = 'slides';
                        elseif (in_array($ext, ['doc', 'docx'])) $detectedFtype = 'docs';
                        elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $detectedFtype = 'sheets';
                        elseif ($ext === 'pdf') $detectedFtype = 'pdf';
                        else $detectedFtype = 'others';
                    } else {
                        $msg = 'Failed to upload file.';
                        $msgType = 'error';
                    }
                }
            } elseif ($externalLink !== '') {
                // Handle external URL/link
                if (!preg_match('/^https?:\/\//i', $externalLink)) {
                    $externalLink = 'https://' . $externalLink;
                }
                $filePath = $externalLink;
                $fileSize = null;
                if (strpos($externalLink, 'drive.google.com/drive/folders') !== false || strpos($externalLink, 'folder') !== false) {
                    $detectedFtype = 'folder';
                } else {
                    $pathExt = strtolower(pathinfo(parse_url($externalLink, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                    if (in_array($pathExt, ['ppt', 'pptx'])) $detectedFtype = 'slides';
                    elseif (in_array($pathExt, ['doc', 'docx'])) $detectedFtype = 'docs';
                    elseif (in_array($pathExt, ['xls', 'xlsx', 'csv'])) $detectedFtype = 'sheets';
                    elseif ($pathExt === 'pdf') $detectedFtype = 'pdf';
                    else $detectedFtype = 'others';
                }
            }

            // Final file_type selection
            if (!$file_type || !isset($fileTypes[$file_type])) {
                $file_type = $detectedFtype ?: 'others';
            }

            if ($msgType !== 'error') {
                try {
                    if ($postAction === 'add') {
                        if ($filePath === '') {
                            $msg = 'Please upload a document file or provide a Document Link / URL.';
                            $msgType = 'error';
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO repositories (title, description, category, document_type, file_type, file_path, uploaded_by, file_size) VALUES (:title, :desc, :cat, :doctype, :ftype, :fpath, :upby, :fsize)");
                            $stmt->execute([
                                ':title'   => $title,
                                ':desc'    => $description,
                                ':cat'     => $category,
                                ':doctype' => $document_type,
                                ':ftype'   => $file_type,
                                ':fpath'   => $filePath,
                                ':upby'    => $uploaded_by,
                                ':fsize'   => $fileSize,
                            ]);
                            $msg = 'Document added successfully.';
                            $msgType = 'success';
                            $action = 'list';
                        }
                    } elseif ($postAction === 'edit' && $editId > 0) {
                        if ($filePath !== '') {
                            $stmt = $pdo->prepare("UPDATE repositories SET title=:title, description=:desc, category=:cat, document_type=:doctype, file_type=:ftype, file_path=:fpath, uploaded_by=:upby, file_size=:fsize WHERE id=:id");
                            $stmt->execute([
                                ':title'   => $title,
                                ':desc'    => $description,
                                ':cat'     => $category,
                                ':doctype' => $document_type,
                                ':ftype'   => $file_type,
                                ':fpath'   => $filePath,
                                ':upby'    => $uploaded_by,
                                ':fsize'   => $fileSize,
                                ':id'      => $editId,
                            ]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE repositories SET title=:title, description=:desc, category=:cat, document_type=:doctype, file_type=:ftype, uploaded_by=:upby WHERE id=:id");
                            $stmt->execute([
                                ':title'   => $title,
                                ':desc'    => $description,
                                ':cat'     => $category,
                                ':doctype' => $document_type,
                                ':ftype'   => $file_type,
                                ':upby'    => $uploaded_by,
                                ':id'      => $editId,
                            ]);
                        }
                        $msg = 'Document updated successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    error_log("Admin repo error: " . $e->getMessage());
                    $msg = 'Database error. Please try again.';
                    $msgType = 'error';
                }
            }
        }
    } elseif ($postAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $stmt = $pdo->prepare("SELECT file_path FROM repositories WHERE id = :id");
                $stmt->execute([':id' => $deleteId]);
                $row = $stmt->fetch();
                if ($row && $row['file_path']) {
                    $fullPath = __DIR__ . '/../' . $row['file_path'];
                    if (file_exists($fullPath)) unlink($fullPath);
                }

                $pdo->prepare("DELETE FROM repositories WHERE id = :id")->execute([':id' => $deleteId]);
                $msg = 'Document deleted successfully.';
                $msgType = 'success';
            } catch (PDOException $e) {
                error_log("Admin repo delete error: " . $e->getMessage());
                $msg = 'Failed to delete document.';
                $msgType = 'error';
            }
        }
        $action = 'list';
    }
}

// Fetch data for list/edit
$records = [];
$editRecord = null;

if ($action === 'list') {
    $filterDocType = $_GET['filter'] ?? '';
    if ($filterDocType === 'session_guidelines') $filterDocType = 'session_guides';
    $where = '';
    $params = [];
    if ($filterDocType && isset($documentTypes[$filterDocType])) {
        $where = 'WHERE document_type = :dt OR category = :dt2';
        $params[':dt']  = $filterDocType;
        $params[':dt2'] = $filterDocType;
    }
    $stmt = $pdo->prepare("SELECT * FROM repositories {$where} ORDER BY date_uploaded DESC");
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} elseif ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM repositories WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editRecord = $stmt->fetch();
    if (!$editRecord) {
        $msg = 'Document not found.';
        $msgType = 'error';
        $action = 'list';
    }
}

function formatFileSize($bytes) {
    if (!$bytes) return '—';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
    return round($bytes, 1) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repositories — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <?php $activePage = 'repositories'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>Repositories Management</h1>
            </div>
            <div class="admin-topbar-actions">
                <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="btn-admin btn-admin-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Document
                    </a>
                <?php else: ?>
                    <a href="repositories.php" class="btn-admin btn-admin-edit">Back to List</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-content">

            <?php if ($msg): ?>
                <div class="admin-alert admin-alert-<?= $msgType ?>">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- Category / Document Type Filter -->
                <div style="margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:0.4rem;align-items:center;">
                    <span style="font-size:0.85rem;font-weight:700;color:var(--admin-navy);">Filter Document Type:</span>
                    <a href="repositories.php" class="btn-admin btn-admin-sm <?= empty($_GET['filter']) ? 'btn-admin-primary' : 'btn-admin-edit' ?>">All</a>
                    <?php foreach ($documentTypes as $key => $label): ?>
                        <a href="?filter=<?= $key ?>" class="btn-admin btn-admin-sm <?= ($_GET['filter'] ?? '') === $key ? 'btn-admin-primary' : 'btn-admin-edit' ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="admin-card">
                    <?php if (empty($records)): ?>
                        <div class="admin-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <p>No documents found. Add your first document.</p>
                            <a href="?action=add" class="btn-admin btn-admin-primary">Add Document</a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Document Type</th>
                                        <th>File Format</th>
                                        <th>Uploaded</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $row): 
                                        $targetUrl = preg_match('/^https?:\/\//i', $row['file_path']) ? $row['file_path'] : '../' . ltrim($row['file_path'], '/');
                                        $docTypeKey = strtolower($row['document_type'] ?? $row['category'] ?? 'others');
                                        $docTypeLabel = $documentTypes[$docTypeKey] ?? ucfirst(str_replace('_', ' ', $docTypeKey));
                                        $fileTypeKey = strtolower($row['file_type'] ?? 'others');
                                        $fileMeta = $fileTypes[$fileTypeKey] ?? $fileTypes['others'];
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--admin-navy); text-decoration:none; font-weight:700;" title="Click to open file or link">
                                                    <?= htmlspecialchars($row['title']) ?>
                                                </a>
                                            </td>
                                            <td><span class="btn-admin btn-admin-sm btn-admin-edit" style="font-weight:600;"><?= htmlspecialchars($docTypeLabel) ?></span></td>
                                            <td><span class="file-badge <?= $fileMeta['class'] ?>"><?= htmlspecialchars($fileMeta['label']) ?></span></td>
                                            <td><?= date('M d, Y', strtotime($row['date_uploaded'])) ?></td>
                                            <td><?= formatFileSize($row['file_size']) ?></td>
                                            <td>
                                                <div style="display:flex;gap:0.35rem">
                                                    <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-admin btn-admin-edit" style="background:rgba(16,185,129,0.1);color:#059669;" title="Open document or link">Open</a>
                                                    <a href="?action=edit&id=<?= $row['id'] ?>" class="btn-admin btn-admin-edit">Edit</a>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this document?')">
                                                        <input type="hidden" name="form_action" value="delete">
                                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn-admin btn-admin-delete">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2><?= $action === 'add' ? 'Add New Document' : 'Edit Document' ?></h2>
                    </div>
                    <div style="padding:1.5rem">
                        <form method="POST" enctype="multipart/form-data" class="admin-form">
                            <input type="hidden" name="form_action" value="<?= $action ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="edit_id" value="<?= $editRecord['id'] ?>">
                            <?php endif; ?>

                            <div class="admin-form-group">
                                <label for="title">Title <span class="required">*</span></label>
                                <input type="text" id="title" name="title" required value="<?= htmlspecialchars($editRecord['title'] ?? $_POST['title'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description"><?= htmlspecialchars($editRecord['description'] ?? $_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="admin-form-group">
                                <label for="document_type">Document Type <span class="required">*</span></label>
                                <select id="document_type" name="document_type" required>
                                    <?php foreach ($documentTypes as $key => $label): ?>
                                        <?php 
                                            $currDocType = $editRecord['document_type'] ?? $editRecord['category'] ?? $_POST['document_type'] ?? '';
                                            if ($currDocType === 'session_guidelines') $currDocType = 'session_guides';
                                        ?>
                                        <option value="<?= $key ?>" <?= $currDocType === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label for="file_type">File Format / Type <span class="required">*</span></label>
                                <select id="file_type" name="file_type" required>
                                    <?php foreach ($fileTypes as $fKey => $fMeta): ?>
                                        <?php 
                                            $currFtype = $editRecord['file_type'] ?? $_POST['file_type'] ?? '';
                                        ?>
                                        <option value="<?= $fKey ?>" <?= $currFtype === $fKey ? 'selected' : '' ?>><?= $fMeta['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label for="uploaded_by">Uploaded By</label>
                                <input type="text" id="uploaded_by" name="uploaded_by" value="<?= htmlspecialchars($editRecord['uploaded_by'] ?? $_POST['uploaded_by'] ?? '') ?>">
                            </div>

                            <!-- Source Selection -->
                            <div class="admin-form-group">
                                <label style="font-weight:700;margin-bottom:0.5rem;display:block;">Upload Source / Mode</label>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                    <label id="lbl_type_file" style="flex:1;min-width:140px;padding:0.6rem 0.8rem;border:2px solid var(--admin-navy);border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-weight:600;background:#eff6ff;">
                                        <input type="radio" name="upload_source_type" value="file" checked onclick="toggleUploadSource('file')"> Single File
                                    </label>
                                    <label id="lbl_type_folder" style="flex:1;min-width:140px;padding:0.6rem 0.8rem;border:2px solid var(--admin-border);border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-weight:600;background:transparent;">
                                        <input type="radio" name="upload_source_type" value="folder" onclick="toggleUploadSource('folder')"> Folder Upload
                                    </label>
                                    <label id="lbl_type_link" style="flex:1;min-width:140px;padding:0.6rem 0.8rem;border:2px solid var(--admin-border);border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-weight:600;background:transparent;">
                                        <input type="radio" name="upload_source_type" value="link" onclick="toggleUploadSource('link')"> External Link
                                    </label>
                                </div>
                            </div>

                            <!-- Single File Input -->
                            <div id="section_file" class="admin-form-group">
                                <label for="document_file">Upload Document File</label>
                                <input type="file" id="document_file" name="document_file" onchange="onSingleFileSelected(this)">
                                <p class="file-info">Max 100MB. Allowed: Documents (Docs, Sheets, Slides), PDF, Videos, Audio, Images, ZIP</p>
                            </div>

                            <!-- Folder Input -->
                            <div id="section_folder" class="admin-form-group" style="display:none;">
                                <label for="folder_files">Upload Entire Folder</label>
                                <input type="file" id="folder_files" name="folder_files[]" webkitdirectory directory multiple onchange="onFolderSelected(this)">
                                <p class="file-info">Select a directory/folder from your computer. All contained files will be archived and saved (max 100MB).</p>
                                <div id="folder_summary" style="display:none;margin-top:0.5rem;padding:0.6rem 0.8rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534;font-size:0.85rem;font-weight:600;"></div>
                            </div>

                            <!-- External Link Input -->
                            <div id="section_link" class="admin-form-group" style="display:none;">
                                <label for="external_link">Document Link / URL <small style="font-weight:normal;color:var(--admin-text-muted)">(e.g. Google Drive Folder, OneDrive, External Link)</small></label>
                                <input type="url" id="external_link" name="external_link" placeholder="https://drive.google.com/file/d/... or https://example.com/document.pdf" value="<?= htmlspecialchars(($editRecord && preg_match('/^https?:\/\//i', $editRecord['file_path'] ?? '')) ? $editRecord['file_path'] : '') ?>" onchange="onLinkEntered(this)">
                                <p class="file-info">Paste a direct web link or cloud folder link for online documents.</p>
                            </div>

                            <?php if ($action === 'edit' && !empty($editRecord['file_path'])): ?>
                                <?php $currUrl = preg_match('/^https?:\/\//i', $editRecord['file_path']) ? $editRecord['file_path'] : '../' . ltrim($editRecord['file_path'], '/'); ?>
                                <p class="file-info" style="margin-top:0.5rem;font-weight:600;">
                                    Current document source: 
                                    <a href="<?= htmlspecialchars($currUrl) ?>" target="_blank" style="color:var(--admin-navy);text-decoration:underline;">
                                        <?= htmlspecialchars($editRecord['file_path']) ?>
                                    </a> 
                                    (<?= formatFileSize($editRecord['file_size']) ?>)
                                </p>
                            <?php endif; ?>

                            <script>
                            function toggleUploadSource(type) {
                                document.getElementById('section_file').style.display = (type === 'file') ? 'block' : 'none';
                                document.getElementById('section_folder').style.display = (type === 'folder') ? 'block' : 'none';
                                document.getElementById('section_link').style.display = (type === 'link') ? 'block' : 'none';

                                ['file', 'folder', 'link'].forEach(function(t) {
                                    var lbl = document.getElementById('lbl_type_' + t);
                                    if (lbl) {
                                        lbl.style.borderColor = (t === type) ? 'var(--admin-navy)' : 'var(--admin-border)';
                                        lbl.style.background = (t === type) ? '#eff6ff' : 'transparent';
                                    }
                                });

                                if (type === 'folder') {
                                    var fSelect = document.getElementById('file_type');
                                    if (fSelect) fSelect.value = 'folder';
                                }
                            }

                            function onSingleFileSelected(input) {
                                if (!input.files || input.files.length === 0) return;
                                var fname = input.files[0].name.toLowerCase();
                                var fSelect = document.getElementById('file_type');
                                if (!fSelect) return;

                                if (fname.endsWith('.ppt') || fname.endsWith('.pptx')) fSelect.value = 'slides';
                                else if (fname.endsWith('.doc') || fname.endsWith('.docx')) fSelect.value = 'docs';
                                else if (fname.endsWith('.xls') || fname.endsWith('.xlsx') || fname.endsWith('.csv')) fSelect.value = 'sheets';
                                else if (fname.endsWith('.pdf')) fSelect.value = 'pdf';
                            }

                            function onFolderSelected(input) {
                                var files = input.files;
                                var summary = document.getElementById('folder_summary');
                                var fSelect = document.getElementById('file_type');
                                if (fSelect) fSelect.value = 'folder';

                                if (!files || files.length === 0) {
                                    summary.style.display = 'none';
                                    return;
                                }
                                var totalBytes = 0;
                                var folderName = '';
                                for (var i = 0; i < files.length; i++) {
                                    totalBytes += files[i].size;
                                    if (!folderName && files[i].webkitRelativePath) {
                                        folderName = files[i].webkitRelativePath.split('/')[0];
                                    }
                                }
                                var sizeMB = (totalBytes / (1024 * 1024)).toFixed(2);
                                summary.style.display = 'block';
                                summary.innerHTML = 'Selected Folder: <strong>' + (folderName || 'Uploaded Folder') + '</strong> (' + files.length + ' files, ~' + sizeMB + ' MB)';
                                
                                var titleInput = document.getElementById('title');
                                if (titleInput && (!titleInput.value || titleInput.value.trim() === '')) {
                                    titleInput.value = folderName || 'Uploaded Folder';
                                }
                            }

                            function onLinkEntered(input) {
                                var val = input.value.toLowerCase();
                                var fSelect = document.getElementById('file_type');
                                if (!fSelect) return;

                                if (val.includes('folder') || val.includes('drive.google.com/drive/folders')) {
                                    fSelect.value = 'folder';
                                } else if (val.endsWith('.pdf')) {
                                    fSelect.value = 'pdf';
                                }
                            }
                            </script>

                            <div style="display:flex;gap:0.75rem;margin-top:1.5rem">
                                <button type="submit" class="btn-admin btn-admin-primary">
                                    <?= $action === 'add' ? 'Upload Document' : 'Save Changes' ?>
                                </button>
                                <a href="repositories.php" class="btn-admin btn-admin-edit">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
