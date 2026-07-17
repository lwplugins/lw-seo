<?php
/**
 * Characterization tests for ReplaceVars.
 *
 * These tests pin down the CURRENT behaviour (see .claude/rules/tests.md).
 * Odd behaviour is asserted as-is and flagged with a TODO comment — it is
 * never "fixed" in a characterization phase.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\ReplaceVars;

/**
 * @covers \LightweightPlugins\SEO\ReplaceVars
 */
final class ReplaceVarsTest extends MonkeyTestCase {

	/**
	 * Stub the option layer so Options::get() resolves to plugin defaults.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->alias(
			static fn( string $key, $default_value = false ) => match ( $key ) {
				'lw_seo_options' => [],
				'date_format'    => 'Y. m. d.',
				default          => $default_value,
			}
		);
	}

	/**
	 * Keep the static Options cache from leaking into other test classes.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Site-level variables resolve from site state.
	 *
	 * @dataProvider provide_site_variables
	 *
	 * @param string $input    Template string.
	 * @param string $expected Expected replacement result.
	 */
	public function test_replaces_site_level_variables( string $input, string $expected ): void {
		Functions\when( 'get_bloginfo' )->alias(
			static fn( string $show ) => match ( $show ) {
				'name'        => 'Teszt Oldal',
				'description' => 'Ez a szlogen',
				default       => '',
			}
		);
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'get_search_query' )->justReturn( 'piros alma' );
		Functions\when( 'wp_date' )->alias(
			static fn( $format ) => 'Y. m. d.' === $format ? '2026. 07. 17.' : 'DATE(' . $format . ')'
		);

		$this->assertSame( $expected, ReplaceVars::replace( $input ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provide_site_variables(): array {
		return [
			'sitename'      => [ '%%sitename%% - Rólunk', 'Teszt Oldal - Rólunk' ],
			'sitedesc'      => [ '%%sitedesc%%', 'Ez a szlogen' ],
			'siteurl'       => [ '%%siteurl%%', 'https://example.test' ],
			'sep (default)' => [ 'A %%sep%% B', 'A - B' ],
			'searchphrase'  => [ 'Keresés: %%searchphrase%%', 'Keresés: piros alma' ],
			'currentdate'   => [ '%%currentdate%%', '2026. 07. 17.' ],
			// TODO: gyanús viselkedés — post nélkül a %%title%% üres stringre cserélődik,
			// így a cím vezető separatorral kezdődik. Szándékos?
			'üres title + sep' => [ '%%title%% %%sep%% %%sitename%%', '- Teszt Oldal' ],
		];
	}

	/**
	 * The separator comes from the saved plugin options when set.
	 */
	public function test_sep_uses_separator_from_saved_options(): void {
		Functions\when( 'get_option' )->alias(
			static fn( string $key, $default_value = false ) => 'lw_seo_options' === $key
				? [ 'separator' => '»' ]
				: $default_value
		);

		$this->assertSame( 'A » B', ReplaceVars::replace( 'A %%sep%% B' ) );
	}

	public function test_replaces_title_from_post(): void {
		$post = new \WP_Post( [ 'ID' => 7, 'post_title' => 'Cikk címe' ] );
		Functions\when( 'get_the_title' )->alias( static fn( $p ) => $p->post_title );

		$this->assertSame( 'Cikk címe', ReplaceVars::replace( '%%title%%', $post ) );
	}

	public function test_title_uses_term_name_when_only_term_given(): void {
		$term = new \WP_Term( [ 'name' => 'Technológia' ] );

		$this->assertSame( 'Technológia', ReplaceVars::replace( '%%title%%', null, $term ) );
	}

	public function test_replaces_excerpt_from_post_excerpt_field(): void {
		$post = new \WP_Post(
			[
				'post_excerpt' => 'Kivonat szöveg',
				'post_content' => 'Teljes tartalom, aminek nem szabad megjelennie',
			]
		);

		$this->assertSame( 'Kivonat szöveg', ReplaceVars::replace( '%%excerpt%%', $post ) );
	}

	/**
	 * Empty excerpt falls back to the trimmed, tag-stripped post content.
	 */
	public function test_excerpt_falls_back_to_trimmed_content_when_excerpt_empty(): void {
		$post = new \WP_Post(
			[
				'post_excerpt' => '',
				'post_content' => '<p>Első második harmadik</p>',
			]
		);
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'wp_trim_words' )->alias(
			static function ( $text, $num_words = 55, $more = null ) {
				$words = preg_split( '/\s+/', trim( $text ) );
				if ( count( $words ) <= $num_words ) {
					return trim( $text );
				}
				return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
			}
		);

		$this->assertSame( 'Első második harmadik', ReplaceVars::replace( '%%excerpt%%', $post ) );
	}

	public function test_excerpt_is_empty_without_post(): void {
		$this->assertSame( '', ReplaceVars::replace( '%%excerpt%%' ) );
	}

	public function test_replaces_author_via_post_author_meta(): void {
		$post = new \WP_Post( [ 'post_author' => 42 ] );
		Functions\when( 'get_the_author_meta' )->alias(
			static fn( string $field, $user_id ) => 'display_name' === $field && 42 === $user_id
				? 'Szerző Anna'
				: ''
		);

		$this->assertSame( 'Szerző Anna', ReplaceVars::replace( '%%author%%', $post ) );
	}

	/**
	 * When both a user and a post are given, the user wins — the post author
	 * meta lookup never happens (an unstubbed call would fail the test).
	 */
	public function test_author_prefers_user_display_name_over_post_author(): void {
		$post = new \WP_Post( [ 'post_author' => 42 ] );
		$user = new \WP_User( [ 'display_name' => 'Felhasználó Béla' ] );

		$this->assertSame( 'Felhasználó Béla', ReplaceVars::replace( '%%author%%', $post, null, $user ) );
	}

	public function test_replaces_primary_category_with_first_category(): void {
		$post = new \WP_Post( [ 'ID' => 7 ] );
		Functions\when( 'get_the_category' )->justReturn(
			[
				new \WP_Term( [ 'name' => 'Hírek' ] ),
				new \WP_Term( [ 'name' => 'Egyéb' ] ),
			]
		);

		$this->assertSame( 'Hírek', ReplaceVars::replace( '%%category%%', $post ) );
	}

	public function test_category_is_removed_when_post_has_no_categories(): void {
		$post = new \WP_Post( [ 'ID' => 7 ] );
		Functions\when( 'get_the_category' )->justReturn( [] );

		$this->assertSame( 'Cikk - vége', ReplaceVars::replace( 'Cikk - %%category%% vége', $post ) );
	}

	public function test_replaces_term_title_from_term(): void {
		$term = new \WP_Term( [ 'name' => 'Technológia' ] );

		$this->assertSame( 'Technológia', ReplaceVars::replace( '%%term_title%%', null, $term ) );
	}

	public function test_term_title_falls_back_to_queried_term_title(): void {
		Functions\when( 'single_term_title' )->justReturn( 'Aktuális Terminus' );

		$this->assertSame( 'Aktuális Terminus', ReplaceVars::replace( '%%term_title%%' ) );
	}

	/**
	 * Edge cases that need no scenario-specific stubs.
	 *
	 * @dataProvider provide_edge_cases
	 *
	 * @param string $input    Template string.
	 * @param string $expected Expected replacement result.
	 */
	public function test_edge_case_characterization( string $input, string $expected ): void {
		$this->assertSame( $expected, ReplaceVars::replace( $input ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provide_edge_cases(): array {
		return [
			'üres input'         => [ '', '' ],
			'nincs változó'      => [ 'Sima cím', 'Sima cím' ],
			'fél százalékjel'    => [ '50% kedvezmény', '50% kedvezmény' ],
			'ismeretlen változó' => [ 'Cím %%nemletezik%%', 'Cím' ],
			// TODO: gyanús viselkedés — a nagybetűs változónév nem illeszkedik a
			// kisbetűs regexre, ezért literálisan a kimenetben marad. Szándékos?
			'nagybetűs változó'  => [ '%%Sitename%%', '%%Sitename%%' ],
			// TODO: gyanús viselkedés — minden whitespace-sorozat egy szóközre
			// vonódik össze akkor is, ha az input nem tartalmaz változót. Szándékos?
			'whitespace collapse' => [ "Nagy    szóköz\tés  tab", 'Nagy szóköz és tab' ],
			'szélek trimmelve'    => [ '  körülötte szóköz  ', 'körülötte szóköz' ],
		];
	}
}
