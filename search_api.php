<?php
/**
 * QPTEO Portal — Unified Search API Endpoint
 * Searches across Submenu Items, Memorandums, Repositories, COEs, and Systems.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([
        'status'  => 'success',
        'query'   => $query,
        'results' => [
            'submenus'     => [],
            'memorandums'  => [],
            'repositories' => [],
            'coes'         => [],
            'systems'      => []
        ],
        'total'   => 0
    ]);
    exit;
}

$searchParam = "%{$query}%";
$results = [
    'submenus'     => [],
    'memorandums'  => [],
    'repositories' => [],
    'coes'         => [],
    'systems'      => []
];
$totalCount = 0;

// 1. Navigation Submenu Items Search
$submenusList = [
    // Systems dropdown submenus
    ['title' => 'Document Tracking System', 'parent' => 'Systems', 'url' => '/landing/dts'],
    ['title' => 'Document Library System', 'parent' => 'Systems', 'url' => '/landing/dls/pages/login.php'],
    ['title' => 'Online Electronic Logbook', 'parent' => 'Systems', 'url' => '/oel/login.php'],
    ['title' => 'DIRECTOry', 'parent' => 'Systems', 'url' => '#'],

    // Repositories dropdown submenus
    ['title' => 'Presentation', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=presentation'],
    ['title' => 'Concept Paper', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=concept_paper'],
    ['title' => 'Checklist', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=checklist'],
    ['title' => 'Briefer', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=briefer'],
    ['title' => 'Report', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=report'],
    ['title' => 'Minutes', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=minutes'],
    ['title' => 'Session Guides', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=session_guides'],
    ['title' => 'Others', 'parent' => 'Repositories', 'url' => '/landing/repositories.php?category=others'],

    // Issuances dropdown submenus
    ['title' => 'QPTEO Office Memorandums', 'parent' => 'Issuances', 'url' => '/landing/memorandums.php'],

    // Centers of Excellence dropdown submenus
    ['title' => 'Teacher Education Centers of Excellence 2026', 'parent' => 'Centers of Excellence', 'url' => '/landing/coes.php']
];

foreach ($submenusList as $sub) {
    if (stripos($sub['title'], $query) !== false || stripos($sub['parent'], $query) !== false) {
        $results['submenus'][] = $sub;
        $totalCount++;
    }
}

// 2. Systems Search
$systemsList = [
    [
        'title'       => 'Document Tracking System',
        'code'        => 'DTS',
        'description' => 'Track incoming and outgoing official office documents in real-time.',
        'url'         => '/landing/dts'
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

foreach ($systemsList as $sys) {
    if (stripos($sys['title'], $query) !== false || stripos($sys['code'], $query) !== false || stripos($sys['description'], $query) !== false) {
        $results['systems'][] = $sys;
        $totalCount++;
    }
}

try {
    $pdo = getPortalDB();

    // 3. Memorandums Search (Memo Numbers, Subject, Description, Issued By)
    $memoStmt = $pdo->prepare("
        SELECT id, memo_number, subject, description, date_issued, file_path 
        FROM memorandums 
        WHERE memo_number LIKE :q OR subject LIKE :q OR description LIKE :q OR issued_by LIKE :q 
        ORDER BY date_issued DESC 
        LIMIT 6
    ");
    $memoStmt->execute([':q' => $searchParam]);
    $memos = $memoStmt->fetchAll();
    foreach ($memos as $m) {
        $results['memorandums'][] = [
            'id'          => $m['id'],
            'memo_number' => $m['memo_number'],
            'title'       => $m['subject'],
            'description' => $m['description'] ?? '',
            'date'        => $m['date_issued'],
            'file_path'   => $m['file_path'],
            'url'         => '/landing/memorandums.php?search=' . urlencode($m['memo_number'])
        ];
        $totalCount++;
    }

    // 4. Repositories Search (Presentations, Briefers, Reports, Minutes, Checklists, etc.)
    $repoStmt = $pdo->prepare("
        SELECT id, title, description, category, document_type, file_type, date_uploaded, file_path 
        FROM repositories 
        WHERE title LIKE :q OR description LIKE :q OR category LIKE :q OR uploaded_by LIKE :q OR document_type LIKE :q OR file_type LIKE :q
        ORDER BY date_uploaded DESC 
        LIMIT 6
    ");
    $repoStmt->execute([':q' => $searchParam]);
    $repos = $repoStmt->fetchAll();
    foreach ($repos as $r) {
        $docTypeNice = ucfirst(str_replace('_', ' ', $r['document_type'] ?? $r['category'] ?? 'Document'));
        $results['repositories'][] = [
            'id'          => $r['id'],
            'title'       => $r['title'],
            'category'    => $docTypeNice,
            'file_type'   => $r['file_type'] ?? 'others',
            'description' => $r['description'] ?? '',
            'date'        => $r['date_uploaded'],
            'file_path'   => $r['file_path'],
            'url'         => '/landing/repositories.php?category=' . urlencode($r['document_type'] ?? $r['category']) . '&search=' . urlencode($r['title'])
        ];
        $totalCount++;
    }

    // 5. Centers of Excellence Search
    $coeStmt = $pdo->prepare("
        SELECT id, institution_name, region, province, description 
        FROM centers_of_excellence 
        WHERE institution_name LIKE :q OR region LIKE :q OR province LIKE :q OR description LIKE :q 
        ORDER BY region ASC 
        LIMIT 6
    ");
    $coeStmt->execute([':q' => $searchParam]);
    $coes = $coeStmt->fetchAll();
    foreach ($coes as $c) {
        $results['coes'][] = [
            'id'          => $c['id'],
            'title'       => $c['institution_name'],
            'region'      => $c['region'],
            'province'    => $c['province'],
            'description' => $c['description'] ?? '',
            'url'         => '/landing/coes.php?search=' . urlencode($c['institution_name'])
        ];
        $totalCount++;
    }

} catch (PDOException $e) {
    error_log("Search API error: " . $e->getMessage());
}

echo json_encode([
    'status'  => 'success',
    'query'   => $query,
    'results' => $results,
    'total'   => $totalCount
]);
