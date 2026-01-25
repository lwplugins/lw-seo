/**
 * LW SEO Redirect Manager JavaScript
 *
 * @package LightweightPlugins\SEO
 */

(function () {
	'use strict';

	// Elements.
	const form          = document.getElementById( 'lw-seo-redirect-form' );
	const editIdInput   = document.getElementById( 'lw-redirect-edit-id' );
	const sourceInput   = document.getElementById( 'lw-redirect-source' );
	const destInput     = document.getElementById( 'lw-redirect-destination' );
	const typeSelect    = document.getElementById( 'lw-redirect-type' );
	const regexCheckbox = document.getElementById( 'lw-redirect-regex' );
	const saveButton    = document.getElementById( 'lw-redirect-save' );
	const cancelButton  = document.getElementById( 'lw-redirect-cancel' );
	const messageSpan   = document.getElementById( 'lw-redirect-message' );
	const table         = document.getElementById( 'lw-seo-redirects-table' );
	const exportButton  = document.getElementById( 'lw-redirect-export' );
	const importFile    = document.getElementById( 'lw-redirect-import-file' );
	const importButton  = document.getElementById( 'lw-redirect-import' );
	const importResult  = document.getElementById( 'lw-redirect-import-result' );

	if ( ! form) {
		return;
	}

	/**
	 * Show message to user.
	 *
	 * @param {string} text Message text.
	 * @param {string} type Message type ('success' or 'error').
	 */
	function showMessage(text, type) {
		messageSpan.textContent = text;
		messageSpan.className   = 'lw-seo-message lw-seo-message--' + type;

		setTimeout(
			function () {
				messageSpan.textContent = '';
				messageSpan.className   = 'lw-seo-message';
			},
			5000
		);
	}

	/**
	 * Reset form to add mode.
	 */
	function resetForm() {
		editIdInput.value     = '';
		sourceInput.value     = '';
		destInput.value       = '';
		typeSelect.value      = '301';
		regexCheckbox.checked = false;

		saveButton.textContent     = lwSeoRedirectsL10n.addButton;
		cancelButton.style.display = 'none';
	}

	/**
	 * Set form to edit mode.
	 *
	 * @param {Object} redirect Redirect data.
	 * @param {number} id       Redirect ID.
	 */
	function setEditMode(redirect, id) {
		editIdInput.value     = id;
		sourceInput.value     = redirect.source;
		destInput.value       = redirect.destination || '';
		typeSelect.value      = redirect.type;
		regexCheckbox.checked = redirect.regex;

		saveButton.textContent     = lwSeoRedirectsL10n.updateButton;
		cancelButton.style.display = 'inline-block';

		// Scroll to form.
		form.scrollIntoView( { behavior: 'smooth' } );
		sourceInput.focus();
	}

	/**
	 * Make AJAX request.
	 *
	 * @param {string}   action   AJAX action.
	 * @param {Object}   data     Request data.
	 * @param {Function} callback Success callback.
	 */
	function ajaxRequest(action, data, callback) {
		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( 'nonce', lwSeoRedirectsL10n.nonce );

		for (const key in data) {
			if (data.hasOwnProperty( key )) {
				formData.append( key, data[key] );
			}
		}

		fetch(
			ajaxurl,
			{
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			}
		)
		.then(
			function (response) {
				return response.json(); }
		)
		.then(
			function (result) {
				if (result.success) {
					callback( result.data );
				} else {
					showMessage( result.data.message || 'An error occurred.', 'error' );
				}
			}
		)
		.catch(
			function () {
				showMessage( 'Network error occurred.', 'error' );
			}
		);
	}

	/**
	 * Create a code element with text content.
	 *
	 * @param {string} text Text content.
	 * @return {HTMLElement} Code element.
	 */
	function createCodeElement(text) {
		const code       = document.createElement( 'code' );
		code.textContent = text;
		return code;
	}

	/**
	 * Add row to table.
	 *
	 * @param {Object} redirect Redirect data.
	 * @param {number} id       Redirect ID.
	 */
	function addTableRow(redirect, id) {
		if ( ! table) {
			// Reload page to show table.
			location.reload();
			return;
		}

		const tbody    = table.querySelector( 'tbody' );
		const row      = document.createElement( 'tr' );
		row.dataset.id = id;

		// Source column.
		const sourceCell     = document.createElement( 'td' );
		sourceCell.className = 'column-source';
		sourceCell.appendChild( createCodeElement( redirect.source ) );
		if (redirect.regex) {
			const badge       = document.createElement( 'span' );
			badge.className   = 'lw-seo-badge';
			badge.textContent = lwSeoRedirectsL10n.regex;
			sourceCell.appendChild( document.createTextNode( ' ' ) );
			sourceCell.appendChild( badge );
		}

		// Destination column.
		const destCell     = document.createElement( 'td' );
		destCell.className = 'column-destination';
		if ([410, 451].includes( redirect.type )) {
			const em       = document.createElement( 'em' );
			em.textContent = lwSeoRedirectsL10n.na;
			destCell.appendChild( em );
		} else {
			destCell.appendChild( createCodeElement( redirect.destination ) );
		}

		// Type column.
		const typeCell       = document.createElement( 'td' );
		typeCell.className   = 'column-type';
		const typeSpan       = document.createElement( 'span' );
		typeSpan.className   = 'lw-seo-type-' + redirect.type;
		typeSpan.textContent = redirect.type;
		typeCell.appendChild( typeSpan );

		// Hits column.
		const hitsCell       = document.createElement( 'td' );
		hitsCell.className   = 'column-hits';
		hitsCell.textContent = '0';

		// Actions column.
		const actionsCell     = document.createElement( 'td' );
		actionsCell.className = 'column-actions';

		const editBtn       = document.createElement( 'button' );
		editBtn.type        = 'button';
		editBtn.className   = 'button button-small lw-redirect-edit';
		editBtn.dataset.id  = id;
		editBtn.textContent = lwSeoRedirectsL10n.editButton;

		const deleteBtn       = document.createElement( 'button' );
		deleteBtn.type        = 'button';
		deleteBtn.className   = 'button button-small button-link-delete lw-redirect-delete';
		deleteBtn.dataset.id  = id;
		deleteBtn.textContent = lwSeoRedirectsL10n.deleteButton;

		actionsCell.appendChild( editBtn );
		actionsCell.appendChild( document.createTextNode( ' ' ) );
		actionsCell.appendChild( deleteBtn );

		row.appendChild( sourceCell );
		row.appendChild( destCell );
		row.appendChild( typeCell );
		row.appendChild( hitsCell );
		row.appendChild( actionsCell );

		tbody.appendChild( row );
		attachRowHandlers( row );
	}

	/**
	 * Update table row.
	 *
	 * @param {Object} redirect Redirect data.
	 * @param {number} id       Redirect ID.
	 */
	function updateTableRow(redirect, id) {
		const row = table.querySelector( 'tr[data-id="' + id + '"]' );
		if ( ! row) {
			return;
		}

		// Update source column.
		const sourceCell       = row.querySelector( '.column-source' );
		sourceCell.textContent = '';
		sourceCell.appendChild( createCodeElement( redirect.source ) );
		if (redirect.regex) {
			const badge       = document.createElement( 'span' );
			badge.className   = 'lw-seo-badge';
			badge.textContent = lwSeoRedirectsL10n.regex;
			sourceCell.appendChild( document.createTextNode( ' ' ) );
			sourceCell.appendChild( badge );
		}

		// Update destination column.
		const destCell       = row.querySelector( '.column-destination' );
		destCell.textContent = '';
		if ([410, 451].includes( redirect.type )) {
			const em       = document.createElement( 'em' );
			em.textContent = lwSeoRedirectsL10n.na;
			destCell.appendChild( em );
		} else {
			destCell.appendChild( createCodeElement( redirect.destination ) );
		}

		// Update type column.
		const typeCell       = row.querySelector( '.column-type' );
		typeCell.textContent = '';
		const typeSpan       = document.createElement( 'span' );
		typeSpan.className   = 'lw-seo-type-' + redirect.type;
		typeSpan.textContent = redirect.type;
		typeCell.appendChild( typeSpan );
	}

	/**
	 * Remove table row.
	 *
	 * @param {number} id Redirect ID.
	 */
	function removeTableRow(id) {
		const row = table.querySelector( 'tr[data-id="' + id + '"]' );
		if (row) {
			row.remove();
		}
	}

	/**
	 * Attach event handlers to table row.
	 *
	 * @param {HTMLElement} row Table row element.
	 */
	function attachRowHandlers(row) {
		const editBtn   = row.querySelector( '.lw-redirect-edit' );
		const deleteBtn = row.querySelector( '.lw-redirect-delete' );

		if (editBtn) {
			editBtn.addEventListener( 'click', handleEdit );
		}

		if (deleteBtn) {
			deleteBtn.addEventListener( 'click', handleDelete );
		}
	}

	/**
	 * Handle edit button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleEdit(e) {
		const id       = parseInt( e.target.dataset.id, 10 );
		const redirect = typeof lwSeoRedirects !== 'undefined' ? lwSeoRedirects[id] : null;

		if (redirect) {
			setEditMode( redirect, id );
		} else {
			// Fetch from server.
			ajaxRequest(
				'lw_seo_redirect_get',
				{ id: id },
				function (data) {
					setEditMode( data.redirect, id );
				}
			);
		}
	}

	/**
	 * Handle delete button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleDelete(e) {
		if ( ! confirm( lwSeoRedirectsL10n.confirmDelete )) {
			return;
		}

		const id = parseInt( e.target.dataset.id, 10 );

		ajaxRequest(
			'lw_seo_redirect_delete',
			{ id: id },
			function (data) {
				showMessage( data.message, 'success' );
				removeTableRow( id );
			}
		);
	}

	/**
	 * Handle save button click.
	 */
	function handleSave() {
		const source      = sourceInput.value.trim();
		const destination = destInput.value.trim();
		const type        = parseInt( typeSelect.value, 10 );
		const regex       = regexCheckbox.checked;
		const editId      = editIdInput.value;

		if ( ! source) {
			showMessage( lwSeoRedirectsL10n.sourceRequired, 'error' );
			sourceInput.focus();
			return;
		}

		const data = {
			source: source,
			destination: destination,
			type: type,
			regex: regex ? 'true' : 'false'
		};

		if (editId !== '') {
			// Update existing.
			data.id = parseInt( editId, 10 );
			ajaxRequest(
				'lw_seo_redirect_update',
				data,
				function (result) {
					showMessage( result.message, 'success' );
					updateTableRow( result.redirect, data.id );
					resetForm();

					// Update local data.
					if (typeof lwSeoRedirects !== 'undefined') {
						lwSeoRedirects[data.id] = result.redirect;
					}
				}
			);
		} else {
			// Add new.
			ajaxRequest(
				'lw_seo_redirect_add',
				data,
				function (result) {
					showMessage( result.message, 'success' );
					addTableRow( result.redirect, result.id );
					resetForm();

					// Update local data.
					if (typeof lwSeoRedirects !== 'undefined') {
						lwSeoRedirects[result.id] = result.redirect;
					}
				}
			);
		}
	}

	/**
	 * Handle export button click.
	 */
	function handleExport() {
		ajaxRequest(
			'lw_seo_redirect_export',
			{},
			function (data) {
				// Create download.
				const blob = new Blob( [data.csv], { type: 'text/csv' } );
				const url  = window.URL.createObjectURL( blob );
				const a    = document.createElement( 'a' );
				a.href     = url;
				a.download = data.filename;
				document.body.appendChild( a );
				a.click();
				document.body.removeChild( a );
				window.URL.revokeObjectURL( url );
			}
		);
	}

	/**
	 * Handle import button click.
	 */
	function handleImport() {
		const file = importFile.files[0];
		if ( ! file) {
			showMessage( lwSeoRedirectsL10n.selectFile, 'error' );
			return;
		}

		const reader   = new FileReader();
		reader.onload  = function (e) {
			const csv = e.target.result;

			ajaxRequest(
				'lw_seo_redirect_import',
				{ csv: csv },
				function (data) {
					// Clear previous results.
					importResult.textContent = '';

					// Success notice.
					const successDiv     = document.createElement( 'div' );
					successDiv.className = 'notice notice-success';
					const successP       = document.createElement( 'p' );
					successP.textContent = data.message;
					successDiv.appendChild( successP );
					importResult.appendChild( successDiv );

					// Error notices if any.
					if (data.errors && data.errors.length > 0) {
						const errorDiv     = document.createElement( 'div' );
						errorDiv.className = 'notice notice-warning';
						const errorP       = document.createElement( 'p' );
						errorP.textContent = lwSeoRedirectsL10n.importErrors;
						errorDiv.appendChild( errorP );

						const ul = document.createElement( 'ul' );
						data.errors.forEach(
							function (err) {
								const li       = document.createElement( 'li' );
								li.textContent = err;
								ul.appendChild( li );
							}
						);
						errorDiv.appendChild( ul );
						importResult.appendChild( errorDiv );
					}

					importFile.value = '';

					// Reload to show imported redirects.
					if (data.imported > 0) {
						setTimeout(
							function () {
								location.reload();
							},
							2000
						);
					}
				}
			);
		};
		reader.onerror = function () {
			showMessage( 'Failed to read file.', 'error' );
		};
		reader.readAsText( file );
	}

	/**
	 * Initialize.
	 */
	function init() {
		// Save button.
		if (saveButton) {
			saveButton.addEventListener( 'click', handleSave );
		}

		// Cancel button.
		if (cancelButton) {
			cancelButton.addEventListener( 'click', resetForm );
		}

		// Export button.
		if (exportButton) {
			exportButton.addEventListener( 'click', handleExport );
		}

		// Import button.
		if (importButton) {
			importButton.addEventListener( 'click', handleImport );
		}

		// Table row handlers.
		if (table) {
			const rows = table.querySelectorAll( 'tbody tr' );
			rows.forEach( attachRowHandlers );
		}

		// Toggle destination field based on type.
		if (typeSelect) {
			typeSelect.addEventListener(
				'change',
				function () {
					const type         = parseInt( this.value, 10 );
					const noDestTypes  = [410, 451];
					destInput.disabled = noDestTypes.includes( type );
					if (destInput.disabled) {
						destInput.value       = '';
						destInput.placeholder = lwSeoRedirectsL10n.notRequired;
					} else {
						destInput.placeholder = '/new-page/ or https://example.com/page/';
					}
				}
			);
		}
	}

	// Run on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
