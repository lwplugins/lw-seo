/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const SIGNAL_OPTIONS = [
	{ value: '', label: __( 'Default (use global setting)', 'lw-seo' ) },
	{ value: 'yes', label: __( 'Yes', 'lw-seo' ) },
	{ value: 'no', label: __( 'No', 'lw-seo' ) },
];

/**
 * AI content-signal override (default / yes / no).
 *
 * @param {Object} props SelectControl props (label, value, onChange, name).
 */
export default function SignalSelect( props ) {
	return (
		<SelectControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			options={ SIGNAL_OPTIONS }
			{ ...props }
		/>
	);
}
