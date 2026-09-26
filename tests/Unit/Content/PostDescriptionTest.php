<?php
/**
 * PostDescription unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Content;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Content\PostDescription;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Content\PostDescription
 */
final class PostDescriptionTest extends MonkeyTestCase {

	/**
	 * Post meta values by key (without prefix).
	 *
	 * @var array<string, string>
	 */
	private array $meta = [];

	protected function setUp(): void {
		parent::setUp();

		$this->meta = [];
		Functions\when( 'get_post_meta' )->alias(
			fn( int $id, string $key ): string => $this->meta[ substr( $key, strlen( Options::META_PREFIX ) ) ] ?? ''
		);
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Functions\when( 'wp_trim_words' )->alias(
			static function ( string $text, int $num_words, string $more ): string {
				$words = preg_split( '/\s+/', trim( $text ) );
				return count( $words ) <= $num_words ? trim( $text ) : implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
			}
		);
		Functions\when( 'post_password_required' )->justReturn( false );
	}

	/**
	 * @param array<string, mixed> $props Overrides.
	 */
	private function post( array $props = [] ): \WP_Post {
		return new \WP_Post(
			array_merge(
				[
					'ID'           => 9,
					'post_excerpt' => '',
					'post_content' => '<p>Members only secret text</p>',
				],
				$props
			)
		);
	}

	public function test_generated_description_comes_from_get_the_excerpt_not_the_raw_content(): void {
		Functions\when( 'get_the_excerpt' )->justReturn( 'Join to read this post.' );

		$this->assertSame( 'Join to read this post.', PostDescription::generated( $this->post() ) );
	}

	public function test_generated_description_is_empty_for_a_password_protected_post(): void {
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\expect( 'get_the_excerpt' )->never();

		$this->assertSame( '', PostDescription::generated( $this->post( [ 'post_excerpt' => 'Manual' ] ) ) );
	}

	public function test_automatic_excerpt_is_cut_to_the_word_limit_and_decoded(): void {
		$long = 'Tom&#8217;s ' . implode( ' ', array_fill( 0, 40, 'word' ) );
		Functions\when( 'get_the_excerpt' )->justReturn( $long );

		$description = PostDescription::generated( $this->post() );

		$this->assertStringStartsWith( "Tom\u{2019}s word", $description );
		$this->assertSame( PostDescription::WORDS, count( explode( ' ', substr( $description, 0, -3 ) ) ) );
		$this->assertStringEndsWith( '...', $description );
	}

	public function test_manual_excerpt_is_kept_whole(): void {
		$long = implode( ' ', array_fill( 0, 40, 'word' ) );
		Functions\when( 'get_the_excerpt' )->justReturn( $long );

		$this->assertSame( $long, PostDescription::generated( $this->post( [ 'post_excerpt' => $long ] ) ) );
	}

	public function test_manual_excerpt_helper_never_builds_an_automatic_excerpt(): void {
		Functions\expect( 'get_the_excerpt' )->never();

		$this->assertSame( '', PostDescription::manual_excerpt( $this->post() ) );
	}

	public function test_manual_excerpt_helper_reads_through_get_the_excerpt(): void {
		Functions\when( 'get_the_excerpt' )->justReturn( 'Masked teaser' );

		$this->assertSame( 'Masked teaser', PostDescription::manual_excerpt( $this->post( [ 'post_excerpt' => 'Secret summary' ] ) ) );
	}

	public function test_manual_excerpt_helper_is_empty_for_a_password_protected_post(): void {
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\expect( 'get_the_excerpt' )->never();

		$this->assertSame( '', PostDescription::manual_excerpt( $this->post( [ 'post_excerpt' => 'Secret summary' ] ) ) );
	}

	public function test_for_head_filters_each_output_with_its_context(): void {
		Functions\when( 'get_the_excerpt' )->justReturn( 'Generated' );
		$post = $this->post();

		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Generated', $post, 'meta' )->andReturn( 'M' );
		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Generated', $post, 'og' )->andReturn( 'O' );
		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Generated', $post, 'twitter' )->andReturn( 'T' );

		$this->assertSame(
			[
				'meta'    => 'M',
				'og'      => 'O',
				'twitter' => 'T',
			],
			PostDescription::for_head( $post )
		);
	}

	public function test_for_head_passes_editor_descriptions_through_the_filter(): void {
		Functions\expect( 'get_the_excerpt' )->never();
		$this->meta = [
			'description'    => 'Custom <b>meta</b>',
			'og_description' => 'Custom og',
		];
		$post       = $this->post();

		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Custom meta', $post, 'meta' )->andReturn( '' );
		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Custom og', $post, 'og' )->andReturn( '' );
		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Custom og', $post, 'twitter' )->andReturn( '' );

		$this->assertSame(
			[
				'meta'    => '',
				'og'      => '',
				'twitter' => '',
			],
			PostDescription::for_head( $post )
		);
	}

	public function test_og_and_twitter_fall_back_to_the_unfiltered_source(): void {
		$this->meta = [ 'description' => 'Custom meta' ];
		$post       = $this->post();

		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Custom meta', $post, 'meta' )->andReturn( 'changed' );
		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Custom meta', $post, 'og' )->andReturnFirstArg();
		Filters\expectApplied( PostDescription::FILTER )->once()->with( 'Custom meta', $post, 'twitter' )->andReturnFirstArg();

		$this->assertSame( 'Custom meta', PostDescription::for_head( $post )['og'] );
	}
}
