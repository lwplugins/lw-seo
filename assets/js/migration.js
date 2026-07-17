/**
 * LW SEO Migration JavaScript
 *
 * Provider-aware: each `.lw-migration-provider` block (RankMath, Yoast) binds
 * its own detect/preview/run buttons and sends its `provider` slug to AJAX.
 *
 * @package LightweightPlugins\SEO
 */

(function () {
	'use strict';

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
	 * Make an AJAX request.
	 *
	 * @param {string}      action    AJAX action name.
	 * @param {Object}      data      Additional POST data.
	 * @param {Function}    callback  Success callback.
	 * @param {HTMLElement} errorNode Container for error notices.
	 */
	function ajaxRequest( action, data, callback, errorNode ) {
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
					showNotice( ( result.data && result.data.message ) || 'An error occurred.', 'error', errorNode );
				}
			}
		)
		.catch(
			function () {
				showNotice( 'Network error occurred.', 'error', errorNode );
			}
		);
	}

	/**
	 * Show a notice in a container.
	 *
	 * @param {string}      text      Notice text.
	 * @param {string}      type      Notice type: 'success', 'error', 'info'.
	 * @param {HTMLElement} container Target container.
	 */
	function showNotice( text, type, container ) {
		const div     = document.createElement( 'div' );
		div.className = 'notice notice-' + type;
		const p       = document.createElement( 'p' );
		p.textContent = text;
		div.appendChild( p );
		clearElement( container );
		container.style.display = 'block';
		container.appendChild( div );
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
	 * Render a list of warnings as notices.
	 *
	 * @param {Array}       warnings  Warning entries: {code, severity, message}.
	 * @param {HTMLElement} container Target container.
	 */
	function renderWarnings( warnings, container ) {
		if ( ! Array.isArray( warnings ) || warnings.length === 0 ) {
			return;
		}
		const heading       = document.createElement( 'h4' );
		heading.textContent = lwSeoMigrationL10n.warnings;
		container.appendChild( heading );

		warnings.forEach(
			function ( warning ) {
				const severity = warning.severity === 'error' ? 'error' : ( warning.severity === 'warning' ? 'warning' : 'info' );
				const div      = document.createElement( 'div' );
				div.className  = 'notice notice-' + severity + ' inline';
				const p        = document.createElement( 'p' );
				p.textContent  = warning.message;
				div.appendChild( p );
				container.appendChild( div );
			}
		);
	}

	/**
	 * Render detection results.
	 *
	 * @param {Object}      data      Detection data.
	 * @param {HTMLElement} container Detect results container.
	 */
	function renderDetectResults( data, container ) {
		const table     = document.createElement( 'table' );
		table.className = 'widefat striped lw-seo-migration-detect-table';

		const tbody = document.createElement( 'tbody' );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.options, data.has_options ? lwSeoMigrationL10n.found : lwSeoMigrationL10n.notFound ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.posts, data.post_count.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.terms, data.term_count.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.users, ( data.user_count || 0 ).toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.redirects, ( data.redirects_count || 0 ).toString() ) );

		table.appendChild( tbody );
		clearElement( container );
		container.appendChild( table );
		renderWarnings( data.warnings, container );
	}

	/**
	 * Render migration results.
	 *
	 * @param {Object}      data      Migration result data.
	 * @param {boolean}     dryRun    Whether this was a dry run.
	 * @param {HTMLElement} container Run results container.
	 */
	function renderRunResults( data, dryRun, container ) {
		const heading       = document.createElement( 'h4' );
		heading.textContent = dryRun ? lwSeoMigrationL10n.previewTitle : lwSeoMigrationL10n.resultTitle;

		const table     = document.createElement( 'table' );
		table.className = 'widefat striped lw-seo-migration-results-table';

		const posts     = data.posts || { migrated: 0, skipped_already_present: 0, skipped_no_data: 0 };
		const terms     = data.terms || { migrated: 0, skipped_already_present: 0, skipped_no_data: 0 };
		const users     = data.users || { migrated: 0 };
		const primary   = data.primary_terms || { migrated: 0 };
		const redirects = data.redirects || { migrated: 0, skipped: 0 };

		const tbody = document.createElement( 'tbody' );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.optionsMigrated, data.options_migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.postsMigrated, posts.migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.postsAlreadyFull, posts.skipped_already_present.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.postsNoData, posts.skipped_no_data.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.termsMigrated, terms.migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.termsAlreadyFull, terms.skipped_already_present.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.termsNoData, terms.skipped_no_data.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.usersMigrated, users.migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.primaryTermsMigrated, primary.migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.redirectsMigrated, redirects.migrated.toString() ) );
		tbody.appendChild( createTableRow( lwSeoMigrationL10n.redirectsSkipped, redirects.skipped.toString() ) );

		table.appendChild( tbody );

		clearElement( container );
		container.style.display = 'block';
		container.appendChild( heading );

		if ( dryRun ) {
			const notice       = document.createElement( 'p' );
			notice.className   = 'description';
			notice.textContent = lwSeoMigrationL10n.dryRunNotice;
			container.appendChild( notice );
		}

		container.appendChild( table );
		renderWarnings( data.warnings, container );
	}

	/**
	 * Wire up a single provider block.
	 *
	 * @param {HTMLElement} block The .lw-migration-provider element.
	 */
	function initBlock( block ) {
		const provider      = block.dataset.provider;
		const detectButton  = block.querySelector( '.lw-migration-detect' );
		const detectSpinner = block.querySelector( '.lw-migration-detect-spinner' );
		const resultsArea   = block.querySelector( '.lw-migration-results' );
		const detectResults = block.querySelector( '.lw-migration-detect-results' );
		const actionsArea   = block.querySelector( '.lw-migration-actions' );
		const previewButton = block.querySelector( '.lw-migration-preview' );
		const runButton     = block.querySelector( '.lw-migration-run' );
		const runSpinner    = block.querySelector( '.lw-migration-run-spinner' );
		const runResults    = block.querySelector( '.lw-migration-run-results' );

		if ( ! detectButton ) {
			return;
		}

		function handleDetect() {
			setSpinner( detectSpinner, true );
			detectButton.disabled = true;

			ajaxRequest(
				'lw_seo_migration_detect',
				{ provider: provider },
				function ( data ) {
					setSpinner( detectSpinner, false );
					detectButton.disabled     = false;
					resultsArea.style.display = 'block';

					if ( ! data.found ) {
						showNotice( lwSeoMigrationL10n.noData, 'info', detectResults );
						actionsArea.style.display = 'none';
						return;
					}

					renderDetectResults( data, detectResults );
					actionsArea.style.display = 'block';
				},
				detectResults
			);
		}

		function handleRun( dryRun ) {
			setSpinner( runSpinner, true );
			previewButton.disabled = true;
			runButton.disabled     = true;

			ajaxRequest(
				'lw_seo_migration_run',
				{ provider: provider, dry_run: dryRun ? 'true' : 'false' },
				function ( data ) {
					setSpinner( runSpinner, false );
					previewButton.disabled = false;
					runButton.disabled     = false;
					renderRunResults( data, dryRun, runResults );
				},
				runResults
			);
		}

		detectButton.addEventListener( 'click', handleDetect );

		if ( previewButton ) {
			previewButton.addEventListener(
				'click',
				function () {
					handleRun( true );
				}
			);
		}

		if ( runButton ) {
			runButton.addEventListener(
				'click',
				function () {
					if ( confirm( lwSeoMigrationL10n.confirmRun ) ) {
						handleRun( false );
					}
				}
			);
		}
	}

	/**
	 * Initialize all provider blocks.
	 */
	function init() {
		const blocks = document.querySelectorAll( '.lw-migration-provider' );
		blocks.forEach( initBlock );
	}

	// Run on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
