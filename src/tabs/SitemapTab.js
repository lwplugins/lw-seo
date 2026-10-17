/**
 * WordPress dependencies
 */
import { ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CopyField from '../components/CopyField';
import { SelectRow, SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';
import IndexNowSection from './sitemap/IndexNowSection';
import SettingRow from '../components/SettingRow';
import TypeToggles from '../components/TypeToggles';

const inc = __( 'Included', 'lw-seo' );
const exc = __( 'Left out', 'lw-seo' );

export default function SitemapTab( { store } ) {
	const { options, meta } = store.data;

	// An empty selection lists posts and pages.
	const htmlTypes =
		Object.keys( options.html_sitemap_post_types ?? {} ).length > 0
			? options.html_sitemap_post_types
			: { post: true, page: true };

	return (
		<>
			<Section
				title={ __( 'XML Sitemap', 'lw-seo' ) }
				description={
					<>
						{ __( 'Your sitemap:', 'lw-seo' ) }{ ' ' }
						<ExternalLink href={ meta.urls.sitemap }>
							{ meta.urls.sitemap }
						</ExternalLink>
					</>
				}
			>
				<SwitchRow
					title={ __( 'XML Sitemap', 'lw-seo' ) }
					store={ store }
					name="sitemap_enabled"
					onText={ __( 'Enabled', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Include images', 'lw-seo' ) }
					store={ store }
					name="sitemap_images"
					onText={ __( 'Included', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Readable view in browsers (XSL)', 'lw-seo' ) }
					store={ store }
					name="sitemap_stylesheet"
					onText={ __( 'Enabled', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
					help={ __(
						'Browsers that dropped XSLT show the raw XML; search engines are not affected.',
						'lw-seo'
					) }
				/>
				<SwitchRow
					title={ __( 'Posts', 'lw-seo' ) }
					store={ store }
					name="sitemap_posts"
					onText={ inc }
					offText={ exc }
				/>
				<SwitchRow
					title={ __( 'Pages', 'lw-seo' ) }
					store={ store }
					name="sitemap_pages"
					onText={ inc }
					offText={ exc }
				/>
				{ meta.sitemap_post_types.length > 0 && (
					<SettingRow
						title={ __( 'Custom Post Types', 'lw-seo' ) }
						help={ __(
							'New custom post types are included automatically. Content set to noindex is always left out.',
							'lw-seo'
						) }
					>
						<TypeToggles
							items={ meta.sitemap_post_types }
							value={ options.sitemap_post_types }
							fallback
							onChange={ ( map ) =>
								store.set( 'sitemap_post_types', map )
							}
						/>
					</SettingRow>
				) }
				<SwitchRow
					title={ __( 'Categories', 'lw-seo' ) }
					store={ store }
					name="sitemap_categories"
					onText={ inc }
					offText={ exc }
				/>
				<SwitchRow
					title={ __( 'Tags', 'lw-seo' ) }
					store={ store }
					name="sitemap_tags"
					onText={ inc }
					offText={ exc }
				/>
				{ meta.sitemap_taxonomies.length > 0 && (
					<SettingRow
						title={ __( 'Custom Taxonomies', 'lw-seo' ) }
						help={ __(
							'Custom taxonomy archives are only listed when switched on here.',
							'lw-seo'
						) }
					>
						<TypeToggles
							items={ meta.sitemap_taxonomies }
							value={ options.sitemap_taxonomies }
							fallback={ false }
							onChange={ ( map ) =>
								store.set( 'sitemap_taxonomies', map )
							}
						/>
					</SettingRow>
				) }
			</Section>
			<Section
				title={ __( 'HTML sitemap', 'lw-seo' ) }
				description={ __(
					'A page for your visitors that links to your content. Add it with the shortcode or the "HTML Sitemap (LW SEO)" block.',
					'lw-seo'
				) }
			>
				<SettingRow
					title={ __( 'Shortcode', 'lw-seo' ) }
					help={ __(
						'Optional attributes: post_types, taxonomies, max, order (title or date). They override the settings below.',
						'lw-seo'
					) }
				>
					<CopyField text="[lw_html_sitemap]" />
				</SettingRow>
				<SettingRow
					title={ __( 'Post types', 'lw-seo' ) }
					help={ __(
						'Content set to noindex is always left out.',
						'lw-seo'
					) }
				>
					<TypeToggles
						items={ meta.post_types }
						value={ htmlTypes }
						fallback={ false }
						onChange={ ( map ) =>
							store.set( 'html_sitemap_post_types', map )
						}
					/>
				</SettingRow>
				{ meta.taxonomies.length > 0 && (
					<SettingRow title={ __( 'Taxonomies', 'lw-seo' ) }>
						<TypeToggles
							items={ meta.taxonomies }
							value={ options.html_sitemap_taxonomies }
							fallback={ false }
							onChange={ ( map ) =>
								store.set( 'html_sitemap_taxonomies', map )
							}
						/>
					</SettingRow>
				) }
				<TextRow
					title={ __( 'Max items per section', 'lw-seo' ) }
					help={ __(
						'Longer lists are cut off and get a "View all" link.',
						'lw-seo'
					) }
					store={ store }
					name="html_sitemap_max_items"
					type="number"
				/>
				<SelectRow
					title={ __( 'Order', 'lw-seo' ) }
					store={ store }
					name="html_sitemap_order"
					options={ [
						{ label: __( 'Title', 'lw-seo' ), value: 'title' },
						{
							label: __( 'Newest first', 'lw-seo' ),
							value: 'date',
						},
					] }
				/>
			</Section>
			<IndexNowSection store={ store } />
		</>
	);
}
