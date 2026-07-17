<?php
/**
 * Characterization tests for the Schema JSON-LD graph builder.
 *
 * These tests pin down the CURRENT behaviour (see .claude/rules/tests.md)
 * via the public build_graph_for_post() entry point (the REST path), which
 * returns the graph as a plain array — asserted at array level, never as a
 * serialized string.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Schema\Schema;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Schema\Schema
 */
final class SchemaTest extends MonkeyTestCase {

	/**
	 * Stub the shared WordPress surface used by every graph builder.
	 *
	 * @param array<string, mixed> $option_overrides Values layered onto lw_seo_options.
	 * @return void
	 */
	private function arrange_environment( array $option_overrides = [] ): void {
		Options::clear_cache();

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->alias(
			static fn( string $key, $default_value = false ) => 'lw_seo_options' === $key
				? $option_overrides
				: $default_value
		);
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'get_bloginfo' )->alias(
			static fn( string $show ) => match ( $show ) {
				'name'        => 'Teszt Oldal',
				'description' => 'Ez a szlogen',
				default       => '',
			}
		);
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/cikk/' );
		Functions\when( 'get_the_title' )->alias(
			static fn( $p ) => $p instanceof \WP_Post ? $p->post_title : ''
		);
		Functions\when( 'get_the_date' )->justReturn( '2026-07-17T10:00:00+00:00' );
		Functions\when( 'get_the_modified_date' )->justReturn( '2026-07-17T12:00:00+00:00' );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'has_blocks' )->justReturn( false );
	}

	/**
	 * @return \WP_Post
	 */
	private function make_post( string $post_type ): \WP_Post {
		return new \WP_Post(
			[
				'ID'           => 7,
				'post_type'    => $post_type,
				'post_author'  => 42,
				'post_title'   => 'Cikk címe',
				// ASCII content keeps str_word_count() deterministic across locales.
				'post_content' => 'one two three four five',
			]
		);
	}

	/**
	 * The wrapper carries @context and a @graph list.
	 */
	public function test_graph_is_wrapped_with_context_and_graph_list(): void {
		$this->arrange_environment();
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$result = ( new Schema() )->build_graph_for_post( $this->make_post( 'page' ) );

		$this->assertSame( 'https://schema.org', $result['@context'] );
		$this->assertArrayHasKey( '@graph', $result );
		$this->assertIsList( $result['@graph'] );
	}

	/**
	 * A page graph holds WebSite, Organization, WebPage — and no Article.
	 */
	public function test_page_graph_contains_expected_node_types(): void {
		$this->arrange_environment();
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$graph = ( new Schema() )->build_graph_for_post( $this->make_post( 'page' ) )['@graph'];
		$types = array_column( $graph, '@type' );

		$this->assertSame( [ 'WebSite', 'Organization', 'WebPage' ], $types );
	}

	/**
	 * A post graph additionally holds an Article node.
	 */
	public function test_post_graph_includes_article_node(): void {
		$this->arrange_environment();
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$graph = ( new Schema() )->build_graph_for_post( $this->make_post( 'post' ) )['@graph'];
		$types = array_column( $graph, '@type' );

		$this->assertSame( [ 'WebSite', 'Organization', 'WebPage', 'Article' ], $types );
	}

	/**
	 * WebSite node: identity, SearchAction potentialAction, publisher link.
	 */
	public function test_website_node_structure(): void {
		$this->arrange_environment();
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$graph   = ( new Schema() )->build_graph_for_post( $this->make_post( 'page' ) )['@graph'];
		$website = $graph[0];

		$this->assertSame( 'WebSite', $website['@type'] );
		$this->assertSame( 'https://example.test/#website', $website['@id'] );
		$this->assertSame( 'Teszt Oldal', $website['name'] );
		$this->assertSame( 'hu_HU', $website['inLanguage'] );
		$this->assertSame( 'SearchAction', $website['potentialAction']['@type'] );
		$this->assertSame(
			'https://example.test/?s={search_term_string}',
			$website['potentialAction']['target']['urlTemplate']
		);
		$this->assertSame( 'https://example.test/#organization', $website['publisher']['@id'] );
	}

	/**
	 * Organization knowledge node with a logo and social sameAs profiles.
	 */
	public function test_organization_node_includes_logo_and_sameas(): void {
		$this->arrange_environment(
			[
				'knowledge_type'  => 'organization',
				'knowledge_logo'  => 'https://example.test/logo.png',
				'social_facebook' => 'https://facebook.test/oldal',
				'social_twitter'  => 'https://twitter.test/oldal',
			]
		);
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$graph = ( new Schema() )->build_graph_for_post( $this->make_post( 'page' ) )['@graph'];
		$org   = $graph[1];

		$this->assertSame( 'Organization', $org['@type'] );
		$this->assertSame( 'https://example.test/#organization', $org['@id'] );
		$this->assertSame( 'Teszt Oldal', $org['name'] );
		$this->assertSame( 'ImageObject', $org['logo']['@type'] );
		$this->assertSame( 'https://example.test/logo.png', $org['logo']['url'] );
		$this->assertSame(
			[ 'https://facebook.test/oldal', 'https://twitter.test/oldal' ],
			$org['sameAs']
		);
	}

	/**
	 * Person knowledge node: no logo branch, ucfirst-ed @type, prefix-keyed @id.
	 */
	public function test_person_knowledge_node_has_no_logo(): void {
		$this->arrange_environment(
			[
				'knowledge_type' => 'person',
				'knowledge_name' => 'Szerző Anna',
			]
		);
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$graph  = ( new Schema() )->build_graph_for_post( $this->make_post( 'page' ) )['@graph'];
		$person = $graph[1];

		$this->assertSame( 'Person', $person['@type'] );
		$this->assertSame( 'https://example.test/#person', $person['@id'] );
		$this->assertSame( 'Szerző Anna', $person['name'] );
		$this->assertArrayNotHasKey( 'logo', $person );
	}

	/**
	 * Article node: identity, author Person, publisher link, category keywords.
	 */
	public function test_article_node_structure(): void {
		$this->arrange_environment();
		Functions\when( 'get_userdata' )->justReturn(
			new \WP_User( [ 'ID' => 42, 'display_name' => 'Szerző Anna' ] )
		);
		Functions\when( 'get_author_posts_url' )->justReturn( 'https://example.test/szerzo/anna/' );
		Functions\when( 'get_the_category' )->justReturn(
			[
				new \WP_Term( [ 'name' => 'Hírek' ] ),
				new \WP_Term( [ 'name' => 'Tech' ] ),
			]
		);
		Functions\when( 'wp_list_pluck' )->alias(
			static fn( array $list, string $field ) => array_map( static fn( $t ) => $t->{$field}, $list )
		);

		$graph   = ( new Schema() )->build_graph_for_post( $this->make_post( 'post' ) )['@graph'];
		$article = $graph[3];

		$this->assertSame( 'Article', $article['@type'] );
		$this->assertSame( 'https://example.test/cikk/#article', $article['@id'] );
		$this->assertSame( 'Cikk címe', $article['headline'] );
		$this->assertSame( 'https://example.test/cikk/#webpage', $article['mainEntityOfPage']['@id'] );
		$this->assertSame( 5, $article['wordCount'] );
		$this->assertSame( 'Person', $article['author']['@type'] );
		$this->assertSame( 'Szerző Anna', $article['author']['name'] );
		$this->assertSame( 'https://example.test/#organization', $article['publisher']['@id'] );
		$this->assertSame( 'Hírek, Tech', $article['keywords'] );
	}

	/**
	 * Empty knowledge_type drops the Organization/Person node entirely.
	 */
	public function test_empty_knowledge_type_omits_knowledge_node(): void {
		$this->arrange_environment( [ 'knowledge_type' => '' ] );
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$graph = ( new Schema() )->build_graph_for_post( $this->make_post( 'page' ) )['@graph'];
		$types = array_column( $graph, '@type' );

		$this->assertNotContains( 'Organization', $types );
		$this->assertNotContains( 'Person', $types );
		$this->assertSame( [ 'WebSite', 'WebPage' ], $types );
	}
}
