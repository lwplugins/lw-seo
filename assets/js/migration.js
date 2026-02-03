/**
 * LW SEO Migration JavaScript
 *
 * @package LightweightPlugins\SEO
 */

(function () {
	'use strict';

	// Elements.
	const detectButton  = document.getElementById( 'lw-migration-detect' );
	const detectSpinner = document.getElementById( 'lw-migration-detect-spinner' );
	const resultsArea   = document.getElementById( 'lw-migration-results' );
	const detectResults = document.getElementById( 'lw-migration-detect-results' );
	const actionsArea   = document.getElementById( 'lw-migration-actions' );
	const previewButton = document.getElementById( 'lw-migration-preview' );
	const runButton     = document.getElementById( 'lw-migration-run' );
	const runSpinner    = document.getElementById( 'lw-migration-run-spinner' );
	const runResults    = document.getElementById( 'lw-migration-run-results' );

	if ( ! detectButton ) {
		return;
	}

	/**
	 * Remove all child nodes from an element.
	 *
	 * @param {HTMLElement} element The element to clear.
	 */
	function clearElement( element ) {
		while ( element.firstChild ) {
			element.removeChild( element.firstChild );
		}
	}

	/**
	 * Make AJAX request.
	 *
	 * @param {string}   action   AJAX action name.
	 * @param {Object}   data     Additional POST data.
	 * @param {Function} callback Success callback.
	 */
	function ajaxRequest( action, data, callback ) {
		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( 'nonce', lwSeoMigrationL10n.nonce );

		for ( const key in data ) {
			if ( data.hasOwnProperty( key ) ) {
				formData.append( key, data[ key ] );
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
			function ( response ) {
				return response.json();
			}
		)
		.then(
			function ( result ) {
				if ( result.success ) {
						callback( result.data );
				} else {
					showNotice( result.data.message || 'An error occurred.', 'error' );
				}
			}
		)
		.catch(
			function () {
				showNotice( 'Network error occurred.', 'error' );
			}
		);
	}

	/**
	 * Show a notice in a container.
	 *
	 * @param {string}      text      Notice text.
	 * @param {string}      type      Notice type: 'success', 'error', 'info'.
	 * @param {HTMLElement} container Target container (defaults to runResults).
	 */
	function showNotice( text, type, container ) {
		const target  = container || runResults;
		const div     = document.createElement( 'div' );
		div.className = 'notice notice-' + type;
		const p       = document.createElement( 'p' );
		p.textContent = text;
		div.appendChild( p );
		clearElement( target );
		target.style.display = 'block';
		target.appendChild( div );
	}

	/**
	 * Set spinner visibility.
	 *
	 * @param {HTMLElement} spinner Spinner element.
	 * @param {boolean}     active  Whether spinner is active.
	 */
	function setSpinner( spinner, active ) {
		if ( active ) {
			spinner.classList.add( 'is-active' );
		} else {
			spinner.classList.remove( 'is-active' );
		}
	}

	/**
	 * Create a table row with th and td.
	 *
	 * @param {string} label Row label.
	 * @param {string} value Row value.
	 * @return {HTMLElement} The tr element.
	 */
	function createTableRow( label, value ) {
		const tr       = document.createElement( 'tr' );
		const th       = document.createElement( 'th' );
		const td       = document.createElement( 'td' );
		th.textContent = label;
		td.textContent = value;
		tr.appendChild( th );
		tr.appendChild( td );
		return tr;
	}

	/**
	 * Handle detect button click.
	 */
	function handleDetect() {
		setSpinner( detectSpinner, true );
		detectButton.disabled = true;

		ajaxRequest(
			'lw_seo_migration_detect',
			{},
			function ( data ) {
				setSpinner( detectSpinner, false );
				detectButton.disabled     = false;
				resultsArea.style.display = 'block';

				if ( ! data.found ) {
					showNotice( lwSeoMigrationL10n.noData, 'info', detectResults );
					actionsArea.style.display = 'none';
					return;
				}

				renderDetectResults( data );
				actionsArea.style.display = 'block';
			}
		);
	}

	/**
	 * Render detection results.
	 *
	 * @param {Object} data Detection data.
	 */
	function renderDetectResults( data ) {
		const table     = document.createElement( 'table' );
		table.className = 'widefat striped lw-seo-migration-detect-table';

		const tbody = document.createElement( 'tbody' );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.options, data.has_options ? lwSeoMigrationL10n.found : lwSeoMigrationL10n.notFound ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.posts, data.post_count.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.terms, data.term_count.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.users, data.user_count.toString() ) );

		table.appendChild( tbody );
		clearElement( detectResults );
		detectResults.appendChild( table );
	}

	/**
	 * Render migration results.
	 *
	 * @param {Object}  data   Migration result data.
	 * @param {boolean} dryRun Whether this was a dry run.
	 */
	function renderRunResults( data, dryRun ) {
		const heading       = document.createElement( 'h4' );
		heading.textContent = dryRun ? lwSeoMigrationL10n.previewTitle : lwSeoMigrationL10n.resultTitle;

		const table     = document.createElement( 'table' );
		table.className = 'widefat striped lw-seo-migration-results-table';

		const tbody = document.createElement( 'tbody' );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.optionsMigrated, data.options_migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.postsMigrated, data.posts_migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.postsSkipped, data.posts_skipped.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.termsMigrated, data.terms_migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.termsSkipped, data.terms_skipped.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.usersMigrated, data.users_migrated.toString() ) );

		table.appendChild( tbody );

		clearElement( runResults );
		runResults.style.display = 'block';
		runResults.appendChild( heading );

		if ( dryRun ) {
			const notice       = document.createElement( 'p' );
			notice.className   = 'description';
			notice.textContent = lwSeoMigrationL10n.dryRunNotice;
			runResults.appendChild( notice );
		}

		runResults.appendChild( table );
	}

	/**
	 * Handle preview (dry run) button click.
	 */
	function handlePreview() {
		setSpinner( runSpinner, true );
		previewButton.disabled = true;
		runButton.disabled     = true;

		ajaxRequest(
			'lw_seo_migration_run',
			{ dry_run: 'true' },
			function ( data ) {
				setSpinner( runSpinner, false );
				previewButton.disabled = false;
				runButton.disabled     = false;
				renderRunResults( data, true );
			}
		);
	}

	/**
	 * Handle run migration button click.
	 */
	function handleRun() {
		if ( ! confirm( lwSeoMigrationL10n.confirmRun ) ) {
			return;
		}

		setSpinner( runSpinner, true );
		previewButton.disabled = true;
		runButton.disabled     = true;

		ajaxRequest(
			'lw_seo_migration_run',
			{ dry_run: 'false' },
			function ( data ) {
				setSpinner( runSpinner, false );
				previewButton.disabled = false;
				runButton.disabled     = false;
				renderRunResults( data, false );
			}
		);
	}

	/**
	 * Initialize event listeners.
	 */
	function init() {
		detectButton.addEventListener( 'click', handleDetect );

		if ( previewButton ) {
			previewButton.addEventListener( 'click', handlePreview );
		}

		if ( runButton ) {
			runButton.addEventListener( 'click', handleRun );
		}
	}

	// Run on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
