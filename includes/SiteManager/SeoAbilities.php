<?php
/**
 * SEO Ability Definitions for LW Site Manager.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\SiteManager;

/**
 * Registers SEO-specific abilities with the WordPress Abilities API.
 */
final class SeoAbilities {

	/**
	 * Register all SEO abilities.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	public static function register( object $permissions ): void {
		self::register_meta_abilities( $permissions );
		self::register_signals_abilities( $permissions );
		self::register_markdown_abilities( $permissions );
		self::register_options_abilities( $permissions );
	}

	/**
	 * Register SEO meta abilities (get/set for posts and terms).
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_meta_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-seo/get-meta',
			[
				'label'               => __( 'Get SEO Meta', 'lw-seo' ),
				'description'         => __( 'Get SEO metadata for a post or term (title, description, social, signals).', 'lw-seo' ),
				'category'            => 'seo',
				'execute_callback'    => [ SeoService::class, 'get_meta' ],
				'permission_callback' => $permissions->callback( 'can_edit_posts' ),
				'input_schema'        => [
					'type'       => 'object',
					'default'    => [],
					'properties' => [
						'post_id' => [
							'type'        => 'integer',
							'description' => __( 'Post ID. Provide post_id or term_id.', 'lw-seo' ),
						],
						'term_id' => [
							'type'        => 'integer',
							'description' => __( 'Term ID. Provide post_id or term_id.', 'lw-seo' ),
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success' => [ 'type' => 'boolean' ],
						'meta'    => [ 'type' => 'object' ],
					],
				],
				'meta'                => self::readonly_meta(),
			]
		);

		wp_register_ability(
			'lw-seo/set-meta',
			[
				'label'               => __( 'Set SEO Meta', 'lw-seo' ),
				'description'         => __( 'Set SEO metadata for a post or term.', 'lw-seo' ),
				'category'            => 'seo',
				'execute_callback'    => [ SeoService::class, 'set_meta' ],
				'permission_callback' => $permissions->callback( 'can_edit_posts' ),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'meta' ],
					'properties' => [
						'post_id' => [
							'type'        => 'integer',
							'description' => __( 'Post ID. Provide post_id or term_id.', 'lw-seo' ),
						],
						'term_id' => [
							'type'        => 'integer',
							'description' => __( 'Term ID. Provide post_id or term_id.', 'lw-seo' ),
						],
						'meta'    => [
							'type'        => 'object',
							'description' => __( 'SEO fields: title, description, noindex, og_title, og_description, og_image, ai_train, ai_input, search, markdown_content.', 'lw-seo' ),
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success' => [ 'type' => 'boolean' ],
						'message' => [ 'type' => 'string' ],
					],
				],
				'meta'                => self::write_meta(),
			]
		);
	}

	/**
	 * Register Content Signals abilities.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_signals_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-seo/get-content-signals',
			[
				'label'               => __( 'Get Content Signals', 'lw-seo' ),
				'description'         => __( 'Get resolved AI content signals for a post or term.', 'lw-seo' ),
				'category'            => 'seo',
				'execute_callback'    => [ SeoService::class, 'get_content_signals' ],
				'permission_callback' => $permissions->callback( 'can_edit_posts' ),
				'input_schema'        => [
					'type'       => 'object',
					'default'    => [],
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'term_id' => [ 'type' => 'integer' ],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success' => [ 'type' => 'boolean' ],
						'signals' => [ 'type' => 'object' ],
					],
				],
				'meta'                => self::readonly_meta(),
			]
		);
	}

	/**
	 * Register markdown abilities.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_markdown_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-seo/get-markdown',
			[
				'label'               => __( 'Get Markdown', 'lw-seo' ),
				'description'         => __( 'Get the markdown representation of a post, product, or term.', 'lw-seo' ),
				'category'            => 'seo',
				'execute_callback'    => [ SeoService::class, 'get_markdown' ],
				'permission_callback' => $permissions->callback( 'can_edit_posts' ),
				'input_schema'        => [
					'type'       => 'object',
					'default'    => [],
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'term_id' => [ 'type' => 'integer' ],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'  => [ 'type' => 'boolean' ],
						'markdown' => [ 'type' => 'string' ],
						'tokens'   => [ 'type' => 'integer' ],
					],
				],
				'meta'                => self::readonly_meta(),
			]
		);
	}

	/**
	 * Register global SEO options abilities.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_options_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-seo/get-options',
			[
				'label'               => __( 'Get SEO Options', 'lw-seo' ),
				'description'         => __( 'Get global LW SEO settings.', 'lw-seo' ),
				'category'            => 'seo',
				'execute_callback'    => [ SeoService::class, 'get_options' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'    => 'object',
					'default' => [],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success' => [ 'type' => 'boolean' ],
						'options' => [ 'type' => 'object' ],
					],
				],
				'meta'                => self::readonly_meta(),
			]
		);
	}

	/**
	 * Read-only ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function readonly_meta(): array {
		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}

	/**
	 * Write ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function write_meta(): array {
		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}
}
