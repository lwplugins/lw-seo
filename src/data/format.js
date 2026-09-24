/**
 * WordPress dependencies
 */
import { dateI18n } from '@wordpress/date';
import { __, sprintf } from '@wordpress/i18n';

const number = new Intl.NumberFormat();

export const formatNumber = ( value ) => number.format( Number( value ) || 0 );

/**
 * "today 14:02" for today (site timezone), else "Sep 24, 14:02"; "—" for 0.
 *
 * @param {number} ts Unix seconds.
 * @return {string} Label.
 */
export function formatDatetime( ts ) {
	if ( ! ts ) {
		return '—';
	}
	const ms = ts * 1000;
	if ( dateI18n( 'Y-m-d', ms ) === dateI18n( 'Y-m-d', Date.now() ) ) {
		return sprintf(
			/* translators: %s: time of day, e.g. 14:02. */ __(
				'today %s',
				'lw-seo'
			),
			dateI18n( 'H:i', ms )
		);
	}
	return dateI18n( 'M j, H:i', ms );
}

/**
 * "38 s" / "2 m 11 s" / "1 h 4 m"; "—" when unknown or negative.
 *
 * @param {number|null} seconds Duration.
 * @return {string} Label.
 */
export function formatDuration( seconds ) {
	if ( seconds === null || seconds === undefined || seconds < 0 ) {
		return '—';
	}
	const s = Math.round( seconds );
	if ( s < 60 ) {
		return sprintf(
			/* translators: %d: seconds. */ __( '%d s', 'lw-seo' ),
			s
		);
	}
	if ( s < 3600 ) {
		return sprintf(
			/* translators: 1: minutes, 2: seconds. */
			__( '%1$d m %2$d s', 'lw-seo' ),
			Math.floor( s / 60 ),
			s % 60
		);
	}
	return sprintf(
		/* translators: 1: hours, 2: minutes. */
		__( '%1$d h %2$d m', 'lw-seo' ),
		Math.floor( s / 3600 ),
		Math.floor( ( s % 3600 ) / 60 )
	);
}

export const formatClock = ( seconds ) => {
	const s = Math.max( 0, Math.round( seconds || 0 ) );
	return `${ Math.floor( s / 60 ) }:${ String( s % 60 ).padStart( 2, '0' ) }`;
};

export function formatBytes( bytes ) {
	const units = [ 'B', 'KB', 'MB', 'GB' ];
	let value = Math.max( 0, bytes || 0 );
	let unit = 0;
	while ( value >= 1024 && unit < units.length - 1 ) {
		value /= 1024;
		unit++;
	}
	return `${ Number.isInteger( value ) ? value : value.toFixed( 1 ) } ${ units[ unit ] }`;
}
