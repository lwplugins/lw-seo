<?php
/**
 * Redirect Handler class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

use LightweightPlugins\SEO\Options;

/**
 * Handles redirect execution on the frontend.
 */
final class Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Only run if redirects are enabled.
		if ( ! Options::get( 'redirects_enabled', true ) ) {
			return;
		}

		// Hook early to catch redirects before WordPress loads.
		add_action( 'template_redirect', [ $this, 'maybe_redirect' ], 1 );
	}

	/**
	 * Check if current URL matches a redirect and perform it.
	 *
	 * @return void
	 */
	public function maybe_redirect(): void {
		// Don't redirect in admin.
		if ( is_admin() ) {
			return;
		}

		// Get current URL path.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( empty( $request_uri ) ) {
			return;
		}

		// Remove query string for matching.
		$path = strtok( $request_uri, '?' );
		if ( ! $path ) {
			return;
		}

		// Find matching redirect.
		$match = Manager::find_match( $path );
		if ( ! $match ) {
			return;
		}

		$redirect = $match['redirect'];
		$type     = (int) $redirect['type'];

		// Record the hit.
		Manager::record_hit( $match['id'] );

		// Handle different redirect types.
		switch ( $type ) {
			case 410:
				$this->send_410();
				break;

			case 451:
				$this->send_451();
				break;

			default:
				$destination = $redirect['destination'];

				// Handle regex replacement in destination.
				if ( $redirect['regex'] && str_contains( $destination, '$' ) ) {
					$pattern     = '@' . str_replace( '@', '\\@', $redirect['source'] ) . '@i';
					$destination = preg_replace( $pattern, $destination, $path );
				}

				// Make absolute URL if relative.
				if ( ! str_starts_with( $destination, 'http' ) ) {
					$destination = home_url( $destination );
				}

				$this->send_redirect( $destination, $type );
				break;
		}
	}

	/**
	 * Send redirect headers.
	 *
	 * @param string $destination Destination URL.
	 * @param int    $type        Redirect type (301, 302, 307).
	 * @return void
	 */
	private function send_redirect( string $destination, int $type ): void {
		// Validate redirect type.
		if ( ! in_array( $type, [ 301, 302, 307 ], true ) ) {
			$type = 301;
		}

		// Use WordPress redirect function.
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- User-defined redirects require external URLs.
		wp_redirect( $destination, $type, 'LW SEO' );
		exit;
	}

	/**
	 * Send 410 Gone response.
	 *
	 * @return void
	 */
	private function send_410(): void {
		status_header( 410 );
		nocache_headers();

		// Try to load theme's 410 template, fallback to simple message.
		$template = get_query_template( '410' );
		if ( $template ) {
			include $template;
		} else {
			$this->render_error_page(
				410,
				__( 'Content Deleted', 'lw-seo' ),
				__( 'The content you are looking for has been permanently removed.', 'lw-seo' )
			);
		}
		exit;
	}

	/**
	 * Send 451 Unavailable For Legal Reasons response.
	 *
	 * @return void
	 */
	private function send_451(): void {
		status_header( 451 );
		nocache_headers();

		// Try to load theme's 451 template, fallback to simple message.
		$template = get_query_template( '451' );
		if ( $template ) {
			include $template;
		} else {
			$this->render_error_page(
				451,
				__( 'Unavailable For Legal Reasons', 'lw-seo' ),
				__( 'This content is not available due to legal reasons.', 'lw-seo' )
			);
		}
		exit;
	}

	/**
	 * Render a simple error page.
	 *
	 * @param int    $code    HTTP status code.
	 * @param string $title   Page title.
	 * @param string $message Error message.
	 * @return void
	 */
	private function render_error_page( int $code, string $title, string $message ): void {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="robots" content="noindex, nofollow">
			<title><?php echo esc_html( $code . ' - ' . $title ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: #f1f1f1;
					color: #444;
					margin: 0;
					padding: 0;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 100vh;
				}
				.error-container {
					background: #fff;
					padding: 40px 60px;
					border-radius: 4px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.13);
					text-align: center;
					max-width: 500px;
				}
				h1 {
					font-size: 72px;
					margin: 0 0 10px;
					color: #0073aa;
				}
				h2 {
					font-size: 24px;
					margin: 0 0 20px;
					font-weight: 400;
				}
				p {
					color: #666;
					line-height: 1.6;
				}
				a {
					color: #0073aa;
					text-decoration: none;
				}
				a:hover {
					text-decoration: underline;
				}
			</style>
		</head>
		<body>
			<div class="error-container">
				<h1><?php echo esc_html( $code ); ?></h1>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $message ); ?></p>
				<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return to homepage', 'lw-seo' ); ?></a></p>
			</div>
		</body>
		</html>
		<?php
	}
}
