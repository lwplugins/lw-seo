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
     * Initialize on DOM ready.
     */
    function init() {
        initCounters();
        initCollapsibles();
    }

    // Run on DOM ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
