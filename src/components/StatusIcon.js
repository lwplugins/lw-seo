/**
 * Status icons: Font Awesome Pro duotone (circle-check, triangle-exclamation,
 * circle-xmark), inline. Filled shapes stay crisp at large sizes, unlike the
 * 1.5px stroke icons of `@wordpress/icons` 17. Both layers follow `color`
 * (secondary at 40%).
 */
const PATHS = {
	ok: [
		'M0 256a256 256 0 1 0 512 0 256 256 0 1 0 -512 0zm135.1 7.1c9.4-9.4 24.6-9.4 33.9 0L221.1 315.2 340.5 151c7.8-10.7 22.8-13.1 33.5-5.3s13.1 22.8 5.3 33.5L243.4 366.1c-4.1 5.7-10.5 9.3-17.5 9.8s-13.9-2-18.8-7l-72-72c-9.4-9.4-9.4-24.6 0-33.9z',
		'M340.5 151c7.8-10.7 22.8-13.1 33.5-5.3s13.1 22.8 5.3 33.5L243.4 366.1c-4.1 5.7-10.5 9.3-17.5 9.8s-13.9-2-18.8-7l-72-72c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0L221.1 315.2 340.5 151z',
	],
	warning: [
		'M4.8 421c-6.7 12.4-6.4 27.4 .8 39.5S25.9 480 40 480l432 0c14.1 0 27.1-7.4 34.4-19.5s7.5-27.1 .8-39.5L291.2 21C284.2 8.1 270.7 0 256 0s-28.2 8.1-35.2 21L4.8 421zM288 384a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zM224.6 193.7C223.3 175.5 237.7 160 256 160s32.7 15.5 31.4 33.7l-7.4 104C279 310.3 268.6 320 256 320s-23-9.7-23.9-22.3l-7.4-104z',
		'M256 416a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm0-256c18.3 0 32.7 15.5 31.4 33.7l-7.4 104C279 310.3 268.6 320 256 320s-23-9.7-23.9-22.3l-7.4-104C223.3 175.5 237.7 160 256 160z',
	],
	critical: [
		'M0 256a256 256 0 1 0 512 0 256 256 0 1 0 -512 0zm167-89c9.4-9.4 24.6-9.4 33.9 0l55 55 55-55c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-55 55 55 55c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-55-55-55 55c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l55-55-55-55c-9.4-9.4-9.4-24.6 0-33.9z',
		'M201 167c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l55 55-55 55c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l55-55 55 55c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-55-55 55-55c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-55 55-55-55z',
	],
};

/**
 * @param {Object} props
 * @param {string} props.status ok|warning|critical.
 * @param {number} props.size   Pixel size.
 */
export default function StatusIcon( { status, size = 32 } ) {
	const [ secondary, primary ] = PATHS[ status ] || PATHS.ok;

	return (
		<svg
			className="lw-admin-status-icon"
			width={ size }
			height={ size }
			viewBox="0 0 512 512"
			aria-hidden="true"
			focusable="false"
		>
			<path opacity=".4" fill="currentColor" d={ secondary } />
			<path fill="currentColor" d={ primary } />
		</svg>
	);
}
