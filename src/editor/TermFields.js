/**
 * WordPress dependencies
 */
import {
	Button,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Collapsible from './Collapsible';
import Counter from './Counter';
import SignalSelect from './SignalSelect';

const boot = window.lwSeoTerm || {};
const TITLE_MAX = boot.titleMax || 60;
const DESC_MAX = boot.descMax || 160;

/**
 * SEO fields on the term edit screen. They render real inputs named
 * lw_seo_{field} inside the core term form, so the classic save handler
 * (nonce + edited_{taxonomy}) stays in charge of saving.
 */
export default function TermFields() {
	const [ v, setV ] = useState( {
		title: '',
		description: '',
		noindex: false,
		og_title: '',
		og_description: '',
		og_image: '',
		ai_train: '',
		ai_input: '',
		search: '',
		markdown_content: '',
		...boot.values,
	} );
	const set = ( key ) => ( value ) =>
		setV( ( prev ) => ( { ...prev, [ key ]: value } ) );

	const pickImage = () => {
		const frame = window.wp?.media?.( {
			title: __( 'Select Image', 'lw-seo' ),
			multiple: false,
			library: { type: 'image' },
		} );
		if ( ! frame ) {
			return;
		}
		frame.on( 'select', () =>
			set( 'og_image' )(
				frame.state().get( 'selection' ).first().toJSON().url
			)
		);
		frame.open();
	};

	return (
		<div className="lw-seo-term">
			<h2 className="lw-seo-term__title">{ __( 'LW SEO', 'lw-seo' ) }</h2>
			<div className="lw-seo-field">
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					name="lw_seo_title"
					label={ __( 'SEO Title', 'lw-seo' ) }
					placeholder={ boot.termName }
					help={ __(
						'Leave empty to use the default title template.',
						'lw-seo'
					) }
					value={ v.title }
					onChange={ set( 'title' ) }
				/>
				<Counter value={ v.title } max={ TITLE_MAX } />
			</div>
			<div className="lw-seo-field">
				<TextareaControl
					__nextHasNoMarginBottom
					name="lw_seo_description"
					label={ __( 'Meta Description', 'lw-seo' ) }
					rows={ 3 }
					value={ v.description }
					onChange={ set( 'description' ) }
				/>
				<Counter value={ v.description } max={ DESC_MAX } />
			</div>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'noindex this archive', 'lw-seo' ) }
				checked={ v.noindex }
				onChange={ set( 'noindex' ) }
			/>
			{ /* The switch is not a named form control; this hidden input carries its value. */ }
			{ v.noindex && (
				<input type="hidden" name="lw_seo_noindex" value="1" />
			) }

			<Collapsible
				title={ __( 'Social', 'lw-seo' ) }
				initialOpen={ Boolean(
					v.og_title || v.og_description || v.og_image
				) }
			>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					name="lw_seo_og_title"
					label={ __( 'Social Title', 'lw-seo' ) }
					placeholder={ __( 'Defaults to SEO title', 'lw-seo' ) }
					value={ v.og_title }
					onChange={ set( 'og_title' ) }
				/>
				<TextareaControl
					__nextHasNoMarginBottom
					name="lw_seo_og_description"
					label={ __( 'Social Description', 'lw-seo' ) }
					placeholder={ __(
						'Defaults to meta description',
						'lw-seo'
					) }
					rows={ 2 }
					value={ v.og_description }
					onChange={ set( 'og_description' ) }
				/>
				<div className="lw-seo-field">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="url"
						name="lw_seo_og_image"
						label={ __( 'Social Image', 'lw-seo' ) }
						placeholder="https://"
						value={ v.og_image }
						onChange={ set( 'og_image' ) }
					/>
					{ v.og_image && (
						<img
							src={ v.og_image }
							alt=""
							className="lw-seo-image"
						/>
					) }
					<div className="lw-seo-row">
						{ window.wp?.media && (
							<Button
								variant="secondary"
								size="compact"
								onClick={ pickImage }
							>
								{ v.og_image
									? __( 'Replace', 'lw-seo' )
									: __( 'Select Image', 'lw-seo' ) }
							</Button>
						) }
						{ v.og_image && (
							<Button
								variant="tertiary"
								size="compact"
								isDestructive
								onClick={ () => set( 'og_image' )( '' ) }
							>
								{ __( 'Remove', 'lw-seo' ) }
							</Button>
						) }
					</div>
				</div>
			</Collapsible>

			<Collapsible
				title={ __( 'AI Content Signals', 'lw-seo' ) }
				initialOpen={ Boolean( v.ai_train || v.ai_input || v.search ) }
			>
				<SignalSelect
					name="lw_seo_ai_train"
					label={ __( 'AI Training', 'lw-seo' ) }
					value={ v.ai_train }
					onChange={ set( 'ai_train' ) }
				/>
				<SignalSelect
					name="lw_seo_ai_input"
					label={ __( 'AI Input (RAG)', 'lw-seo' ) }
					value={ v.ai_input }
					onChange={ set( 'ai_input' ) }
				/>
				<SignalSelect
					name="lw_seo_search"
					label={ __( 'AI Search', 'lw-seo' ) }
					value={ v.search }
					onChange={ set( 'search' ) }
				/>
			</Collapsible>

			<Collapsible
				title={ __( 'Markdown Content', 'lw-seo' ) }
				initialOpen={ Boolean( v.markdown_content ) }
			>
				<TextareaControl
					__nextHasNoMarginBottom
					name={
						boot.canEditMarkdown
							? 'lw_seo_markdown_content'
							: undefined
					}
					label={ __( 'Markdown Content', 'lw-seo' ) }
					className="lw-seo-mono"
					rows={ 10 }
					placeholder="# Title..."
					disabled={ ! boot.canEditMarkdown }
					value={ v.markdown_content }
					onChange={ set( 'markdown_content' ) }
					help={
						boot.canEditMarkdown
							? __(
									'If filled, this markdown is served at the /md endpoint instead of the auto-generated content.',
									'lw-seo'
								)
							: __(
									'Only users allowed to post unfiltered HTML can edit the Markdown override.',
									'lw-seo'
								)
					}
				/>
			</Collapsible>
		</div>
	);
}
