/**
 * QPTEO Portal — Custom Bottom Sheet Picker Component
 * Provides an accessible, mobile-first bottom sheet picker replacing native <select> elements.
 * Features:
 * - Slide up/down animations with backdrop blur
 * - Anchored to bottom with 70% viewport max height
 * - Real-time search filtering with instant clear
 * - Document counts formatted as distinct subtle pills
 * - Highlighted selected state with SVG checkmark
 * - Escape key, backdrop click, and swipe-down touch gestures
 */

class PortalBottomSheet {
    constructor() {
        this.activeSheet = null;
        this.touchStartY = 0;
        this.touchCurrentY = 0;
        this.init();
    }

    init() {
        // Global escape key listener
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeSheet) {
                this.close(this.activeSheet);
            }
        });

        // Initialize all sheet search inputs and items
        document.querySelectorAll('.portal-bottom-sheet').forEach((sheet) => {
            const searchInput = sheet.querySelector('.portal-sheet-search-input');
            const clearBtn = sheet.querySelector('.portal-sheet-search-clear');
            const items = sheet.querySelectorAll('.portal-sheet-item');
            const emptyState = sheet.querySelector('.portal-sheet-empty');

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.trim().toLowerCase();
                    if (clearBtn) {
                        clearBtn.style.display = query ? 'flex' : 'none';
                    }

                    let visibleCount = 0;
                    items.forEach((item) => {
                        const label = (item.getAttribute('data-label') || item.textContent).toLowerCase();
                        const matches = !query || label.includes(query);
                        item.style.display = matches ? 'flex' : 'none';
                        if (matches) visibleCount++;
                    });

                    // Hide empty optgroups
                    sheet.querySelectorAll('.portal-sheet-group').forEach((group) => {
                        const groupItems = group.querySelectorAll('.portal-sheet-item');
                        const anyVisible = Array.from(groupItems).some(it => it.style.display !== 'none');
                        group.style.display = anyVisible ? 'block' : 'none';
                    });

                    if (emptyState) {
                        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
                    }
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        searchInput.value = '';
                        searchInput.dispatchEvent(new Event('input'));
                        searchInput.focus();
                    });
                }
            }

            // Touch gesture (drag down to dismiss)
            const handle = sheet.querySelector('.portal-sheet-handle') || sheet.querySelector('.portal-sheet-header');
            const panel = sheet.querySelector('.portal-sheet-panel');

            if (handle && panel) {
                handle.addEventListener('touchstart', (e) => {
                    this.touchStartY = e.touches[0].clientY;
                    this.touchCurrentY = this.touchStartY;
                    panel.style.transition = 'none';
                }, { passive: true });

                handle.addEventListener('touchmove', (e) => {
                    this.touchCurrentY = e.touches[0].clientY;
                    const deltaY = this.touchCurrentY - this.touchStartY;
                    if (deltaY > 0) {
                        panel.style.transform = `translateY(${deltaY}px)`;
                    }
                }, { passive: true });

                handle.addEventListener('touchend', () => {
                    panel.style.transition = '';
                    const deltaY = this.touchCurrentY - this.touchStartY;
                    if (deltaY > 80) {
                        this.close(sheet.id);
                    } else {
                        panel.style.transform = '';
                    }
                });
            }
        });
    }

    open(sheetId) {
        const sheet = document.getElementById(sheetId);
        if (!sheet) return;

        // Close any currently active sheet
        if (this.activeSheet && this.activeSheet !== sheetId) {
            this.close(this.activeSheet);
        }

        this.activeSheet = sheetId;
        sheet.classList.add('is-open');
        document.body.classList.add('portal-sheet-open');

        const panel = sheet.querySelector('.portal-sheet-panel');
        if (panel) panel.style.transform = '';

        // Reset search
        const searchInput = sheet.querySelector('.portal-sheet-search-input');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }

        // Scroll selected item into view smoothly
        setTimeout(() => {
            const selectedItem = sheet.querySelector('.portal-sheet-item.is-selected');
            const body = sheet.querySelector('.portal-sheet-body');
            if (selectedItem && body) {
                const itemTop = selectedItem.offsetTop;
                body.scrollTop = Math.max(0, itemTop - 60);
            }
            if (searchInput && window.innerWidth >= 768) {
                searchInput.focus();
            }
        }, 150);
    }

    close(sheetId) {
        const sheet = typeof sheetId === 'string' ? document.getElementById(sheetId) : sheetId;
        if (!sheet) return;

        sheet.classList.remove('is-open');
        if (this.activeSheet === sheet.id) {
            this.activeSheet = null;
        }

        // Only remove body scroll lock if no other sheets are open
        const anyOpen = document.querySelector('.portal-bottom-sheet.is-open');
        if (!anyOpen) {
            document.body.classList.remove('portal-sheet-open');
        }
    }

    selectItem(sheetId, value, callback) {
        this.close(sheetId);
        if (typeof callback === 'function') {
            callback(value);
        }
    }
}

// Global instance attached to window
window.portalBottomSheet = new PortalBottomSheet();

// Helper global functions for inline onclick handlers
function openBottomSheet(sheetId) {
    if (window.portalBottomSheet) {
        window.portalBottomSheet.open(sheetId);
    }
}

function closeBottomSheet(sheetId) {
    if (window.portalBottomSheet) {
        window.portalBottomSheet.close(sheetId);
    }
}
