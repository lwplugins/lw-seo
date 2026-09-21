<?php
/**
 * Accept header negotiation.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Decides from the Accept header whether the client prefers Markdown.
 */
final class AcceptNegotiator {

	/**
	 * Markdown wins when its q-value is above 0 and not below text/html.
	 *
	 * @param string $accept Accept header value.
	 * @return bool
	 */
	public static function prefers_markdown( string $accept ): bool {
		$qualities = self::qualities( $accept );
		$markdown  = $qualities['text/markdown'] ?? 0.0;

		return $markdown > 0.0 && $markdown >= ( $qualities['text/html'] ?? 0.0 );
	}

	/**
	 * Media type => q-value (highest wins on duplicates).
	 *
	 * @param string $accept Accept header value.
	 * @return array<string, float>
	 */
	public static function qualities( string $accept ): array {
		$result = [];

		foreach ( explode( ',', $accept ) as $part ) {
			$params = array_map( 'trim', explode( ';', $part ) );
			$type   = strtolower( (string) array_shift( $params ) );
			if ( '' === $type ) {
				continue;
			}

			$quality = 1.0;
			foreach ( $params as $param ) {
				if ( str_starts_with( strtolower( $param ), 'q=' ) ) {
					$quality = max( 0.0, min( 1.0, (float) substr( $param, 2 ) ) );
				}
			}

			$result[ $type ] = max( $result[ $type ] ?? 0.0, $quality );
		}

		return $result;
	}
}
