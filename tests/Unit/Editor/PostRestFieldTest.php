<?php
/**
 * PostRestField unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Editor;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Editor\PostRestField;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PostRestFieldTest extends MonkeyTestCase {

	/**
	 * Meta writes: key => value ('' when deleted).
	 *
	 * @var array<string, string>
	 */
	private array $writes = [];

	protected function setUp(): void {
		parent::setUp();
		$this->writes = [];

		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'esc_url_raw' )->alias( static fn( $v ): string => (string) $v );
		Functions\when( 'update_post_meta' )->alias(
			function ( int $post_id, string $key, $value ): bool {
				$this->writes[ $key ] = (string) $value;
				return true;
			}
		);
		Functions\when( 'delete_post_meta' )->alias(
			function ( int $post_id, string $key ): bool {
				$this->writes[ $key ] = '';
				return true;
			}
		);
	}

	/**
	 * Grant edit_post and optionally unfiltered_html.
	 *
	 * @param bool $unfiltered Whether the user has unfiltered_html.
	 */
	private function grant( bool $unfiltered ): void {
		Functions\when( 'current_user_can' )->alias(
			static fn( string $cap ): bool => 'edit_post' === $cap || ( 'unfiltered_html' === $cap && $unfiltered )
		);
	}

	private function post(): \WP_Post {
		return new \WP_Post( [ 'ID' => 12 ] );
	}

	public function test_update_writes_only_submitted_keys(): void {
		$this->grant( true );

		( new PostRestField() )->update_value( [ 'title' => ' <i>New</i> ', 'noindex' => true ], $this->post() );

		$this->assertSame( [ '_lw_seo_title' => 'New', '_lw_seo_noindex' => '1' ], $this->writes );
	}

	public function test_update_deletes_empty_and_false_values(): void {
		$this->grant( true );

		( new PostRestField() )->update_value( [ 'description' => '', 'nofollow' => false ], $this->post() );

		$this->assertSame( [ '_lw_seo_description' => '', '_lw_seo_nofollow' => '' ], $this->writes );
	}

	public function test_update_skips_markdown_without_unfiltered_html(): void {
		$this->grant( false );

		( new PostRestField() )->update_value( [ 'markdown_content' => '# Hi', 'og_title' => 'Social' ], $this->post() );

		$this->assertSame( [ '_lw_seo_og_title' => 'Social' ], $this->writes );
	}

	public function test_update_writes_markdown_with_unfiltered_html(): void {
		$this->grant( true );

		( new PostRestField() )->update_value( [ 'markdown_content' => '# Hi' ], $this->post() );

		$this->assertSame( [ '_lw_seo_markdown_content' => '# Hi' ], $this->writes );
	}

	public function test_update_ignores_can_edit_markdown(): void {
		$this->grant( true );

		( new PostRestField() )->update_value( [ 'can_edit_markdown' => true ], $this->post() );

		$this->assertSame( [], $this->writes );
	}

	public function test_update_requires_edit_post(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'rest_authorization_required_code' )->justReturn( 403 );

		$result = ( new PostRestField() )->update_value( [ 'title' => 'x' ], $this->post() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [], $this->writes );
	}

	public function test_get_value_reports_markdown_permission(): void {
		$this->grant( false );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$values = ( new PostRestField() )->get_value( [ 'id' => 12 ] );

		$this->assertFalse( $values['can_edit_markdown'] );
		$this->assertFalse( $values['noindex'] );
		$this->assertSame( '', $values['canonical'] );
	}
}
