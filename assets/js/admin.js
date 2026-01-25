/**
 * LW SEO Admin JavaScript
 */
(function () {
    'use strict';

    /**
     * Initialize character counters.
     */
    function initCounters() {
        const inputs = document.querySelectorAll('[data-max-length]');

        inputs.forEach(function (input) {
            const counter = document.querySelector('[data-for="' + input.id + '"]');
            if (!counter) return;

            const maxLength = parseInt(input.dataset.maxLength, 10);
            const currentSpan = counter.querySelector('.lw-seo-counter__current');

            function updateCounter() {
                const length = input.value.length;
                currentSpan.textContent = length;

                // Remove existing classes.
                counter.classList.remove('lw-seo-counter--warning', 'lw-seo-counter--error');

                // Add appropriate class based on length.
                if (length > maxLength) {
                    counter.classList.add('lw-seo-counter--error');
                } else if (length > maxLength * 0.9) {
                    counter.classList.add('lw-seo-counter--warning');
                }
            }

            // Initial update.
            updateCounter();

            // Listen for input events.
            input.addEventListener('input', updateCounter);
        });
    }

    /**
     * Initialize collapsible sections memory.
     */
    function initCollapsibles() {
        const details = document.querySelectorAll('.lw-seo-meta-box details');
        const storageKey = 'lwSeoCollapsibles';

        // Load saved state.
        let savedState = {};
        try {
            savedState = JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch (e) {
            savedState = {};
        }

        details.forEach(function (detail, index) {
            const key = 'section_' + index;

            // Restore state.
            if (savedState[key] === true) {
                detail.open = true;
            }

            // Save state on toggle.
            detail.addEventListener('toggle', function () {
                savedState[key] = detail.open;
                try {
                    localStorage.setItem(storageKey, JSON.stringify(savedState));
                } catch (e) {
                    // Storage full or not available.
                }
            });
        });
    }

    /**
     * Initialize settings page tabs.
     */
    function initSettingsTabs() {
        const tabLinks = document.querySelectorAll('.lw-seo-tabs a');
        const tabPanels = document.querySelectorAll('.lw-seo-tab-panel');

        if (!tabLinks.length || !tabPanels.length) return;

        const storageKey = 'lwSeoActiveTab';

        // Get saved tab or default to first.
        let activeTab = localStorage.getItem(storageKey) || 'general';

        // Activate saved tab.
        activateTab(activeTab);

        // Handle tab clicks.
        tabLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const tabId = this.getAttribute('href').substring(1);
                activateTab(tabId);
                localStorage.setItem(storageKey, tabId);
            });
        });

        function activateTab(tabId) {
            // Update tab links.
            tabLinks.forEach(function (link) {
                const linkTabId = link.getAttribute('href').substring(1);
                if (linkTabId === tabId) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Update tab panels.
            tabPanels.forEach(function (panel) {
                if (panel.id === 'tab-' + tabId) {
                    panel.classList.add('active');
                } else {
                    panel.classList.remove('active');
                }
            });
        }
    }

    /**
     * Initialize crawler card visual feedback.
     */
    function initCrawlerCards() {
        const cards = document.querySelectorAll('.lw-seo-crawler-card');

        cards.forEach(function (card) {
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (!checkbox) return;

            // Update card state on change.
            function updateCardState() {
                if (checkbox.checked) {
                    card.classList.add('blocked');
                } else {
                    card.classList.remove('blocked');
                }
            }

            // Initial state.
            updateCardState();

            // Listen for changes.
            checkbox.addEventListener('change', updateCardState);
        });
    }

    /**
     * Initialize on DOM ready.
     */
    function init() {
        initCounters();
        initCollapsibles();
        initSettingsTabs();
        initCrawlerCards();
    }

    // Run on DOM ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
