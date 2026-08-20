<?php
/**
 * QPTEO Portal — Centers of Excellence Page
 * Displays Teacher Education Institutions designated as COEs in a horizontal list.
 */
require_once __DIR__ . '/config/database.php';

// Search
$search = trim($_GET['search'] ?? '');

try {
    $pdo = getPortalDB();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = '(institution_name LIKE :s1 OR region LIKE :s2 OR province LIKE :s3 OR description LIKE :s4)';
        $params[':s1'] = "%{$search}%";
        $params[':s2'] = "%{$search}%";
        $params[':s3'] = "%{$search}%";
        $params[':s4'] = "%{$search}%";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT * FROM centers_of_excellence {$whereClause} ORDER BY region ASC, institution_name ASC");
    $stmt->execute($params);
    $records = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("COEs query error: " . $e->getMessage());
    $records = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Teacher Education Centers of Excellence 2026 — Designated Teacher Education Institutions recognized for excellence.">
    <title>Teacher Education Centers of Excellence 2026 — QPTEO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php $activeNav = 'coes'; include 'includes/navbar.php'; ?>

    <main class="portal-main">
        <div class="portal-section">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb portal-breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Centers of Excellence 2026</li>
                </ol>
            </nav>

            <!-- Section Header -->
            <div class="portal-section-header">
                <h1 class="portal-section-title">Teacher Education Centers of Excellence 2026</h1>
                <p class="portal-section-subtitle">Teacher Education Institutions designated as Centers of Excellence by the Teacher Education Council.</p>
            </div>

            <!-- Search Bar -->
            <div style="max-width:400px;margin-bottom:1.5rem">
                <div class="portal-search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="coeSearch" placeholder="Search institutions..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearch()">
                </div>
            </div>

            <?php if (empty($records)): ?>
                <!-- Empty State -->
                <div class="portal-empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path>
                        <line x1="8" y1="14" x2="8" y2="17"></line>
                        <line x1="12" y1="14" x2="12" y2="17"></line>
                        <line x1="16" y1="14" x2="16" y2="17"></line>
                    </svg>
                    <h3>No institutions found</h3>
                    <p>There are no Centers of Excellence entries yet. Check back later.</p>
                </div>
            <?php else: ?>
                <!-- Results count -->
                <p style="font-size:0.85rem;color:var(--portal-text-muted);margin-bottom:1rem">
                    <?= count($records) ?> institution<?= count($records) !== 1 ? 's' : '' ?> found
                </p>

                <!-- Horizontal List -->
                <div class="coe-list">
                    <?php foreach ($records as $row): ?>
                        <div class="coe-list-item">
                            <!-- Logo -->
                            <div class="coe-list-logo">
                                <?php if ($row['logo_path'] && file_exists(__DIR__ . '/' . $row['logo_path'])): ?>
                                    <img src="<?= htmlspecialchars($row['logo_path']) ?>" alt="<?= htmlspecialchars($row['institution_name']) ?> Logo">
                                <?php else: ?>
                                    <svg class="placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"></path>
                                        <line x1="8" y1="14" x2="8" y2="17"></line>
                                        <line x1="12" y1="14" x2="12" y2="17"></line>
                                        <line x1="16" y1="14" x2="16" y2="17"></line>
                                    </svg>
                                <?php endif; ?>
                            </div>

                            <!-- Info -->
                            <div class="coe-list-info">
                                <h3 class="coe-list-name"><?= htmlspecialchars($row['institution_name']) ?></h3>

                                <?php
                                    // Build address string: full address (Region)
                                    $addressParts = [];
                                    if ($row['address']) $addressParts[] = $row['address'];
                                    $regionStr = '';
                                    if ($row['region']) $regionStr = $row['region'];

                                    $displayAddress = '';
                                    if (!empty($addressParts)) {
                                        $displayAddress = implode(', ', $addressParts);
                                        if ($regionStr) $displayAddress .= ' (' . $regionStr . ')';
                                    } elseif ($regionStr) {
                                        // Fallback: just show region if no address
                                        $displayAddress = $regionStr;
                                        if ($row['province']) $displayAddress = $row['province'] . ' (' . $regionStr . ')';
                                    }
                                ?>
                                <?php if ($displayAddress): ?>
                                    <p class="coe-list-address"><?= htmlspecialchars($displayAddress) ?></p>
                                <?php endif; ?>

                                <div class="coe-list-buttons">
                                    <?php if (!empty($row['doc_link'])): ?>
                                        <a href="<?= htmlspecialchars($row['doc_link']) ?>" target="_blank" rel="noopener noreferrer" class="coe-btn-website">Website</a>
                                    <?php endif; ?>
                                    <?php if (!empty($row['social_media_link'])): ?>
                                        <a href="<?= htmlspecialchars($row['social_media_link']) ?>" target="_blank" rel="noopener noreferrer" class="coe-btn-social">Social Media</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function applySearch() {
            const val = document.getElementById('coeSearch').value.trim();
            const url = new URL(window.location.href);
            if (val) {
                url.searchParams.set('search', val);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    </script>

</body>
</html>
