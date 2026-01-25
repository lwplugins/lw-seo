<?php
/**
 * Main Plugin class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Admin\Settings_Page;
use LightweightPlugins\SEO\Schema\Schema;
use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Load required files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		// Core classes.
		require_once LW_SEO_PATH . 'includes/class-options.php';
		require_once LW_SEO_PATH . 'includes/class-replace-vars.php';
		require_once LW_SEO_PATH . 'includes/class-meta-box.php';
		require_once LW_SEO_PATH . 'includes/class-breadcrumbs.php';
		require_once LW_SEO_PATH . 'includes/class-robots-txt.php';
		require_once LW_SEO_PATH . 'includes/class-llms-txt.php';
		require_once LW_SEO_PATH . 'includes/functions.php';

		// Admin.
		require_once LW_SEO_PATH . 'includes/admin/class-parent-page.php';

		// Admin settings - interface and trait first.
		require_once LW_SEO_PATH . 'includes/admin/settings/interface-tab.php';
		require_once LW_SEO_PATH . 'includes/admin/settings/trait-field-renderer.php';

		// Admin data classes.
		require_once LW_SEO_PATH . 'includes/admin/data/class-ai-crawlers.php';

		// Admin settings tabs.
		require_once LW_SEO_PATH . 'includes/admin/settings/class-tab-general.php';
		require_once LW_SEO_PATH . 'includes/admin/settings/class-tab-content.php';
		require_once LW_SEO_PATH . 'includes/admin/settings/class-tab-social.php';
		require_once LW_SEO_PATH . 'includes/admin/settings/class-tab-sitemap.php';
		require_once LW_SEO_PATH . 'includes/admin/settings/class-tab-ai.php';
		require_once LW_SEO_PATH . 'includes/admin/settings/class-tab-advanced.php';

		// Settings page coordinator.
		require_once LW_SEO_PATH . 'includes/admin/class-settings-page.php';

		// Sitemap.
		require_once LW_SEO_PATH . 'includes/sitemap/interface-provider.php';
		require_once LW_SEO_PATH . 'includes/sitemap/class-post-provider.php';
		require_once LW_SEO_PATH . 'includes/sitemap/class-page-provider.php';
		require_once LW_SEO_PATH . 'includes/sitemap/class-taxonomy-provider.php';
		require_once LW_SEO_PATH . 'includes/sitemap/class-sitemap.php';

		// Schema.
		require_once LW_SEO_PATH . 'includes/schema/class-schema.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'wp_head', [ $this, 'output_meta_tags' ], 1 );
		add_filter( 'document_title_parts', [ $this, 'filter_title' ], 10, 1 );
		add_filter( 'document_title_separator', [ $this, 'filter_title_separator' ], 10, 1 );

		// Cleanup hooks.
		if ( Options::get( 'remove_shortlinks' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		}

		if ( Options::get( 'remove_rsd' ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}

		if ( Options::get( 'remove_wlw' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		// Admin components.
		if ( is_admin() ) {
			new Meta_Box();
			new Settings_Page();
		}

		// Frontend/shared components.
		new Sitemap();
		new Schema();
		new Breadcrumbs();
		new Robots_Txt();
		new Llms_Txt();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'lw-seo',
			false,
			dirname( plugin_basename( LW_SEO_FILE ) ) . '/languages'
		);
	}

	/**
	 * Filter document title.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @return array<string, string>
	 */
	public function filter_title( array $title_parts ): array {
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$custom_title = Options::get_post_meta( $post->ID, 'title' );
				if ( ! empty( $custom_title ) ) {
					$title_parts['title'] = $custom_title;
				} else {
					// Apply template.
					$template = Options::get( 'title_' . $post->post_type );
					if ( $template ) {
						$title_parts['title'] = Replace_Vars::replace( $template, $post );
						unset( $title_parts['site'], $title_parts['tagline'] );
					}
				}
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term     = get_queried_object();
			$template = Options::get( 'title_' . ( $term->taxonomy ?? 'category' ) );
			if ( $template && $term instanceof \WP_Term ) {
				$title_parts['title'] = Replace_Vars::replace( $template, null, $term );
				unset( $title_parts['site'], $title_parts['tagline'] );
			}
		} elseif ( is_author() ) {
			$user     = get_queried_object();
			$template = Options::get( 'title_author' );
			if ( $template && $user instanceof \WP_User ) {
				$title_parts['title'] = Replace_Vars::replace( $template, null, null, $user );
				unset( $title_parts['site'], $title_parts['tagline'] );
			}
		} elseif ( is_search() ) {
			$template = Options::get( 'title_search' );
			if ( $template ) {
				$title_parts['title'] = Replace_Vars::replace( $template );
				unset( $title_parts['site'], $title_parts['tagline'] );
			}
		} elseif ( is_404() ) {
			$template = Options::get( 'title_404' );
			if ( $template ) {
				$title_parts['title'] = Replace_Vars::replace( $template );
				unset( $title_parts['site'], $title_parts['tagline'] );
			}
		}

		return $title_parts;
	}

	/**
	 * Filter document title separator.
	 *
	 * @param string $sep Default separator.
	 * @return string
	 */
	public function filter_title_separator( string $sep ): string {
		$custom_sep = Options::get( 'separator' );

		if ( ! empty( $custom_sep ) ) {
			return $custom_sep;
		}

		return $sep;
	}

	/**
	 * Output meta tags in head.
	 *
	 * @return void
	 */
	public function output_meta_tags(): void {
		// Check if another SEO plugin is active.
		if ( $this->is_conflicting_plugin_active() ) {
			return;
		}

		if ( is_singular() ) {
			$this->output_singular_meta();
		} elseif ( is_front_page() || is_home() ) {
			$this->output_home_meta();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$this->output_taxonomy_meta();
		} elseif ( is_author() ) {
			$this->output_author_meta();
		} elseif ( is_date() ) {
			$this->output_archive_meta( 'date' );
		}
	}

	/**
	 * Check if a conflicting SEO plugin is active.
	 *
	 * @return bool
	 */
	private function is_conflicting_plugin_active(): bool {
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

	/**
	 * Output meta tags for singular posts/pages.
	 *
	 * @return void
	 */
	private function output_singular_meta(): void {
		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Check robots meta.
		$noindex  = Options::get_post_meta( $post->ID, 'noindex' ) || Options::get( 'noindex_' . $post->post_type );
		$nofollow = Options::get_post_meta( $post->ID, 'nofollow' );

		if ( $noindex || $nofollow ) {
			$robots = [];
			if ( $noindex ) {
				$robots[] = 'noindex';
			}
			if ( $nofollow ) {
				$robots[] = 'nofollow';
			}
			printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( implode( ', ', $robots ) ) );
		}

		// Get meta values.
		$custom_title = Options::get_post_meta( $post->ID, 'title' );
		$title        = ! empty( $custom_title ) ? $custom_title : get_the_title( $post );
		$description  = $this->get_meta_description( $post );
		$custom_canon = Options::get_post_meta( $post->ID, 'canonical' );
		$canonical    = ! empty( $custom_canon ) ? $custom_canon : get_permalink( $post );

		// Get OG specific values.
		$custom_og_title = Options::get_post_meta( $post->ID, 'og_title' );
		$og_title        = ! empty( $custom_og_title ) ? $custom_og_title : $title;
		$custom_og_desc  = Options::get_post_meta( $post->ID, 'og_description' );
		$og_description  = ! empty( $custom_og_desc ) ? $custom_og_desc : $description;
		$og_image        = $this->get_og_image( $post );

		$this->render_meta_tags( $title, $description, $canonical, $og_title, $og_description, $og_image, 'article' );
	}

	/**
	 * Output meta tags for home/front page.
	 *
	 * @return void
	 */
	private function output_home_meta(): void {
		$title       = get_bloginfo( 'name' );
		$custom_desc = Options::get( 'desc_home' );
		$description = ! empty( $custom_desc ) ? $custom_desc : get_bloginfo( 'description' );
		$url         = home_url( '/' );

		$this->render_meta_tags( $title, $description, $url, $title, $description, '', 'website' );
	}

	/**
	 * Output meta tags for taxonomy archives.
	 *
	 * @return void
	 */
	private function output_taxonomy_meta(): void {
		$term = get_queried_object();

		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		// Check noindex setting.
		$noindex = Options::get( 'noindex_' . $term->taxonomy );
		if ( $noindex ) {
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		}

		$term_title  = single_term_title( '', false );
		$title       = ! empty( $term_title ) ? $term_title : $term->name;
		$term_desc   = term_description( $term );
		$description = ! empty( $term_desc ) ? $term_desc : '';
		$description = wp_strip_all_tags( $description );
		$url         = get_term_link( $term );

		if ( is_string( $url ) ) {
			$this->render_meta_tags( $title, $description, $url, $title, $description, '', 'website' );
		}
	}

	/**
	 * Output meta tags for author archives.
	 *
	 * @return void
	 */
	private function output_author_meta(): void {
		$author = get_queried_object();

		if ( ! $author instanceof \WP_User ) {
			return;
		}

		// Check noindex setting.
		if ( Options::get( 'noindex_author' ) ) {
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		}

		$title       = $author->display_name;
		$description = get_the_author_meta( 'description', $author->ID );
		$url         = get_author_posts_url( $author->ID );

		$this->render_meta_tags( $title, $description, $url, $title, $description, '', 'profile' );
	}

	/**
	 * Output meta tags for archive pages.
	 *
	 * @param string $type Archive type.
	 * @return void
	 */
	private function output_archive_meta( string $type ): void {
		// Check noindex setting.
		if ( Options::get( 'noindex_' . $type ) ) {
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		}
	}

	/**
	 * Get meta description for a post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string
	 */
	private function get_meta_description( \WP_Post $post ): string {
		$description = Options::get_post_meta( $post->ID, 'description' );

		if ( empty( $description ) ) {
			$description = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
		}

		return wp_strip_all_tags( $description );
	}

	/**
	 * Get Open Graph image for a post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string Image URL.
	 */
	private function get_og_image( \WP_Post $post ): string {
		$og_image = Options::get_post_meta( $post->ID, 'og_image' );

		if ( ! empty( $og_image ) ) {
			return $og_image;
		}

		if ( has_post_thumbnail( $post ) ) {
			$thumbnail = get_the_post_thumbnail_url( $post, 'large' );
			if ( $thumbnail ) {
				return $thumbnail;
			}
		}

		// Fallback to default OG image.
		$default_image = Options::get( 'default_og_image' );
		if ( ! empty( $default_image ) ) {
			return $default_image;
		}

		return '';
	}

	/**
	 * Render meta tags.
	 *
	 * @param string $title          The page title.
	 * @param string $description    The meta description.
	 * @param string $canonical      The canonical URL.
	 * @param string $og_title       The OG title.
	 * @param string $og_description The OG description.
	 * @param string $og_image       The OG image URL.
	 * @param string $og_type        The OG type.
	 * @return void
	 */
	private function render_meta_tags(
		string $title,
		string $description,
		string $canonical,
		string $og_title,
		string $og_description,
		string $og_image,
		string $og_type
	): void {
		echo "\n<!-- LW SEO -->\n";

		// Meta description.
		if ( ! empty( $description ) ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}

		// Canonical URL.
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );

		// Open Graph tags.
		if ( Options::get( 'opengraph_enabled' ) ) {
			printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( get_locale() ) );
			printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $og_type ) );
			printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $og_title ) );
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
			printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );

			if ( ! empty( $og_description ) ) {
				printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $og_description ) );
			}

			if ( ! empty( $og_image ) ) {
				printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $og_image ) );
			}

			// Article specific.
			if ( 'article' === $og_type && is_singular() ) {
				$post = get_queried_object();
				if ( $post instanceof \WP_Post ) {
					printf(
						'<meta property="article:published_time" content="%s" />' . "\n",
						esc_attr( get_the_date( 'c', $post ) )
					);
					printf(
						'<meta property="article:modified_time" content="%s" />' . "\n",
						esc_attr( get_the_modified_date( 'c', $post ) )
					);
				}
			}
		}

		// Twitter Cards.
		if ( Options::get( 'twitter_enabled' ) ) {
			printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( Options::get( 'twitter_card_type' ) ) );
			printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $og_title ) );

			if ( ! empty( $og_description ) ) {
				printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $og_description ) );
			}

			if ( ! empty( $og_image ) ) {
				printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $og_image ) );
			}
		}

		echo "<!-- /LW SEO -->\n\n";
	}
}
