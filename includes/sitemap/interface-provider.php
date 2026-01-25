<?php
/**
 * Sitemap Provider Interface.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

/**
 * Interface for sitemap providers.
 */
interface Provider_Interface {

	/**
	 * Check if this provider is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool;

	/**
	 * Get total number of pages.
	 *
	 * @return int
	 */
	public function get_total_pages(): int;

	/**
	 * Get items for a specific page.
	 *
	 * @param int $page Page number.
	 * @return array<array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
	 */
	public function get_items( int $page ): array;
}
