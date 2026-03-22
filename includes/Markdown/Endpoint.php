<?php
/**
 * Markdown Endpoint.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\ContentSignals;

/**
 * Handles /md endpoint, Accept header, and query parameter routing.
 */
final class Endpoint {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_request' ] );
	}

	/**
	 * Add /md rewrite endpoint to all permalink structures.
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		// EP_ALL covers posts, pages, categories, tags, and custom taxonomies (product_cat, etc.).
		add_rewrite_endpoint( 'md', EP_ALL );
		add_rewrite_endpoint( 'markdown', EP_ALL );
	}

	/**
	 * Register query variables.
	 *
	 * @param array<string> $vars Existing query vars.
	 * @return array<string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'format';
		return $vars;
	}

	/**
	 * Handle markdown requests.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		if ( ! $this->is_markdown_request() ) {
			return;
		}

		$object = $this->resolve_object();
		if ( null === $object ) {
			status_header( 404 );
			header( 'Content-Type: text/markdown; charset=UTF-8' );
			echo "# 404 Not Found\n";
			exit;
		}

		// Security checks for posts.
		if ( $object instanceof \WP_Post ) {
			$this->check_post_access( $object );
		}

		// Security checks for taxonomy terms.
		if ( $object instanceof \WP_Term ) {
			$this->check_term_access( $object );
		}

		/**
		 * Filter whether this request supports markdown output.
		 *
		 * @param bool      $supported Whether markdown is supported.
		 * @param \WP_Query $query     Current query.
		 */
		$supported = apply_filters( 'lw_seo_markdown_is_supported', true, $GLOBALS['wp_query'] );
		if ( ! $supported ) {
			return;
		}

		$output = Dispatcher::dispatch( $object );
		if ( null === $output ) {
			return;
		}

		$this->send_response( $output, $object );
	}

	/**
	 * Check if this is a markdown request.
	 *
	 * @return bool
	 */
	private function is_markdown_request(): bool {
		// Endpoint query var: /hello-world/md/ (set by add_rewrite_endpoint).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query var check.
		if ( isset( $GLOBALS['wp_query']->query_vars['md'] ) || isset( $GLOBALS['wp_query']->query_vars['markdown'] ) ) {
			return true;
		}

		// URL suffix detection for taxonomy archives where endpoint rewrite may not work.
		$path = trim( wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH ) ?? '', '/' );
		if ( str_ends_with( $path, '/md' ) || str_ends_with( $path, '/markdown' ) ) {
			return true;
		}

		// Query parameter: ?format=md.
		if ( 'md' === get_query_var( 'format' ) ) {
			return true;
		}

		// Accept header: text/markdown (singular pages only).
		if ( is_singular() && $this->accepts_markdown() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the Accept header includes text/markdown.
	 *
	 * @return bool
	 */
	private function accepts_markdown(): bool {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) )
			: '';

		if ( '' === $accept ) {
			return false;
		}

		$types = array_map( 'trim', explode( ',', $accept ) );
		$types = array_map(
			fn( string $type ): string => explode( ';', $type )[0],
			$types
		);

		return in_array( 'text/markdown', $types, true );
	}

	/**
	 * Resolve the queried object for markdown output.
	 *
	 * @return \WP_Post|\WP_Term|null
	 */
	private function resolve_object(): \WP_Post|\WP_Term|null {
		$object = get_queried_object();

		if ( $object instanceof \WP_Post ) {
			return $object;
		}

		if ( $object instanceof \WP_Term ) {
			return $object;
		}

		// Fallback: resolve from URL path when endpoint rewrite doesn't set the queried object.
		return $this->resolve_from_url();
	}

	/**
	 * Try to resolve a WP_Post or WP_Term from the current URL path.
	 *
	 * Strips the /md or /markdown suffix and queries WordPress for the base URL.
	 *
	 * @return \WP_Post|\WP_Term|null
	 */
	private function resolve_from_url(): \WP_Post|\WP_Term|null {
		$path = trim( wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH ) ?? '', '/' );

		// Strip /md or /markdown suffix.
		if ( str_ends_with( $path, '/md' ) ) {
			$path = substr( $path, 0, -3 );
		} elseif ( str_ends_with( $path, '/markdown' ) ) {
			$path = substr( $path, 0, -9 );
		} else {
			return null;
		}

		$base_url = home_url( '/' . $path . '/' );
		$post_id  = url_to_postid( $base_url );

		if ( $post_id ) {
			$post = get_post( $post_id );
			return $post instanceof \WP_Post ? $post : null;
		}

		// Try to resolve as taxonomy term.
		return $this->resolve_term_from_path( $path );
	}

	/**
	 * Try to resolve a taxonomy term from a URL path.
	 *
	 * @param string $path URL path without /md suffix.
	 * @return \WP_Term|null
	 */
	private function resolve_term_from_path( string $path ): ?\WP_Term {
		$taxonomies = get_taxonomies(
			[
				'public'  => true,
				'rewrite' => true,
			],
			'objects'
		);

		foreach ( $taxonomies as $taxonomy ) {
			$rewrite_slug = $taxonomy->rewrite['slug'] ?? $taxonomy->name;

			if ( ! str_starts_with( $path, $rewrite_slug . '/' ) ) {
				continue;
			}

			$term_slug = substr( $path, strlen( $rewrite_slug ) + 1 );
			$term_slug = trim( $term_slug, '/' );

			// Handle hierarchical slugs (parent/child).
			if ( str_contains( $term_slug, '/' ) ) {
				$parts     = explode( '/', $term_slug );
				$term_slug = end( $parts );
			}

			$term = get_term_by( 'slug', $term_slug, $taxonomy->name );
			if ( $term instanceof \WP_Term ) {
				return $term;
			}
		}

		return null;
	}

	/**
	 * Check if a post is accessible for markdown output.
	 * Exits with appropriate status code if not accessible.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	private function check_post_access( \WP_Post $post ): void {
		if ( ! in_array( $post->post_status, [ 'publish', 'private' ], true ) ) {
			status_header( 404 );
			header( 'Content-Type: text/markdown; charset=UTF-8' );
			echo "# 404 Not Found\n";
			exit;
		}

		if ( 'private' === $post->post_status && ! current_user_can( 'read_private_posts' ) ) {
			status_header( 403 );
			header( 'Content-Type: text/markdown; charset=UTF-8' );
			echo "# Forbidden\n";
			exit;
		}

		if ( post_password_required( $post ) ) {
			status_header( 403 );
			header( 'Content-Type: text/markdown; charset=UTF-8' );
			echo "# Password Protected\n\nThis content is password protected.\n";
			exit;
		}
	}

	/**
	 * Check if a taxonomy term is accessible.
	 * Exits with 404 if taxonomy is not public.
	 *
	 * @param \WP_Term $term Term object.
	 * @return void
	 */
	private function check_term_access( \WP_Term $term ): void {
		$taxonomy = get_taxonomy( $term->taxonomy );
		if ( ! $taxonomy || ! $taxonomy->public ) {
			status_header( 404 );
			header( 'Content-Type: text/markdown; charset=UTF-8' );
			echo "# 404 Not Found\n";
			exit;
		}
	}

	/**
	 * Send the markdown response with proper headers.
	 *
	 * @param string            $output Markdown content.
	 * @param \WP_Post|\WP_Term $object Queried object.
	 * @return void
	 */
	private function send_response( string $output, \WP_Post|\WP_Term $object ): void {
		$signals     = ContentSignals::resolve( $object );
		$token_count = (int) ( mb_strlen( $output ) / 4 );

		header( 'Content-Type: text/markdown; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex' );
		header( 'X-Content-Signals: ' . ContentSignals::format_header( $signals ) );
		header( 'X-Markdown-Tokens: ' . $token_count );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown content is pre-built.
		echo $output;
		exit;
	}

	/**
	 * Flush rewrite rules on activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// EP_ALL covers posts, pages, categories, tags, and custom taxonomies (product_cat, etc.).
		add_rewrite_endpoint( 'md', EP_ALL );
		add_rewrite_endpoint( 'markdown', EP_ALL );
		flush_rewrite_rules();
	}
}
