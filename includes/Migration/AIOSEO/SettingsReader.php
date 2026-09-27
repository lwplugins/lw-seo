<?php
/**
 * Reads All in One SEO settings from their stored JSON.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

/**
 * Read-only access to All in One SEO's settings.
 *
 * The aioseo_options and aioseo_options_dynamic options hold the whole settings tree as
 * JSON with plain leaf values (app/Common/Traits/Options.php:748, 968-1002).
 * For "localized" settings a non-empty entry in aioseo_options_localized /
 * aioseo_options_dynamic_localized under the key "group_sub_name" wins
 * (Traits/Options.php:206-218). Keys missing from the stored tree mean the
 * All in One SEO default, so callers pass the default they need.
 */
final class SettingsReader {

	/**
	 * Decoded aioseo_options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options;

	/**
	 * Decoded aioseo_options_dynamic.
	 *
	 * @var array<string, mixed>
	 */
	private array $dynamic;

	/**
	 * Localized overrides (both option names merged).
	 *
	 * @var array<string, mixed>
	 */
	private array $localized;

	/**
	 * Constructor.
	 *
	 * @param mixed $options   Raw aioseo_options value.
	 * @param mixed $dynamic   Raw aioseo_options_dynamic value.
	 * @param mixed $localized Raw localized overrides (array of both options merged).
	 */
	public function __construct( mixed $options, mixed $dynamic, mixed $localized = [] ) {
		$this->options   = self::decode( $options );
		$this->dynamic   = self::decode( $dynamic );
		$this->localized = is_array( $localized ) ? $localized : [];
	}

	/**
	 * Reader over the stored options of this site.
	 *
	 * @return self
	 */
	public static function from_site(): self {
		$localized = get_option( 'aioseo_options_localized', [] );
		$dynamic   = get_option( 'aioseo_options_dynamic_localized', [] );

		return new self(
			get_option( 'aioseo_options', '' ),
			get_option( 'aioseo_options_dynamic', '' ),
			array_merge( is_array( $localized ) ? $localized : [], is_array( $dynamic ) ? $dynamic : [] )
		);
	}

	/**
	 * Whether any settings are stored.
	 *
	 * @return bool
	 */
	public function has_settings(): bool {
		return ! empty( $this->options ) || ! empty( $this->dynamic );
	}

	/**
	 * A value from aioseo_options.
	 *
	 * @param string $path     Dot path, e.g. "searchAppearance.global.separator".
	 * @param mixed  $fallback Value when the path is missing.
	 * @return mixed
	 */
	public function get( string $path, mixed $fallback = null ): mixed {
		return $this->lookup( $this->options, $path, $fallback );
	}

	/**
	 * A value from aioseo_options_dynamic.
	 *
	 * @param string $path     Dot path, e.g. "searchAppearance.postTypes.post.title".
	 * @param mixed  $fallback Value when the path is missing.
	 * @return mixed
	 */
	public function dynamic( string $path, mixed $fallback = null ): mixed {
		return $this->lookup( $this->dynamic, $path, $fallback );
	}

	/**
	 * A localizable text setting: the localized override when set, else the stored value.
	 *
	 * @param string $path    Dot path.
	 * @param bool   $dynamic Whether the path is in aioseo_options_dynamic.
	 * @return mixed
	 */
	public function text( string $path, bool $dynamic = false ): mixed {
		$key = str_replace( '.', '_', $path );
		if ( isset( $this->localized[ $key ] ) && is_string( $this->localized[ $key ] ) && '' !== $this->localized[ $key ] ) {
			return $this->localized[ $key ];
		}

		return $dynamic ? $this->dynamic( $path ) : $this->get( $path );
	}

	/**
	 * Resolved noindex of a robots-meta settings group.
	 *
	 * Mirrors Meta/Robots.php: show=false forces noindex; a group left on
	 * "use default" inherits the global robots settings.
	 *
	 * @param string $base    Dot path of the group, e.g. "searchAppearance.postTypes.post".
	 * @param bool   $dynamic Whether the group is in aioseo_options_dynamic.
	 * @return bool|null Null when nothing is stored for the group.
	 */
	public function noindex( string $base, bool $dynamic = true ): ?bool {
		$group = $dynamic ? $this->dynamic( $base ) : $this->get( $base );
		if ( ! is_array( $group ) ) {
			return null;
		}

		if ( array_key_exists( 'show', $group ) && ! $group['show'] ) {
			return true;
		}

		$robots = $group['advanced']['robotsMeta'] ?? null;
		if ( ! is_array( $robots ) ) {
			return null;
		}

		if ( ! empty( $robots['default'] ) ) {
			$global = $this->get( 'searchAppearance.advanced.globalRobotsMeta' );
			return is_array( $global ) && empty( $global['default'] ) && ! empty( $global['noindex'] );
		}

		return ! empty( $robots['noindex'] );
	}

	/**
	 * Decode a stored JSON option.
	 *
	 * @param mixed $raw Raw option value.
	 * @return array<string, mixed>
	 */
	private static function decode( mixed $raw ): array {
		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}

		$decoded = json_decode( $raw, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Walk a dot path.
	 *
	 * @param array<string, mixed> $tree     Tree.
	 * @param string               $path     Dot path.
	 * @param mixed                $fallback Fallback.
	 * @return mixed
	 */
	private function lookup( array $tree, string $path, mixed $fallback ): mixed {
		$node = $tree;
		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $node ) || ! array_key_exists( $segment, $node ) ) {
				return $fallback;
			}
			$node = $node[ $segment ];
		}

		return $node;
	}
}
