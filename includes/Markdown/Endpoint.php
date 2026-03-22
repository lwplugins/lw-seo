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
		$mask = EP_PERMALINK | EP_PAGES | EP_CATEGORIES | EP_TAGS;
		add_rewrite_endpoint( 'md', $mask );
		add_rewrite_endpoint( 'markdown', $mask );
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
		// Endpoint: /hello-world/md/ or /hello-world/markdown/.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query var check.
		if ( isset( $GLOBALS['wp_query']->query_vars['md'] ) || isset( $GLOBALS['wp_query']->query_vars['markdown'] ) ) {
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
		$mask = EP_PERMALINK | EP_PAGES | EP_CATEGORIES | EP_TAGS;
		add_rewrite_endpoint( 'md', $mask );
		add_rewrite_endpoint( 'markdown', $mask );
		flush_rewrite_rules();
	}
}
