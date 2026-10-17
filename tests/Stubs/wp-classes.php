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
		public int $post_parent = 0;

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

if ( ! class_exists( 'WP_Query' ) ) {
	#[AllowDynamicProperties]
	class WP_Query {
		/**
		 * @var array<int, mixed>
		 */
		public $posts = [];
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

if ( ! class_exists( 'WP_Comment' ) ) {
	#[AllowDynamicProperties]
	class WP_Comment {
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

if ( ! class_exists( 'WP_Taxonomy' ) ) {
	#[AllowDynamicProperties]
	class WP_Taxonomy {
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

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/**
		 * @param string|int $code    Error code.
		 * @param string     $message Error message.
		 * @param mixed      $data    Error data.
		 */
		public function __construct( private string|int $code = '', private string $message = '', private mixed $data = '' ) {}

		public function get_error_code(): string|int {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): mixed {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		/**
		 * @param array<string, mixed> $params Request parameters.
		 */
		public function __construct( private array $params = [] ) {}

		public function get_param( string $key ): mixed {
			return $this->params[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		/**
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status.
		 */
		public function __construct( private mixed $data = null, private int $status = 200 ) {}

		public function get_data(): mixed {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		public const READABLE  = 'GET';
		public const CREATABLE = 'POST';
		public const EDITABLE  = 'POST, PUT, PATCH';
		public const DELETABLE = 'DELETE';
	}
}

if ( ! class_exists( 'wpdb' ) ) {
	/**
	 * Recording $wpdb stand-in: keeps every executed SQL string and returns
	 * canned results. prepare() mimics the %s / %d / %% placeholders.
	 */
	#[AllowDynamicProperties]
	class wpdb {
		public $prefix = 'wp_';

		/** @var array<int, string> */
		public array $queries = [];

		/** @var array<int, array<string, mixed>> */
		public array $rows = [];

		public $var = 0;

		public $query_result = 1;

		/** @var array<int, mixed> Queued query() answers (then $query_result). */
		public array $query_queue = [];

		/** @var array<int, array<int, array<string, mixed>>> Queued get_results() answers (then $rows). */
		public array $results_queue = [];

		/** @var array<int, mixed> Queued get_var() answers (then $var). */
		public array $var_queue = [];

		public function prepare( $query, ...$args ) {
			if ( isset( $args[0] ) && is_array( $args[0] ) ) {
				$args = $args[0];
			}
			$query = str_replace( '%%', "\0", $query );
			$query = (string) preg_replace_callback(
				'/%([sd])/',
				static function ( array $m ) use ( &$args ): string {
					$value = array_shift( $args );
					return 'd' === $m[1] ? (string) (int) $value : "'" . addslashes( (string) $value ) . "'";
				},
				$query
			);
			return str_replace( "\0", '%', $query );
		}

		public function esc_like( $text ) {
			return addcslashes( (string) $text, '_%\\' );
		}

		public function query( $sql ) {
			$this->queries[] = $sql;
			return [] !== $this->query_queue ? array_shift( $this->query_queue ) : $this->query_result;
		}

		public function get_results( $sql, $output = 'OBJECT' ) {
			$this->queries[] = $sql;
			return [] !== $this->results_queue ? array_shift( $this->results_queue ) : $this->rows;
		}

		public function get_var( $sql ) {
			$this->queries[] = $sql;
			return [] !== $this->var_queue ? array_shift( $this->var_queue ) : $this->var;
		}

		public function get_charset_collate() {
			return 'DEFAULT CHARACTER SET utf8mb4';
		}
	}
}

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Recording WP_CLI stand-in for command unit tests.
	 */
	class WP_CLI {
		/** @var array<int, string> */
		public static array $log = [];

		public static function confirm( string $question, array $assoc_args = [] ): void {
			self::$log[] = 'confirm: ' . $question;
		}

		public static function success( string $message ): void {
			self::$log[] = 'success: ' . $message;
		}

		public static function error( string $message ): void {
			self::$log[] = 'error: ' . $message;
		}
	}
}
