<?php
/**
 * Main Sitemap class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

use LightweightPlugins\SEO\Options;

/**
 * Handles XML sitemap generation.
 */
final class Sitemap {

	/**
	 * Sitemap providers.
	 *
	 * @var array<ProviderInterface>
	 */
	private array $providers = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Options::get( 'sitemap_enabled' ) ) {
			return;
		}

		$this->register_providers();

		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'template_redirect', [ $this, 'handle_sitemap_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		// Disable core sitemaps.
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
	}

	/**
	 * Register sitemap providers.
	 *
	 * @return void
	 */
	private function register_providers(): void {
		$this->providers['post']     = new PostProvider();
		$this->providers['page']     = new PageProvider();
		$this->providers['category'] = new TaxonomyProvider( 'category' );
		$this->providers['post_tag'] = new TaxonomyProvider( 'post_tag' );
	}

	/**
	 * Add rewrite rules.
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule(
			'^sitemap\.xml$',
			'index.php?lw_sitemap=index',
			'top'
		);

		add_rewrite_rule(
			'^sitemap-([a-z_]+)\.xml$',
			'index.php?lw_sitemap=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^sitemap-([a-z_]+)-(\d+)\.xml$',
			'index.php?lw_sitemap=$matches[1]&lw_sitemap_page=$matches[2]',
			'top'
		);
	}

	/**
	 * Add query vars.
	 *
	 * @param array<string> $vars Query vars.
	 * @return array<string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'lw_sitemap';
		$vars[] = 'lw_sitemap_page';
		return $vars;
	}

	/**
	 * Handle sitemap request.
	 *
	 * @return void
	 */
	public function handle_sitemap_request(): void {
		$sitemap = get_query_var( 'lw_sitemap' );

		if ( empty( $sitemap ) ) {
			return;
		}

		$this->set_headers();

		if ( 'index' === $sitemap ) {
			$this->render_index();
		} elseif ( isset( $this->providers[ $sitemap ] ) ) {
			$page = (int) get_query_var( 'lw_sitemap_page', 1 );
			$this->render_sitemap( $sitemap, max( 1, $page ) );
		} else {
			status_header( 404 );
			exit;
		}

		exit;
	}

	/**
	 * Set XML headers.
	 *
	 * @return void
	 */
	private function set_headers(): void {
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );
	}

	/**
	 * Render sitemap index.
	 *
	 * @return void
	 */
	private function render_index(): void {
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $this->providers as $name => $provider ) {
			if ( ! $provider->is_enabled() ) {
				continue;
			}

			$count = $provider->get_total_pages();

			for ( $page = 1; $page <= $count; $page++ ) {
				$url = $this->get_sitemap_url( $name, $page );
				echo "\t<sitemap>\n";
				echo "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
				echo "\t\t<lastmod>" . esc_html( wp_date( 'c' ) ) . "</lastmod>\n";
				echo "\t</sitemap>\n";
			}
		}

		echo '</sitemapindex>';
	}

	/**
	 * Render individual sitemap.
	 *
	 * @param string $name Sitemap name.
	 * @param int    $page Page number.
	 * @return void
	 */
	private function render_sitemap( string $name, int $page ): void {
		$provider = $this->providers[ $name ];

		if ( ! $provider->is_enabled() ) {
			status_header( 404 );
			return;
		}

		$items = $provider->get_items( $page );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $items as $item ) {
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( $item['loc'] ) . "</loc>\n";

			if ( ! empty( $item['lastmod'] ) ) {
				echo "\t\t<lastmod>" . esc_html( $item['lastmod'] ) . "</lastmod>\n";
			}

			if ( ! empty( $item['changefreq'] ) ) {
				echo "\t\t<changefreq>" . esc_html( $item['changefreq'] ) . "</changefreq>\n";
			}

			if ( ! empty( $item['priority'] ) ) {
				echo "\t\t<priority>" . esc_html( $item['priority'] ) . "</priority>\n";
			}

			echo "\t</url>\n";
		}

		echo '</urlset>';
	}

	/**
	 * Get sitemap URL.
	 *
	 * @param string $name Sitemap name.
	 * @param int    $page Page number.
	 * @return string
	 */
	private function get_sitemap_url( string $name, int $page = 1 ): string {
		if ( $page > 1 ) {
			return home_url( "/sitemap-{$name}-{$page}.xml" );
		}

		return home_url( "/sitemap-{$name}.xml" );
	}

	/**
	 * Get sitemap index URL.
	 *
	 * @return string
	 */
	public static function get_index_url(): string {
		return home_url( '/sitemap.xml' );
	}

	/**
	 * Flush rewrite rules on activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$sitemap = new self();
		$sitemap->add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
