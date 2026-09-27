<?php
/**
 * SEOPress redirects migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\RedirectWriter;

/**
 * Imports SEOPress redirects into the LW SEO redirect manager:
 *
 *   - SEOPress PRO: published `seopress_404` posts whose `_seopress_redirections_type`
 *     is set. The origin is the post title (a path without the leading slash, or a
 *     pattern when `_seopress_redirections_enabled_regex` = 'yes'); 404 log entries
 *     have no type and are ignored (wp-seopress-pro src/Services/Redirection.php).
 *   - SEOPress free: the per-post / per-term redirect (`_seopress_redirections_*`
 *     meta), from the object's URL to `_seopress_redirections_value`
 *     (inc/functions/options-redirections.php:171-201).
 *
 * Only enabled redirects ('yes') are imported, and not the ones limited to
 * logged-in users (LW SEO redirects apply to everyone).
 */
final class RedirectsMigrator {

	/**
	 * Redirect writer.
	 *
	 * @var RedirectWriter
	 */
	private RedirectWriter $writer;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->writer = new RedirectWriter( $dry_run );
	}

	/**
	 * Run the redirect import.
	 *
	 * @param array<array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}> $entries Entries from RedirectSource.
	 * @return array{migrated: int, skipped: int, skipped_already_present: int, errors: array<string>}
	 */
	public function migrate( array $entries ): array {
		$result = [
			'migrated'                => 0,
			'skipped'                 => 0,
			'skipped_already_present' => 0,
			'errors'                  => [],
		];

		foreach ( $entries as $entry ) {
			if ( ! self::importable( $entry ) ) {
				++$result['skipped'];
				continue;
			}

			$outcome = $this->writer->add( $entry['source'], $entry['destination'], $entry['type'], $entry['regex'] );
			if ( RedirectWriter::ADDED === $outcome ) {
				++$result['migrated'];
				continue;
			}

			++$result['skipped'];
			if ( RedirectWriter::PRESENT === $outcome ) {
				++$result['skipped_already_present'];
			} else {
				$result['errors'][] = $entry['label'];
			}
		}

		return $result;
	}

	/**
	 * Whether a SEOPress redirect entry should be imported.
	 *
	 * @param array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string} $entry Entry.
	 * @return bool
	 */
	public static function importable( array $entry ): bool {
		return $entry['enabled']
			&& 'only_logged_in' !== $entry['logged_status']
			&& in_array( $entry['type'], Mappings::REDIRECT_TYPES, true )
			&& '' !== $entry['source'];
	}
}
