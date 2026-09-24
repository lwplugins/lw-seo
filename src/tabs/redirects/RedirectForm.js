/**
 * WordPress dependencies
 */
import {
	Button,
	CheckboxControl,
	Modal,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { api, errorMessage } from '../../data/api';

const NO_DESTINATION = [ 410, 451 ];

/**
 * Add / edit a redirect in a modal. Saves straight to the API.
 *
 * @param {Object}   props
 * @param {Object}   props.initial Redirect being edited, or null for a new one.
 * @param {Object}   props.types   { code: label }.
 * @param {Function} props.onSaved Receives the saved item.
 * @param {Function} props.onClose Close.
 */
export default function RedirectForm( { initial, types, onSaved, onClose } ) {
	const [ form, setForm ] = useState(
		initial || { source: '', destination: '', type: 301, regex: false }
	);
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const needsDestination = ! NO_DESTINATION.includes( Number( form.type ) );
	const set = ( values ) => setForm( ( prev ) => ( { ...prev, ...values } ) );

	const submit = async ( event ) => {
		event.preventDefault();
		setBusy( true );
		setError( '' );
		const data = {
			source: form.source,
			destination: needsDestination ? form.destination : '',
			type: Number( form.type ),
			regex: form.regex,
		};
		try {
			const item = initial
				? await api.updateRedirect( initial.id, data )
				: await api.addRedirect( data );
			onSaved( item );
		} catch ( e ) {
			setError( errorMessage( e ) );
			setBusy( false );
		}
	};

	return (
		<Modal
			title={
				initial
					? __( 'Edit redirect', 'lw-seo' )
					: __( 'Add redirect', 'lw-seo' )
			}
			onRequestClose={ onClose }
			size="medium"
		>
			<form onSubmit={ submit } className="lw-admin-form">
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Source URL', 'lw-seo' ) }
					help={ __(
						'The old URL path that should redirect (e.g., /old-page/).',
						'lw-seo'
					) }
					className="lw-admin-mono"
					placeholder="/old-page/"
					value={ form.source }
					onChange={ ( source ) => set( { source } ) }
					required
				/>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Redirect Type', 'lw-seo' ) }
					value={ String( form.type ) }
					options={ Object.entries( types ).map(
						( [ code, label ] ) => ( {
							value: code,
							label: `${ code } - ${ label }`,
						} )
					) }
					onChange={ ( type ) => set( { type: Number( type ) } ) }
				/>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Destination URL', 'lw-seo' ) }
					help={
						needsDestination
							? __( 'The new URL to redirect to.', 'lw-seo' )
							: __( 'Not required for this type.', 'lw-seo' )
					}
					className="lw-admin-mono"
					placeholder="/new-page/ or https://example.com/page/"
					disabled={ ! needsDestination }
					value={ needsDestination ? form.destination : '' }
					onChange={ ( destination ) => set( { destination } ) }
				/>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Source is a regular expression', 'lw-seo' ) }
					help={ __(
						'Advanced: use patterns like ^/category/(.*)$ with $1 in the destination.',
						'lw-seo'
					) }
					checked={ form.regex }
					onChange={ ( regex ) => set( { regex } ) }
				/>
				{ error && (
					<p className="lw-admin-testresult is-error" role="alert">
						{ error }
					</p>
				) }
				<div className="lw-admin-inline lw-admin-form__actions">
					<Button variant="tertiary" onClick={ onClose }>
						{ __( 'Cancel', 'lw-seo' ) }
					</Button>
					<Button
						__next40pxDefaultSize
						variant="primary"
						type="submit"
						isBusy={ busy }
						disabled={ busy || ! form.source.trim() }
						accessibleWhenDisabled
					>
						{ initial
							? __( 'Update Redirect', 'lw-seo' )
							: __( 'Add Redirect', 'lw-seo' ) }
					</Button>
				</div>
			</form>
		</Modal>
	);
}
