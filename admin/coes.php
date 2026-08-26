<?php
/**
 * Admin — Centers of Excellence CRUD & Content Management
 * List all, filter by category, add, edit, delete COE entries, and edit page overview / challenges.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getPortalDB();
$action = $_GET['action'] ?? 'list';
$catFilter = $_GET['cat'] ?? 'all';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$msgType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'add' || $postAction === 'edit') {
        $institution_name = trim($_POST['institution_name'] ?? '');
        $category         = $_POST['category'] ?? 'national';
        if (!in_array($category, ['national', 'regional'])) $category = 'national';
        $region           = trim($_POST['region'] ?? '');
        $province         = trim($_POST['province'] ?? '');
        $address          = trim($_POST['address'] ?? '');
        $designation_date = $_POST['designation_date'] ?? '';
        $status           = $_POST['status'] ?? 'active';
        $contact_info     = trim($_POST['contact_info'] ?? '');
        $description      = trim($_POST['description'] ?? '');
        $doc_link         = trim($_POST['doc_link'] ?? '');
        $social_media_link = trim($_POST['social_media_link'] ?? '');
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
                        $stmt = $pdo->prepare("INSERT INTO centers_of_excellence (institution_name, category, region, province, address, designation_date, status, contact_info, description, logo_path, doc_link, social_media_link) VALUES (:name, :cat, :region, :prov, :addr, :ddate, :status, :contact, :desc, :logo, :dlink, :smlink)");
                        $stmt->execute([
                            ':name'    => $institution_name,
                            ':cat'     => $category,
                            ':region'  => $region,
                            ':prov'    => $province,
                            ':addr'    => $address,
                            ':ddate'   => $designation_date ?: null,
                            ':status'  => $status,
                            ':contact' => $contact_info,
                            ':desc'    => $description,
                            ':logo'    => $logoPath ?: null,
                            ':dlink'   => $doc_link ?: null,
                            ':smlink'  => $social_media_link ?: null,
                        ]);
                        $msg = 'Institution added successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    } elseif ($postAction === 'edit' && $editId > 0) {
                        if ($logoPath !== '') {
                            $stmt = $pdo->prepare("UPDATE centers_of_excellence SET institution_name=:name, category=:cat, region=:region, province=:prov, address=:addr, designation_date=:ddate, status=:status, contact_info=:contact, description=:desc, logo_path=:logo, doc_link=:dlink, social_media_link=:smlink WHERE id=:id");
                            $stmt->execute([
                                ':name'    => $institution_name,
                                ':cat'     => $category,
                                ':region'  => $region,
                                ':prov'    => $province,
                                ':addr'    => $address,
                                ':ddate'   => $designation_date ?: null,
                                ':status'  => $status,
                                ':contact' => $contact_info,
                                ':desc'    => $description,
                                ':logo'    => $logoPath,
                                ':dlink'   => $doc_link ?: null,
                                ':smlink'  => $social_media_link ?: null,
                                ':id'      => $editId,
                            ]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE centers_of_excellence SET institution_name=:name, category=:cat, region=:region, province=:prov, address=:addr, designation_date=:ddate, status=:status, contact_info=:contact, description=:desc, doc_link=:dlink, social_media_link=:smlink WHERE id=:id");
                            $stmt->execute([
                                ':name'    => $institution_name,
                                ':cat'     => $category,
                                ':region'  => $region,
                                ':prov'    => $province,
                                ':addr'    => $address,
                                ':ddate'   => $designation_date ?: null,
                                ':status'  => $status,
                                ':contact' => $contact_info,
                                ':desc'    => $description,
                                ':dlink'   => $doc_link ?: null,
                                ':smlink'  => $social_media_link ?: null,
                                ':id'      => $editId,
                            ]);
                        }
                        $msg = 'Institution updated successfully.';
                        $msgType = 'success';
                        $action = 'list';
                    }
                } catch (PDOException $e) {
                    error_log("Admin COE error: " . $e->getMessage());
                    $msg = 'Database error: ' . $e->getMessage();
                    $msgType = 'error';
                }
            }
        }
    } elseif ($postAction === 'update_content') {
        // Save National & Regional COE content
        $natTitle     = trim($_POST['coe_national_title'] ?? 'NATIONAL COEs');
        $natIntro     = trim($_POST['coe_national_intro'] ?? '');
        $natChallHead = trim($_POST['coe_national_challenges_title'] ?? 'Priority challenges requiring national-level action');
        $natChall     = trim($_POST['coe_national_challenges'] ?? '');

        $regTitle     = trim($_POST['coe_regional_title'] ?? 'REGIONAL COEs');
        $regIntro     = trim($_POST['coe_regional_intro'] ?? '');
        $regChallHead = trim($_POST['coe_regional_challenges_title'] ?? 'Challenges that the Regional Teacher Education COEs need to address');
        $regChall     = trim($_POST['coe_regional_challenges'] ?? '');

        // Validate JSON if JSON syntax is used
        $natChallDecoded = json_decode($natChall, true);
        if ($natChall && json_last_error() !== JSON_ERROR_NONE) {
            $msg = 'Invalid JSON in National Challenges. Please check formatting.';
            $msgType = 'error';
            $action = 'content';
        }

        $regChallDecoded = json_decode($regChall, true);
        if ($regChall && json_last_error() !== JSON_ERROR_NONE) {
            $msg = 'Invalid JSON in Regional Challenges. Please check formatting.';
            $msgType = 'error';
            $action = 'content';
        }

        if ($msgType !== 'error') {
            try {
                $updates = [
                    'coe_national_title'            => $natTitle,
                    'coe_national_intro'            => $natIntro,
                    'coe_national_challenges_title' => $natChallHead,
                    'coe_national_challenges'       => $natChall,
                    'coe_regional_title'            => $regTitle,
                    'coe_regional_intro'            => $regIntro,
                    'coe_regional_challenges_title' => $regChallHead,
                    'coe_regional_challenges'       => $regChall
                ];

                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2");
                foreach ($updates as $k => $v) {
                    $stmt->execute([':k' => $k, ':v' => $v, ':v2' => $v]);
                }

                $msg = 'COEs Overview and Challenges content updated successfully.';
                $msgType = 'success';
            } catch (PDOException $e) {
                error_log("Update COE content error: " . $e->getMessage());
                $msg = 'Failed to update content.';
                $msgType = 'error';
            }
            $action = 'content';
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
$contentSettings = [];

if ($action === 'list') {
    $where = [];
    $params = [];
    if ($catFilter === 'national' || $catFilter === 'regional') {
        $where[] = 'category = :cat';
        $params[':cat'] = $catFilter;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT * FROM centers_of_excellence {$whereSql} ORDER BY category ASC, region ASC, institution_name ASC");
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    // Counts
    $countTotal    = (int)$pdo->query("SELECT COUNT(*) FROM centers_of_excellence")->fetchColumn();
    $countNational = (int)$pdo->query("SELECT COUNT(*) FROM centers_of_excellence WHERE category = 'national'")->fetchColumn();
    $countRegional = (int)$pdo->query("SELECT COUNT(*) FROM centers_of_excellence WHERE category = 'regional'")->fetchColumn();

} elseif ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM centers_of_excellence WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editRecord = $stmt->fetch();
    if (!$editRecord) {
        $msg = 'Institution not found.';
        $msgType = 'error';
        $action = 'list';
    }
} elseif ($action === 'content') {
    $keys = [
        'coe_national_title', 'coe_national_intro', 'coe_national_challenges_title', 'coe_national_challenges',
        'coe_regional_title', 'coe_regional_intro', 'coe_regional_challenges_title', 'coe_regional_challenges'
    ];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'coe_%'");
    $fetched = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($keys as $k) {
        $contentSettings[$k] = $fetched[$k] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centers of Excellence — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <style>
        .cat-pill-filter {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.45rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--admin-text);
            background: #ffffff;
            border: 1px solid var(--admin-border);
            transition: all 0.2s ease;
        }
        .cat-pill:hover {
            border-color: var(--admin-navy);
            color: var(--admin-navy);
        }
        .cat-pill.active {
            background: var(--admin-navy);
            color: #ffffff;
            border-color: var(--admin-navy);
        }
        .cat-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.65rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .cat-badge-national {
            background: rgba(4, 4, 132, 0.1);
            color: #040484;
            border: 1px solid rgba(4, 4, 132, 0.25);
        }
        .cat-badge-regional {
            background: rgba(237, 172, 54, 0.18);
            color: #b8860b;
            border: 1px solid rgba(237, 172, 54, 0.4);
        }
        .content-section-box {
            background: #f8fafc;
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 1.5rem;
            margin-bottom: 1.75rem;
        }
        .content-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--admin-navy);
            margin-bottom: 1rem;
            padding-bottom: 0.4rem;
            border-bottom: 2px solid var(--admin-gold);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
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
                    <a href="?action=content" class="btn-admin" style="background:#040484;color:#fff">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Edit Overview & Challenges Text
                    </a>
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
                
                <!-- Category Filter Tabs -->
                <div class="cat-pill-filter">
                    <a href="?cat=all" class="cat-pill <?= $catFilter === 'all' ? 'active' : '' ?>">
                        All COEs (<?= $countTotal ?>)
                    </a>
                    <a href="?cat=national" class="cat-pill <?= $catFilter === 'national' ? 'active' : '' ?>">
                        National COEs (<?= $countNational ?>)
                    </a>
                    <a href="?cat=regional" class="cat-pill <?= $catFilter === 'regional' ? 'active' : '' ?>">
                        Regional COEs (<?= $countRegional ?>)
                    </a>
                </div>

                <div class="admin-card">
                    <?php if (empty($records)): ?>
                        <div class="admin-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path></svg>
                            <p>No institutions found<?= $catFilter !== 'all' ? ' in this category' : '' ?>. Add your first Center of Excellence.</p>
                            <a href="?action=add" class="btn-admin btn-admin-primary" style="background:#059669">Add Institution</a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Institution Name</th>
                                        <th>Category</th>
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
                                            <td>
                                                <div style="display:flex;align-items:center;gap:0.75rem">
                                                    <?php if ($row['logo_path'] && file_exists(__DIR__ . '/../' . $row['logo_path'])): ?>
                                                        <img src="../<?= htmlspecialchars($row['logo_path']) ?>" alt="" style="width:34px;height:34px;object-fit:contain;border-radius:50%;background:#fff;border:1px solid #ddd;padding:2px">
                                                    <?php endif; ?>
                                                    <strong><?= htmlspecialchars($row['institution_name']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="cat-badge cat-badge-<?= htmlspecialchars($row['category'] ?? 'national') ?>">
                                                    <?= ($row['category'] ?? 'national') === 'regional' ? 'Regional COE' : 'National COE' ?>
                                                </span>
                                            </td>
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

            <?php elseif ($action === 'content'): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Manage Overview & Challenges Text (Public Portal)</h2>
                    </div>
                    <div style="padding:1.5rem">
                        <p style="font-size:0.9rem;color:var(--admin-text-muted);margin-bottom:1.5rem">
                            Edit the header titles, introductory descriptions, and priority challenges displayed on the public Centers of Excellence page.
                        </p>

                        <form method="POST" class="admin-form">
                            <input type="hidden" name="form_action" value="update_content">

                            <!-- Section 1: National COEs -->
                            <div class="content-section-box">
                                <h3 class="content-section-title">
                                    <span class="cat-badge cat-badge-national">National</span>
                                    National COEs Section
                                </h3>

                                <div class="admin-form-group">
                                    <label for="coe_national_title">Section Title <span class="required">*</span></label>
                                    <input type="text" id="coe_national_title" name="coe_national_title" required value="<?= htmlspecialchars($contentSettings['coe_national_title'] ?? 'NATIONAL COEs') ?>">
                                </div>

                                <div class="admin-form-group">
                                    <label for="coe_national_intro">Introductory Paragraph <span class="required">*</span></label>
                                    <textarea id="coe_national_intro" name="coe_national_intro" rows="4" required style="min-height:90px"><?= htmlspecialchars($contentSettings['coe_national_intro'] ?? '') ?></textarea>
                                </div>

                                <div class="admin-form-group">
                                    <label for="coe_national_challenges_title">Challenges Subheading <span class="required">*</span></label>
                                    <input type="text" id="coe_national_challenges_title" name="coe_national_challenges_title" required value="<?= htmlspecialchars($contentSettings['coe_national_challenges_title'] ?? 'Priority challenges requiring national-level action') ?>">
                                </div>

                                <div class="admin-form-group">
                                    <label for="coe_national_challenges">Challenges (Structured JSON list)</label>
                                    <textarea id="coe_national_challenges" name="coe_national_challenges" rows="12" style="font-family:monospace;font-size:0.85rem"><?= htmlspecialchars($contentSettings['coe_national_challenges'] ?? '') ?></textarea>
                                    <p class="file-info">Array of items with <code>"title"</code> and <code>"description"</code>.</p>
                                </div>
                            </div>

                            <!-- Section 2: Regional COEs -->
                            <div class="content-section-box">
                                <h3 class="content-section-title">
                                    <span class="cat-badge cat-badge-regional">Regional</span>
                                    Regional COEs Section
                                </h3>

                                <div class="admin-form-group">
                                    <label for="coe_regional_title">Section Title <span class="required">*</span></label>
                                    <input type="text" id="coe_regional_title" name="coe_regional_title" required value="<?= htmlspecialchars($contentSettings['coe_regional_title'] ?? 'REGIONAL COEs') ?>">
                                </div>

                                <div class="admin-form-group">
                                    <label for="coe_regional_intro">Introductory Paragraph <span class="required">*</span></label>
                                    <textarea id="coe_regional_intro" name="coe_regional_intro" rows="4" required style="min-height:90px"><?= htmlspecialchars($contentSettings['coe_regional_intro'] ?? '') ?></textarea>
                                </div>

                                <div class="admin-form-group">
                                    <label for="coe_regional_challenges_title">Challenges Subheading <span class="required">*</span></label>
                                    <input type="text" id="coe_regional_challenges_title" name="coe_regional_challenges_title" required value="<?= htmlspecialchars($contentSettings['coe_regional_challenges_title'] ?? 'Challenges that the Regional Teacher Education COEs need to address') ?>">
                                </div>

                                <div class="admin-form-group">
                                    <label for="coe_regional_challenges">Challenges (Structured JSON list with categories)</label>
                                    <textarea id="coe_regional_challenges" name="coe_regional_challenges" rows="14" style="font-family:monospace;font-size:0.85rem"><?= htmlspecialchars($contentSettings['coe_regional_challenges'] ?? '') ?></textarea>
                                    <p class="file-info">Array of categories with <code>"category"</code> and <code>"items"</code>.</p>
                                </div>
                            </div>

                            <div style="display:flex;gap:0.75rem;margin-top:1.5rem">
                                <button type="submit" class="btn-admin btn-admin-primary" style="background:#040484">
                                    Save Page Content
                                </button>
                                <a href="coes.php" class="btn-admin btn-admin-edit">Cancel</a>
                            </div>
                        </form>
                    </div>
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

                            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem">
                                <div class="admin-form-group">
                                    <label for="institution_name">Institution Name <span class="required">*</span></label>
                                    <input type="text" id="institution_name" name="institution_name" required value="<?= htmlspecialchars($editRecord['institution_name'] ?? $_POST['institution_name'] ?? '') ?>">
                                </div>

                                <div class="admin-form-group">
                                    <label for="category">COE Classification / Category <span class="required">*</span></label>
                                    <select id="category" name="category" required style="font-weight:600">
                                        <option value="national" <?= ($editRecord['category'] ?? $_POST['category'] ?? 'national') === 'national' ? 'selected' : '' ?>>National COE</option>
                                        <option value="regional" <?= ($editRecord['category'] ?? $_POST['category'] ?? '') === 'regional' ? 'selected' : '' ?>>Regional COE</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                                <div class="admin-form-group">
                                    <label for="region">Region</label>
                                    <input type="text" id="region" name="region" placeholder="e.g. National Capital Region, Region V, Region VII" value="<?= htmlspecialchars($editRecord['region'] ?? $_POST['region'] ?? '') ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="province">Province</label>
                                    <input type="text" id="province" name="province" placeholder="e.g. Albay, Cebu, Isabela" value="<?= htmlspecialchars($editRecord['province'] ?? $_POST['province'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <label for="address">Address <small style="font-weight:normal;color:var(--admin-text-muted)">(e.g. Taft Avenue cor. Ayala Boulevard, Ermita, Manila)</small></label>
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
                                <label for="doc_link">Website Link <small style="font-weight:normal;color:var(--admin-text-muted)">(Official institution website URL)</small></label>
                                <input type="url" id="doc_link" name="doc_link" placeholder="https://example.edu.ph" value="<?= htmlspecialchars($editRecord['doc_link'] ?? $_POST['doc_link'] ?? '') ?>">
                                <p class="file-info">The official website link shown on the public COEs page (Website button).</p>
                            </div>

                            <div class="admin-form-group">
                                <label for="social_media_link">Social Media Link <small style="font-weight:normal;color:var(--admin-text-muted)">(Facebook, Twitter/X, Instagram, etc.)</small></label>
                                <input type="url" id="social_media_link" name="social_media_link" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($editRecord['social_media_link'] ?? $_POST['social_media_link'] ?? '') ?>">
                                <p class="file-info">Social media page link shown on the public COEs page (Social Media button).</p>
                            </div>

                            <div class="admin-form-group">
                                <label for="logo_file">Institution Logo/Image <?= $action === 'edit' ? '(leave empty to keep current)' : '' ?></label>
                                <input type="file" id="logo_file" name="logo_file" accept="image/*">
                                <p class="file-info">Allowed: JPG, PNG, GIF, WebP. Max 25MB.</p>
                                <?php if ($action === 'edit' && $editRecord['logo_path']): ?>
                                    <p class="file-info">Current: <?= htmlspecialchars(basename($editRecord['logo_path'])) ?></p>
                                    <img src="../<?= htmlspecialchars($editRecord['logo_path']) ?>" alt="Current logo" style="max-width:120px;max-height:80px;margin-top:0.5rem;border-radius:6px;border:1px solid var(--admin-border);background:#fff;padding:4px">
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
