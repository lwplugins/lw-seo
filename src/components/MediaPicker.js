/**
 * WordPress dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Image URL field with the WordPress media library (stores the URL, like
 * the classic screens did). Needs wp_enqueue_media() on the page.
 *
 * @param {Object}   props
 * @param {string}   props.label    Accessible label (hidden; the row shows it).
 * @param {string}   props.value    Image URL.
 * @param {Function} props.onChange Receives the URL ('' to remove).
 */
export default function MediaPicker( { label, value, onChange } ) {
	const open = () => {
		const frame = window.wp?.media?.( {
			title: __( 'Select Image', 'lw-seo' ),
			multiple: false,
			library: { type: 'image' },
		} );
		if ( ! frame ) {
			return;
		}
		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			onChange( attachment.url );
		} );
		frame.open();
	};

	return (
		<div className="lw-admin-media">
			{ value && (
				<img src={ value } alt="" className="lw-admin-media__preview" />
			) }
			<div className="lw-admin-inline">
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ label }
					hideLabelFromVision
					type="url"
					placeholder="https://"
					value={ value }
					onChange={ onChange }
				/>
				{ window.wp?.media && (
					<Button
						__next40pxDefaultSize
						variant="secondary"
						onClick={ open }
					>
						{ value
							? __( 'Replace', 'lw-seo' )
							: __( 'Select Image', 'lw-seo' ) }
					</Button>
				) }
				{ value && (
					<Button
						__next40pxDefaultSize
						variant="tertiary"
						isDestructive
						onClick={ () => onChange( '' ) }
					>
						{ __( 'Remove', 'lw-seo' ) }
					</Button>
				) }
			</div>
		</div>
	);
}
