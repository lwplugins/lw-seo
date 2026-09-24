<?php
/**
 * Redirect Validator unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Redirects;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Validator;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ValidatorTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: int, 3: bool, 4: string|null}>
	 */
	public static function input_provider(): array {
		return [
			'valid 301'                   => [ '/old', '/new', 301, false, null ],
			'missing source'              => [ '  ', '/new', 301, false, 'Source URL is required.' ],
			'missing destination'         => [ '/old', '', 302, false, 'Destination URL is required for this redirect type.' ],
			'410 needs no destination'    => [ '/gone', '', 410, false, null ],
			'451 needs no destination'    => [ '/legal', '', 451, false, null ],
			'unknown type acts as 301'    => [ '/old', '', 999, false, 'Destination URL is required for this redirect type.' ],
			'valid regex'                 => [ '^/blog/(.*)$', '/news/$1', 301, true, null ],
			'invalid regex'               => [ '/blog/(', '/news', 301, true, 'Invalid regex pattern.' ],
			'regex with @ is escaped'     => [ '/user@(\d+)', '/u/$1', 301, true, null ],
			'bracket not checked as text' => [ '/blog/(', '/news', 301, false, null ],
		];
	}

	/**
	 * @dataProvider input_provider
	 *
	 * @param string      $source      Source.
	 * @param string      $destination Destination.
	 * @param int         $type        Type.
	 * @param bool        $regex       Regex flag.
	 * @param string|null $expected    Expected error.
	 */
	public function test_error_reports_the_first_problem( string $source, string $destination, int $type, bool $regex, ?string $expected ): void {
		$this->assertSame( $expected, Validator::error( $source, $destination, $type, $regex ) );
	}

	/**
	 * @return array<string, array{0: int, 1: int}>
	 */
	public static function type_provider(): array {
		return [
			'supported' => [ 307, 307 ],
			'unknown'   => [ 200, 301 ],
			'zero'      => [ 0, 301 ],
		];
	}

	/**
	 * @dataProvider type_provider
	 *
	 * @param int $type     Requested type.
	 * @param int $expected Normalised type.
	 */
	public function test_normalize_type_falls_back_to_301( int $type, int $expected ): void {
		$this->assertSame( $expected, Validator::normalize_type( $type ) );
	}
}
