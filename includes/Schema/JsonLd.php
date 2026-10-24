<?php
/**
 * Shared JSON-LD encoder.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Schema;

/**
 * Encodes schema data for output inside a JSON-LD script element.
 */
final class JsonLd {

	/**
	 * Encode schema data as JSON-LD.
	 *
	 * Markup-significant characters (<, >, &) are written as \u escapes so the
	 * JSON stays inert inside the script element; slashes and Unicode stay readable.
	 *
	 * @param array<mixed> $schema Schema data.
	 * @return string
	 */
	public static function encode( array $schema ): string {
		return (string) wp_json_encode(
			$schema,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP
		);
	}
}
