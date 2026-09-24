/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';

const inc = __( 'Included', 'lw-seo' );
const exc = __( 'Left out', 'lw-seo' );

export default function WooTab( { store } ) {
	const skipped = store.data.meta.woo_skipped_categories;

	return (
		<>
			<Section
				title={ __( 'General', 'lw-seo' ) }
				description={ __(
					'Optimize your WooCommerce products for search engines and social media.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'WooCommerce SEO', 'lw-seo' ) }
					help={ __(
						'Adds product-specific OpenGraph tags, Schema.org Product markup, and more.',
						'lw-seo'
					) }
					store={ store }
					name="woo_enabled"
					onText={ __( 'Enabled', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<TextRow
					title={ __( 'Product Title', 'lw-seo' ) }
					store={ store }
					name="title_product"
					placeholder="%%title%% %%sep%% %%sitename%%"
					mono
				/>
				<TextRow
					title={ __( 'Shop Title', 'lw-seo' ) }
					help={ __(
						'Title of the shop page (product archive). %%title%% is the shop page title.',
						'lw-seo'
					) }
					store={ store }
					name="title_ptarchive_product"
					mono
				/>
				<SwitchRow
					title={ __( 'Products', 'lw-seo' ) }
					help={ __( 'Add noindex to product pages.', 'lw-seo' ) }
					store={ store }
					name="noindex_product"
					onText={ __( 'noindex', 'lw-seo' ) }
					offText={ __( 'Indexed', 'lw-seo' ) }
				/>
			</Section>
			<Section
				title={ __( 'Permalinks', 'lw-seo' ) }
				description={ __(
					'Slug-only WooCommerce permalinks. Categories whose root slug collides with a page, reserved slug, or another taxonomy/CPT base are automatically skipped to avoid breaking existing URLs.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'Category base', 'lw-seo' ) }
					store={ store }
					name="wc_remove_category_base"
					onText={ __(
						'Remove /product-category/ from product category URLs',
						'lw-seo'
					) }
					offText={ __( 'Keep', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Parent slugs', 'lw-seo' ) }
					store={ store }
					name="wc_remove_category_parent_slugs"
					onText={ __(
						'Remove parent category slugs (leaf-only URLs)',
						'lw-seo'
					) }
					offText={ __( 'Keep', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Product base', 'lw-seo' ) }
					store={ store }
					name="wc_remove_product_base"
					onText={ __(
						'Remove /product/ from product URLs',
						'lw-seo'
					) }
					offText={ __( 'Keep', 'lw-seo' ) }
				/>
				{ skipped.length > 0 && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __(
								'Skipped categories (slug collision):',
								'lw-seo'
							) }
						</strong>{ ' ' }
						{ skipped.join( ', ' ) }.{ ' ' }
						{ __(
							'These category slugs already belong to a page, reserved WordPress slug, or another taxonomy/CPT base. They keep their default WooCommerce URL. Rename either the category or the conflicting slug to convert.',
							'lw-seo'
						) }
					</Notice>
				) }
			</Section>
			<Section title={ __( 'Schema.org and sitemap', 'lw-seo' ) }>
				<SwitchRow
					title={ __( 'Product Schema', 'lw-seo' ) }
					help={ __(
						'Structured data for products including price, availability, and reviews.',
						'lw-seo'
					) }
					store={ store }
					name="woo_schema_enabled"
					onText={ __( 'Enabled', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Include Reviews', 'lw-seo' ) }
					store={ store }
					name="woo_schema_reviews"
					onText={ __(
						'Include product reviews in Schema',
						'lw-seo'
					) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Products in Sitemap', 'lw-seo' ) }
					store={ store }
					name="sitemap_products"
					onText={ inc }
					offText={ exc }
				/>
				<SwitchRow
					title={ __( 'Product Categories', 'lw-seo' ) }
					store={ store }
					name="sitemap_product_cat"
					onText={ inc }
					offText={ exc }
				/>
				<SwitchRow
					title={ __( 'Product Tags', 'lw-seo' ) }
					store={ store }
					name="sitemap_product_tag"
					onText={ inc }
					offText={ exc }
				/>
			</Section>
		</>
	);
}
