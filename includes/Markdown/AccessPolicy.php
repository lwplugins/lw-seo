<?php
/**
 * Markdown access policy.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Decides the HTTP status for serving an object as Markdown.
 */
final class AccessPolicy {

	/**
	 * Status for a post.
	 *
	 * Private posts answer 404 to readers without access so their
	 * existence is not revealed.
	 *
	 * @param array{status: string, can_read_private: bool, password_required: bool, visible: bool} $facts Post facts.
	 * @return int
	 */
	public static function post_status( array $facts ): int {
		if ( 'private' === $facts['status'] ) {
			return $facts['can_read_private'] ? 200 : 404;
		}

		if ( 'publish' !== $facts['status'] ) {
			return 404;
		}

		if ( $facts['password_required'] ) {
			return 403;
		}

		return $facts['visible'] ? 200 : 404;
	}

	/**
	 * Status for a taxonomy term.
	 *
	 * @param array{public: bool, noindex: bool} $facts Term facts.
	 * @return int
	 */
	public static function term_status( array $facts ): int {
		return $facts['public'] && ! $facts['noindex'] ? 200 : 404;
	}
}
