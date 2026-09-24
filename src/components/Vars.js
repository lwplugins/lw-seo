/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * "Variables: %%a%%, %%b%%" help line for title templates.
 *
 * @param {Object}   props
 * @param {string[]} props.names Variable names without %%.
 */
export default function Vars( { names } ) {
	return (
		<>
			{ __( 'Variables:', 'lw-seo' ) }{ ' ' }
			{ names.map( ( name, index ) => (
				<span key={ name }>
					{ index > 0 && ', ' }
					<code>%%{ name }%%</code>
				</span>
			) ) }
		</>
	);
}
