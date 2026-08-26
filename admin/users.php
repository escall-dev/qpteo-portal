<?php
/**
 * User Management Page
 * Only accessible by superadmins.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// RBAC: Check for superadmin role
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

$pdo = getPortalDB();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $fullName = trim($_POST['full_name'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $office = trim($_POST['office'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        
        if ($fullName === '' || $username === '' || $password === '') {
            $error = "Full Name, Username, and Password are required fields.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = "Username already exists.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $pdo->prepare("INSERT INTO admin_users (full_name, nickname, office, email, contact_number, designation, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([$fullName, $nickname, $office, $email, $contactNumber, $designation, $username, $hash, $role]);
                    $message = "User successfully added.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'edit_user') {
        $id = (int)$_POST['id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $office = trim($_POST['office'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';

        if ($fullName === '' || $username === '') {
            $error = "Full Name and Username are required.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) {
                    $error = "Username is already taken by another user.";
                } else {
                    if ($password !== '') {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare("UPDATE admin_users SET full_name=?, nickname=?, office=?, email=?, contact_number=?, designation=?, username=?, password=?, role=? WHERE id=?");
                        $upd->execute([$fullName, $nickname, $office, $email, $contactNumber, $designation, $username, $hash, $role, $id]);
                    } else {
                        $upd = $pdo->prepare("UPDATE admin_users SET full_name=?, nickname=?, office=?, email=?, contact_number=?, designation=?, username=?, role=? WHERE id=?");
                        $upd->execute([$fullName, $nickname, $office, $email, $contactNumber, $designation, $username, $role, $id]);
                    }
                    $message = "User successfully updated.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['admin_user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            try {
                $del = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                $del->execute([$id]);
                $message = "User successfully deleted.";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch users
$users = [];
try {
    $stmt = $pdo->query("SELECT id, full_name, nickname, office, email, contact_number, designation, username, role, created_at FROM admin_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load users.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <style>
        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .admin-modal {
            max-width: 700px; /* Wider to accommodate grid */
        }
    </style>
</head>
<body>

    <?php $activePage = 'users'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>User Management</h1>
            </div>
            <div class="admin-topbar-actions">
                <span style="font-size:0.85rem;color:var(--admin-text-muted)">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?> (<?= htmlspecialchars($_SESSION['admin_role'] ?? 'admin') ?>)</span>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($error): ?>
                <div class="admin-alert admin-alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="admin-alert admin-alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Admin Users</h2>
                    <button class="btn-admin btn-admin-primary" onclick="openAddModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add New User
                    </button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Office</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['full_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($u['office'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <span class="file-badge <?= $u['role'] === 'superadmin' ? 'file-badge-docs' : 'file-badge-folder' ?>">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <button class="btn-admin btn-admin-edit btn-admin-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                        'id' => $u['id'],
                                        'full_name' => $u['full_name'] ?? '',
                                        'nickname' => $u['nickname'] ?? '',
                                        'office' => $u['office'] ?? '',
                                        'email' => $u['email'] ?? '',
                                        'contact_number' => $u['contact_number'] ?? '',
                                        'designation' => $u['designation'] ?? '',
                                        'username' => $u['username'],
                                        'role' => $u['role']
                                    ])) ?>)">Edit</button>
                                    
                                    <?php if ($u['id'] !== $_SESSION['admin_user_id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-admin btn-admin-delete btn-admin-sm">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:2rem;color:var(--admin-text-muted)">No users found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="admin-modal-overlay" id="addModalOverlay">
        <div class="admin-modal">
            <h3>Add New User</h3>
            <p>Enter details for the new admin user.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user">
                
                <div class="modal-grid">
                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="nickname">Nickname</label>
                        <input type="text" id="nickname" name="nickname">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="office">Office</label>
                        <input type="text" id="office" name="office">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="designation">Designation</label>
                        <input type="text" id="designation" name="designation">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>

                <div class="admin-form-group" style="margin-top:1rem">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="admin-modal-actions" style="margin-top:1.5rem">
                    <button type="button" class="btn-admin" style="background:#e5e7eb;color:#374151" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-admin btn-admin-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="admin-modal-overlay" id="editModalOverlay">
        <div class="admin-modal">
            <h3>Edit User</h3>
            <p>Update user details. Leave password blank to keep it unchanged.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-grid">
                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="edit_full_name" name="full_name" required>
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_nickname">Nickname</label>
                        <input type="text" id="edit_nickname" name="nickname">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_office">Office</label>
                        <input type="text" id="edit_office" name="office">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_designation">Designation</label>
                        <input type="text" id="edit_designation" name="designation">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_email">Email Address</label>
                        <input type="email" id="edit_email" name="email">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_contact_number">Contact Number</label>
                        <input type="text" id="edit_contact_number" name="contact_number">
                    </div>

                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_username">Username <span class="required">*</span></label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="admin-form-group" style="margin-bottom:0">
                        <label for="edit_role">Role <span class="required">*</span></label>
                        <select id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>

                <div class="admin-form-group" style="margin-top:1rem">
                    <label for="edit_password">New Password (optional)</label>
                    <input type="password" id="edit_password" name="password" placeholder="Leave blank to keep current">
                </div>

                <div class="admin-modal-actions" style="margin-top:1.5rem">
                    <button type="button" class="btn-admin" style="background:#e5e7eb;color:#374151" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-admin btn-admin-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModalOverlay').classList.add('show');
        }
        function closeAddModal() {
            document.getElementById('addModalOverlay').classList.remove('show');
        }
        
        function openEditModal(userData) {
            document.getElementById('edit_id').value = userData.id;
            document.getElementById('edit_full_name').value = userData.full_name;
            document.getElementById('edit_nickname').value = userData.nickname;
            document.getElementById('edit_office').value = userData.office;
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_contact_number').value = userData.contact_number;
            document.getElementById('edit_designation').value = userData.designation;
            document.getElementById('edit_username').value = userData.username;
            document.getElementById('edit_role').value = userData.role;
            document.getElementById('edit_password').value = '';
            document.getElementById('editModalOverlay').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModalOverlay').classList.remove('show');
        }
    </script>
</body>
</html>
