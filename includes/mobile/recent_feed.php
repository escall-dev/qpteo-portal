<?php
/**
 * QPTEO Portal — Mobile Recent Content Feed Component
 * Displays the 2-3 most recent issuances/memorandums with "See all" navigation.
 */
$rootPath = $rootPath ?? '.';

$feedRecords = [];
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config/database.php';
        $pdo = getPortalDB();
    }
    $feedStmt = $pdo->query("SELECT id, memo_number, subject, date_issued, file_path, file_size FROM memorandums ORDER BY date_issued DESC, id DESC LIMIT 3");
    $feedRecords = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $feedRecords = [];
}

// Fallback items if database has no records yet
if (empty($feedRecords)) {
    $feedRecords = [
        [
            'id' => 1,
            'memo_number' => 'QPTEO-OM-2026-003',
            'subject' => 'Orientation and Capacity Building on Quality Pre-Service Teacher Education Framework',
            'date_issued' => date('Y-m-d', strtotime('-2 days')),
            'file_path' => '#'
        ],
        [
            'id' => 2,
            'memo_number' => 'QPTEO-OM-2026-002',
            'subject' => 'Call for Submissions: Research Initiatives in Teacher Education (RITE) 2026',
            'date_issued' => date('Y-m-d', strtotime('-1 week')),
            'file_path' => '#'
        ],
        [
            'id' => 3,
            'memo_number' => 'QPTEO-OM-2026-001',
            'subject' => 'Designation Guidelines and Operational Procedures for Centers of Excellence',
            'date_issued' => date('Y-m-d', strtotime('-2 weeks')),
            'file_path' => '#'
        ]
    ];
}
?>
<section class="qpteo-content-feed-section" aria-label="Recent Content Feed">
    <div class="qpteo-feed-header">
        <div class="qpteo-feed-title-wrap">
            <svg class="qpteo-feed-badge-icon" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10"></circle>
            </svg>
            <h2 class="qpteo-feed-title">Recent Issuances</h2>
        </div>
        <a href="<?= $rootPath ?>/memorandums.php" class="qpteo-feed-see-all">
            See all
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </a>
    </div>

    <div class="qpteo-feed-list">
        <?php foreach ($feedRecords as $item): ?>
            <?php 
                $dateFormatted = !empty($item['date_issued']) ? date('M d, Y', strtotime($item['date_issued'])) : '';
                $itemLink = !empty($item['file_path']) && $item['file_path'] !== '#' ? $item['file_path'] : $rootPath . '/memorandums.php';
            ?>
            <a href="<?= htmlspecialchars($itemLink) ?>" class="qpteo-feed-card" <?= !empty($item['file_path']) && $item['file_path'] !== '#' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                <div class="qpteo-feed-card-meta">
                    <span class="qpteo-feed-card-tag"><?= htmlspecialchars($item['memo_number'] ?? 'MEMO') ?></span>
                    <?php if ($dateFormatted): ?>
                        <span class="qpteo-feed-card-date"><?= htmlspecialchars($dateFormatted) ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="qpteo-feed-card-title"><?= htmlspecialchars($item['subject'] ?? '') ?></h3>
            </a>
        <?php endforeach; ?>
    </div>
</section>
