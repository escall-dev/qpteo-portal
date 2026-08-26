<?php
/**
 * Admin Login Page
 * Authenticates against admin_users table in qpteo_portal.
 */
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Temporary snippet to create admin user 'alex'
try {
    $pdo = getPortalDB();
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = 'alex'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, role) VALUES ('alex', ?, 'superadmin')");
        $stmt->execute([password_hash('escall', PASSWORD_DEFAULT)]);
    }
} catch (Exception $e) {
    // ignore
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getPortalDB();
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM admin_users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_user_id']    = $user['id'];
                $_SESSION['admin_username']   = $user['username'];
                $_SESSION['admin_role']       = $user['role'];
                $_SESSION['admin_last_regen'] = time();
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'A system error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — QPTEO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>
<body class="admin-login-page">

    <div class="admin-login-container">
        <div class="admin-login-card">
            <img src="../branding/qpteo logo unfinalized-jukebox-bg-removed.png" alt="QPTEO Logo">
            <h1>Admin Panel</h1>
            <p class="login-subtitle">Quality Pre-Service Teacher Education Office</p>

            <?php if ($error): ?>
                <div class="admin-alert admin-alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="admin-form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" autocomplete="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="admin-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-admin btn-admin-primary">Sign In</button>
            </form>
        </div>
    </div>

</body>
</html>
