/**
 * External dependencies
 */
import { DataTable, DEFAULT_QUERY } from '@lwplugins/data-table';
import '@lwplugins/data-table/style.css';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import Section from '../../components/Section';
import { api, errorMessage } from '../../data/api';
import { formatDatetime, formatNumber } from '../../data/format';
import RedirectForm from '../redirects/RedirectForm';
import { tableLabels } from '../redirects/tableLabels';

const toTimestamp = ( utc ) =>
	Math.floor( Date.parse( String( utc ).replace( ' ', 'T' ) + 'Z' ) / 1000 );

/**
 * The logged 404 URLs: server-paged table, create a redirect from a row,
 * delete a row, clear the log (inline confirm).
 */
export default function NotFoundLog() {
	const [ query, setQuery ] = useState( {
		...DEFAULT_QUERY,
		sort: { field: 'last_seen', direction: 'desc' },
		perPage: 20,
	} );
	const [ result, setResult ] = useState( { items: [], total: 0 } );
	const [ isLoading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ reloadKey, setReloadKey ] = useState( 0 );
	const [ confirmClear, setConfirmClear ] = useState( false );
	const [ creating, setCreating ] = useState( null ); // { item, types }
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );
	const reload = useCallback( () => setReloadKey( ( k ) => k + 1 ), [] );

	useEffect( () => {
		const controller = new AbortController();
		setLoading( true );
		api.notFoundLog(
			{
				page: query.page,
				per_page: query.perPage,
				search: query.search,
				orderby: query.sort?.field,
				order: query.sort?.direction,
			},
			controller.signal
		)
			.then( ( data ) => {
				setResult( data );
				setError( null );
			} )
			.catch(
				( e ) =>
					e.name !== 'AbortError' && setError( errorMessage( e ) )
			)
			.finally(
				() => ! controller.signal.aborted && setLoading( false )
			);
		return () => controller.abort();
	}, [ query, reloadKey ] );

	const run = async ( action, message ) => {
		try {
			await action();
			if ( message ) {
				createSuccessNotice( message, { type: 'snackbar' } );
			}
			reload();
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
	};

	const startCreate = async ( item ) => {
		try {
			const data = await api.redirects();
			setCreating( { item, types: data.types || {} } );
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
	};

	const columns = [
		{
			id: 'url',
			label: __( 'URL', 'lw-seo' ),
			sortable: true,
			defaultSortDirection: 'asc',
			render: ( r ) => <code className="lw-admin-code">{ r.url }</code>,
		},
		{
			id: 'hits',
			label: __( 'Hits', 'lw-seo' ),
			sortable: true,
			align: 'end',
			render: ( r ) => formatNumber( r.hits ),
		},
		{
			id: 'last_seen',
			label: __( 'Last seen', 'lw-seo' ),
			sortable: true,
			render: ( r ) => formatDatetime( toTimestamp( r.last_seen ) ),
		},
		{
			id: 'referrer',
			label: __( 'Referrer', 'lw-seo' ),
			render: ( r ) =>
				r.referrer ? (
					<span className="lw-admin-hint">{ r.referrer }</span>
				) : (
					<span className="lw-admin-muted">—</span>
				),
		},
		{
			id: 'actions',
			label: __( 'Actions', 'lw-seo' ),
			render: ( r ) => (
				<span className="lw-admin-inline lw-admin-actions">
					<Button
						size="compact"
						variant="secondary"
						onClick={ () => startCreate( r ) }
					>
						{ __( 'Create redirect', 'lw-seo' ) }
					</Button>
					<Button
						size="compact"
						variant="tertiary"
						isDestructive
						onClick={ () =>
							run(
								() => api.deleteNotFound( r.id ),
								__( 'Log entry deleted.', 'lw-seo' )
							)
						}
					>
						{ __( 'Delete', 'lw-seo' ) }
					</Button>
				</span>
			),
		},
	];

	const clearAction = confirmClear ? (
		<span className="lw-admin-inline">
			<span>{ __( 'Delete every logged URL?', 'lw-seo' ) }</span>
			<Button
				size="compact"
				variant="primary"
				isDestructive
				onClick={ () => {
					setConfirmClear( false );
					run(
						() => api.clearNotFound(),
						__( '404 log cleared.', 'lw-seo' )
					);
				} }
			>
				{ __( 'Yes, clear log', 'lw-seo' ) }
			</Button>
			<Button
				size="compact"
				variant="tertiary"
				onClick={ () => setConfirmClear( false ) }
			>
				{ __( 'Cancel', 'lw-seo' ) }
			</Button>
		</span>
	) : (
		<Button
			variant="secondary"
			isDestructive
			disabled={ ! result.total }
			accessibleWhenDisabled
			onClick={ () => setConfirmClear( true ) }
		>
			{ __( 'Clear log', 'lw-seo' ) }
		</Button>
	);

	return (
		<>
			<Section
				title={ __( 'Logged 404 URLs', 'lw-seo' ) }
				description={ __(
					'Missing URLs visitors and bots requested. Create a redirect to send them somewhere useful.',
					'lw-seo'
				) }
				actions={ clearAction }
			>
				<DataTable
					columns={ columns }
					rows={ result.items }
					total={ result.total }
					totalPages={ Math.max(
						1,
						Math.ceil( result.total / query.perPage )
					) }
					query={ query }
					onQueryChange={ setQuery }
					isLoading={ isLoading }
					error={ error }
					errorAction={
						<Button variant="secondary" onClick={ reload }>
							{ __( 'Retry', 'lw-seo' ) }
						</Button>
					}
					caption={ __( 'Logged 404 URLs', 'lw-seo' ) }
					labels={ {
						...tableLabels(),
						search: __( 'Search URL', 'lw-seo' ),
						empty: __(
							'No logged URLs match this search.',
							'lw-seo'
						),
						emptyAll: __( 'No 404 URLs logged yet.', 'lw-seo' ),
					} }
					getRowId={ ( r ) => r.id }
				/>
			</Section>
			{ creating && (
				<RedirectForm
					initial={ null }
					source={ creating.item.url }
					types={ creating.types }
					onClose={ () => setCreating( null ) }
					onSaved={ () => {
						const { item } = creating;
						setCreating( null );
						run(
							() => api.deleteNotFound( item.id ),
							__( 'Redirect added.', 'lw-seo' )
						);
					} }
				/>
			) }
		</>
	);
}
