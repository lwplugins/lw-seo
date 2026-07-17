<?php
/**
 * Yoast Migrator orchestrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\WooCommerce\SlugCollisionDetector;

/**
 * Wires together all Yoast sub-migrators and surfaces detection counts +
 * warnings for the migration UI. Output shape matches RankMath\Migrator.
 */
final class Migrator implements MigratorInterface {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Run the full migration.
	 *
	 * @return array<string, mixed>
	 */
	public function run(): array {
		$options       = ( new OptionsMigrator( $this->dry_run ) )->migrate();
		$posts         = ( new PostMetaMigrator( $this->dry_run ) )->migrate();
		$terms         = ( new TermMetaMigrator( $this->dry_run ) )->migrate();
		$primary_terms = ( new PrimaryTermMigrator( $this->dry_run ) )->migrate();
		$redirects     = ( new RedirectsMigrator( $this->dry_run ) )->migrate();
		$warnings      = ( new WarningCollector() )->collect();

		if ( ! $this->dry_run ) {
			SlugCollisionDetector::invalidate();
			flush_rewrite_rules( false );
		}

		return [
			'dry_run'          => $this->dry_run,
			'options_migrated' => $options['count'],
			'options_details'  => $options['details'],
			'posts'            => $posts,
			'terms'            => $terms,
			'users'            => [ 'migrated' => 0 ],
			'primary_terms'    => $primary_terms,
			'redirects'        => $redirects,
			'warnings'         => $warnings,
		];
	}

	/**
	 * Detect available Yoast data for migration.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array {
		global $wpdb;

		$has_options = ! empty( get_option( 'wpseo_titles', [] ) ) || ! empty( get_option( 'wpseo_social', [] ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time detection.
		$post_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key LIKE '_yoast_wpseo_%'"
		);

		$tax_meta   = get_option( 'wpseo_taxonomy_meta', [] );
		$term_count = 0;
		if ( is_array( $tax_meta ) ) {
			foreach ( $tax_meta as $terms ) {
				$term_count += is_array( $terms ) ? count( $terms ) : 0;
			}
		}

		$redirects_count = ( new RedirectsMigrator() )->count();
		$warnings        = ( new WarningCollector() )->collect();

		return [
			'found'           => $has_options || $post_count > 0 || $term_count > 0 || $redirects_count > 0,
			'has_options'     => $has_options,
			'post_count'      => $post_count,
			'term_count'      => $term_count,
			'user_count'      => 0,
			'redirects_count' => $redirects_count,
			'warnings'        => $warnings,
		];
	}
}
