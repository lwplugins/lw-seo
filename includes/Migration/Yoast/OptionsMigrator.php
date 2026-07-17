<?php
/**
 * Yoast Options Migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Options;

/**
 * Migrates Yoast options (wpseo_titles, wpseo_social) to LW SEO options.
 */
final class OptionsMigrator {

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
	 * Run options migration.
	 *
	 * @return array{count: int, details: array<string>}
	 */
	public function migrate(): array {
		$lw      = get_option( Options::OPTION_NAME, [] );
		$titles  = get_option( 'wpseo_titles', [] );
		$social  = get_option( 'wpseo_social', [] );
		$titles  = is_array( $titles ) ? $titles : [];
		$social  = is_array( $social ) ? $social : [];
		$lw      = is_array( $lw ) ? $lw : [];
		$details = [];
		$count   = 0;

		$count += $this->migrate_titles( $titles, $lw, $details );
		$count += $this->migrate_noindex( $titles, $lw, $details );
		$count += ( new KnowledgeMigrator() )->migrate( $titles, $lw, $details );
		$count += $this->migrate_social( $social, $lw, $details );
		$this->set_feature_flags( $titles, $social, $lw );

		if ( ! $this->dry_run ) {
			update_option( Options::OPTION_NAME, $lw );
		}

		return [
			'count'   => $count,
			'details' => $details,
		];
	}

	/**
	 * Whether an LW option slot is already filled (never overwrite).
	 *
	 * @param array  $lw  LW options.
	 * @param string $key LW option key.
	 * @return bool
	 */
	private function is_filled( array $lw, string $key ): bool {
		return isset( $lw[ $key ] ) && '' !== $lw[ $key ];
	}

	/**
	 * Migrate title/description templates and the separator.
	 *
	 * @param array         $titles  Yoast wpseo_titles.
	 * @param array         $lw      LW options (by reference).
	 * @param array<string> $details Details log (by reference).
	 * @return int
	 */
	private function migrate_titles( array $titles, array &$lw, array &$details ): int {
		$count = 0;

		foreach ( Mappings::TITLE_OPTIONS_MAP as $yoast_key => $lw_key ) {
			if ( ! isset( $titles[ $yoast_key ] ) || '' === $titles[ $yoast_key ] || $this->is_filled( $lw, $lw_key ) ) {
				continue;
			}

			$value = (string) $titles[ $yoast_key ];
			if ( 'separator' === $lw_key ) {
				$value = SeparatorMap::to_char( $value );
			} elseif ( str_starts_with( $lw_key, 'title_' ) || 'desc_home' === $lw_key ) {
				$value = VariableConverter::convert( $value );
			}

			$lw[ $lw_key ] = $value;
			$details[]     = $yoast_key . ' -> ' . $lw_key;
			++$count;
		}

		return $count;
	}

	/**
	 * Migrate per-type noindex flags (bool cast).
	 *
	 * @param array         $titles  Yoast wpseo_titles.
	 * @param array         $lw      LW options (by reference).
	 * @param array<string> $details Details log (by reference).
	 * @return int
	 */
	private function migrate_noindex( array $titles, array &$lw, array &$details ): int {
		$count = 0;

		foreach ( Mappings::NOINDEX_OPTIONS_MAP as $yoast_key => $lw_key ) {
			if ( ! isset( $titles[ $yoast_key ] ) || $this->is_filled( $lw, $lw_key ) ) {
				continue;
			}

			$lw[ $lw_key ] = (bool) $titles[ $yoast_key ];
			$details[]     = $yoast_key . ' -> ' . $lw_key;
			++$count;
		}

		return $count;
	}

	/**
	 * Migrate social profile URLs and defaults.
	 *
	 * @param array         $social  Yoast wpseo_social.
	 * @param array         $lw      LW options (by reference).
	 * @param array<string> $details Details log (by reference).
	 * @return int
	 */
	private function migrate_social( array $social, array &$lw, array &$details ): int {
		$count = 0;

		foreach ( Mappings::SOCIAL_OPTIONS_MAP as $yoast_key => $lw_key ) {
			if ( ! isset( $social[ $yoast_key ] ) || '' === $social[ $yoast_key ] || $this->is_filled( $lw, $lw_key ) ) {
				continue;
			}

			$lw[ $lw_key ] = (string) $social[ $yoast_key ];
			$details[]     = $yoast_key . ' -> ' . $lw_key;
			++$count;
		}

		return $count;
	}

	/**
	 * Set OpenGraph/Twitter/sitemap feature flags from Yoast state.
	 *
	 * @param array $titles Yoast wpseo_titles.
	 * @param array $social Yoast wpseo_social.
	 * @param array $lw     LW options (by reference).
	 * @return void
	 */
	private function set_feature_flags( array $titles, array $social, array &$lw ): void {
		if ( isset( $social['opengraph'] ) && ! $this->is_filled( $lw, 'opengraph_enabled' ) ) {
			$lw['opengraph_enabled'] = (bool) $social['opengraph'];
		}

		if ( isset( $social['twitter'] ) && ! $this->is_filled( $lw, 'twitter_enabled' ) ) {
			$lw['twitter_enabled'] = (bool) $social['twitter'];
		}

		if ( ! empty( $titles ) && ! $this->is_filled( $lw, 'sitemap_enabled' ) ) {
			$lw['sitemap_enabled'] = true;
		}
	}
}
