<?php
/**
 * Head meta output dispatcher.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

/**
 * Picks the right meta output for the current query context.
 */
final class HeadMeta {

	/**
	 * Singular meta output.
	 *
	 * @var SingularMeta
	 */
	private SingularMeta $singular;

	/**
	 * Archive meta output.
	 *
	 * @var ArchiveMeta
	 */
	private ArchiveMeta $archive;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$renderer       = new TagRenderer();
		$this->singular = new SingularMeta( $renderer );
		$this->archive  = new ArchiveMeta( $renderer );
	}

	/**
	 * Register the wp_head hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', [ $this, 'output' ], 1 );
	}

	/**
	 * Output meta tags in head.
	 *
	 * @return void
	 */
	public function output(): void {
		// Check if another SEO plugin is active.
		if ( self::is_conflicting_plugin_active() ) {
			return;
		}

		if ( is_front_page() ) {
			$this->archive->output_home();
		} elseif ( is_home() ) {
			$this->archive->output_posts_page();
		} elseif ( is_singular() ) {
			$this->singular->output();
		} elseif ( is_post_type_archive() ) {
			$this->archive->output_post_type_archive();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$this->archive->output_taxonomy();
		} elseif ( is_author() ) {
			$this->archive->output_author();
		} elseif ( is_date() ) {
			$this->archive->output_date();
		}
	}

	/**
	 * Check if a conflicting SEO plugin is active.
	 *
	 * @return bool
	 */
	public static function is_conflicting_plugin_active(): bool {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return true;
		}

		if ( class_exists( 'RankMath' ) ) {
			return true;
		}

		if ( defined( 'AIOSEO_VERSION' ) ) {
			return true;
		}

		return false;
	}
}
