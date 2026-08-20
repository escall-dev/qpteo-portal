<?php
/**
 * QPTEO Portal — Centers of Excellence Page
 * Displays National and Regional Centers of Excellence with editable introductions,
 * priority challenges, and categorized institution listings.
 */
require_once __DIR__ . '/config/database.php';

// Search
$search = trim($_GET['search'] ?? '');
$categoryView = $_GET['cat'] ?? 'all'; // all, national, regional

try {
    $pdo = getPortalDB();

    // 1. Fetch Page Content Settings
    $keys = [
        'coe_national_title', 'coe_national_intro', 'coe_national_challenges_title', 'coe_national_challenges',
        'coe_regional_title', 'coe_regional_intro', 'coe_regional_challenges_title', 'coe_regional_challenges'
    ];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'coe_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $natTitle      = $settings['coe_national_title'] ?? 'NATIONAL COEs';
    $natIntro      = $settings['coe_national_intro'] ?? '';
    $natChallTitle = $settings['coe_national_challenges_title'] ?? 'Priority challenges requiring national-level action';
    $natChallenges = json_decode($settings['coe_national_challenges'] ?? '[]', true) ?: [];

    $regTitle      = $settings['coe_regional_title'] ?? 'REGIONAL COEs';
    $regIntro      = $settings['coe_regional_intro'] ?? '';
    $regChallTitle = $settings['coe_regional_challenges_title'] ?? 'Challenges that the Regional Teacher Education COEs need to address';
    $regChallenges = json_decode($settings['coe_regional_challenges'] ?? '[]', true) ?: [];

    // 2. Fetch Institutions
    $whereBase = ["status = 'active'"];
    $params = [];

    if ($search !== '') {
        $whereBase[] = '(institution_name LIKE :s1 OR region LIKE :s2 OR province LIKE :s3 OR address LIKE :s4 OR description LIKE :s5)';
        $params[':s1'] = "%{$search}%";
        $params[':s2'] = "%{$search}%";
        $params[':s3'] = "%{$search}%";
        $params[':s4'] = "%{$search}%";
        $params[':s5'] = "%{$search}%";
    }

    $whereSql = implode(' AND ', $whereBase);

    $stmt = $pdo->prepare("SELECT * FROM centers_of_excellence WHERE {$whereSql} ORDER BY category ASC, region ASC, institution_name ASC");
    $stmt->execute($params);
    $allRecords = $stmt->fetchAll();

    $nationalCoes = [];
    $regionalCoes = [];

    foreach ($allRecords as $r) {
        if (($r['category'] ?? 'national') === 'regional') {
            $regionalCoes[] = $r;
        } else {
            $nationalCoes[] = $r;
        }
    }

} catch (PDOException $e) {
    error_log("COEs query error: " . $e->getMessage());
    $nationalCoes = [];
    $regionalCoes = [];
    $natTitle = 'NATIONAL COEs';
    $natIntro = '';
    $natChallTitle = 'Priority challenges requiring national-level action';
    $natChallenges = [];
    $regTitle = 'REGIONAL COEs';
    $regIntro = '';
    $regChallTitle = 'Challenges that the Regional Teacher Education COEs need to address';
    $regChallenges = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Teacher Education Centers of Excellence 2026 — National and Regional Centers of Excellence recognized by the Teacher Education Council.">
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

            <!-- Sticky Navigation & Search Bar -->
            <div class="coe-nav-filter-bar">
                <div class="coe-filter-pills">
                    <button type="button" class="coe-filter-pill active" onclick="filterCategory('all', this)">
                        All COEs (<?= count($nationalCoes) + count($regionalCoes) ?>)
                    </button>
                    <button type="button" class="coe-filter-pill" onclick="filterCategory('national', this)">
                        National COEs (<?= count($nationalCoes) ?>)
                    </button>
                    <button type="button" class="coe-filter-pill" onclick="filterCategory('regional', this)">
                        Regional COEs (<?= count($regionalCoes) ?>)
                    </button>
                </div>

                <!-- Instant Search Box -->
                <div style="max-width:320px;width:100%">
                    <div class="portal-search-box" style="margin:0">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="coeSearch" placeholder="Search institutions..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter')applySearch()" oninput="liveFilterInstitutions(this.value)">
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 1: NATIONAL COEs                                 -->
            <!-- ======================================================== -->
            <section class="coe-section-block" id="national-section">
                <!-- Title -->
                <h2 class="coe-category-heading"><?= htmlspecialchars($natTitle) ?></h2>

                <!-- Intro Paragraph -->
                <?php if ($natIntro): ?>
                    <p class="coe-category-intro"><?= nl2br(htmlspecialchars($natIntro)) ?></p>
                <?php endif; ?>

                <!-- Challenges Subheading -->
                <?php if ($natChallTitle): ?>
                    <h3 class="coe-challenges-heading"><?= htmlspecialchars($natChallTitle) ?></h3>
                <?php endif; ?>

                <!-- Challenges List -->
                <?php if (!empty($natChallenges) && is_array($natChallenges)): ?>
                    <div class="coe-challenges-list">
                        <?php foreach ($natChallenges as $item): ?>
                            <div class="coe-challenge-item">
                                <h4 class="coe-challenge-title"><?= htmlspecialchars($item['title'] ?? '') ?></h4>
                                <p class="coe-challenge-desc"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- National Institutions List -->
                <?php if (empty($nationalCoes)): ?>
                    <div class="portal-empty-state" style="padding:2.5rem 1.5rem;margin-top:1rem">
                        <p>No National Centers of Excellence found matching your search.</p>
                    </div>
                <?php else: ?>
                    <div class="coe-list" id="national-list">
                        <?php foreach ($nationalCoes as $row): ?>
                            <div class="coe-list-item" data-name="<?= htmlspecialchars(strtolower($row['institution_name'] . ' ' . $row['region'] . ' ' . $row['province'] . ' ' . $row['address'])) ?>">
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
                                        $addressParts = [];
                                        if (!empty($row['address'])) $addressParts[] = $row['address'];
                                        $regionStr = $row['region'] ?? '';

                                        $displayAddress = '';
                                        if (!empty($addressParts)) {
                                            $displayAddress = implode(', ', $addressParts);
                                            if ($regionStr) $displayAddress .= ' (' . $regionStr . ')';
                                        } elseif ($regionStr) {
                                            $displayAddress = $regionStr;
                                            if (!empty($row['province'])) $displayAddress = $row['province'] . ' (' . $regionStr . ')';
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
            </section>

            <!-- ======================================================== -->
            <!-- SECTION 2: REGIONAL COEs                                 -->
            <!-- ======================================================== -->
            <section class="coe-section-block" id="regional-section">
                <!-- Title -->
                <h2 class="coe-category-heading"><?= htmlspecialchars($regTitle) ?></h2>

                <!-- Intro Paragraph -->
                <?php if ($regIntro): ?>
                    <p class="coe-category-intro"><?= nl2br(htmlspecialchars($regIntro)) ?></p>
                <?php endif; ?>

                <!-- Challenges Subheading -->
                <?php if ($regChallTitle): ?>
                    <h3 class="coe-challenges-heading"><?= htmlspecialchars($regChallTitle) ?></h3>
                <?php endif; ?>

                <!-- Regional Grouped Challenges List -->
                <?php if (!empty($regChallenges) && is_array($regChallenges)): ?>
                    <div class="coe-challenges-list">
                        <?php foreach ($regChallenges as $group): ?>
                            <div class="coe-challenge-group">
                                <?php if (!empty($group['category'])): ?>
                                    <h4 class="coe-challenge-group-title"><?= htmlspecialchars($group['category']) ?></h4>
                                <?php endif; ?>

                                <?php if (!empty($group['items']) && is_array($group['items'])): ?>
                                    <?php foreach ($group['items'] as $item): ?>
                                        <div class="coe-challenge-item">
                                            <h5 class="coe-challenge-title"><?= htmlspecialchars($item['title'] ?? '') ?></h5>
                                            <p class="coe-challenge-desc"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Regional Institutions List -->
                <?php if (empty($regionalCoes)): ?>
                    <div class="portal-empty-state" style="padding:2.5rem 1.5rem;margin-top:1rem">
                        <p>No Regional Centers of Excellence found matching your search.</p>
                    </div>
                <?php else: ?>
                    <div class="coe-list" id="regional-list">
                        <?php foreach ($regionalCoes as $row): ?>
                            <div class="coe-list-item" data-name="<?= htmlspecialchars(strtolower($row['institution_name'] . ' ' . $row['region'] . ' ' . $row['province'] . ' ' . $row['address'])) ?>">
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
                                        $addressParts = [];
                                        if (!empty($row['address'])) $addressParts[] = $row['address'];
                                        $regionStr = $row['region'] ?? '';

                                        $displayAddress = '';
                                        if (!empty($addressParts)) {
                                            $displayAddress = implode(', ', $addressParts);
                                            if ($regionStr) $displayAddress .= ' (' . $regionStr . ')';
                                        } elseif ($regionStr) {
                                            $displayAddress = $regionStr;
                                            if (!empty($row['province'])) $displayAddress = $row['province'] . ' (' . $regionStr . ')';
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
            </section>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function filterCategory(cat, btn) {
            document.querySelectorAll('.coe-filter-pill').forEach(p => p.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const natSection = document.getElementById('national-section');
            const regSection = document.getElementById('regional-section');

            if (cat === 'national') {
                if (natSection) {
                    natSection.style.display = 'block';
                    natSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                if (regSection) regSection.style.display = 'none';
            } else if (cat === 'regional') {
                if (natSection) natSection.style.display = 'none';
                if (regSection) {
                    regSection.style.display = 'block';
                    regSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                if (natSection) natSection.style.display = 'block';
                if (regSection) regSection.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function liveFilterInstitutions(q) {
            const term = q.trim().toLowerCase();
            const items = document.querySelectorAll('.coe-list-item');
            items.forEach(item => {
                const text = item.getAttribute('data-name') || '';
                if (!term || text.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

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
