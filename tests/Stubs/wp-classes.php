<?php
/**
 * Minimal WordPress class stubs for unit tests.
 *
 * WordPress is never loaded in the unit suite, but plugin code type-hints
 * against these core classes. Pure property bags — no logic.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

// phpcs:disable -- test-only stubs, WPCS does not apply to tests/.

if ( ! class_exists( 'WP_Post' ) ) {
	#[AllowDynamicProperties]
	class WP_Post {
		/**
		 * @param array<string, mixed> $props Properties to set.
		 */
		public function __construct( array $props = [] ) {
			foreach ( $props as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	#[AllowDynamicProperties]
	class WP_Term {
		/**
		 * @param array<string, mixed> $props Properties to set.
		 */
		public function __construct( array $props = [] ) {
			foreach ( $props as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	#[AllowDynamicProperties]
	class WP_User {
		/**
		 * @param array<string, mixed> $props Properties to set.
		 */
		public function __construct( array $props = [] ) {
			foreach ( $props as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Post_Type' ) ) {
	#[AllowDynamicProperties]
	class WP_Post_Type {
		/**
		 * @param array<string, mixed> $props Properties to set.
		 */
		public function __construct( array $props = [] ) {
			foreach ( $props as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Rewrite' ) ) {
	#[AllowDynamicProperties]
	class WP_Rewrite {
		public string $pagination_base = 'page';

		private bool $permalinks;

		public function __construct( bool $permalinks = true, string $pagination_base = 'page' ) {
			$this->permalinks      = $permalinks;
			$this->pagination_base = $pagination_base;
		}

		public function using_permalinks(): bool {
			return $this->permalinks;
		}
	}
}
