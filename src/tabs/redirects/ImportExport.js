/**
 * WordPress dependencies
 */
import { Button, FormFileUpload } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { download, upload } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Section from '../../components/Section';
import SettingRow from '../../components/SettingRow';
import { api, errorMessage } from '../../data/api';

/**
 * CSV export (browser download) and import (read locally, sent as text).
 *
 * @param {Object}   props
 * @param {Function} props.onImported Reload the list.
 */
export default function ImportExport( { onImported } ) {
	const [ busy, setBusy ] = useState( '' );
	const [ result, setResult ] = useState( null );

	const exportCsv = async () => {
		setBusy( 'export' );
		try {
			const { csv, filename } = await api.exportRedirects();
			const url = URL.createObjectURL(
				new Blob( [ csv ], { type: 'text/csv' } )
			);
			const link = document.createElement( 'a' );
			link.href = url;
			link.download = filename;
			link.click();
			URL.revokeObjectURL( url );
		} catch ( e ) {
			setResult( [ 'error', errorMessage( e ), [] ] );
		}
		setBusy( '' );
	};

	const importCsv = async ( file ) => {
		if ( ! file ) {
			return;
		}
		setBusy( 'import' );
		setResult( null );
		try {
			const r = await api.importRedirects( await file.text() );
			setResult( [
				r.errors?.length ? 'error' : 'ok',
				sprintf(
					/* translators: 1: imported redirects, 2: skipped rows. */
					__( 'Imported %1$d redirects, skipped %2$d.', 'lw-seo' ),
					r.imported,
					r.skipped
				),
				r.errors || [],
			] );
			if ( r.imported > 0 ) {
				onImported();
			}
		} catch ( e ) {
			setResult( [ 'error', errorMessage( e ), [] ] );
		}
		setBusy( '' );
	};

	return (
		<Section title={ __( 'Import / Export', 'lw-seo' ) }>
			<SettingRow
				title={ __( 'Export', 'lw-seo' ) }
				help={ __( 'Export all redirects as a CSV file.', 'lw-seo' ) }
			>
				<div className="lw-admin-inline">
					<Button
						__next40pxDefaultSize
						variant="secondary"
						icon={ download }
						isBusy={ busy === 'export' }
						onClick={ exportCsv }
					>
						{ __( 'Download CSV', 'lw-seo' ) }
					</Button>
				</div>
			</SettingRow>
			<SettingRow
				title={ __( 'Import', 'lw-seo' ) }
				help={ __(
					'Import redirects from a CSV file. Format: source,destination,type,regex',
					'lw-seo'
				) }
			>
				<div className="lw-admin-inline">
					<FormFileUpload
						__next40pxDefaultSize
						accept=".csv,text/csv"
						icon={ upload }
						variant="secondary"
						onChange={ ( event ) =>
							importCsv( event.currentTarget.files?.[ 0 ] )
						}
						render={ ( { openFileDialog } ) => (
							<Button
								__next40pxDefaultSize
								variant="secondary"
								icon={ upload }
								isBusy={ busy === 'import' }
								onClick={ openFileDialog }
							>
								{ __( 'Import CSV', 'lw-seo' ) }
							</Button>
						) }
					/>
				</div>
				{ result && (
					<div
						className={ `lw-admin-testresult is-${ result[ 0 ] }` }
						role={ result[ 0 ] === 'error' ? 'alert' : 'status' }
					>
						<p>{ result[ 1 ] }</p>
						{ result[ 2 ].length > 0 && (
							<ul>
								{ result[ 2 ].map( ( line ) => (
									<li key={ line }>{ line }</li>
								) ) }
							</ul>
						) }
					</div>
				) }
			</SettingRow>
		</Section>
	);
}
