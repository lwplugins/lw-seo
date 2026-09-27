<?php
/**
 * Writes resolved SEO fields into LW SEO post or term meta.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Copies already-resolved values (LW field name => value) into `_lw_seo_*`
 * meta, never overwriting a filled LW SEO value.
 *
 * The importers resolve the source plugin's data shape (fallbacks, variable
 * conversion, robots semantics) first; this class only owns the write rules:
 * skip empty and non-string values, keep URL fields to usable URLs, respect
 * existing data and the dry-run flag.
 */
final class MetaWriter {

	/**
	 * LW SEO fields holding a URL.
	 */
	private const URL_FIELDS = [ 'og_image', 'canonical' ];

	/**
	 * Meta type: 'post' or 'term'.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param string $type    'post' or 'term'.
	 * @param bool   $dry_run Whether to simulate without making changes.
	 */
	public function __construct( string $type, bool $dry_run = false ) {
		$this->type    = 'term' === $type ? 'term' : 'post';
		$this->dry_run = $dry_run;
	}

	/**
	 * Write the given fields for one object.
	 *
	 * @param int                  $id     Post or term ID.
	 * @param array<string, mixed> $fields LW field (without prefix) => value.
	 * @return array{migrated: bool, target_full: bool}
	 */
	public function write( int $id, array $fields ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( $fields as $field => $value ) {
			$value = $this->usable_value( (string) $field, $value );
			if ( '' === $value ) {
				continue;
			}

			$key = Options::META_PREFIX . $field;
			if ( $this->is_filled( $this->read( $id, $key ) ) ) {
				$target_full = true;
				continue;
			}

			if ( ! $this->dry_run ) {
				$this->store( $id, $key, $value );
			}
			$migrated = true;
		}

		return [
			'migrated'    => $migrated,
			'target_full' => $target_full,
		];
	}

	/**
	 * The value as a writable string, '' when it must not be written.
	 *
	 * @param string $field LW field name.
	 * @param mixed  $value Resolved source value.
	 * @return string
	 */
	private function usable_value( string $field, mixed $value ): string {
		if ( ! MetaCoerce::is_writable_string( $value, $field ) ) {
			return '';
		}

		$value = in_array( $field, self::URL_FIELDS, true ) ? MetaCoerce::as_url( $value ) : MetaCoerce::as_string( $value );

		return trim( $value );
	}

	/**
	 * Whether an existing LW SEO meta value counts as filled.
	 *
	 * @param mixed $existing Stored value.
	 * @return bool
	 */
	private function is_filled( mixed $existing ): bool {
		return '' !== $existing && false !== $existing && null !== $existing;
	}

	/**
	 * Read one meta value.
	 *
	 * @param int    $id  Object ID.
	 * @param string $key Meta key.
	 * @return mixed
	 */
	private function read( int $id, string $key ): mixed {
		return 'term' === $this->type ? get_term_meta( $id, $key, true ) : get_post_meta( $id, $key, true );
	}

	/**
	 * Store one meta value.
	 *
	 * @param int    $id    Object ID.
	 * @param string $key   Meta key.
	 * @param string $value Value.
	 * @return void
	 */
	private function store( int $id, string $key, string $value ): void {
		if ( 'term' === $this->type ) {
			update_term_meta( $id, $key, $value );
			return;
		}

		update_post_meta( $id, $key, $value );
	}
}
