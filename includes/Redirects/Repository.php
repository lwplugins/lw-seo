<?php
/**
 * Redirects addressed by stable id.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Id-based access to the redirect list for the admin API.
 *
 * Manager (and the frontend Handler) address redirects by list position,
 * which shifts on delete. Every item also carries a stable string `id`;
 * items stored before ids existed get one the first time they are read
 * here, and it is persisted.
 */
final class Repository {

	/**
	 * Every redirect, formatted.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		return array_values( array_map( [ self::class, 'format' ], self::stored() ) );
	}

	/**
	 * One redirect by id.
	 *
	 * @param string $id Redirect id.
	 * @return array<string, mixed>|null
	 */
	public static function find( string $id ): ?array {
		$index = self::index_of( $id );

		return null === $index ? null : self::format( self::stored()[ $index ] );
	}

	/**
	 * Add a redirect (input already validated).
	 *
	 * @param string $source      Source path or pattern.
	 * @param string $destination Destination URL.
	 * @param int    $type        Redirect type.
	 * @param bool   $regex       Regex source.
	 * @return array<string, mixed>|null The created item, null on failure.
	 */
	public static function create( string $source, string $destination, int $type, bool $regex ): ?array {
		$index = Manager::add( $source, $destination, $type, $regex );
		if ( false === $index ) {
			return null;
		}

		$item = self::stored()[ $index ] ?? null;

		return null === $item ? null : self::format( $item );
	}

	/**
	 * Update a redirect (input already validated). An unchanged update
	 * succeeds.
	 *
	 * @param string $id          Redirect id.
	 * @param string $source      Source path or pattern.
	 * @param string $destination Destination URL.
	 * @param int    $type        Redirect type.
	 * @param bool   $regex       Regex source.
	 * @return array<string, mixed>|null The updated item, null when not found.
	 */
	public static function update( string $id, string $source, string $destination, int $type, bool $regex ): ?array {
		$index = self::index_of( $id );
		if ( null === $index ) {
			return null;
		}

		// False also means "nothing changed", which is not an error here.
		Manager::update( $index, $source, $destination, $type, $regex );

		return self::find( $id );
	}

	/**
	 * Delete a redirect.
	 *
	 * @param string $id Redirect id.
	 * @return bool False when not found.
	 */
	public static function delete( string $id ): bool {
		$index = self::index_of( $id );

		return null !== $index && Manager::delete( $index );
	}

	/**
	 * Give every item without an id a new one.
	 *
	 * @param array<int|string, mixed> $redirects Stored list.
	 * @return array<int|string, mixed>
	 */
	public static function with_ids( array $redirects ): array {
		foreach ( $redirects as $index => $redirect ) {
			if ( ! is_array( $redirect ) ) {
				unset( $redirects[ $index ] );
				continue;
			}
			if ( empty( $redirect['id'] ) || ! is_string( $redirect['id'] ) ) {
				$redirects[ $index ]['id'] = wp_generate_uuid4();
			}
		}

		return $redirects;
	}

	/**
	 * API shape of one stored item.
	 *
	 * @param array<string, mixed> $item Stored item.
	 * @return array{id: string, source: string, destination: string, type: int, regex: bool, hits: int, last_accessed: string, created: string}
	 */
	public static function format( array $item ): array {
		return [
			'id'            => (string) ( $item['id'] ?? '' ),
			'source'        => (string) ( $item['source'] ?? '' ),
			'destination'   => (string) ( $item['destination'] ?? '' ),
			'type'          => (int) ( $item['type'] ?? 301 ),
			'regex'         => ! empty( $item['regex'] ),
			'hits'          => (int) ( $item['hits'] ?? 0 ),
			'last_accessed' => (string) ( $item['last_accessed'] ?? '' ),
			'created'       => (string) ( $item['created'] ?? '' ),
		];
	}

	/**
	 * Stored list with ids ensured (persisted when any were missing).
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function stored(): array {
		$redirects = Manager::get_all();
		$with_ids  = self::with_ids( $redirects );

		if ( $with_ids !== $redirects ) {
			update_option( Manager::OPTION_NAME, $with_ids, false );
		}

		return $with_ids;
	}

	/**
	 * List position of an id.
	 *
	 * @param string $id Redirect id.
	 * @return int|null
	 */
	private static function index_of( string $id ): ?int {
		if ( '' === $id ) {
			return null;
		}

		foreach ( self::stored() as $index => $redirect ) {
			if ( ( $redirect['id'] ?? '' ) === $id ) {
				return (int) $index;
			}
		}

		return null;
	}
}
