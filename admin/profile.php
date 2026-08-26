<?php
/**
 * User Profile Page
 * Accessible by all logged-in admin users.
 * Allows users to edit their own details.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getPortalDB();
$userId = (int)$_SESSION['admin_user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $office = trim($_POST['office'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $username === '') {
        $error = "Full Name and Username are required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                $error = "Username is already taken.";
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE admin_users SET full_name=?, nickname=?, office=?, email=?, contact_number=?, username=?, password=? WHERE id=?");
                    $upd->execute([$fullName, $nickname, $office, $email, $contactNumber, $username, $hash, $userId]);
                } else {
                    $upd = $pdo->prepare("UPDATE admin_users SET full_name=?, nickname=?, office=?, email=?, contact_number=?, username=? WHERE id=?");
                    $upd->execute([$fullName, $nickname, $office, $email, $contactNumber, $username, $userId]);
                }
                $_SESSION['admin_username'] = $username;
                $message = "Profile successfully updated.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch current user details
$user = null;
try {
    $stmt = $pdo->prepare("SELECT full_name, nickname, office, email, contact_number, username, role FROM admin_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = "Failed to load profile data.";
}

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — QPTEO Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <style>
        .profile-cards-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }
        .admin-card-header h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        @media (max-width: 900px) {
            .profile-cards-wrapper {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <?php $activePage = 'profile'; include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <button class="admin-sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1>My Profile</h1>
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

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="profile-cards-wrapper">
                    <!-- Left Column: Profile Details -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Profile Details
                            </h2>
                        </div>
                        <div style="padding:2rem;">
                            
                            <div class="admin-form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                <div class="file-info" style="margin-top:0.5rem">Unique username used for portal sign-in (supports letters, numbers, dashes, dots).</div>
                            </div>
                            
                            <div class="admin-form-group">
                                <label>Assigned Role</label>
                                <div style="margin-top: 0.25rem;">
                                    <span class="file-badge <?= $user['role'] === 'superadmin' ? 'file-badge-docs' : 'file-badge-folder' ?>" style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.5rem 1rem;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                        <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <label for="full_name">Display Name (Full Name) <span class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="admin-form-group">
                                <label for="nickname">Nickname</label>
                                <input type="text" id="nickname" name="nickname" value="<?= htmlspecialchars($user['nickname'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="office">Office</label>
                                <input type="text" id="office" name="office" value="<?= htmlspecialchars($user['office'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="email">Contact Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>

                            <div class="admin-form-group" style="margin-bottom:0">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>">
                            </div>

                            <div style="margin-top:2rem;">
                                <button type="submit" class="btn-admin btn-admin-primary" style="padding:0.75rem 2rem;">Update Profile</button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Change Password -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                                Change Password
                            </h2>
                        </div>
                        <div style="padding:2rem;">
                            
                            <div class="admin-form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" placeholder="Min. 6 characters">
                                <div class="file-info" style="margin-top:0.5rem">Leave blank if you do not wish to change your password.</div>
                            </div>
                            
                            <!-- To perfectly match the image, we can add a confirm password field. Though it doesn't do strict JS validation yet, it looks correct visually. -->
                            <div class="admin-form-group" style="margin-bottom:0">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>

                            <div style="margin-top:2rem;">
                                <button type="submit" class="btn-admin btn-admin-primary" style="padding:0.75rem 2rem; background-color:#edac36; color:#040484; border-color:#edac36;">Update Password</button>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Simple JS check to ensure passwords match if filled
        document.querySelector('form').addEventListener('submit', function(e) {
            const pass = document.getElementById('password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            if (pass !== '' && pass !== confirmPass) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
