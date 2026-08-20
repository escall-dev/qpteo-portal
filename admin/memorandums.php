<?php
/**
 * Admin — Memorandums CRUD
 * List all, add, edit, and delete office memorandums.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/upload_helper.php';

$pdo = getPortalDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$msgType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $msg = 'The uploaded payload is too large. Maximum allowed size is 100MB.';
        $msgType = 'error';
    }
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $memo_number = trim($_POST['memo_number'] ?? '');
        $subject     = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date_issued = $_POST['date_issued'] ?? '';
        $issued_by   = trim($_POST['issued_by'] ?? '');
        $editId      = (int)($_POST['edit_id'] ?? 0);

        if ($memo_number === '' || $subject === '' || $date_issued === '') {
            $msg = 'Memo number, subject, and date issued are required.';
            $msgType = 'error';
        } else {
            $filePath = '';
            $fileSize = 0;
            $uploadType = $_POST['memo_upload_type'] ?? 'file';

            if ($uploadType === 'link' && !empty($_POST['external_link'])) {
                $filePath = trim($_POST['external_link']);
                $fileSize = null;
            } elseif (isset($_FILES['folder_files']) && !empty($_FILES['folder_files']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/memorandums/';
                $folderRes = handleFolderUpload($_FILES['folder_files'], $uploadDir, 'uploads/memorandums/');
                if (!$folderRes['success']) {
                    $msg = $folderRes['error'];
                    $msgType = 'error';
                } else {
                    $filePath = $folderRes['filePath'];
                    $fileSize = $folderRes['fileSize'];
                }
            } elseif (isset($_FILES['memo_file']) && $_FILES['memo_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/memorandums/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $origName = basename($_FILES['memo_file']['name']);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $fileSize = $_FILES['memo_file']['size'];

                if ($fileSize > PORTAL_MAX_FILE_SIZE) {
                    $msg = 'File is too large. Maximum size is 100MB.';
                    $msgType = 'error';
                } elseif (!in_array($ext, PORTAL_ALLOWED_FILE_TYPES)) {
                    $msg = 'File type not allowed.';
                    $msgType = 'error';
                } else {
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $origName);
                    if (move_uploaded_file($_FILES['memo_file']['tmp_name'], $uploadDir . $safeName)) {
                        $filePath = 'uploads/memorandums/' . $safeName;
                    } else {
                        $msg = 'Failed to upload file.';
                        $msgType = 'error';
                    }
                }
            }

            if ($msgType !== 'error') {
                try {
                    if ($postAction === 'add') {
                        $stmt = $pdo->prepare("INSERT INTO memorandums (memo_number, subject, description, date_issued, file_path, issued_by, file_size) VALUES (:mnum, :subj, :desc, :di, :fpath, :iby, :fsize)");
                        $stmt->execute([
                            ':mnum'  => $memo_number,
                            ':subj'  => $subject,
                            ':desc'  => $description,
                            ':di'    => $date_issued,
                            ':fpath' => $filePath,
                            ':iby'   => $issued_by,
                            ':fsize' => $fileSize,
                        ]);
                        $msg = 'Memorandum added successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    } elseif ($postAction === 'edit' && $editId > 0) {
                        if ($filePath !== '') {
                            $stmt = $pdo->prepare("UPDATE memorandums SET memo_number=:mnum, subject=:subj, description=:desc, date_issued=:di, file_path=:fpath, issued_by=:iby, file_size=:fsize WHERE id=:id");
                            $stmt->execute([
                                ':mnum'  => $memo_number,
                                ':subj'  => $subject,
                                ':desc'  => $description,
                                ':di'    => $date_issued,
                                ':fpath' => $filePath,
                                ':iby'   => $issued_by,
                                ':fsize' => $fileSize,
                                ':id'    => $editId,
                            ]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE memorandums SET memo_number=:mnum, subject=:subj, description=:desc, date_issued=:di, issued_by=:iby WHERE id=:id");
                            $stmt->execute([
                                ':mnum'  => $memo_number,
                                ':subj'  => $subject,
                                ':desc'  => $description,
                                ':di'    => $date_issued,
                                ':iby'   => $issued_by,
                                ':id'    => $editId,
                            ]);
                        }
                        $msg = 'Memorandum updated successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    error_log("Admin memo error: " . $e->getMessage());
                    $msg = 'Database error. Please try again.';
                    $msgType = 'error';
                }
            }
        }
    } elseif ($postAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $stmt = $pdo->prepare("SELECT file_path FROM memorandums WHERE id = :id");
                $stmt->execute([':id' => $deleteId]);
                $row = $stmt->fetch();
                if ($row && $row['file_path']) {
                    $fullPath = __DIR__ . '/../' . $row['file_path'];
                    if (file_exists($fullPath)) unlink($fullPath);
                }
                $pdo->prepare("DELETE FROM memorandums WHERE id = :id")->execute([':id' => $deleteId]);
                $msg = 'Memorandum deleted successfully.';
                $msgType = 'success';
            } catch (PDOException $e) {
                error_log("Admin memo delete error: " . $e->getMessage());
                $msg = 'Failed to delete memorandum.';
                $msgType = 'error';
            }
        }
        $action = 'list';
    }
}

// Fetch data
$records = [];
$editRecord = null;

if ($action === 'list') {
    $records = $pdo->query("SELECT * FROM memorandums ORDER BY date_issued DESC")->fetchAll();
} elseif ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM memorandums WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editRecord = $stmt->fetch();
    if (!$editRecord) {
        $msg = 'Memorandum not found.';
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
    <title>Memorandums — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <?php $activePage = 'memorandums'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>Memorandums</h1>
            </div>
            <div class="admin-topbar-actions">
                <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="btn-admin btn-admin-gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Memorandum
                    </a>
                <?php else: ?>
                    <a href="memorandums.php" class="btn-admin btn-admin-edit">Back to List</a>
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
                <div class="admin-card">
                    <?php if (empty($records)): ?>
                        <div class="admin-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <p>No memorandums found. Add your first memorandum.</p>
                            <a href="?action=add" class="btn-admin btn-admin-gold">Add Memorandum</a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Memo No.</th>
                                        <th>Subject</th>
                                        <th>Date Issued</th>
                                        <th>Issued By</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $row): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['memo_number']) ?></strong></td>
                                            <td><?= htmlspecialchars(mb_strimwidth($row['subject'], 0, 80, '…')) ?></td>
                                            <td><?= date('M d, Y', strtotime($row['date_issued'])) ?></td>
                                            <td><?= htmlspecialchars($row['issued_by'] ?? '—') ?></td>
                                            <td><?= formatFileSize($row['file_size']) ?></td>
                                            <td>
                                                <div style="display:flex;gap:0.35rem">
                                                    <a href="?action=edit&id=<?= $row['id'] ?>" class="btn-admin btn-admin-edit">Edit</a>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this memorandum?')">
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
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2><?= $action === 'add' ? 'Add New Memorandum' : 'Edit Memorandum' ?></h2>
                    </div>
                    <div style="padding:1.5rem">
                        <form method="POST" enctype="multipart/form-data" class="admin-form">
                            <input type="hidden" name="form_action" value="<?= $action ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="edit_id" value="<?= $editRecord['id'] ?>">
                            <?php endif; ?>

                            <div class="admin-form-group">
                                <label for="memo_number">Memo Number <span class="required">*</span></label>
                                <input type="text" id="memo_number" name="memo_number" required placeholder="e.g. QOM-2026-001" value="<?= htmlspecialchars($editRecord['memo_number'] ?? $_POST['memo_number'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="subject">Subject <span class="required">*</span></label>
                                <input type="text" id="subject" name="subject" required value="<?= htmlspecialchars($editRecord['subject'] ?? $_POST['subject'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description"><?= htmlspecialchars($editRecord['description'] ?? $_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="admin-form-group">
                                <label for="date_issued">Date Issued <span class="required">*</span></label>
                                <input type="date" id="date_issued" name="date_issued" required value="<?= htmlspecialchars($editRecord['date_issued'] ?? $_POST['date_issued'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="issued_by">Issued By</label>
                                <input type="text" id="issued_by" name="issued_by" value="<?= htmlspecialchars($editRecord['issued_by'] ?? $_POST['issued_by'] ?? '') ?>">
                            </div>

                            <!-- Upload Mode Selector -->
                            <div class="admin-form-group">
                                <label style="font-weight:700;margin-bottom:0.5rem;display:block;">Upload Type / Source</label>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                    <label id="lbl_memo_type_file" style="flex:1;min-width:140px;padding:0.6rem 0.8rem;border:2px solid var(--admin-gold, #d97706);border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-weight:600;background:#fffbeb;">
                                        <input type="radio" name="memo_upload_type" value="file" <?= (!preg_match('/^https?:\/\//i', $editRecord['file_path'] ?? '')) ? 'checked' : '' ?> onclick="toggleMemoUploadSource('file')"> Single File
                                    </label>
                                    <label id="lbl_memo_type_folder" style="flex:1;min-width:140px;padding:0.6rem 0.8rem;border:2px solid var(--admin-border);border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-weight:600;background:transparent;">
                                        <input type="radio" name="memo_upload_type" value="folder" onclick="toggleMemoUploadSource('folder')"> Folder Upload
                                    </label>
                                    <label id="lbl_memo_type_link" style="flex:1;min-width:140px;padding:0.6rem 0.8rem;border:2px solid var(--admin-border);border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-weight:600;background:transparent;">
                                        <input type="radio" name="memo_upload_type" value="link" <?= (preg_match('/^https?:\/\//i', $editRecord['file_path'] ?? '')) ? 'checked' : '' ?> onclick="toggleMemoUploadSource('link')"> External Link / URL
                                    </label>
                                </div>
                            </div>

                            <!-- Single File Input -->
                            <div id="section_memo_file" class="admin-form-group" style="<?= preg_match('/^https?:\/\//i', $editRecord['file_path'] ?? '') ? 'display:none;' : '' ?>">
                                <label for="memo_file">Memorandum File <small style="font-weight:normal;color:var(--admin-text-muted)">(Optional)</small></label>
                                <input type="file" id="memo_file" name="memo_file">
                                <p class="file-info">Max 100MB. Allowed: Documents, Spreadsheets, PDF, Videos, Audio, Images, ZIP</p>
                            </div>

                            <!-- Folder Input -->
                            <div id="section_memo_folder" class="admin-form-group" style="display:none;">
                                <label for="folder_files">Upload Memorandum Folder</label>
                                <input type="file" id="folder_files" name="folder_files[]" webkitdirectory directory multiple onchange="onMemoFolderSelected(this)">
                                <p class="file-info">Select a folder containing memorandum files to archive and upload as a package (max 100MB).</p>
                                <div id="memo_folder_summary" style="display:none;margin-top:0.5rem;padding:0.6rem 0.8rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534;font-size:0.85rem;font-weight:600;"></div>
                            </div>

                            <!-- External Link Input -->
                            <div id="section_memo_link" class="admin-form-group" style="<?= preg_match('/^https?:\/\//i', $editRecord['file_path'] ?? '') ? '' : 'display:none;' ?>">
                                <label for="external_link">Document Link / URL <small style="font-weight:normal;color:var(--admin-text-muted)">(e.g. Google Drive File/Folder, OneDrive, External PDF/Web Link)</small></label>
                                <input type="url" id="external_link" name="external_link" placeholder="https://drive.google.com/file/d/... or https://example.com/memo.pdf" value="<?= htmlspecialchars(($editRecord && preg_match('/^https?:\/\//i', $editRecord['file_path'] ?? '')) ? $editRecord['file_path'] : '') ?>">
                                <p class="file-info">Paste a direct web link or cloud document link for online memorandums.</p>
                            </div>

                            <?php if ($action === 'edit' && $editRecord['file_path']): ?>
                                <p class="file-info" style="margin-top:0.5rem;font-weight:600;">
                                    Current source: 
                                    <a href="<?= htmlspecialchars(preg_match('/^https?:\/\//i', $editRecord['file_path']) ? $editRecord['file_path'] : '../' . ltrim($editRecord['file_path'], '/')) ?>" target="_blank" style="color:var(--admin-navy);text-decoration:underline;">
                                        <?= htmlspecialchars($editRecord['file_path']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <script>
                            function toggleMemoUploadSource(type) {
                                document.getElementById('section_memo_file').style.display = (type === 'file') ? 'block' : 'none';
                                document.getElementById('section_memo_folder').style.display = (type === 'folder') ? 'block' : 'none';
                                document.getElementById('section_memo_link').style.display = (type === 'link') ? 'block' : 'none';

                                var lblFile = document.getElementById('lbl_memo_type_file');
                                var lblFolder = document.getElementById('lbl_memo_type_folder');
                                var lblLink = document.getElementById('lbl_memo_type_link');
                                if (lblFile && lblFolder && lblLink) {
                                    lblFile.style.borderColor = (type === 'file') ? 'var(--admin-gold, #d97706)' : 'var(--admin-border)';
                                    lblFile.style.background = (type === 'file') ? '#fffbeb' : 'transparent';
                                    lblFolder.style.borderColor = (type === 'folder') ? 'var(--admin-gold, #d97706)' : 'var(--admin-border)';
                                    lblFolder.style.background = (type === 'folder') ? '#fffbeb' : 'transparent';
                                    lblLink.style.borderColor = (type === 'link') ? 'var(--admin-gold, #d97706)' : 'var(--admin-border)';
                                    lblLink.style.background = (type === 'link') ? '#fffbeb' : 'transparent';
                                }
                            }

                            function onMemoFolderSelected(input) {
                                var files = input.files;
                                var summary = document.getElementById('memo_folder_summary');
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
                                
                                var subjectInput = document.getElementById('subject');
                                if (subjectInput && (!subjectInput.value || subjectInput.value.trim() === '')) {
                                    subjectInput.value = folderName || 'Uploaded Memorandum Folder';
                                }
                            }
                            </script>

                            <div style="display:flex;gap:0.75rem;margin-top:1.5rem">
                                <button type="submit" class="btn-admin btn-admin-gold">
                                    <?= $action === 'add' ? 'Upload Memorandum' : 'Save Changes' ?>
                                </button>
                                <a href="memorandums.php" class="btn-admin btn-admin-edit">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
