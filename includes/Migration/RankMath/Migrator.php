<?php
/**
 * RankMath Migrator orchestrator class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

/**
 * Main orchestrator for RankMath to LW SEO migration.
 */
final class Migrator {

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
		$options_migrator = new OptionsMigrator( $this->dry_run );
		$meta_migrator    = new MetaMigrator( $this->dry_run );

		$options_result = $options_migrator->migrate();
		$posts_result   = $meta_migrator->migrate_posts();
		$terms_result   = $meta_migrator->migrate_terms();
		$users_result   = $meta_migrator->migrate_users();

		return [
			'dry_run'          => $this->dry_run,
			'options_migrated' => $options_result['count'],
			'options_details'  => $options_result['details'],
			'posts_migrated'   => $posts_result['migrated'],
			'posts_skipped'    => $posts_result['skipped'],
			'terms_migrated'   => $terms_result['migrated'],
			'terms_skipped'    => $terms_result['skipped'],
			'users_migrated'   => $users_result['migrated'],
		];
	}

	/**
	 * Detect available RankMath data for migration.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array {
		global $wpdb;

		$rm_titles  = get_option( 'rank-math-options-titles', [] );
		$rm_general = get_option( 'rank-math-options-general', [] );
		$rm_sitemap = get_option( 'rank-math-options-sitemap', [] );

		$has_options = ! empty( $rm_titles ) || ! empty( $rm_general ) || ! empty( $rm_sitemap );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration detection.
		$post_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE 'rank_math_%'
			 AND meta_key NOT LIKE 'rank_math_internal%'
			 AND meta_key NOT LIKE 'rank_math_seo_score%'"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration detection.
		$term_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT term_id) FROM {$wpdb->termmeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration detection.
		$user_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);

		return [
			'found'       => $has_options || $post_count > 0 || $term_count > 0 || $user_count > 0,
			'has_options' => $has_options,
			'post_count'  => $post_count,
			'term_count'  => $term_count,
			'user_count'  => $user_count,
		];
	}
}
