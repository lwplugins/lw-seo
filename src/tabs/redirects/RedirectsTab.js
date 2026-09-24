/**
 * External dependencies
 */
import { DataTable, useTableState } from '@lwplugins/data-table';
import '@lwplugins/data-table/style.css';

/**
 * WordPress dependencies
 */
import {
	Button,
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { SwitchRow } from '../../components/Fields';
import LoadError from '../../components/LoadError';
import Section from '../../components/Section';
import StatusBadge from '../../components/StatusBadge';
import { api, errorMessage } from '../../data/api';
import { formatNumber } from '../../data/format';
import useRemote from '../../data/useRemote';
import ImportExport from './ImportExport';
import RedirectForm from './RedirectForm';
import { tableLabels } from './tableLabels';

/**
 * Redirects: on/off (option), the list (search, type filter, sort by hits),
 * add/edit modal, delete, CSV import/export. List changes save immediately.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function RedirectsTab( { store } ) {
	const load = useCallback( () => api.redirects(), [] );
	const list = useRemote( load );
	const [ editing, setEditing ] = useState( null ); // null | 'new' | item
	const [ deleting, setDeleting ] = useState( null );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );
	const items = list.data?.items || [];
	const types = list.data?.types || {};

	const columns = [
		{
			id: 'source',
			label: __( 'Source', 'lw-seo' ),
			sortable: true,
			defaultSortDirection: 'asc',
			render: ( r ) => (
				<span className="lw-admin-inline">
					<code className="lw-admin-code">{ r.source }</code>
					{ r.regex && (
						<StatusBadge status="warning">
							{ __( 'Regex', 'lw-seo' ) }
						</StatusBadge>
					) }
				</span>
			),
		},
		{
			id: 'destination',
			label: __( 'Destination', 'lw-seo' ),
			render: ( r ) =>
				r.destination ? (
					<code className="lw-admin-code">{ r.destination }</code>
				) : (
					<span className="lw-admin-muted">
						{ __( 'N/A', 'lw-seo' ) }
					</span>
				),
		},
		{
			id: 'type',
			label: __( 'Type', 'lw-seo' ),
			render: ( r ) => (
				<StatusBadge status={ r.type >= 400 ? 'critical' : 'ok' }>
					{ String( r.type ) }
				</StatusBadge>
			),
		},
		{
			id: 'hits',
			label: __( 'Hits', 'lw-seo' ),
			sortable: true,
			align: 'end',
			render: ( r ) => (
				<span className="lw-admin-stack">
					<span>{ formatNumber( r.hits ) }</span>
					{ r.last_accessed && (
						<span className="lw-admin-hint">
							{ r.last_accessed }
						</span>
					) }
				</span>
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
						onClick={ () => setEditing( r ) }
					>
						{ __( 'Edit', 'lw-seo' ) }
					</Button>
					<Button
						size="compact"
						variant="tertiary"
						isDestructive
						onClick={ () => setDeleting( r ) }
					>
						{ __( 'Delete', 'lw-seo' ) }
					</Button>
				</span>
			),
		},
	];
	const table = useTableState( items, {
		searchFields: [ 'source', 'destination' ],
		columns,
		perPage: 25,
	} );

	const remove = async () => {
		const item = deleting;
		setDeleting( null );
		try {
			await api.deleteRedirect( item.id );
			createSuccessNotice( __( 'Redirect deleted.', 'lw-seo' ), {
				type: 'snackbar',
			} );
			list.reload();
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
	};

	return (
		<>
			<Section
				title={ __( 'Redirects', 'lw-seo' ) }
				description={ __(
					'Create and manage URL redirects to prevent 404 errors and preserve SEO value.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'Process redirects', 'lw-seo' ) }
					store={ store }
					name="redirects_enabled"
					onText={ __( 'Redirect rules are active', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
			</Section>

			<Section
				title={ __( 'Existing Redirects', 'lw-seo' ) }
				badge={
					list.data ? (
						<StatusBadge status="idle">
							{ String( items.length ) }
						</StatusBadge>
					) : null
				}
				actions={
					<Button
						variant="primary"
						icon={ plus }
						onClick={ () => setEditing( 'new' ) }
					>
						{ __( 'Add Redirect', 'lw-seo' ) }
					</Button>
				}
			>
				{ list.error ? (
					<LoadError
						message={ list.error.message }
						onRetry={ list.reload }
					/>
				) : (
					<DataTable
						columns={ columns }
						table={ table }
						isLoading={ list.isLoading }
						caption={ __( 'Redirects', 'lw-seo' ) }
						filters={ [
							{
								field: 'type',
								label: __( 'Type', 'lw-seo' ),
								options: Object.keys( types ).map(
									( code ) => ( {
										value: Number( code ),
										label: code,
									} )
								),
							},
						] }
						labels={ {
							...tableLabels(),
							search: __(
								'Search source or destination',
								'lw-seo'
							),
						} }
						getRowId={ ( r ) => r.id }
					/>
				) }
			</Section>

			<ImportExport onImported={ list.reload } />

			{ editing && (
				<RedirectForm
					initial={ editing === 'new' ? null : editing }
					types={ types }
					onClose={ () => setEditing( null ) }
					onSaved={ () => {
						createSuccessNotice(
							editing === 'new'
								? __( 'Redirect added.', 'lw-seo' )
								: __( 'Redirect updated.', 'lw-seo' ),
							{ type: 'snackbar' }
						);
						setEditing( null );
						list.reload();
					} }
				/>
			) }
			<ConfirmDialog
				isOpen={ !! deleting }
				confirmButtonText={ __( 'Delete', 'lw-seo' ) }
				onConfirm={ remove }
				onCancel={ () => setDeleting( null ) }
			>
				{ __(
					'Are you sure you want to delete this redirect?',
					'lw-seo'
				) }{ ' ' }
				<code>{ deleting?.source }</code>
			</ConfirmDialog>
		</>
	);
}
