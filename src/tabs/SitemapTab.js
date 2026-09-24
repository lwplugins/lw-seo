/**
 * WordPress dependencies
 */
import { ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SwitchRow } from '../components/Fields';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';
import TypeToggles from '../components/TypeToggles';

const inc = __( 'Included', 'lw-seo' );
const exc = __( 'Left out', 'lw-seo' );

export default function SitemapTab( { store } ) {
	const { options, meta } = store.data;

	return (
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
	);
}
