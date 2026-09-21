<?php
/**
 * Markdown Endpoint.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\ContentSignals;
use LightweightPlugins\SEO\Options;

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
	 * Explicit requests (/md, /markdown, ?format=md) always get a Markdown
	 * answer, errors included. Accept-negotiated requests fall back to the
	 * regular HTML page whenever Markdown is not available.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$explicit = $this->is_explicit_request();

		if ( ! $explicit && ! $this->is_negotiated_request() ) {
			return;
		}

		$object = $this->resolve_object();
		$status = null === $object ? 404 : $this->status_for( $object );

		/**
		 * Filter whether this request supports markdown output.
		 *
		 * @param bool      $supported Whether markdown is supported.
		 * @param \WP_Query $query     Current query.
		 */
		if ( 200 === $status && ! apply_filters( 'lw_seo_markdown_is_supported', true, $GLOBALS['wp_query'] ) ) {
			$status = 404;
		}

		if ( null === $object || 200 !== $status ) {
			if ( $explicit ) {
				$this->send_error( $status );
			}
			return;
		}

		$this->send_response( Dispatcher::dispatch( $object ), $object, ! $explicit );
	}

	/**
	 * /md, /markdown or ?format=md.
	 *
	 * @return bool
	 */
	private function is_explicit_request(): bool {
		$query_vars = $GLOBALS['wp_query']->query_vars ?? [];

		if ( isset( $query_vars['md'] ) || isset( $query_vars['markdown'] ) ) {
			return true;
		}

		return RequestPath::has_suffix( $this->request_path() ) || 'md' === get_query_var( 'format' );
	}

	/**
	 * Singular page whose Accept header prefers Markdown.
	 *
	 * @return bool
	 */
	private function is_negotiated_request(): bool {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';

		return is_singular() && AcceptNegotiator::prefers_markdown( $accept );
	}

	/**
	 * Request path relative to the site home.
	 *
	 * @return string
	 */
	private function request_path(): string {
		$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		return RequestPath::relative( (string) wp_parse_url( $uri, PHP_URL_PATH ), (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
	}

	/**
	 * Resolve the object to render.
	 *
	 * @return \WP_Post|\WP_Term|null
	 */
	private function resolve_object(): \WP_Post|\WP_Term|null {
		$object = get_queried_object();

		if ( $object instanceof \WP_Post || $object instanceof \WP_Term ) {
			return $object;
		}

		return $this->resolve_from_path( RequestPath::strip( $this->request_path() ) );
	}

	/**
	 * Resolve a post or term from a path ('' = the static front page).
	 *
	 * @param string $path Relative path without the Markdown suffix.
	 * @return \WP_Post|\WP_Term|null
	 */
	private function resolve_from_path( string $path ): \WP_Post|\WP_Term|null {
		if ( '' === $path ) {
			$front = (int) get_option( 'page_on_front' );
			$post  = 'page' === get_option( 'show_on_front' ) && $front > 0 ? get_post( $front ) : null;
			return $post instanceof \WP_Post ? $post : null;
		}

		$post_id = url_to_postid( home_url( '/' . $path . '/' ) );
		if ( $post_id ) {
			$post = get_post( $post_id );
			return $post instanceof \WP_Post ? $post : null;
		}

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
	 * HTTP status for serving an object.
	 *
	 * @param \WP_Post|\WP_Term $object Object.
	 * @return int
	 */
	private function status_for( \WP_Post|\WP_Term $object ): int {
		if ( $object instanceof \WP_Term ) {
			$taxonomy = get_taxonomy( $object->taxonomy );

			return AccessPolicy::term_status(
				[
					'public'  => $taxonomy instanceof \WP_Taxonomy && $taxonomy->public,
					'noindex' => (bool) Options::get_term_meta( (int) $object->term_id, 'noindex' ) || (bool) Options::get( 'noindex_' . $object->taxonomy ),
				]
			);
		}

		return AccessPolicy::post_status(
			[
				'status'            => (string) $object->post_status,
				'can_read_private'  => current_user_can( 'read_post', $object->ID ),
				'password_required' => post_password_required( $object ),
				'visible'           => Eligibility::is_ai_visible( $object ),
			]
		);
	}

	/**
	 * Send a Markdown error and stop.
	 *
	 * @param int $status HTTP status.
	 * @return void
	 */
	private function send_error( int $status ): void {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: text/markdown; charset=UTF-8' );
		header( 'X-Content-Type-Options: nosniff' );

		echo 403 === $status ? "# Password Protected\n\nThis content is password protected.\n" : "# 404 Not Found\n";
		exit;
	}

	/**
	 * Send the markdown response and stop.
	 *
	 * @param string            $output     Markdown content.
	 * @param \WP_Post|\WP_Term $object     Rendered object.
	 * @param bool              $negotiated Whether the HTML URL was answered via Accept.
	 * @return void
	 */
	private function send_response( string $output, \WP_Post|\WP_Term $object, bool $negotiated ): void {
		$canonical = $object instanceof \WP_Post ? get_permalink( $object ) : get_term_link( $object );

		status_header( 200 );
		header( 'Content-Type: text/markdown; charset=UTF-8' );
		// Prevent MIME-sniffing: $output is user-controlled post content echoed
		// unescaped, so a sniffing browser must not reinterpret it as text/html.
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );
		if ( is_string( $canonical ) ) {
			header( 'Link: <' . esc_url_raw( $canonical ) . '>; rel="canonical"' );
		}
		header( 'X-Content-Signals: ' . ContentSignals::format_header( ContentSignals::resolve( $object ) ) );
		header( 'X-Markdown-Tokens: ' . (int) ( mb_strlen( $output ) / 4 ) );

		if ( $negotiated ) {
			// The same URL serves HTML and Markdown: keep caches from mixing them up.
			header( 'Vary: Accept' );
			header( 'Cache-Control: private, no-store' );
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Page-cache plugin convention.
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown body served as text/markdown (non-HTML context) with X-Content-Type-Options: nosniff; HTML-escaping would corrupt the Markdown.
		echo $output;
		exit;
	}
}
