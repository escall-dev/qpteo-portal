<?php
/**
 * QPTEO Portal — Mobile Quick Actions Row Component
 * Direct touch navigation for Memos, Repositories, COEs, and Systems.
 */
$rootPath = $rootPath ?? '.';
?>
<section class="qpteo-quick-actions-section" aria-label="Quick Actions">
    <div class="qpteo-quick-actions-grid">
        
        <!-- 1. Memos -->
        <a href="<?= $rootPath ?>/memorandums.php" class="qpteo-action-btn" title="QPTEO Memorandums">
            <div class="qpteo-action-icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <span class="qpteo-action-label">Memos</span>
        </a>

        <!-- 2. Repositories / Documents -->
        <a href="<?= $rootPath ?>/repositories.php" class="qpteo-action-btn" title="Document Repositories">
            <div class="qpteo-action-icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <span class="qpteo-action-label">Documents</span>
        </a>

        <!-- 3. COEs -->
        <a href="<?= $rootPath ?>/coes.php" class="qpteo-action-btn" title="Centers of Excellence">
            <div class="qpteo-action-icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path>
                </svg>
            </div>
            <span class="qpteo-action-label">COEs</span>
        </a>

        <!-- 4. Systems -->
        <a href="https://dts.qpteo.com/index.php" target="_blank" rel="noopener noreferrer" class="qpteo-action-btn" title="Online Systems">
            <div class="qpteo-action-icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <span class="qpteo-action-label">Systems</span>
        </a>

    </div>
</section>
