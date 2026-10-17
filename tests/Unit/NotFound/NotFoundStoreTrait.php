<?php
/**
 * Shared $wpdb stub wiring for the 404 log tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\NotFound;

/**
 * Installs a fresh recording wpdb as the global.
 */
trait NotFoundStoreTrait {

	private \wpdb $db;

	private function install_wpdb(): void {
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}
		if ( ! defined( 'ARRAY_A' ) ) {
			define( 'ARRAY_A', 'ARRAY_A' );
		}
		$this->db        = new \wpdb();
		$GLOBALS['wpdb'] = $this->db;
	}
}
