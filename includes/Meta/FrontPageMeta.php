<?php
/**
 * Head meta of a static front page.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Content\ObjectText;
use LightweightPlugins\SEO\Content\PostDescription;
use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * When a page is set as the front page, its own LW SEO fields (title,
 * description, canonical, social data, robots) win over the home settings,
 * which stay the fallback (the og:image falls back to the default social
 * image, as before, not to the featured image). With a multilingual plugin
 * (Polylang, WPML) the queried page is the translation being viewed, so every language keeps
 * its own values, and the default canonical is that translation's
 * permalink (/en/), not the default language's home URL.
 */
final class FrontPageMeta {

	/**
	 * Tag renderer.
	 *
	 * @var TagRenderer
	 */
	private TagRenderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param TagRenderer $renderer Tag renderer.
	 */
	public function __construct( TagRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * The static front page being viewed.
	 *
	 * @return \WP_Post|null Null when the front page shows the latest posts.
	 */
	public static function page(): ?\WP_Post {
		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return null;
		}

		$page = get_queried_object();

		return $page instanceof \WP_Post && 'page' === $page->post_type ? $page : null;
	}

	/**
	 * Output the head meta of a static front page.
	 *
	 * @param \WP_Post $page             The front page.
	 * @param string   $home_title       Title from the home settings.
	 * @param string   $home_description Description from the home settings.
	 * @return void
	 */
	public function output( \WP_Post $page, string $home_title, string $home_description ): void {
		$this->render_robots( $page );

		$own_title   = ObjectText::post( $page, 'title' );
		$title       = '' !== $own_title ? $own_title : $home_title;
		$own_desc    = wp_strip_all_tags( ObjectText::post( $page, 'description' ) );
		$description = '' !== trim( $own_desc ) ? $own_desc : $home_description;
		$own_og_desc = wp_strip_all_tags( ObjectText::post( $page, 'og_description' ) );
		$og_desc     = '' !== trim( $own_og_desc ) ? $own_og_desc : $description;
		$own_og      = ObjectText::post( $page, 'og_title' );
		$custom      = Canonical::custom_for_post( (int) $page->ID );
		$canonical   = '' !== $custom ? $custom : ArchiveContext::paged_url( (string) get_permalink( $page ) );
		$own_image   = MetaCoerce::as_url( Options::get_post_meta( (int) $page->ID, 'og_image' ) );
		$og_image    = '' !== $own_image ? $own_image : (string) Options::get( 'default_og_image' );

		$this->renderer->render(
			$title,
			PostDescription::filter( $description, $page, 'meta' ),
			$canonical,
			'' !== $own_og ? $own_og : $title,
			PostDescription::filter( $og_desc, $page, 'og' ),
			$og_image,
			'website',
			PostDescription::filter( $og_desc, $page, 'twitter' )
		);
	}

	/**
	 * Print the page's own noindex / nofollow flags.
	 *
	 * @param \WP_Post $page The front page.
	 * @return void
	 */
	private function render_robots( \WP_Post $page ): void {
		$robots = [];

		foreach ( [ 'noindex', 'nofollow' ] as $flag ) {
			if ( Options::get_post_meta( (int) $page->ID, $flag ) ) {
				$robots[] = $flag;
			}
		}

		$this->renderer->render_robots( $robots );
	}
}
