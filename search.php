<?php
/**
 * QPTEO Portal — Comprehensive Search Results Page
 * Displays unified search results across Memos, Repositories, COEs, and Systems.
 */
require_once __DIR__ . '/config/database.php';

$query = trim($_GET['q'] ?? '');
$searchParam = "%{$query}%";

$results = [
    'submenus'     => [],
    'memorandums'  => [],
    'repositories' => [],
    'coes'         => [],
    'systems'      => []
];
$totalCount = 0;

$submenusList = [
    ['title' => 'Document Tracking System', 'parent' => 'Systems', 'url' => 'https://dts.qpteo.com/index.php'],
    ['title' => 'Document Library System', 'parent' => 'Systems', 'url' => '/landing/dls/pages/login.php'],
    ['title' => 'Online Electronic Logbook', 'parent' => 'Systems', 'url' => '/oel/login.php'],
    ['title' => 'DIRECTOry', 'parent' => 'Systems', 'url' => '#'],

    ['title' => 'Presentation', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=presentation'],
    ['title' => 'Concept Paper', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=concept_paper'],
    ['title' => 'Checklist', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=checklist'],
    ['title' => 'Briefer', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=briefer'],
    ['title' => 'Report', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=report'],
    ['title' => 'Minutes', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=minutes'],
    ['title' => 'Session Guides', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=session_guides'],
    ['title' => 'Others', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=others'],

    ['title' => 'QPTEO Office Memorandums', 'parent' => 'Issuances', 'url' => '/landing/memorandums.php'],

    ['title' => 'Teacher Education Centers of Excellence 2026', 'parent' => 'Centers of Excellence', 'url' => '/landing/coes.php']
];

// Systems matching
$systemsList = [
    [
        'title'       => 'Document Tracking System',
        'code'        => 'DTS',
        'description' => 'Track incoming and outgoing official office documents in real-time.',
        'url'         => 'https://dts.qpteo.com/index.php'
    ],
    [
        'title'       => 'Document Library System',
        'code'        => 'DLS',
        'description' => 'Centralized digital archive for official records, templates, and files.',
        'url'         => '/landing/dls/pages/login.php'
    ],
    [
        'title'       => 'Online Electronic Logbook',
        'code'        => 'OEL',
        'description' => 'Electronic logging system for visitor, client, and transaction management.',
        'url'         => '/oel/login.php'
    ],
    [
        'title'       => 'DIRECTOry',
        'code'        => 'DIRECTORy',
        'description' => 'Directory of personnel, offices, and partner institutions.',
        'url'         => '#'
    ]
];

if ($query !== '') {
    foreach ($submenusList as $sub) {
        if (stripos($sub['title'], $query) !== false || stripos($sub['parent'], $query) !== false) {
            $results['submenus'][] = $sub;
            $totalCount++;
        }
    }
    foreach ($systemsList as $sys) {
        if (stripos($sys['title'], $query) !== false || stripos($sys['code'], $query) !== false || stripos($sys['description'], $query) !== false) {
            $results['systems'][] = $sys;
            $totalCount++;
        }
    }

    try {
        $pdo = getPortalDB();

        // 1. Memorandums
        $memoStmt = $pdo->prepare("
            SELECT id, memo_number, subject, description, issued_by, date_issued, file_path 
            FROM memorandums 
            WHERE memo_number LIKE :q OR subject LIKE :q OR description LIKE :q OR issued_by LIKE :q 
            ORDER BY date_issued DESC
        ");
        $memoStmt->execute([':q' => $searchParam]);
        $results['memorandums'] = $memoStmt->fetchAll();
        $totalCount += count($results['memorandums']);

        // 2. Repositories
        $repoStmt = $pdo->prepare("
            SELECT id, title, description, category, document_type, file_type, uploaded_by, date_uploaded, file_path 
            FROM repositories 
            WHERE title LIKE :q OR description LIKE :q OR category LIKE :q OR uploaded_by LIKE :q OR document_type LIKE :q OR file_type LIKE :q
            ORDER BY date_uploaded DESC
        ");
        $repoStmt->execute([':q' => $searchParam]);
        $results['repositories'] = $repoStmt->fetchAll();
        $totalCount += count($results['repositories']);

        // 3. COEs
        $coeStmt = $pdo->prepare("
            SELECT id, institution_name, region, province, description 
            FROM centers_of_excellence 
            WHERE institution_name LIKE :q OR region LIKE :q OR province LIKE :q OR description LIKE :q 
            ORDER BY region ASC
        ");
        $coeStmt->execute([':q' => $searchParam]);
        $results['coes'] = $coeStmt->fetchAll();
        $totalCount += count($results['coes']);

    } catch (PDOException $e) {
        error_log("Search page DB error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Search results for QPTEO Systems Portal.">
    <title>Search Results: <?= htmlspecialchars($query) ?> — QPTEO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php $activeNav = ''; include 'includes/navbar.php'; ?>

    <main class="portal-main">
        <div class="portal-section">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb portal-breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Search Results</li>
                </ol>
            </nav>

            <header class="portal-page-header">
                <div>
                    <h1 class="portal-page-title">Search Results</h1>
                    <p class="portal-page-desc">
                        Showing results for <strong class="portal-search-query">"<?= htmlspecialchars($query) ?>"</strong> — Found <strong><?= $totalCount ?></strong> matches.
                    </p>
                </div>
            </header>

            <?php if ($query === ''): ?>
                <div class="portal-empty-card">
                    <h3>Please enter a search term</h3>
                    <p>Use the search bar above to look for memorandums, presentations, document titles, or systems.</p>
                </div>
            <?php elseif ($totalCount === 0): ?>
                <div class="portal-empty-card">
                    <h3>No matching results found</h3>
                    <p>We couldn't find anything matching "<strong><?= htmlspecialchars($query) ?></strong>". Try searching for keywords like <em>"memorandum"</em>, <em>"presentation"</em>, <em>"checklist"</em>, or reference numbers.</p>
                </div>
            <?php else: ?>

                <!-- Navigation Submenus Results -->
                <?php if (!empty($results['submenus'])): ?>
                    <section class="portal-search-group">
                        <h2 class="portal-search-group-title">Navigation Submenus (<?= count($results['submenus']) ?>)</h2>
                        <div class="portal-search-grid">
                            <?php foreach ($results['submenus'] as $sub): 
                                $isSys = ($sub['parent'] === 'Systems');
                                $targetAttr = $isSys ? 'target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                                <div class="portal-search-card">
                                    <span class="portal-badge portal-badge-gold"><?= htmlspecialchars($sub['parent']) ?></span>
                                    <h3 class="portal-search-card-title">
                                        <a href="<?= $sub['url'] ?>" <?= $targetAttr ?>><?= htmlspecialchars($sub['title']) ?></a>
                                    </h3>
                                    <p class="portal-search-card-desc">Submenu link under <?= htmlspecialchars($sub['parent']) ?></p>
                                    <a href="<?= $sub['url'] ?>" <?= $targetAttr ?> class="portal-btn portal-btn-sm portal-btn-outline" style="margin-top: 0.5rem;">Open Submenu &rarr;</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Systems Results -->
                <?php if (!empty($results['systems'])): ?>
                    <section class="portal-search-group">
                        <h2 class="portal-search-group-title">Systems & Portals (<?= count($results['systems']) ?>)</h2>
                        <div class="portal-search-grid">
                            <?php foreach ($results['systems'] as $sys): ?>
                                <div class="portal-search-card">
                                    <span class="portal-badge portal-badge-navy"><?= htmlspecialchars($sys['code']) ?></span>
                                    <h3 class="portal-search-card-title">
                                        <a href="<?= $sys['url'] ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($sys['title']) ?></a>
                                    </h3>
                                    <p class="portal-search-card-desc"><?= htmlspecialchars($sys['description']) ?></p>
                                    <a href="<?= $sys['url'] ?>" target="_blank" rel="noopener noreferrer" class="portal-btn portal-btn-sm portal-btn-outline" style="margin-top: 0.5rem;">Access System &rarr;</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Memorandums Results -->
                <?php if (!empty($results['memorandums'])): ?>
                    <section class="portal-search-group">
                        <h2 class="portal-search-group-title">Office Memorandums (<?= count($results['memorandums']) ?>)</h2>
                        <div class="portal-table-wrapper">
                            <table class="portal-table">
                                <thead>
                                    <tr>
                                        <th>Memo #</th>
                                        <th>Subject / Title</th>
                                        <th>Date Issued</th>
                                        <th>Issued By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['memorandums'] as $memo): ?>
                                        <tr>
                                            <td><span class="portal-badge portal-badge-gold"><?= htmlspecialchars($memo['memo_number']) ?></span></td>
                                            <td>
                                                <strong style="color: var(--portal-navy);"><?= htmlspecialchars($memo['subject']) ?></strong>
                                                <?php if (!empty($memo['description'])): ?>
                                                    <div style="font-size: 0.82rem; color: var(--portal-text-muted); margin-top: 0.2rem;"><?= htmlspecialchars(mb_strimwidth($memo['description'], 0, 110, '...')) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($memo['date_issued']) ?></td>
                                            <td><?= htmlspecialchars($memo['issued_by'] ?? 'QPTEO') ?></td>
                                            <td>
                                                <?php if (!empty($memo['file_path'])): ?>
                                                    <a href="<?= htmlspecialchars($memo['file_path']) ?>" target="_blank" class="portal-btn portal-btn-sm portal-btn-navy">View Memo</a>
                                                <?php else: ?>
                                                    <a href="memorandums.php?search=<?= urlencode($memo['memo_number']) ?>" class="portal-btn portal-btn-sm portal-btn-outline">Details</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Repositories Results -->
                <?php if (!empty($results['repositories'])): ?>
                    <section class="portal-search-group">
                        <h2 class="portal-search-group-title">Documents & Repositories (<?= count($results['repositories']) ?>)</h2>
                        <div class="portal-table-wrapper">
                            <table class="portal-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Title</th>
                                        <th>Uploaded By</th>
                                        <th>Date Uploaded</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['repositories'] as $repo): ?>
                                        <?php $catLabel = ucfirst(str_replace('_', ' ', $repo['category'] ?? 'Document')); ?>
                                        <tr>
                                            <td><span class="portal-badge portal-badge-navy"><?= htmlspecialchars($catLabel) ?></span></td>
                                            <td>
                                                <strong style="color: var(--portal-navy);"><?= htmlspecialchars($repo['title']) ?></strong>
                                                <?php if (!empty($repo['description'])): ?>
                                                    <div style="font-size: 0.82rem; color: var(--portal-text-muted); margin-top: 0.2rem;"><?= htmlspecialchars(mb_strimwidth($repo['description'], 0, 110, '...')) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($repo['uploaded_by'] ?? 'Admin') ?></td>
                                            <td><?= htmlspecialchars($repo['date_uploaded']) ?></td>
                                            <td>
                                                <?php if (!empty($repo['file_path'])): ?>
                                                    <a href="<?= htmlspecialchars($repo['file_path']) ?>" target="_blank" class="portal-btn portal-btn-sm portal-btn-navy">Open Document</a>
                                                <?php else: ?>
                                                    <a href="repositories.php?category=<?= urlencode($repo['category']) ?>&search=<?= urlencode($repo['title']) ?>" class="portal-btn portal-btn-sm portal-btn-outline">View</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- COEs Results -->
                <?php if (!empty($results['coes'])): ?>
                    <section class="portal-search-group">
                        <h2 class="portal-search-group-title">Centers of Excellence (<?= count($results['coes']) ?>)</h2>
                        <div class="portal-search-grid">
                            <?php foreach ($results['coes'] as $coe): ?>
                                <div class="portal-search-card">
                                    <span class="portal-badge portal-badge-gold"><?= htmlspecialchars($coe['region']) ?></span>
                                    <h3 class="portal-search-card-title"><?= htmlspecialchars($coe['institution_name']) ?></h3>
                                    <p class="portal-search-card-desc"><?= htmlspecialchars($coe['province'] ?? '') ?> — <?= htmlspecialchars($coe['description'] ?? '') ?></p>
                                    <a href="coes.php?search=<?= urlencode($coe['institution_name']) ?>" class="portal-btn portal-btn-sm portal-btn-outline" style="margin-top: 0.5rem;">View Institution &rarr;</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
