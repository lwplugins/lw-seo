<?php
/**
 * Redirect matcher.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Finds the redirect for a URL: the first stored rule wins, regex rules
 * are case-insensitive, an invalid regex never matches (and never warns).
 */
final class Matcher {

	/**
	 * The redirect a URL resolves to.
	 *
	 * @param string $url Request URI, path or full URL (query string ignored).
	 * @return array{index: int, id: string, source: string, type: int, regex: bool, destination: string}|null
	 */
	public static function match( string $url ): ?array {
		$path = self::request_path( $url );
		if ( '' === $path ) {
			return null;
		}

		$found = self::find( $path );

		return null === $found ? null : self::describe( $found['index'], $found['rule'], $path );
	}

	/**
	 * Every rule's verdict for a URL, for the tester.
	 *
	 * @param string $url Request URI, path or full URL (query string ignored).
	 * @return array{path: string, match: array{index: int, id: string, source: string, type: int, regex: bool, destination: string}|null, rules: array<int, array{id: string, source: string, regex: bool, result: string}>}
	 */
	public static function explain( string $url ): array {
		$path  = self::request_path( $url );
		$match = null;
		$rules = [];

		foreach ( Manager::get_all() as $index => $rule ) {
			$result = 'no';
			if ( null === $match && '' !== $path ) {
				$result = self::verdict( $rule, Manager::normalize_source( $path ) );
			}
			if ( 'matched' === $result ) {
				$match = self::describe( (int) $index, $rule, $path );
			}
			$rules[] = [
				'id'     => (string) ( $rule['id'] ?? $index ),
				'source' => (string) $rule['source'],
				'regex'  => ! empty( $rule['regex'] ),
				'result' => $result,
			];
		}

		return [
			'path'  => '' === $path ? '' : Manager::normalize_source( $path ),
			'match' => $match,
			'rules' => $rules,
		];
	}

	/**
	 * First matching stored rule for a path.
	 *
	 * @param string $url Path (query string already removed).
	 * @return array{index: int, rule: array}|null
	 */
	public static function find( string $url ): ?array {
		$path = Manager::normalize_source( $url );

		foreach ( Manager::get_all() as $index => $rule ) {
			if ( 'matched' === self::verdict( $rule, $path ) ) {
				return [
					'index' => (int) $index,
					'rule'  => $rule,
				];
			}
		}

		return null;
	}

	/**
	 * Whether one rule matches a normalized path.
	 *
	 * @param array  $rule Stored rule.
	 * @param string $path Normalized path.
	 * @return string 'matched', 'no' or 'invalid_regex'.
	 */
	private static function verdict( array $rule, string $path ): string {
		if ( empty( $rule['regex'] ) ) {
			return $rule['source'] === $path ? 'matched' : 'no';
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A stored pattern may be invalid; false means "invalid", not a warning in the front end.
		$hit = @preg_match( Manager::regex_pattern( (string) $rule['source'] ), $path );

		if ( false === $hit ) {
			return 'invalid_regex';
		}

		return 1 === $hit ? 'matched' : 'no';
	}

	/**
	 * Result shape of a matched rule.
	 *
	 * @param int    $index Rule index.
	 * @param array  $rule  Stored rule.
	 * @param string $path  Request path (query string removed).
	 * @return array{index: int, id: string, source: string, type: int, regex: bool, destination: string}
	 */
	private static function describe( int $index, array $rule, string $path ): array {
		$type  = (int) $rule['type'];
		$regex = ! empty( $rule['regex'] );

		return [
			'index'       => $index,
			'id'          => (string) ( $rule['id'] ?? $index ),
			'source'      => (string) $rule['source'],
			'type'        => $type,
			'regex'       => $regex,
			'destination' => in_array( $type, [ 410, 451 ], true ) ? '' : self::destination( $rule, $regex, $path ),
		];
	}

	/**
	 * Absolute destination, with `$n` filled for regex rules.
	 *
	 * @param array  $rule  Stored rule.
	 * @param bool   $regex Whether the rule is a regex.
	 * @param string $path  Request path.
	 * @return string
	 */
	private static function destination( array $rule, bool $regex, string $path ): string {
		$destination = (string) $rule['destination'];

		if ( $regex && str_contains( $destination, '$' ) ) {
			$destination = (string) preg_replace( Manager::regex_pattern( (string) $rule['source'] ), $destination, $path );
		}

		return str_starts_with( $destination, 'http' ) ? $destination : home_url( $destination );
	}

	/**
	 * Path of a request URI or URL: no query string, no fragment, no site URL.
	 *
	 * @param string $url Request URI, path or full URL.
	 * @return string Empty when there is no path.
	 */
	private static function request_path( string $url ): string {
		$path = strtok( $url, '?#' );
		if ( ! $path ) {
			return '';
		}

		$site_url = home_url();
		if ( str_starts_with( $path, $site_url ) ) {
			$path = substr( $path, strlen( $site_url ) );
		}

		return '' === $path ? '/' : $path;
	}
}
