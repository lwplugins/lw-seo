/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Small expandable group inside the SEO panel. The body stays mounted (only
 * hidden) while closed: on the term screen its inputs are real form fields,
 * and the classic save handler deletes every field missing from the POST.
 *
 * @param {Object}  props
 * @param {string}  props.title       Group title.
 * @param {boolean} props.initialOpen Open on load.
 * @param {Element} props.children    Fields.
 */
export default function Collapsible( {
	title,
	initialOpen = false,
	children,
} ) {
	const [ open, setOpen ] = useState( initialOpen );
	const id = useInstanceId( Collapsible, 'lw-seo-group' );
	return (
		<div className="lw-seo-group">
			<Button
				className="lw-seo-group__toggle"
				icon={ open ? chevronUp : chevronDown }
				iconPosition="right"
				aria-expanded={ open }
				aria-controls={ id }
				onClick={ () => setOpen( ! open ) }
			>
				{ title }
			</Button>
			<div id={ id } className="lw-seo-group__body" hidden={ ! open }>
				{ children }
			</div>
		</div>
	);
}
