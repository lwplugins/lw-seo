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

	registerBlockType(
		'lw-seo/html-sitemap',
		{
			edit( { attributes, setAttributes } ) {
				const blockProps = useBlockProps();

				return el(
					Fragment,
					null,
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{
								title: __( 'HTML sitemap', 'lw-seo' ),
								initialOpen: true,
							},
							el(
								TextControl,
								{
									label: __( 'Post types', 'lw-seo' ),
									help: __(
										'Comma-separated, e.g. page,post. Empty uses the setting.',
										'lw-seo'
									),
								value: attributes.post_types,
								onChange: ( value ) =>
								setAttributes( { post_types: value } ),
								}
							),
							el(
								TextControl,
								{
									label: __( 'Taxonomies', 'lw-seo' ),
									help: __(
										'Comma-separated, e.g. category. Empty uses the setting.',
										'lw-seo'
									),
								value: attributes.taxonomies,
								onChange: ( value ) =>
								setAttributes( { taxonomies: value } ),
								}
							),
							el(
								TextControl,
								{
									label: __( 'Max items per section', 'lw-seo' ),
									help: __( '0 uses the setting.', 'lw-seo' ),
									type: 'number',
									min: 0,
									value: attributes.max,
									onChange: ( value ) =>
									setAttributes(
										{
											max: parseInt( value, 10 ) || 0,
										}
									),
								}
							),
							el(
								SelectControl,
								{
									label: __( 'Order', 'lw-seo' ),
									value: attributes.order,
									options: [
									{
										label: __( 'Use the setting', 'lw-seo' ),
										value: '',
									},
									{
										label: __( 'Title', 'lw-seo' ),
										value: 'title',
									},
									{
										label: __( 'Date', 'lw-seo' ),
										value: 'date',
									},
									],
									onChange: ( value ) =>
									setAttributes( { order: value } ),
								}
							)
						)
					),
					el(
						'div',
						blockProps,
						el(
							ServerSideRender,
							{
								block: 'lw-seo/html-sitemap',
								attributes,
							}
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
