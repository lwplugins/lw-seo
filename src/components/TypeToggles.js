/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';

/**
 * One checkbox per post type / taxonomy, stored as a name → bool map.
 * A type missing from the map uses `fallback`.
 *
 * @param {Object}   props
 * @param {Array}    props.items    { name, label }.
 * @param {Object}   props.value    Map.
 * @param {boolean}  props.fallback Value for missing entries.
 * @param {Function} props.onChange Receives the new map.
 */
export default function TypeToggles( {
	items,
	value = {},
	fallback,
	onChange,
} ) {
	return (
		<div className="lw-admin-checklist">
			{ items.map( ( item ) => (
				<CheckboxControl
					key={ item.name }
					__nextHasNoMarginBottom
					label={
						<>
							{ item.label } <code>{ item.name }</code>
						</>
					}
					checked={
						item.name in value ? !! value[ item.name ] : fallback
					}
					onChange={ ( checked ) =>
						onChange( { ...value, [ item.name ]: checked } )
					}
				/>
			) ) }
		</div>
	);
}
