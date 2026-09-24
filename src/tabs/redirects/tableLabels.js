/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Translated UI strings for `@lwplugins/data-table` (it has no text domain).
 *
 * @return {Object} Labels.
 */
export function tableLabels() {
	return {
		search: __( 'Search', 'lw-seo' ),
		filter: __( 'Filter', 'lw-seo' ),
		clear: __( 'Clear', 'lw-seo' ),
		clearAll: __( 'Clear all filters', 'lw-seo' ),
		all: __( 'All', 'lw-seo' ),
		empty: __( 'No redirects match these filters.', 'lw-seo' ),
		emptyAll: __( 'No redirects configured yet.', 'lw-seo' ),
		loading: __( 'Loading…', 'lw-seo' ),
		previous: __( 'Previous page', 'lw-seo' ),
		next: __( 'Next page', 'lw-seo' ),
		perPage: __( 'Rows per page', 'lw-seo' ),
		selectAll: __( 'Select all redirects on this page', 'lw-seo' ),
		clearSelection: __( 'Clear selection', 'lw-seo' ),
		bulkActions: __( 'Bulk actions', 'lw-seo' ),
		entries: ( n ) =>
			sprintf(
				/* translators: %d: number of rows. */ _n(
					'%d entry',
					'%d entries',
					n,
					'lw-seo'
				),
				n
			),
		results: ( n ) =>
			sprintf(
				/* translators: %d: number of results. */ _n(
					'%d result',
					'%d results',
					n,
					'lw-seo'
				),
				n
			),
		page: ( p, t ) =>
			sprintf(
				/* translators: 1: current page, 2: total pages. */ __(
					'Page %1$d of %2$d',
					'lw-seo'
				),
				p,
				t
			),
		selectRow: ( label ) =>
			sprintf(
				/* translators: %s: redirect source. */ __(
					'Select: %s',
					'lw-seo'
				),
				label
			),
		selected: ( n, onPage ) =>
			n === onPage
				? sprintf(
						/* translators: %d: number of selected redirects. */
						_n( '%d selected', '%d selected', n, 'lw-seo' ),
						n
					)
				: sprintf(
						/* translators: 1: selected redirects, 2: of those, on this page. */
						__( '%1$d selected, %2$d on this page', 'lw-seo' ),
						n,
						onPage
					),
		eligible: ( e, n ) =>
			sprintf(
				/* translators: 1: redirects the action applies to, 2: selected redirects on this page. */ __(
					'applies to %1$d of %2$d',
					'lw-seo'
				),
				e,
				n
			),
	};
}
