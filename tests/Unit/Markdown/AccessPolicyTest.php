<?php
/**
 * AccessPolicy unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use LightweightPlugins\SEO\Markdown\AccessPolicy;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class AccessPolicyTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: array{status: string, can_read_private: bool, password_required: bool, visible: bool}, 1: int}>
	 */
	public static function post_provider(): array {
		$base = [
			'status'            => 'publish',
			'can_read_private'  => false,
			'password_required' => false,
			'visible'           => true,
		];

		return [
			'public post'                => [ $base, 200 ],
			'noindex or ai-input=no'     => [ array_merge( $base, [ 'visible' => false ] ), 404 ],
			'draft'                      => [ array_merge( $base, [ 'status' => 'draft' ] ), 404 ],
			'password protected'         => [ array_merge( $base, [ 'password_required' => true ] ), 403 ],
			'private, no capability'     => [ array_merge( $base, [ 'status' => 'private' ] ), 404 ],
			'private, can read'          => [ array_merge( $base, [ 'status' => 'private', 'can_read_private' => true ] ), 200 ],
		];
	}

	/**
	 * @dataProvider post_provider
	 *
	 * @param array{status: string, can_read_private: bool, password_required: bool, visible: bool} $facts    Facts.
	 * @param int                                                                                    $expected Status.
	 */
	public function test_post_status( array $facts, int $expected ): void {
		$this->assertSame( $expected, AccessPolicy::post_status( $facts ) );
	}

	public function test_term_status_hides_noindex_and_private_taxonomies(): void {
		$this->assertSame( 200, AccessPolicy::term_status( [ 'public' => true, 'noindex' => false ] ) );
		$this->assertSame( 404, AccessPolicy::term_status( [ 'public' => true, 'noindex' => true ] ) );
		$this->assertSame( 404, AccessPolicy::term_status( [ 'public' => false, 'noindex' => false ] ) );
	}
}
