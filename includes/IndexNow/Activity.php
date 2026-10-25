<?php
/**
 * IndexNow activity: whether this site may notify search engines.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

use LightweightPlugins\SEO\Options;

/**
 * IndexNow runs only when it is switched on AND the site is meant to be
 * indexed: search engines are not discouraged (Settings → Reading) and the
 * environment type is production. A copied database (staging, local) keeps
 * the switch and the key, so the switch alone is not enough.
 */
final class Activity {

	/**
	 * The feature is switched off.
	 */
	public const REASON_DISABLED = 'disabled';

	/**
	 * "Discourage search engines from indexing this site" is set.
	 */
	public const REASON_NOT_PUBLIC = 'not_public';

	/**
	 * The environment type is not production.
	 */
	public const REASON_ENVIRONMENT = 'environment';

	/**
	 * The lw_seo_indexnow_active filter turned it off.
	 */
	public const REASON_FILTER = 'filter';

	/**
	 * Whether IndexNow may queue, send and serve its key file now.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return '' === self::reason();
	}

	/**
	 * Why IndexNow is inactive ('' when it is active).
	 *
	 * @return string One of the REASON_* constants, or ''.
	 */
	public static function reason(): string {
		if ( ! Options::get( 'indexnow_enabled' ) ) {
			return self::REASON_DISABLED;
		}

		$reason = '';
		if ( '0' === (string) get_option( 'blog_public' ) ) {
			$reason = self::REASON_NOT_PUBLIC;
		} elseif ( 'production' !== wp_get_environment_type() ) {
			$reason = self::REASON_ENVIRONMENT;
		}

		/**
		 * Filters whether IndexNow may notify search engines from this site.
		 * Only consulted while the feature is switched on.
		 *
		 * @param bool   $active Default: the site is public and production.
		 * @param string $reason Why it is inactive by default ('' when active).
		 */
		$active = (bool) apply_filters( 'lw_seo_indexnow_active', '' === $reason, $reason );

		if ( $active ) {
			return '';
		}

		return '' === $reason ? self::REASON_FILTER : $reason;
	}

	/**
	 * Human-readable explanation of a reason.
	 *
	 * @param string $reason One of the REASON_* constants.
	 * @return string '' for an active site.
	 */
	public static function message( string $reason ): string {
		return match ( $reason ) {
			self::REASON_DISABLED    => __( 'IndexNow is turned off.', 'lw-seo' ),
			self::REASON_NOT_PUBLIC  => __( 'IndexNow is paused: search engines are discouraged from indexing this site (Settings → Reading).', 'lw-seo' ),
			self::REASON_ENVIRONMENT => sprintf(
				/* translators: %s: environment type, e.g. staging. */
				__( 'IndexNow is paused: this is a "%s" environment, not production.', 'lw-seo' ),
				wp_get_environment_type()
			),
			self::REASON_FILTER      => __( 'IndexNow is paused by the lw_seo_indexnow_active filter.', 'lw-seo' ),
			default                  => '',
		};
	}
}
