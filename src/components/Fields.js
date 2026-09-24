/**
 * Small field shorthands so the tabs stay declarative.
 */
/**
 * WordPress dependencies
 */
import {
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import SettingRow from './SettingRow';
import ToggleRow from './ToggleRow';

export function TextRow( {
	title,
	help,
	store,
	name,
	type = 'text',
	placeholder,
	mono,
} ) {
	return (
		<SettingRow title={ title } help={ help }>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				type={ type }
				className={ mono ? 'lw-admin-mono' : undefined }
				placeholder={ placeholder }
				value={ store.data.options[ name ] ?? '' }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

export function AreaRow( {
	title,
	help,
	store,
	name,
	rows = 3,
	placeholder,
	mono,
} ) {
	return (
		<SettingRow title={ title } help={ help } stacked={ rows > 4 }>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				rows={ rows }
				className={ mono ? 'lw-admin-mono' : undefined }
				placeholder={ placeholder }
				value={ store.data.options[ name ] ?? '' }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

export function SelectRow( { title, help, store, name, options } ) {
	return (
		<SettingRow title={ title } help={ help }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				value={ String( store.data.options[ name ] ?? '' ) }
				options={ options }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

export function SwitchRow( { title, help, store, name, onText, offText } ) {
	return (
		<ToggleRow
			title={ title }
			help={ help }
			checked={ !! store.data.options[ name ] }
			onChange={ ( value ) => store.set( name, value ) }
			onText={ onText }
			offText={ offText }
		/>
	);
}
