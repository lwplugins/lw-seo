/**
 * LW SEO FAQ Block
 *
 * @package LightweightPlugins\SEO
 */

( function ( wp ) {
	'use strict';

	const { registerBlockType }                          = wp.blocks;
	const { createElement: el, Fragment, useState }      = wp.element;
	const { InspectorControls, RichText, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, Button }           = wp.components;
	const { __ } = wp.i18n;

	/**
	 * Generate unique ID for FAQ item.
	 *
	 * @return {string} Unique ID.
	 */
	function generateId() {
		return 'faq-' + Math.random().toString( 36 ).substring( 2, 11 );
	}

	/**
	 * FAQ Item Component.
	 */
	function FAQItem( { question, index, onChange, onRemove, onMoveUp, onMoveDown, isFirst, isLast } ) {
		const [ isExpanded, setIsExpanded ] = useState( true );

		return el(
			'div',
			{ className: 'lw-seo-faq-editor-item' + ( question.visible ? '' : ' is-hidden' ) },
			el(
				'div',
				{ className: 'lw-seo-faq-editor-item-header' },
				el(
					Button,
					{
						className: 'lw-seo-faq-toggle',
						onClick: () => setIsExpanded( ! isExpanded ),
						'aria-expanded': isExpanded,
					},
					el( 'span', { className: 'dashicons dashicons-' + ( isExpanded ? 'arrow-down-alt2' : 'arrow-right-alt2' ) } ),
					el( 'span', { className: 'lw-seo-faq-item-title' }, question.title || __( 'New Question', 'lw-seo' ) )
				),
				el(
					'div',
					{ className: 'lw-seo-faq-editor-actions' },
					el(
						Button,
						{
							icon: 'arrow-up-alt2',
							disabled: isFirst,
							onClick: onMoveUp,
							label: __( 'Move up', 'lw-seo' ),
							size: 'small',
						}
					),
					el(
						Button,
						{
							icon: 'arrow-down-alt2',
							disabled: isLast,
							onClick: onMoveDown,
							label: __( 'Move down', 'lw-seo' ),
							size: 'small',
						}
					),
					el(
						Button,
						{
							icon: question.visible ? 'visibility' : 'hidden',
							onClick: () => onChange( { ...question, visible: ! question.visible } ),
							label: question.visible ? __( 'Hide', 'lw-seo' ) : __( 'Show', 'lw-seo' ),
							size: 'small',
						}
					),
					el(
						Button,
						{
							icon: 'trash',
							onClick: onRemove,
							label: __( 'Remove', 'lw-seo' ),
							isDestructive: true,
							size: 'small',
						}
					)
				)
			),
			isExpanded && el(
				'div',
				{ className: 'lw-seo-faq-editor-item-content' },
				el(
					'div',
					{ className: 'lw-seo-faq-editor-field' },
					el( 'label', {}, __( 'Question', 'lw-seo' ) ),
					el(
						RichText,
						{
							tagName: 'div',
							value: question.title,
							onChange: ( value ) => onChange( { ...question, title: value } ),
							placeholder: __( 'Enter your question...', 'lw-seo' ),
							allowedFormats: [ 'core/bold', 'core/italic' ],
						}
					)
				),
				el(
					'div',
					{ className: 'lw-seo-faq-editor-field' },
					el( 'label', {}, __( 'Answer', 'lw-seo' ) ),
					el(
						RichText,
						{
							tagName: 'div',
							value: question.content,
							onChange: ( value ) => onChange( { ...question, content: value } ),
							placeholder: __( 'Enter the answer...', 'lw-seo' ),
							allowedFormats: [ 'core/bold', 'core/italic', 'core/link', 'core/list' ],
						}
					)
				)
			)
		);
	}

	/**
	 * FAQ Block Edit Component.
	 */
	function FAQEdit( { attributes, setAttributes } ) {
		const { questions, titleWrapper } = attributes;
		const blockProps                  = useBlockProps( { className: 'lw-seo-faq-editor' } );

		function addQuestion() {
			const item     = { id: generateId(), title: '', content: '', visible: true };
			const newItems = [ ...questions, item ];
			setAttributes( { questions: newItems } );
		}

		function updateQuestion( index, question ) {
			const newItems    = [ ...questions ];
			newItems[ index ] = question;
			setAttributes( { questions: newItems } );
		}

		function removeQuestion( index ) {
			const newItems = questions.filter( ( _, i ) => i !== index );
			setAttributes( { questions: newItems } );
		}

		function moveQuestion( index, direction ) {
			const newItems = [ ...questions ];
			const target   = index + direction;

			[ newItems[ index ], newItems[ target ] ] = [ newItems[ target ], newItems[ index ] ];
			setAttributes( { questions: newItems } );
		}

		return el(
			Fragment,
			{},
			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'FAQ Settings', 'lw-seo' ) },
					el(
						SelectControl,
						{
							label: __( 'Question Heading Tag', 'lw-seo' ),
							value: titleWrapper,
							options: [
								{ label: 'H2', value: 'h2' },
								{ label: 'H3', value: 'h3' },
								{ label: 'H4', value: 'h4' },
								{ label: 'H5', value: 'h5' },
								{ label: 'H6', value: 'h6' },
								{ label: 'Paragraph', value: 'p' },
								{ label: 'Div', value: 'div' },
							],
							onChange: ( value ) => setAttributes( { titleWrapper: value } ),
						}
					),
					el(
						'p',
						{ className: 'components-base-control__help' },
						__( 'This block automatically generates FAQPage schema for better SEO.', 'lw-seo' )
					)
				)
			),
			el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'lw-seo-faq-editor-header' },
					el( 'span', { className: 'dashicons dashicons-editor-help' } ),
					el( 'span', {}, __( 'FAQ Block', 'lw-seo' ) ),
					el(
						'span',
						{ className: 'lw-seo-faq-count' },
						questions.length + ' ' + ( questions.length === 1 ? __( 'question', 'lw-seo' ) : __( 'questions', 'lw-seo' ) )
					)
				),
				questions.length === 0 && el(
					'div',
					{ className: 'lw-seo-faq-empty' },
					el( 'p', {}, __( 'No questions yet. Click the button below to add your first FAQ.', 'lw-seo' ) )
				),
				questions.map(
					function ( question, index ) {
						return el(
							FAQItem,
							{
								key: question.id || index,
								question: question,
								index: index,
								onChange: ( q ) => updateQuestion( index, q ),
								onRemove: () => removeQuestion( index ),
								onMoveUp: () => moveQuestion( index, -1 ),
								onMoveDown: () => moveQuestion( index, 1 ),
								isFirst: index === 0,
								isLast: index === questions.length - 1,
							}
						);
					}
				),
				el(
					Button,
					{
						className: 'lw-seo-faq-add-button',
						variant: 'secondary',
						onClick: addQuestion,
					},
					el( 'span', { className: 'dashicons dashicons-plus-alt2' } ),
					__( 'Add Question', 'lw-seo' )
				)
			)
		);
	}

	/**
	 * Register FAQ Block.
	 */
	registerBlockType(
		'lw-seo/faq',
		{
			edit: FAQEdit,
			save: () => null, // Dynamic block, rendered via PHP.
		}
	);

	/**
	 * Block type filter callback.
	 *
	 * @param {Object} settings Block settings.
	 * @return {Object} Block settings.
	 */
	function filterBlockType( settings ) {
		return settings;
	}

	wp.hooks.addFilter( 'blocks.registerBlockType', 'lw-seo/faq-category', filterBlockType );

	/**
	 * Register custom block category.
	 */
	function registerCategory() {
		const categories  = wp.blocks.getCategories();
		const hasCategory = categories.some( ( cat ) => cat.slug === 'lw-seo' );

		if ( ! hasCategory ) {
			wp.blocks.setCategories(
				[
					{
						slug: 'lw-seo',
						title: __( 'LW SEO', 'lw-seo' ),
						icon: 'search',
					},
					...categories,
				]
			);
		}
	}

	wp.domReady( registerCategory );

} )( window.wp );
