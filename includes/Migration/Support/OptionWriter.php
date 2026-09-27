<?php
/**
 * Collects imported values for lw_seo_options.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

/**
 * Applies imported option values to a copy of the LW SEO options without
 * overwriting anything the site owner customised.
 *
 * Many sites store the whole LW SEO option array, defaults included, so "set"
 * cannot mean "not customised". A slot counts as customised when it holds a
 * non-empty value that differs from the LW SEO default. Values equal to the
 * current one are not counted, so a second run reports 0.
 */
final class OptionWriter {

	/**
	 * LW SEO options being built.
	 *
	 * @var array<string, mixed>
	 */
	private array $options;

	/**
	 * LW SEO default options.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults;

	/**
	 * "source -> target" log lines.
	 *
	 * @var array<string>
	 */
	private array $details = [];

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $options  Current stored LW SEO options.
	 * @param array<string, mixed> $defaults LW SEO default options.
	 */
	public function __construct( array $options, array $defaults ) {
		$this->options  = $options;
		$this->defaults = $defaults;
	}

	/**
	 * Import one value.
	 *
	 * @param string $key    LW SEO option key.
	 * @param mixed  $value  Converted value; null or '' means "nothing to import".
	 * @param string $source Source setting label for the log.
	 * @return bool Whether the value was applied.
	 */
	public function set( string $key, mixed $value, string $source ): bool {
		if ( null === $value || '' === $value ) {
			return false;
		}

		if ( array_key_exists( $key, $this->options ) && $this->options[ $key ] === $value ) {
			return false;
		}

		if ( $this->is_customised( $key ) ) {
			return false;
		}

		$this->options[ $key ] = $value;
		$this->details[]       = $source . ' -> ' . $key;

		return true;
	}

	/**
	 * Whether the site owner changed this option from the LW SEO default.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public function is_customised( string $key ): bool {
		if ( ! array_key_exists( $key, $this->options ) ) {
			return false;
		}

		$current = $this->options[ $key ];
		if ( null === $current || '' === $current ) {
			return false;
		}

		return ! array_key_exists( $key, $this->defaults ) || $current !== $this->defaults[ $key ];
	}

	/**
	 * The resulting options.
	 *
	 * @return array<string, mixed>
	 */
	public function options(): array {
		return $this->options;
	}

	/**
	 * Log lines of applied values.
	 *
	 * @return array<string>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Number of applied values.
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->details );
	}
}
