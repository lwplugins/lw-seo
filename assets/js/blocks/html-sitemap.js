/**
 * LW SEO HTML Sitemap Block
 *
 * @package LightweightPlugins\SEO
 */

( function ( wp ) {
	'use strict';

	const { registerBlockType }                     = wp.blocks;
	const { createElement: el, Fragment }           = wp.element;
	const { InspectorControls, useBlockProps }      = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl } = wp.components;
	const ServerSideRender                          = wp.serverSideRender;
	const { __ }                                    = wp.i18n;

	const ORDER_OPTIONS = [
		{ label: __( 'Use the setting', 'lw-seo' ), value: '' },
		{ label: __( 'Title', 'lw-seo' ), value: 'title' },
		{ label: __( 'Date', 'lw-seo' ), value: 'date' },
	];

	function Settings( { attributes, setAttributes } ) {
		const textField = ( name, label, help ) => el(
			TextControl,
			{
				label: label,
				help: help,
				value: attributes[ name ],
				onChange: ( value ) => setAttributes( { [ name ]: value } ),
			}
		);

		return el(
			PanelBody,
			{ title: __( 'HTML sitemap', 'lw-seo' ), initialOpen: true },
			textField(
				'post_types',
				__( 'Post types', 'lw-seo' ),
				__( 'Comma-separated, e.g. page,post. Empty uses the setting.', 'lw-seo' )
			),
			textField(
				'taxonomies',
				__( 'Taxonomies', 'lw-seo' ),
				__( 'Comma-separated, e.g. category. Empty uses the setting.', 'lw-seo' )
			),
			el(
				TextControl,
				{
					label: __( 'Max items per section', 'lw-seo' ),
					help: __( '0 uses the setting; it can only lower it.', 'lw-seo' ),
					type: 'number',
					min: 0,
					value: attributes.max,
					onChange: ( value ) => setAttributes( { max: parseInt( value, 10 ) || 0 } ),
				}
			),
			el(
				SelectControl,
				{
					label: __( 'Order', 'lw-seo' ),
					value: attributes.order,
					options: ORDER_OPTIONS,
					onChange: ( value ) => setAttributes( { order: value } ),
				}
			)
		);
	}

	registerBlockType(
		'lw-seo/html-sitemap',
		{
			edit( props ) {
				return el(
					Fragment,
					null,
					el( InspectorControls, null, el( Settings, props ) ),
					el(
						'div',
						useBlockProps(),
						el(
							ServerSideRender,
							{ block: 'lw-seo/html-sitemap', attributes: props.attributes }
						)
					)
				);
			},
			save() {
				return null;
			},
		}
	);
} )( window.wp );
