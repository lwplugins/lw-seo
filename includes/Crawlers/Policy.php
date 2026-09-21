<?php
/**
 * AI crawler blocking policy.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Crawlers;

/**
 * Decides which user agents get a "Disallow: /" group.
 */
final class Policy {

	/**
	 * Agents blocked individually or through one of their purposes.
	 *
	 * @param array<string, array{agent: string, purposes: array<int, string>}> $crawlers Registry.
	 * @param array<string, mixed>                                              $options  Plugin options.
	 * @return array<int, string>
	 */
	public static function blocked_agents( array $crawlers, array $options ): array {
		$agents = [];

		foreach ( $crawlers as $key => $crawler ) {
			if ( ! empty( $options[ 'block_' . $key ] ) || self::purpose_blocked( $crawler['purposes'], $options ) ) {
				$agents[] = $crawler['agent'];
			}
		}

		return array_values( array_unique( $agents ) );
	}

	/**
	 * Whether any of the purposes is blocked.
	 *
	 * @param array<int, string>   $purposes Purposes.
	 * @param array<string, mixed> $options  Plugin options.
	 * @return bool
	 */
	private static function purpose_blocked( array $purposes, array $options ): bool {
		foreach ( $purposes as $purpose ) {
			if ( ! empty( $options[ 'block_purpose_' . $purpose ] ) ) {
				return true;
			}
		}

		return false;
	}
}
