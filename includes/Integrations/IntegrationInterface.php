<?php
/**
 * Integration contract.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations;

/**
 * Code that works with another plugin or theme. The Loader registers it
 * only when that plugin or theme is present.
 */
interface IntegrationInterface {

	/**
	 * Integration ID: bricks, polylang, woocommerce, site-manager.
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Whether the plugin or theme this integration works with is present.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Hook the integration in.
	 *
	 * @return void
	 */
	public function register(): void;
}
