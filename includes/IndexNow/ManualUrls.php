<?php
/**
 * Manually entered IndexNow URLs.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

/**
 * Splits hand-typed URLs into accepted (this site's host) and rejected ones.
 */
final class ManualUrls {

	/**
	 * Clean a URL list: trims, drops blanks, dedupes, and keeps only URLs on
	 * this site's host (case-insensitive).
	 *
	 * @param array<int, mixed> $urls Raw URLs.
	 * @return array{accepted: array<int, string>, rejected: array<int, string>}
	 */
	public static function split( array $urls ): array {
		$host     = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$accepted = [];
		$rejected = [];

		foreach ( $urls as $url ) {
			$url = trim( (string) $url );
			if ( '' === $url ) {
				continue;
			}

			$url_host = wp_parse_url( $url, PHP_URL_HOST );
			$same     = is_string( $url_host ) && '' !== $host && strtolower( $url_host ) === $host;
			if ( $same && 1 === preg_match( '#^https?://#i', $url ) ) {
				$accepted[] = $url;
			} else {
				$rejected[] = $url;
			}
		}

		return [
			'accepted' => array_values( array_unique( $accepted ) ),
			'rejected' => array_values( array_unique( $rejected ) ),
		];
	}
}
