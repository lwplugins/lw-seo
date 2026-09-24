/**
 * WordPress dependencies
 */
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	Button,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Collapsible from './Collapsible';
import Counter from './Counter';
import SignalSelect from './SignalSelect';

const boot = window.lwSeoEditor || {};
const TITLE_MAX = boot.titleMax || 60;
const DESC_MAX = boot.descMax || 160;

/**
 * The post's SEO fields, edited through the `lw_seo` REST field and saved
 * together with the post (core-data entity edits).
 */
export default function SeoPanel() {
	const { postType, postTitle, permalink } = useSelect( ( select ) => {
		const editor = select( editorStore );
		return {
			postType: editor.getCurrentPostType(),
			postTitle: editor.getEditedPostAttribute( 'title' ),
			permalink: editor.getPermalink(),
		};
	}, [] );
	const [ seo, setSeo ] = useEntityProp( 'postType', postType, 'lw_seo' );

	if ( ! seo ) {
		return null;
	}
	const set = ( key ) => ( value ) => setSeo( { ...seo, [ key ]: value } );

	return (
		<div className="lw-seo-panel">
			<div className="lw-seo-field">
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'SEO Title', 'lw-seo' ) }
					placeholder={ postTitle }
					value={ seo.title }
					onChange={ set( 'title' ) }
					help={ __(
						'Leave empty to use the default title template.',
						'lw-seo'
					) }
				/>
				<Counter value={ seo.title } max={ TITLE_MAX } />
			</div>
			<div className="lw-seo-field">
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Meta Description', 'lw-seo' ) }
					placeholder={ __( 'Enter a meta description…', 'lw-seo' ) }
					rows={ 3 }
					value={ seo.description }
					onChange={ set( 'description' ) }
				/>
				<Counter value={ seo.description } max={ DESC_MAX } />
			</div>
			<ToggleControl
				__nextHasNoMarginBottom
				label="noindex"
				help={ __(
					'Keep this page out of search results and the sitemap.',
					'lw-seo'
				) }
				checked={ seo.noindex }
				onChange={ set( 'noindex' ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label="nofollow"
				help={ __(
					'Ask search engines not to follow the links on this page.',
					'lw-seo'
				) }
				checked={ seo.nofollow }
				onChange={ set( 'nofollow' ) }
			/>

			<Collapsible title={ __( 'Social', 'lw-seo' ) }>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Social Title', 'lw-seo' ) }
					placeholder={ __( 'Defaults to SEO title', 'lw-seo' ) }
					value={ seo.og_title }
					onChange={ set( 'og_title' ) }
				/>
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Social Description', 'lw-seo' ) }
					placeholder={ __(
						'Defaults to meta description',
						'lw-seo'
					) }
					rows={ 2 }
					value={ seo.og_description }
					onChange={ set( 'og_description' ) }
				/>
				<div className="lw-seo-field">
					<span className="lw-seo-label">
						{ __( 'Social Image', 'lw-seo' ) }
					</span>
					{ seo.og_image && (
						<img
							src={ seo.og_image }
							alt=""
							className="lw-seo-image"
						/>
					) }
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							onSelect={ ( media ) =>
								set( 'og_image' )( media.url )
							}
							render={ ( { open } ) => (
								<div className="lw-seo-row">
									<Button
										variant="secondary"
										size="compact"
										onClick={ open }
									>
										{ seo.og_image
											? __( 'Replace', 'lw-seo' )
											: __( 'Select Image', 'lw-seo' ) }
									</Button>
									{ seo.og_image && (
										<Button
											variant="tertiary"
											size="compact"
											isDestructive
											onClick={ () =>
												set( 'og_image' )( '' )
											}
										>
											{ __( 'Remove', 'lw-seo' ) }
										</Button>
									) }
								</div>
							) }
						/>
					</MediaUploadCheck>
					<p className="lw-seo-help">
						{ __(
							'Priority: Social Image, then Featured Image, then the default image.',
							'lw-seo'
						) }
					</p>
				</div>
			</Collapsible>

			<Collapsible title={ __( 'Advanced', 'lw-seo' ) }>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					type="url"
					label={ __( 'Canonical URL', 'lw-seo' ) }
					placeholder={ permalink }
					help={ __(
						'Leave empty to use the default permalink.',
						'lw-seo'
					) }
					value={ seo.canonical }
					onChange={ set( 'canonical' ) }
				/>
			</Collapsible>

			<Collapsible title={ __( 'AI Content Signals', 'lw-seo' ) }>
				<p className="lw-seo-help">
					{ __(
						'Override the global Content Signals settings for this content.',
						'lw-seo'
					) }
				</p>
				<SignalSelect
					label={ __( 'AI Training', 'lw-seo' ) }
					value={ seo.ai_train }
					onChange={ set( 'ai_train' ) }
				/>
				<SignalSelect
					label={ __( 'AI Input (RAG)', 'lw-seo' ) }
					value={ seo.ai_input }
					onChange={ set( 'ai_input' ) }
				/>
				<SignalSelect
					label={ __( 'AI Search', 'lw-seo' ) }
					value={ seo.search }
					onChange={ set( 'search' ) }
				/>
			</Collapsible>

			<Collapsible title={ __( 'Markdown Content', 'lw-seo' ) }>
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Custom Markdown', 'lw-seo' ) }
					className="lw-seo-mono"
					rows={ 10 }
					placeholder="# Title..."
					disabled={ ! seo.can_edit_markdown }
					value={ seo.markdown_content }
					onChange={ set( 'markdown_content' ) }
					help={
						seo.can_edit_markdown
							? __(
									'If filled, this markdown is served at the /md endpoint instead of the auto-generated content. Ideal for page builder pages.',
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
