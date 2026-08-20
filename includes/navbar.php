<?php
/**
 * QPTEO Portal — Shared Self-Contained Navbar Component
 * 
 * Expected variable (optional):
 *   $activeNav — string identifying the active nav item: 'home', 'systems', 'repositories', 'issuances', 'coes'
 */
$activeNav = $activeNav ?? '';
$isSubdir  = !file_exists('includes/navbar.php');
$rootPath  = $isSubdir ? '..' : '.';

require_once __DIR__ . '/../config/database.php';
try {
    $pdo = getPortalDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'meeting_recordings_url'");
    $stmt->execute();
    $meetingRecordingsUrl = $stmt->fetchColumn() ?: '#';
} catch (Exception $e) {
    $meetingRecordingsUrl = '#';
}
?>
<!-- Shared Portal CSS -->
<link rel="stylesheet" href="<?= $rootPath ?>/assets/css/portal.css?v=<?= time() ?>">
<!-- Dedicated Mobile Stylesheet -->
<link rel="stylesheet" href="<?= $rootPath ?>/assets/mobile/mobile.css?v=<?= time() ?>">
<!-- Portal Bottom Sheet Controller -->
<script src="<?= $rootPath ?>/assets/js/portal-bottom-sheet.js?v=<?= time() ?>"></script>

<header class="qpteo-navbar">
    <div class="qpteo-navbar-container">
        
        <!-- Brand Logo Only (Standalone Image) -->
        <a href="<?= $rootPath ?>/home.php" class="qpteo-brand" title="QPTEO Portal Home">
            <img src="<?= $rootPath ?>/branding/qpteo logo unfainalized no bg.png" alt="QPTEO Logo" class="qpteo-navbar-logo">
        </a>

        <!-- Mobile Menu Toggle Hamburger Button -->
        <button class="qpteo-mobile-toggle" id="qpteoMobileToggle" aria-label="Toggle navigation menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- Navigation Menu -->
        <ul class="qpteo-nav-menu" id="qpteoNavMenu">

            <!-- Home -->
            <li class="qpteo-nav-item">
                <a href="<?= $rootPath ?>/home.php" class="qpteo-nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">
                    Home
                </a>
            </li>

            <!-- Systems Dropdown -->
            <li class="qpteo-nav-item has-dropdown">
                <a href="#" class="qpteo-nav-link <?= $activeNav === 'systems' ? 'active' : '' ?>" onclick="return false;">
                    Systems
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <ul class="qpteo-dropdown-menu">
                    <li><a href="/landing/dls/pages/login.php" class="qpteo-dropdown-item" target="_blank" rel="noopener noreferrer">Document Library System</a></li>
                    <li><a href="https://oel.qpteo.com/login.php" class="qpteo-dropdown-item" target="_blank" rel="noopener noreferrer">Online Electronic Logbook</a></li>
                     <li><a href="https://qpteo.com/virtual-co-design-board/login.php" class="qpteo-dropdown-item" target="_blank" rel="noopener noreferrer">Virtual Co-Design Board</a></li>
                    <li><a href="#" class="qpteo-dropdown-item" target="_blank" rel="noopener noreferrer">DIRECTOry</a></li>
                </ul>
            </li>

            <!-- Repositories Dropdown -->
            <li class="qpteo-nav-item has-dropdown">
                <a href="#" class="qpteo-nav-link <?= $activeNav === 'repositories' ? 'active' : '' ?>" onclick="return false;">
                    Repositories
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <ul class="qpteo-dropdown-menu">
                    <li><a href="<?= $rootPath ?>/repositories.php?category=presentations" class="qpteo-dropdown-item">Presentations</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=concept_papers" class="qpteo-dropdown-item">Concept Papers</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=checklists" class="qpteo-dropdown-item">Checklists</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=briefers" class="qpteo-dropdown-item">Briefers</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=reports" class="qpteo-dropdown-item">Reports</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=session_guides" class="qpteo-dropdown-item">Session Guides</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=accomplishment_reports" class="qpteo-dropdown-item">Accomplishment Reports</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=leave_forms" class="qpteo-dropdown-item">Leave Forms</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=proposals" class="qpteo-dropdown-item">Proposals</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=program_completion_reports" class="qpteo-dropdown-item">Program Completion Reports</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=monitoring_evaluation" class="qpteo-dropdown-item">Monitoring and Evaluation Results</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=others" class="qpteo-dropdown-item">Others</a></li>
                </ul>
            </li>

            <!-- Meetings Dropdown -->
            <li class="qpteo-nav-item has-dropdown">
                <a href="#" class="qpteo-nav-link <?= $activeNav === 'meetings' ? 'active' : '' ?>" onclick="return false;">
                    Meetings
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <ul class="qpteo-dropdown-menu">
                    <!-- Nested Dropdown for Minutes of the Meetings -->
                    <li class="has-nested-dropdown">
                        <a href="#" class="qpteo-dropdown-item qpteo-nested-toggle" onclick="return false;" style="display: flex; justify-content: space-between; align-items: center;">
                            Minutes of the Meetings
                            <svg class="nested-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="margin-left: 0.5rem;"><path d="M9 18l6-6-6-6"/></svg>
                        </a>
                        <ul class="qpteo-nested-dropdown-menu">
                            <li><a href="<?= $rootPath ?>/repositories.php?category=qpteo_office_meetings" class="qpteo-dropdown-item">QPTEO Office Meetings</a></li>
                            <li><a href="<?= $rootPath ?>/repositories.php?category=execom_meetings" class="qpteo-dropdown-item">ExeCom Meetings</a></li>
                            <li><a href="<?= $rootPath ?>/repositories.php?category=other_meetings" class="qpteo-dropdown-item">Other Meetings</a></li>
                        </ul>
                    </li>
                    <li><a href="<?= htmlspecialchars($meetingRecordingsUrl) ?>" target="_blank" rel="noopener noreferrer" class="qpteo-dropdown-item">Meeting Recordings</a></li>
                </ul>
            </li>

            <!-- References Dropdown -->
            <li class="qpteo-nav-item has-dropdown">
                <a href="#" class="qpteo-nav-link <?= $activeNav === 'references' ? 'active' : '' ?>" onclick="return false;">
                    References
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <ul class="qpteo-dropdown-menu">
                    <li><a href="<?= $rootPath ?>/repositories.php?category=cmos" class="qpteo-dropdown-item">CHED Memorandum Orders (CMOs)</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=psgs" class="qpteo-dropdown-item">Policies, Standards and Guidelines (PSGs)</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=ppst" class="qpteo-dropdown-item">Philippine Professional Standards for Teachers (PPST)</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=policies" class="qpteo-dropdown-item">Policies</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=guidelines" class="qpteo-dropdown-item">Guidelines</a></li>
                    <li><a href="<?= $rootPath ?>/repositories.php?category=rite" class="qpteo-dropdown-item">Research Initiatives in Teacher Education (RITE)</a></li>
                </ul>
            </li>

            <!-- Issuances Dropdown -->
            <li class="qpteo-nav-item has-dropdown">
                <a href="#" class="qpteo-nav-link <?= $activeNav === 'issuances' ? 'active' : '' ?>" onclick="return false;">
                    Issuances
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <ul class="qpteo-dropdown-menu">
                    <li><a href="<?= $rootPath ?>/memorandums.php" class="qpteo-dropdown-item">QPTEO Office Memorandums</a></li>
                </ul>
            </li>

            <!-- Centers of Excellence Dropdown -->
            <li class="qpteo-nav-item has-dropdown">
                <a href="#" class="qpteo-nav-link <?= $activeNav === 'coes' ? 'active' : '' ?>" onclick="return false;">
                    Centers of Excellence
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <ul class="qpteo-dropdown-menu">
                    <li><a href="<?= $rootPath ?>/coes.php" class="qpteo-dropdown-item">Teacher Education Centers of Excellence 2026</a></li>
                </ul>
            </li>

        </ul>

        <!-- Global Search Bar (Right Side of Navbar) -->
        <div class="qpteo-search-box">
            <form action="<?= $rootPath ?>/search.php" method="GET" class="qpteo-search-form" id="qpteoSearchForm">
                <div class="qpteo-search-input-wrapper">
                    <svg class="qpteo-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="q" id="qpteoSearchInput" class="qpteo-search-input" placeholder="Search memos, titles, systems..." autocomplete="off" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="button" id="qpteoSearchClear" class="qpteo-search-clear" aria-label="Clear search">&times;</button>
                </div>
            </form>

            <!-- Live Search Overlay Dropdown -->
            <div class="qpteo-search-results-dropdown" id="qpteoSearchResultsDropdown">
                <div class="qpteo-search-results-content" id="qpteoSearchResultsContent"></div>
            </div>
        </div>

    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('qpteoMobileToggle');
    const navMenu   = document.getElementById('qpteoNavMenu');

    if (toggleBtn && navMenu) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('mobile-open');
        });
    }

    // Toggle dropdowns on mobile click
    const dropdownItems = document.querySelectorAll('.qpteo-nav-item.has-dropdown');
    dropdownItems.forEach(item => {
        const link = item.querySelector('.qpteo-nav-link');
        link.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                e.preventDefault();
                e.stopPropagation();
                dropdownItems.forEach(other => {
                    if (other !== item) {
                        other.classList.remove('open');
                        other.querySelectorAll('.has-nested-dropdown').forEach(n => n.classList.remove('open'));
                    }
                });
                item.classList.toggle('open');
            }
        });
    });

    // Toggle nested dropdowns on click (both desktop & mobile)
    const nestedDropdowns = document.querySelectorAll('.has-nested-dropdown');
    nestedDropdowns.forEach(nestedItem => {
        const nestedToggle = nestedItem.querySelector('.qpteo-nested-toggle') || nestedItem.querySelector('a');
        if (nestedToggle) {
            nestedToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                nestedDropdowns.forEach(other => {
                    if (other !== nestedItem) other.classList.remove('open');
                });
                nestedItem.classList.toggle('open');
            });
        }
    });

    // Reset open nested dropdown state when mouse leaves parent nav item on desktop
    dropdownItems.forEach(item => {
        item.addEventListener('mouseleave', function() {
            if (window.innerWidth >= 992) {
                item.querySelectorAll('.has-nested-dropdown').forEach(n => n.classList.remove('open'));
            }
        });
    });

    // --- Live Search Integration ---
    const searchInput    = document.getElementById('qpteoSearchInput');
    const searchForm     = document.getElementById('qpteoSearchForm');
    const searchClear    = document.getElementById('qpteoSearchClear');
    const dropdown       = document.getElementById('qpteoSearchResultsDropdown');
    const content        = document.getElementById('qpteoSearchResultsContent');
    const rootPath       = '<?= $rootPath ?>';
    let debounceTimer    = null;

    if (searchInput) {
        // Toggle clear button state
        function updateClearBtn() {
            if (searchClear) {
                searchClear.style.display = searchInput.value.trim() ? 'block' : 'none';
            }
        }
        updateClearBtn();

        searchInput.addEventListener('input', function() {
            updateClearBtn();
            const q = searchInput.value.trim();

            clearTimeout(debounceTimer);
            if (q.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`${rootPath}/search_api.php?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status !== 'success') return;
                        renderLiveSearchResults(data, q);
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                    });
            }, 180);
        });

        if (searchClear) {
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                updateClearBtn();
                dropdown.style.display = 'none';
                searchInput.focus();
            });
        }
    }

    function renderLiveSearchResults(data, q) {
        if (!content || !dropdown) return;
        const res = data.results;
        let html  = '';

        if (data.total === 0) {
            html = `<div class="qpteo-search-no-results">No matches found for "<strong>${escapeHTML(q)}</strong>"</div>`;
        } else {
            // Navigation Submenus
            if (res.submenus && res.submenus.length > 0) {
                html += `<div class="qpteo-search-cat-title">Navigation Submenus</div>`;
                res.submenus.forEach(item => {
                    html += `
                        <a href="${item.url}" class="qpteo-search-item">
                            <span class="qpteo-search-badge badge-gold">${escapeHTML(item.parent)}</span>
                            <div class="qpteo-search-item-info">
                                <div class="qpteo-search-item-title">${escapeHTML(item.title)}</div>
                                <div class="qpteo-search-item-sub">Submenu link under ${escapeHTML(item.parent)}</div>
                            </div>
                        </a>
                    `;
                });
            }

            // Systems
            if (res.systems && res.systems.length > 0) {
                html += `<div class="qpteo-search-cat-title">Systems & Portals</div>`;
                res.systems.forEach(item => {
                    html += `
                        <a href="${item.url}" class="qpteo-search-item" target="_blank" rel="noopener noreferrer">
                            <span class="qpteo-search-badge badge-navy">${escapeHTML(item.code)}</span>
                            <div class="qpteo-search-item-info">
                                <div class="qpteo-search-item-title">${escapeHTML(item.title)}</div>
                                <div class="qpteo-search-item-sub">${escapeHTML(item.description)}</div>
                            </div>
                        </a>
                    `;
                });
            }

            // Memorandums
            if (res.memorandums && res.memorandums.length > 0) {
                html += `<div class="qpteo-search-cat-title">Office Memorandums</div>`;
                res.memorandums.forEach(item => {
                    html += `
                        <a href="${item.url}" class="qpteo-search-item">
                            <span class="qpteo-search-badge badge-gold">${escapeHTML(item.memo_number)}</span>
                            <div class="qpteo-search-item-info">
                                <div class="qpteo-search-item-title">${escapeHTML(item.title)}</div>
                                <div class="qpteo-search-item-sub">Date: ${escapeHTML(item.date)}</div>
                            </div>
                        </a>
                    `;
                });
            }

            // Repositories (Presentations, Briefers, Reports, etc.)
            if (res.repositories && res.repositories.length > 0) {
                html += `<div class="qpteo-search-cat-title">Documents & Repositories</div>`;
                res.repositories.forEach(item => {
                    html += `
                        <a href="${item.url}" class="qpteo-search-item">
                            <span class="qpteo-search-badge badge-navy">${escapeHTML(item.category)}</span>
                            <div class="qpteo-search-item-info">
                                <div class="qpteo-search-item-title">${escapeHTML(item.title)}</div>
                                <div class="qpteo-search-item-sub">Uploaded: ${escapeHTML(item.date)}</div>
                            </div>
                        </a>
                    `;
                });
            }

            // Centers of Excellence
            if (res.coes && res.coes.length > 0) {
                html += `<div class="qpteo-search-cat-title">Centers of Excellence</div>`;
                res.coes.forEach(item => {
                    html += `
                        <a href="${item.url}" class="qpteo-search-item">
                            <span class="qpteo-search-badge badge-gold">${escapeHTML(item.region)}</span>
                            <div class="qpteo-search-item-info">
                                <div class="qpteo-search-item-title">${escapeHTML(item.title)}</div>
                                <div class="qpteo-search-item-sub">${escapeHTML(item.province || '')}</div>
                            </div>
                        </a>
                    `;
                });
            }

            // View all button
            html += `
                <div class="qpteo-search-view-all">
                    <a href="${rootPath}/search.php?q=${encodeURIComponent(q)}" class="qpteo-search-view-all-btn">
                        View all ${data.total} search results &rarr;
                    </a>
                </div>
            `;
        }

        content.innerHTML      = html;
        dropdown.style.display = 'block';
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        dropdownItems.forEach(item => item.classList.remove('open'));
        nestedDropdowns.forEach(item => item.classList.remove('open'));
        if (navMenu) navMenu.classList.remove('mobile-open');

        const searchBox = document.querySelector('.qpteo-search-box');
        if (searchBox && !searchBox.contains(e.target)) {
            if (dropdown) dropdown.style.display = 'none';
        }
    });
});
</script>
