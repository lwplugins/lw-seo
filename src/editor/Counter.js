/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * "42 / 60" character counter: warning above 90 %, error over the limit.
 *
 * @param {Object} props
 * @param {string} props.value Text.
 * @param {number} props.max   Recommended maximum.
 */
export default function Counter( { value = '', max } ) {
	const length = value.length;
	let state = '';
	if ( length > max ) {
		state = 'is-error';
	} else if ( length > max * 0.9 ) {
		state = 'is-warning';
	}
	return (
		<span className={ `lw-seo-counter ${ state }` } aria-live="polite">
			{ sprintf(
				/* translators: 1: characters used, 2: recommended maximum. */
				__( '%1$d / %2$d characters', 'lw-seo' ),
				length,
				max
			) }
		</span>
	);
}
