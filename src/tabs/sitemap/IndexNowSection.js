/**
 * WordPress dependencies
 */
import { Button, TextareaControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import CopyField from '../../components/CopyField';
import { SwitchRow } from '../../components/Fields';
import Section from '../../components/Section';
import SettingRow from '../../components/SettingRow';
import { api, errorMessage } from '../../data/api';
import { formatDatetime } from '../../data/format';

const MAX_URLS = 100;

function resultLabel( entry ) {
	const text = entry.message || '';
	return entry.code ? `${ entry.code } ${ text }` : text;
}

/**
 * IndexNow: switch with disclosure, key file, manual submit and the last
 * submissions. Manual actions need the switch saved first (the server
 * refuses while the feature is off).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function IndexNowSection( { store } ) {
	const { options, meta } = store.data;
	// Fresher values from our own requests win over the settings payload.
	const [ newKeyUrl, setKeyUrl ] = useState( null );
	const [ newLog, setLog ] = useState( null );
	const keyUrl = newKeyUrl ?? meta.indexnow?.key_url ?? '';
	const log = newLog ?? meta.indexnow?.log ?? [];
	const [ urls, setUrls ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	// The server refuses manual actions until the switch is saved.
	const canAct = !! options.indexnow_enabled && ! store.hasEdits;

	const list = urls
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.filter( Boolean );

	const run = async ( action, onDone ) => {
		setBusy( true );
		try {
			onDone( await action() );
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
		setBusy( false );
	};

	const submit = () =>
		run(
			() => api.indexNowSubmit( list ),
			( data ) => {
				setLog( data.log || [] );
				if ( data.ok ) {
					setUrls( '' );
					createSuccessNotice(
						sprintf(
							/* translators: %d: number of URLs. */
							__( 'Submitted %d URL(s) to IndexNow.', 'lw-seo' ),
							list.length
						),
						{ type: 'snackbar' }
					);
				} else {
					createErrorNotice( data.message, { type: 'snackbar' } );
				}
			}
		);

	const regenerate = () =>
		run( api.indexNowRegenerateKey, ( data ) => {
			setKeyUrl( data.key_url );
			createSuccessNotice( __( 'New key generated.', 'lw-seo' ), {
				type: 'snackbar',
			} );
		} );

	return (
		<Section
			title={ __( 'IndexNow', 'lw-seo' ) }
			description={ __(
				'Tell search engines about new, changed and deleted content right away instead of waiting for the next crawl.',
				'lw-seo'
			) }
		>
			<SwitchRow
				title={ __( 'IndexNow', 'lw-seo' ) }
				store={ store }
				name="indexnow_enabled"
				onText={ __( 'Enabled', 'lw-seo' ) }
				offText={ __( 'Off', 'lw-seo' ) }
				help={ __(
					'When on, URLs of public content are sent to api.indexnow.org when they are published, updated or deleted. Bing, Yandex, Seznam, Naver and others use it; Google does not.',
					'lw-seo'
				) }
			/>
			{ options.indexnow_enabled && keyUrl && (
				<SettingRow
					title={ __( 'Key file', 'lw-seo' ) }
					help={ __(
						'Search engines fetch this file to confirm the site is yours.',
						'lw-seo'
					) }
				>
					<CopyField text={ keyUrl } />
					<Button
						variant="tertiary"
						size="compact"
						disabled={ ! canAct || busy }
						onClick={ regenerate }
					>
						{ __( 'Generate a new key', 'lw-seo' ) }
					</Button>
				</SettingRow>
			) }
			{ options.indexnow_enabled && (
				<SettingRow
					stacked
					title={ __( 'Submit URLs now', 'lw-seo' ) }
					help={ sprintf(
						/* translators: %d: maximum number of URLs. */
						__(
							'One address per line, from this site only, up to %d. Save your changes first.',
							'lw-seo'
						),
						MAX_URLS
					) }
				>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'URLs', 'lw-seo' ) }
						hideLabelFromVision
						rows={ 4 }
						className="lw-admin-mono"
						value={ urls }
						onChange={ setUrls }
					/>
					<Button
						variant="secondary"
						isBusy={ busy }
						disabled={ ! canAct || busy || list.length === 0 }
						onClick={ submit }
					>
						{ __( 'Submit', 'lw-seo' ) }
					</Button>
				</SettingRow>
			) }
			{ log.length > 0 && (
				<SettingRow
					stacked
					title={ __( 'Recent submissions', 'lw-seo' ) }
				>
					<table className="widefat striped">
						<thead>
							<tr>
								<th>{ __( 'Time', 'lw-seo' ) }</th>
								<th>{ __( 'URLs', 'lw-seo' ) }</th>
								<th>{ __( 'Result', 'lw-seo' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ log.map( ( entry, i ) => (
								<tr key={ `${ entry.time }-${ i }` }>
									<td>{ formatDatetime( entry.time ) }</td>
									<td>{ entry.count }</td>
									<td>{ resultLabel( entry ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</SettingRow>
			) }
		</Section>
	);
}
