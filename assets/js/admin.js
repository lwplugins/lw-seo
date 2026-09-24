/**
 * LW SEO classic editor meta box JavaScript
 *
 * @package LightweightPlugins\SEO
 */

(function () {
	'use strict';

	/**
	 * Initialize character counters.
	 */
	function initCounters() {
		const inputs = document.querySelectorAll( '[data-max-length]' );

		inputs.forEach(
			function (input) {
				const counter = document.querySelector( '[data-for="' + input.id + '"]' );
				if ( ! counter) {
					return;
				}

				const maxLength   = parseInt( input.dataset.maxLength, 10 );
				const currentSpan = counter.querySelector( '.lw-seo-counter__current' );

				function updateCounter() {
					const length            = input.value.length;
					currentSpan.textContent = length;

					// Remove existing classes.
					counter.classList.remove( 'lw-seo-counter--warning', 'lw-seo-counter--error' );

					// Add appropriate class based on length.
					if (length > maxLength) {
						counter.classList.add( 'lw-seo-counter--error' );
					} else if (length > maxLength * 0.9) {
						counter.classList.add( 'lw-seo-counter--warning' );
					}
				}

				// Initial update.
				updateCounter();

				// Listen for input events.
				input.addEventListener( 'input', updateCounter );
			}
		);
	}

	/**
	 * Initialize collapsible sections memory.
	 */
	function initCollapsibles() {
		const details    = document.querySelectorAll( '.lw-seo-meta-box details' );
		const storageKey = 'lwSeoCollapsibles';

		// Load saved state.
		let savedState = {};
		try {
			savedState = JSON.parse( localStorage.getItem( storageKey ) || '{}' );
		} catch (e) {
			savedState = {};
		}

		details.forEach(
			function (detail, index) {
				const key = 'section_' + index;

				// Restore state.
				if (savedState[key] === true) {
					detail.open = true;
				}

				// Save state on toggle.
				detail.addEventListener(
					'toggle',
					function () {
						savedState[key] = detail.open;
						try {
							localStorage.setItem( storageKey, JSON.stringify( savedState ) );
						} catch (e) {
							// Storage full or not available.
						}
					}
				);
			}
		);
	}

	/**
	 * Initialize media uploader for image fields.
	 */
	function initMediaUploader() {
		const uploadButtons = document.querySelectorAll( '.lw-seo-upload-image' );
		const removeButtons = document.querySelectorAll( '.lw-seo-remove-image' );

		uploadButtons.forEach(
			function (button) {
				button.addEventListener(
					'click',
					function (e) {
						e.preventDefault();

						const container    = this.closest( '.lw-seo-image-field' );
						const input        = container.querySelector( '.lw-seo-image-url' );
						const preview      = container.querySelector( '.lw-seo-image-preview' );
						const previewImg   = preview.querySelector( 'img' );
						const removeButton = container.querySelector( '.lw-seo-remove-image' );

						// Check if wp.media exists.
						if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
							alert( 'Media library not available.' );
							return;
						}

						// Create media frame.
						const frame = wp.media(
							{
								title: 'Select Image',
								multiple: false,
								library: { type: 'image' }
							}
						);

						// Handle selection.
						frame.on(
							'select',
							function () {
								const attachment = frame.state().get( 'selection' ).first().toJSON();
								input.value      = attachment.url;

								// Show preview.
								previewImg.src        = attachment.url;
								preview.style.display = 'block';

								// Show remove button.
								if (removeButton) {
									removeButton.style.display = '';
								}
							}
						);

						frame.open();
					}
				);
			}
		);

		// Handle remove button clicks.
		removeButtons.forEach(
			function (button) {
				button.addEventListener(
					'click',
					function (e) {
						e.preventDefault();

						const container  = this.closest( '.lw-seo-image-field' );
						const input      = container.querySelector( '.lw-seo-image-url' );
						const preview    = container.querySelector( '.lw-seo-image-preview' );
						const previewImg = preview.querySelector( 'img' );

						// Clear input.
						input.value = '';

						// Hide preview.
						previewImg.src        = '';
						preview.style.display = 'none';

						// Hide remove button.
						this.style.display = 'none';
					}
				);
			}
		);
	}

	/**
	 * Initialize on DOM ready.
	 */
	function init() {
		initCounters();
		initCollapsibles();
		initMediaUploader();
	}

	// Run on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
