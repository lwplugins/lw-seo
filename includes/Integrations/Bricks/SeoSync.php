<?php
/**
 * Two-way sync between Bricks page SEO settings and LW SEO fields.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\Bricks;

use LightweightPlugins\SEO\Editor\MetaFields;
use LightweightPlugins\SEO\Options;

/**
 * Bricks keeps its own SEO settings (Page settings → SEO / Social media)
 * that LW SEO turns off on the frontend (see Bricks). This keeps them in
 * step with the LW SEO fields at the meta level, so every save path is
 * covered (Bricks builder, block editor, meta box, REST, CLI):
 *
 * - a Bricks save copies the SEO values it changed into LW SEO;
 * - an LW SEO save copies the value into the Bricks settings of posts
 *   edited with Bricks.
 *
 * Cleared text is never copied, so neither side loses text because the
 * other side is empty; robots flags follow both on and off (SeoMap).
 */
final class SeoSync {

	/**
	 * Bricks page settings meta key (BRICKS_DB_PAGE_SETTINGS).
	 */
	public const BRICKS_KEY = '_bricks_page_settings';

	/**
	 * Whether a sync write is in progress (its own meta hooks are skipped).
	 *
	 * @var bool
	 */
	private static bool $syncing = false;

	/**
	 * Bricks settings of each post before the running update/delete.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $before = [];

	/**
	 * Register the meta hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'update_post_metadata', [ $this, 'remember' ], 10, 3 );
		add_filter( 'delete_post_metadata', [ $this, 'remember' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'written' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'written' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'deleted' ], 10, 3 );
	}

	/**
	 * Run a callback without syncing the meta it writes.
	 *
	 * @param callable $callback Callback.
	 * @return mixed Its return value.
	 */
	public static function paused( callable $callback ): mixed {
		$was           = self::$syncing;
		self::$syncing = true;

		try {
			return $callback();
		} finally {
			self::$syncing = $was;
		}
	}

	/**
	 * Keep the Bricks settings a write is about to replace.
	 *
	 * @param mixed  $check     Short-circuit value, passed through.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @return mixed
	 */
	public function remember( $check, $object_id, $meta_key ) {
		if ( self::BRICKS_KEY === $meta_key && ! self::$syncing ) {
			$this->before[ (int) $object_id ] = self::settings( (int) $object_id );
		}

		return $check;
	}

	/**
	 * A meta value was added or updated.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New value.
	 * @return void
	 */
	public function written( $meta_id, $object_id, $meta_key, $meta_value ): void {
		$this->sync( (int) $object_id, (string) $meta_key, $meta_value, false );
	}

	/**
	 * A meta value was deleted.
	 *
	 * @param mixed  $meta_ids  Deleted meta IDs.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	public function deleted( $meta_ids, $object_id, $meta_key ): void {
		$this->sync( (int) $object_id, (string) $meta_key, '', true );
	}

	/**
	 * Route a meta change to the direction it belongs to.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   New value ('' when deleted).
	 * @param bool   $deleted Whether the meta was deleted.
	 * @return void
	 */
	private function sync( int $post_id, string $key, mixed $value, bool $deleted ): void {
		if ( self::$syncing || $post_id <= 0 || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( self::BRICKS_KEY === $key ) {
			$before = $this->before[ $post_id ] ?? [];
			unset( $this->before[ $post_id ] );
			self::paused( fn() => $this->to_lw( $post_id, $before, $deleted || ! is_array( $value ) ? [] : $value ) );
			return;
		}

		$field = str_starts_with( $key, Options::META_PREFIX ) ? substr( $key, strlen( Options::META_PREFIX ) ) : '';

		if ( in_array( $field, SeoMap::FIELDS, true ) && ( ! $deleted || isset( SeoMap::FLAGS[ $field ] ) ) ) {
			self::paused( fn() => $this->to_bricks( $post_id, $field, is_scalar( $value ) ? (string) $value : '' ) );
		}
	}

	/**
	 * Copy what a Bricks save changed into the LW SEO fields.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $old     Bricks settings before the save.
	 * @param array<string, mixed> $new     Bricks settings after the save.
	 * @return void
	 */
	private function to_lw( int $post_id, array $old, array $new ): void {
		foreach ( SeoMap::changes( $old, $new ) as $field => $value ) {
			Options::set_post_meta( $post_id, $field, MetaFields::sanitize( MetaFields::POST[ $field ], $value ) );
		}
	}

	/**
	 * Copy an LW SEO field into the Bricks settings of a Bricks post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field   LW SEO field.
	 * @param string $value   Its new value ('' when cleared).
	 * @return void
	 */
	private function to_bricks( int $post_id, string $field, string $value ): void {
		$settings = self::settings( $post_id );

		if ( [] === $settings && 'bricks' !== get_post_meta( $post_id, '_bricks_editor_mode', true ) ) {
			return;
		}

		$image_id = 'og_image' === $field && '' !== $value ? (int) attachment_url_to_postid( $value ) : 0;
		$updated  = SeoMap::apply( $settings, $field, $value, $image_id );

		if ( null === $updated || $updated === $settings ) {
			return;
		}

		if ( [] === $updated ) {
			delete_post_meta( $post_id, self::BRICKS_KEY );
			return;
		}

		update_post_meta( $post_id, self::BRICKS_KEY, $updated );
	}

	/**
	 * A post's Bricks page settings.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function settings( int $post_id ): array {
		$settings = get_post_meta( $post_id, self::BRICKS_KEY, true );

		return is_array( $settings ) ? $settings : [];
	}
}
