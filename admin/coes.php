<?php
/**
 * Admin — Centers of Excellence CRUD
 * List all, add, edit, and delete COE entries.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getPortalDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$msgType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $institution_name = trim($_POST['institution_name'] ?? '');
        $region           = trim($_POST['region'] ?? '');
        $province         = trim($_POST['province'] ?? '');
        $address          = trim($_POST['address'] ?? '');
        $designation_date = $_POST['designation_date'] ?? '';
        $status           = $_POST['status'] ?? 'active';
        $contact_info     = trim($_POST['contact_info'] ?? '');
        $description      = trim($_POST['description'] ?? '');
        $doc_link         = trim($_POST['doc_link'] ?? '');
        $editId           = (int)($_POST['edit_id'] ?? 0);

        if ($institution_name === '') {
            $msg = 'Institution name is required.';
            $msgType = 'error';
        } else {
            $logoPath = '';

            // Handle logo upload
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/coes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $origName = basename($_FILES['logo_file']['name']);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowedImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if ($_FILES['logo_file']['size'] > PORTAL_MAX_FILE_SIZE) {
                    $msg = 'Image is too large. Maximum size is 25MB.';
                    $msgType = 'error';
                } elseif (!in_array($ext, $allowedImg)) {
                    $msg = 'Only image files (JPG, PNG, GIF, WebP) are allowed.';
                    $msgType = 'error';
                } else {
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $origName);
                    if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . $safeName)) {
                        $logoPath = 'uploads/coes/' . $safeName;
                    } else {
                        $msg = 'Failed to upload image.';
                        $msgType = 'error';
                    }
                }
            }

            if ($msgType !== 'error') {
                try {
                    if ($postAction === 'add') {
                        $stmt = $pdo->prepare("INSERT INTO centers_of_excellence (institution_name, region, province, address, designation_date, status, contact_info, description, logo_path, doc_link) VALUES (:name, :region, :prov, :addr, :ddate, :status, :contact, :desc, :logo, :dlink)");
                        $stmt->execute([
                            ':name'    => $institution_name,
                            ':region'  => $region,
                            ':prov'    => $province,
                            ':addr'    => $address,
                            ':ddate'   => $designation_date ?: null,
                            ':status'  => $status,
                            ':contact' => $contact_info,
                            ':desc'    => $description,
                            ':logo'    => $logoPath ?: null,
                            ':dlink'   => $doc_link ?: null,
                        ]);
                        $msg = 'Institution added successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    } elseif ($postAction === 'edit' && $editId > 0) {
                        if ($logoPath !== '') {
                            $stmt = $pdo->prepare("UPDATE centers_of_excellence SET institution_name=:name, region=:region, province=:prov, address=:addr, designation_date=:ddate, status=:status, contact_info=:contact, description=:desc, logo_path=:logo, doc_link=:dlink WHERE id=:id");
                            $stmt->execute([
                                ':name'    => $institution_name,
                                ':region'  => $region,
                                ':prov'    => $province,
                                ':addr'    => $address,
                                ':ddate'   => $designation_date ?: null,
                                ':status'  => $status,
                                ':contact' => $contact_info,
                                ':desc'    => $description,
                                ':logo'    => $logoPath,
                                ':dlink'   => $doc_link ?: null,
                                ':id'      => $editId,
                            ]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE centers_of_excellence SET institution_name=:name, region=:region, province=:prov, address=:addr, designation_date=:ddate, status=:status, contact_info=:contact, description=:desc, doc_link=:dlink WHERE id=:id");
                            $stmt->execute([
                                ':name'    => $institution_name,
                                ':region'  => $region,
                                ':prov'    => $province,
                                ':addr'    => $address,
                                ':ddate'   => $designation_date ?: null,
                                ':status'  => $status,
                                ':contact' => $contact_info,
                                ':desc'    => $description,
                                ':dlink'   => $doc_link ?: null,
                                ':id'      => $editId,
                            ]);
                        }
                        $msg = 'Institution updated successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    error_log("Admin COE error: " . $e->getMessage());
                    $msg = 'Database error. Please try again.';
                    $msgType = 'error';
                }
            }
        }
    } elseif ($postAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $stmt = $pdo->prepare("SELECT logo_path FROM centers_of_excellence WHERE id = :id");
                $stmt->execute([':id' => $deleteId]);
                $row = $stmt->fetch();
                if ($row && $row['logo_path']) {
                    $fullPath = __DIR__ . '/../' . $row['logo_path'];
                    if (file_exists($fullPath)) unlink($fullPath);
                }
                $pdo->prepare("DELETE FROM centers_of_excellence WHERE id = :id")->execute([':id' => $deleteId]);
                $msg = 'Institution deleted successfully.';
                $msgType = 'success';
            } catch (PDOException $e) {
                error_log("Admin COE delete error: " . $e->getMessage());
                $msg = 'Failed to delete institution.';
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
    $records = $pdo->query("SELECT * FROM centers_of_excellence ORDER BY region ASC, institution_name ASC")->fetchAll();
} elseif ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM centers_of_excellence WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editRecord = $stmt->fetch();
    if (!$editRecord) {
        $msg = 'Institution not found.';
        $msgType = 'error';
        $action = 'list';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centers of Excellence — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <?php $activePage = 'coes'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>Centers of Excellence</h1>
            </div>
            <div class="admin-topbar-actions">
                <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="btn-admin btn-admin-primary" style="background:#059669">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Institution
                    </a>
                <?php else: ?>
                    <a href="coes.php" class="btn-admin btn-admin-edit">Back to List</a>
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path></svg>
                            <p>No institutions found. Add your first Center of Excellence.</p>
                            <a href="?action=add" class="btn-admin btn-admin-primary" style="background:#059669">Add Institution</a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Institution Name</th>
                                        <th>Region</th>
                                        <th>Province</th>
                                        <th>Status</th>
                                        <th>Designation Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $row): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['institution_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($row['province'] ?? '—') ?></td>
                                            <td>
                                                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:600;<?= $row['status'] === 'active' ? 'background:rgba(16,185,129,0.1);color:#059669' : 'background:rgba(239,68,68,0.1);color:#dc2626' ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $row['designation_date'] ? date('M d, Y', strtotime($row['designation_date'])) : '—' ?></td>
                                            <td>
                                                <div style="display:flex;gap:0.35rem">
                                                    <a href="?action=edit&id=<?= $row['id'] ?>" class="btn-admin btn-admin-edit">Edit</a>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this institution?')">
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
                        <h2><?= $action === 'add' ? 'Add New Institution' : 'Edit Institution' ?></h2>
                    </div>
                    <div style="padding:1.5rem">
                        <form method="POST" enctype="multipart/form-data" class="admin-form">
                            <input type="hidden" name="form_action" value="<?= $action ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="edit_id" value="<?= $editRecord['id'] ?>">
                            <?php endif; ?>

                            <div class="admin-form-group">
                                <label for="institution_name">Institution Name <span class="required">*</span></label>
                                <input type="text" id="institution_name" name="institution_name" required value="<?= htmlspecialchars($editRecord['institution_name'] ?? $_POST['institution_name'] ?? '') ?>">
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                                <div class="admin-form-group">
                                    <label for="region">Region</label>
                                    <input type="text" id="region" name="region" placeholder="e.g. Region IV-A" value="<?= htmlspecialchars($editRecord['region'] ?? $_POST['region'] ?? '') ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="province">Province</label>
                                    <input type="text" id="province" name="province" value="<?= htmlspecialchars($editRecord['province'] ?? $_POST['province'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" style="min-height:60px"><?= htmlspecialchars($editRecord['address'] ?? $_POST['address'] ?? '') ?></textarea>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                                <div class="admin-form-group">
                                    <label for="designation_date">Designation Date</label>
                                    <input type="date" id="designation_date" name="designation_date" value="<?= htmlspecialchars($editRecord['designation_date'] ?? $_POST['designation_date'] ?? '') ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status">
                                        <option value="active" <?= ($editRecord['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($editRecord['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <label for="contact_info">Contact Info</label>
                                <input type="text" id="contact_info" name="contact_info" placeholder="Phone, email, etc." value="<?= htmlspecialchars($editRecord['contact_info'] ?? $_POST['contact_info'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description"><?= htmlspecialchars($editRecord['description'] ?? $_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="admin-form-group">
                                <label for="doc_link">Document / Website Link <small style="font-weight:normal;color:var(--admin-text-muted)">(e.g. Google Drive Brochure, Website, Official Document URL)</small></label>
                                <input type="url" id="doc_link" name="doc_link" placeholder="https://drive.google.com/... or https://example.edu.ph" value="<?= htmlspecialchars($editRecord['doc_link'] ?? $_POST['doc_link'] ?? '') ?>">
                                <p class="file-info">Add a link so users can preview and view official institution documents or website links.</p>
                            </div>

                            <div class="admin-form-group">
                                <label for="logo_file">Institution Logo/Image <?= $action === 'edit' ? '(leave empty to keep current)' : '' ?></label>
                                <input type="file" id="logo_file" name="logo_file" accept="image/*">
                                <p class="file-info">Allowed: JPG, PNG, GIF, WebP. Max 25MB.</p>
                                <?php if ($action === 'edit' && $editRecord['logo_path']): ?>
                                    <p class="file-info">Current: <?= htmlspecialchars(basename($editRecord['logo_path'])) ?></p>
                                    <img src="../<?= htmlspecialchars($editRecord['logo_path']) ?>" alt="Current logo" style="max-width:120px;max-height:80px;margin-top:0.5rem;border-radius:6px;border:1px solid var(--admin-border)">
                                <?php endif; ?>
                            </div>

                            <div style="display:flex;gap:0.75rem;margin-top:1.5rem">
                                <button type="submit" class="btn-admin btn-admin-primary" style="background:#059669">
                                    <?= $action === 'add' ? 'Add Institution' : 'Save Changes' ?>
                                </button>
                                <a href="coes.php" class="btn-admin btn-admin-edit">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
