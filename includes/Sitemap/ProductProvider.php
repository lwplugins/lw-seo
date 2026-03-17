<?php
/**
 * Product Sitemap Provider.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

/**
 * Provides WooCommerce products for sitemap.
 */
final class ProductProvider extends PostProvider {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected string $post_type = 'product';

	/**
	 * Option key.
	 *
	 * @var string
	 */
	protected string $option_key = 'sitemap_products';
}
