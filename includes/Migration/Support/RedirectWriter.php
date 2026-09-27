<?php
/**
 * Adds imported redirects to the LW SEO redirect manager.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Redirects\Validator;

/**
 * Validates and adds redirects, skipping sources LW SEO already has so a
 * repeated import never creates duplicates.
 */
final class RedirectWriter {

	/**
	 * Result: redirect added (or would be, in a dry run).
	 */
	public const ADDED = 'added';

	/**
	 * Result: LW SEO already has a redirect for this source.
	 */
	public const PRESENT = 'present';

	/**
	 * Result: the source data is not a usable redirect.
	 */
	public const INVALID = 'invalid';

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Known sources ("r:" / "p:" + stored source), filled lazily.
	 *
	 * @var array<string, true>|null
	 */
	private ?array $known = null;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Add one redirect.
	 *
	 * @param string $source      Source path or pattern.
	 * @param string $destination Destination URL ('' for 410/451).
	 * @param int    $type        HTTP status.
	 * @param bool   $regex       Whether the source is a regex.
	 * @return string One of the result constants.
	 */
	public function add( string $source, string $destination, int $type, bool $regex ): string {
		if ( ! isset( Manager::TYPES[ $type ] ) || null !== Validator::error( $source, $destination, $type, $regex ) ) {
			return self::INVALID;
		}

		$key = $this->key( $source, $regex );
		if ( isset( $this->known()[ $key ] ) ) {
			return self::PRESENT;
		}

		if ( ! $this->dry_run && false === Manager::add( $source, $destination, $type, $regex ) ) {
			return self::INVALID;
		}

		$this->known[ $key ] = true;

		return self::ADDED;
	}

	/**
	 * Sources LW SEO already redirects.
	 *
	 * @return array<string, true>
	 */
	private function known(): array {
		if ( null === $this->known ) {
			$this->known = [];
			foreach ( Manager::get_all() as $redirect ) {
				$this->known[ $this->key( $redirect['source'], $redirect['regex'] ) ] = true;
			}
		}

		return $this->known;
	}

	/**
	 * Comparable key of a source, as Manager stores it.
	 *
	 * @param string $source Source.
	 * @param bool   $regex  Whether it is a regex.
	 * @return string
	 */
	private function key( string $source, bool $regex ): string {
		return $regex ? 'r:' . trim( $source ) : 'p:' . Manager::normalize_source( trim( $source ) );
	}
}
