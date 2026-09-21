<?php
/**
 * Per-version upgrade routine.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Runs option migrations and a rewrite flush once after each version change.
 *
 * Activation hooks do not fire on plugin updates, so changed rewrite rules
 * and renamed options would otherwise stay stale until a manual flush.
 */
final class Upgrader {

	/**
	 * Option holding the plugin version the stored data belongs to.
	 */
	public const VERSION_OPTION = 'lw_seo_version';

	/**
	 * Register the upgrade check.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'maybe_upgrade' ], 1 );
	}

	/**
	 * Migrate stored data when the plugin version changed.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		$stored = (string) get_option( self::VERSION_OPTION, '' );

		if ( LW_SEO_VERSION === $stored ) {
			return;
		}

		$saved = get_option( Options::OPTION_NAME, [] );
		if ( is_array( $saved ) && [] !== $saved ) {
			$migrated = self::migrate_options( $saved, $stored );
			if ( $migrated !== $saved ) {
				update_option( Options::OPTION_NAME, $migrated );
				Options::clear_cache();
			}
		}

		update_option( self::VERSION_OPTION, LW_SEO_VERSION );

		// Rewrite rules can change between versions; RewriteFlusher rebuilds
		// them on wp_loaded, after every init callback registered its rules.
		set_transient( RewriteFlusher::FLAG, 1, HOUR_IN_SECONDS );
	}

	/**
	 * Apply every migration newer than the version the data was written by.
	 *
	 * @param array<string, mixed> $options Saved options.
	 * @param string               $from    Stored version ('' = before 1.6.0).
	 * @return array<string, mixed>
	 */
	public static function migrate_options( array $options, string $from ): array {
		if ( '' === $from || version_compare( $from, '1.6.0', '<' ) ) {
			$options = self::to_160( $options );
		}

		return $options;
	}

	/**
	 * 1.6.0 option migrations.
	 *
	 * @param array<string, mixed> $options Saved options.
	 * @return array<string, mixed>
	 */
	private static function to_160( array $options ): array {
		// Anthropic retired Claude-Web; ClaudeBot is the crawler to block.
		if ( ! empty( $options['block_claude_web'] ) ) {
			$options['block_claudebot'] = true;
		}

		// Neither token is documented by its vendor any more.
		unset( $options['block_claude_web'], $options['block_cohere_ai'] );

		// Content signals became three-state: keep what existing sites emit.
		foreach ( SignalValue::KEYS as $option ) {
			if ( isset( $options[ $option ] ) && is_bool( $options[ $option ] ) ) {
				$options[ $option ] = $options[ $option ] ? 'yes' : 'no';
			}
		}

		return $options;
	}
}
