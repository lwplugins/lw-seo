<?php
/**
 * AI crawler registry.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Crawlers;

/**
 * Known AI crawlers grouped by purpose.
 *
 * Tokens verified against each vendor's documentation on 2026-09-21.
 * Pure data class.
 */
final class Registry {

	/**
	 * Collects content to train models.
	 */
	public const TRAINING = 'training';

	/**
	 * Builds an AI search index.
	 */
	public const SEARCH = 'search';

	/**
	 * Fetches pages on a user's request.
	 */
	public const USER = 'user';

	/**
	 * Built-in crawlers.
	 *
	 * @return array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}>
	 */
	public static function builtin(): array {
		return [
			'gptbot'               => self::entry( 'GPTBot', 'OpenAI', self::TRAINING ),
			'oai_searchbot'        => self::entry( 'OAI-SearchBot', 'OpenAI', self::SEARCH ),
			'chatgpt_user'         => self::entry( 'ChatGPT-User', 'OpenAI', self::USER ),
			'claudebot'            => self::entry( 'ClaudeBot', 'Anthropic', self::TRAINING ),
			'claude_searchbot'     => self::entry( 'Claude-SearchBot', 'Anthropic', self::SEARCH ),
			'claude_user'          => self::entry( 'Claude-User', 'Anthropic', self::USER ),
			'google_extended'      => self::entry( 'Google-Extended', 'Google', self::TRAINING ),
			'applebot_extended'    => self::entry( 'Applebot-Extended', 'Apple', self::TRAINING ),
			'perplexitybot'        => self::entry( 'PerplexityBot', 'Perplexity', self::SEARCH ),
			'perplexity_user'      => self::entry( 'Perplexity-User', 'Perplexity', self::USER ),
			'meta_externalagent'   => self::entry( 'meta-externalagent', 'Meta', self::TRAINING ),
			'meta_webindexer'      => self::entry( 'meta-webindexer', 'Meta', self::SEARCH ),
			'meta_externalfetcher' => self::entry( 'meta-externalfetcher', 'Meta', self::USER ),
			'amazonbot'            => self::entry( 'Amazonbot', 'Amazon', self::TRAINING ),
			'amzn_searchbot'       => self::entry( 'Amzn-SearchBot', 'Amazon', self::SEARCH ),
			'amzn_user'            => self::entry( 'Amzn-User', 'Amazon', self::USER ),
			'mistralai_training'   => self::entry( 'MistralAI-Training', 'Mistral AI', self::TRAINING ),
			'mistralai_index'      => self::entry( 'MistralAI-Index', 'Mistral AI', self::SEARCH ),
			'mistralai_user'       => self::entry( 'MistralAI-User', 'Mistral AI', self::USER ),
			'ccbot'                => self::entry( 'CCBot', 'Common Crawl', self::TRAINING ),
			'ai2bot'               => self::entry( 'AI2Bot', 'Allen Institute for AI', self::TRAINING ),
			'bytespider'           => self::entry( 'Bytespider', 'ByteDance', self::SEARCH ),
		];
	}

	/**
	 * Built-in crawlers plus third-party additions.
	 *
	 * Filtered crawlers are governed by the purpose toggles only; they have
	 * no individual setting.
	 *
	 * @return array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}>
	 */
	public static function all(): array {
		/**
		 * Filter the AI crawler registry.
		 *
		 * @since 1.6.0
		 *
		 * @param array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}> $crawlers Crawlers.
		 */
		return (array) apply_filters( 'lw_seo_ai_crawlers', self::builtin() );
	}

	/**
	 * Option defaults: one block_{key} per crawler plus the purpose toggles.
	 *
	 * @return array<string, bool>
	 */
	public static function option_defaults(): array {
		$defaults = [];

		foreach ( [ self::TRAINING, self::SEARCH, self::USER ] as $purpose ) {
			$defaults[ 'block_purpose_' . $purpose ] = false;
		}

		foreach ( array_keys( self::builtin() ) as $key ) {
			$defaults[ 'block_' . $key ] = false;
		}

		return $defaults;
	}

	/**
	 * One registry entry.
	 *
	 * @param string $agent   User-agent token.
	 * @param string $company Operator.
	 * @param string $purpose Purpose.
	 * @return array{name: string, company: string, agent: string, purposes: array<int, string>}
	 */
	private static function entry( string $agent, string $company, string $purpose ): array {
		return [
			'name'     => $agent,
			'company'  => $company,
			'agent'    => $agent,
			'purposes' => [ $purpose ],
		];
	}
}
