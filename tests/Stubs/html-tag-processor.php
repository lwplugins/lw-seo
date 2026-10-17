<?php
/**
 * Minimal WP_HTML_Tag_Processor stand-in. Loaded on demand (separate process) because
 * ImageCollector switches behaviour when the class exists.
 */

declare(strict_types=1);

// phpcs:disable -- test-only stub.

if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	/** Minimal single-<img> stand-in: next_tag, get/set_attribute, get_updated_html. */
	class WP_HTML_Tag_Processor {
		private string $html;
		/** @var array<string, string> */
		private array $attrs = [];
		private bool $found  = false;

		public function __construct( string $html ) {
			$this->html = $html;
		}

		public function next_tag( $query ): bool {
			$tag = is_array( $query ) ? (string) $query['tag_name'] : (string) $query;
			if ( ! preg_match( '/^<' . $tag . '\b([^>]*?)\s*\/?>/i', $this->html, $m ) ) {
				return false;
			}
			preg_match_all( '/([\w-]+)="([^"]*)"/', $m[1], $pairs, PREG_SET_ORDER );
			foreach ( $pairs as $pair ) {
				$this->attrs[ $pair[1] ] = $pair[2];
			}
			$this->found = true;
			return true;
		}

		public function get_attribute( string $name ) {
			return $this->attrs[ $name ] ?? null;
		}

		public function set_attribute( string $name, string $value ): bool {
			$this->attrs[ $name ] = $value;
			return true;
		}

		public function get_updated_html(): string {
			$out = '<img';
			foreach ( $this->attrs as $k => $v ) {
				$out .= ' ' . $k . '="' . $v . '"';
			}
			return $out . ' />';
		}
	}
}
