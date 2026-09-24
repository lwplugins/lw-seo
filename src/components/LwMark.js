/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

/**
 * LW SEO mark (the plugin's duotone icon); both layers follow `color`.
 */
export default function LwMark() {
	return (
		<SVG
			className="lw-admin-mark"
			viewBox="0 0 640 640"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
			focusable="false"
		>
			<Path
				opacity=".4"
				fill="currentColor"
				d="M176 320C176 399.5 240.5 464 320 464C399.5 464 464 399.5 464 320C464 240.5 399.5 176 320 176C240.5 176 176 240.5 176 320zM400 320C400 364.2 364.2 400 320 400C275.8 400 240 364.2 240 320C240 275.8 275.8 240 320 240C364.2 240 400 275.8 400 320z"
			/>
			<Path
				fill="currentColor"
				d="M512 320C512 214 426 128 320 128C214 128 128 214 128 320C128 426 214 512 320 512C426 512 512 426 512 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320zM320 288C337.7 288 352 302.3 352 320C352 337.7 337.7 352 320 352C302.3 352 288 337.7 288 320C288 302.3 302.3 288 320 288z"
			/>
		</SVG>
	);
}
