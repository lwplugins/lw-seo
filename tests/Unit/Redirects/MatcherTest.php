<?php
/**
 * Redirect Matcher unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Redirects;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Redirects\Matcher;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Redirects\Matcher
 */
final class MatcherTest extends MonkeyTestCase {

	/**
	 * In-memory wp_options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	protected function setUp(): void {
		parent::setUp();
		$this->options = [];

		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ) => 'https://example.test' . $path );
	}

	/**
	 * Store rules.
	 *
	 * @param array<int, array{0: string, 1: string, 2: int, 3: bool}> $rules source, destination, type, regex.
	 * @return void
	 */
	private function rules( array $rules ): void {
		$stored = [];
		foreach ( $rules as $i => $rule ) {
			$stored[] = [
				'id'            => 'id-' . $i,
				'source'        => $rule[0],
				'destination'   => $rule[1],
				'type'          => $rule[2],
				'regex'         => $rule[3],
				'hits'          => 0,
				'last_accessed' => '',
			];
		}
		$this->options[ Manager::OPTION_NAME ] = $stored;
	}

	public function test_first_rule_wins(): void {
		$this->rules(
			[
				[ '/old', '/first', 301, false ],
				[ '@^/old$@', '/second', 302, true ],
			]
		);

		$match = Matcher::match( '/old' );

		$this->assertSame( 0, $match['index'] );
		$this->assertSame( 'id-0', $match['id'] );
		$this->assertSame( 'https://example.test/first', $match['destination'] );
		$this->assertSame( 301, $match['type'] );
		$this->assertFalse( $match['regex'] );
	}

	public function test_regex_backreference(): void {
		$this->rules( [ [ '^/blog/(.*)', '/news/$1', 301, true ] ] );

		$match = Matcher::match( '/BLOG/hello' );

		$this->assertSame( 'https://example.test/news/hello', $match['destination'] );
		$this->assertTrue( $match['regex'] );
	}

	public function test_legacy_regex_source(): void {
		$this->rules( [ [ '/^/old/(\d+)', 'https://other.test/$1', 301, true ] ] );

		$match = Matcher::match( '/old/42' );

		$this->assertSame( 'https://other.test/42', $match['destination'] );
	}

	public function test_query_string_ignored(): void {
		$this->rules( [ [ '/old', '/new', 301, false ] ] );

		$this->assertNotNull( Matcher::match( '/old?utm=1&x=2' ) );
		$this->assertNotNull( Matcher::match( 'https://example.test/old?x=1' ) );
	}

	public function test_trailing_slash_normalised(): void {
		$this->rules( [ [ '/old', '/new', 301, false ] ] );

		$this->assertNotNull( Matcher::match( '/old/' ) );
		$this->assertNull( Matcher::match( '/other/' ) );
	}

	/**
	 * Browsers send non-ASCII paths percent-encoded; the stored source may
	 * be written either way (#29).
	 *
	 * @dataProvider provide_encoded_paths
	 */
	public function test_non_ascii_path_matches_in_either_form( string $source, string $request ): void {
		$this->rules( [ [ $source, '/new', 301, false ] ] );

		$this->assertNotNull( Matcher::match( $request ) );
	}

	public static function provide_encoded_paths(): array {
		return [
			'decoded source, encoded request' => [ '/tag/café', '/tag/caf%C3%A9/' ],
			'encoded source, encoded request' => [ '/tag/caf%C3%A9', '/tag/caf%C3%A9/' ],
			'encoded source, decoded request' => [ '/tag/caf%C3%A9', '/tag/café' ],
			'lowercase hex in the request'    => [ '/tag/café', '/tag/caf%c3%a9' ],
		];
	}

	public function test_non_ascii_path_does_not_match_its_stripped_form(): void {
		$this->rules( [ [ '/tag/caf', '/new', 301, false ] ] );

		$this->assertNull( Matcher::match( '/tag/caf%C3%A9/' ) );
	}

	public function test_regex_sees_the_decoded_path(): void {
		$this->rules( [ [ '^/tag/(.+)$', '/topic/$1', 301, true ] ] );

		$match = Matcher::match( '/tag/caf%C3%A9/' );

		$this->assertSame( 'https://example.test/topic/café/', $match['destination'] );
	}

	public function test_gone_has_empty_destination(): void {
		$this->rules(
			[
				[ '/gone', '', 410, false ],
				[ '/legal', 'ignored', 451, false ],
			]
		);

		$this->assertSame( '', Matcher::match( '/gone' )['destination'] );
		$this->assertSame( 410, Matcher::match( '/gone' )['type'] );
		$this->assertSame( '', Matcher::match( '/legal' )['destination'] );
	}

	public function test_explain_reports_invalid_regex_without_warning(): void {
		$this->rules(
			[
				[ '(unclosed', '/x', 301, true ],
				[ '/ok', '/y', 301, false ],
			]
		);

		set_error_handler(
			static function ( int $no, string $str ): bool {
				// Honour the @ operator like PHP's own handler does.
				if ( ! ( error_reporting() & $no ) ) {
					return false;
				}
				throw new \ErrorException( $str, 0, $no );
			}
		);
		try {
			$explain = Matcher::explain( '/ok' );
			$this->assertNull( Matcher::match( '/nope' ) );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( 'invalid_regex', $explain['rules'][0]['result'] );
		$this->assertSame( 'matched', $explain['rules'][1]['result'] );
		$this->assertSame( 'id-1', $explain['match']['id'] );
	}

	public function test_explain_marks_only_first_match(): void {
		$this->rules(
			[
				[ '/a', '/1', 301, false ],
				[ '/b', '/2', 301, false ],
				[ '/b', '/3', 301, false ],
			]
		);

		$explain = Matcher::explain( '/b?x=1' );

		$this->assertSame( '/b', $explain['path'] );
		$this->assertSame(
			[ 'no', 'matched', 'no' ],
			array_column( $explain['rules'], 'result' )
		);
		$this->assertSame( 1, $explain['match']['index'] );
	}

	public function test_explain_without_match(): void {
		$this->rules( [ [ '/a', '/1', 301, false ] ] );

		$explain = Matcher::explain( '/zzz' );

		$this->assertNull( $explain['match'] );
		$this->assertSame( [ 'no' ], array_column( $explain['rules'], 'result' ) );
	}
}
