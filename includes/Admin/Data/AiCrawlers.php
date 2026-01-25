<?php
/**
 * AI Crawlers Data.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Data;

/**
 * Provides AI crawler data.
 */
final class AiCrawlers {

	/**
	 * Get all AI crawlers.
	 *
	 * @return array<string, array{name: string, company: string, agent: string}>
	 */
	public static function get_all(): array {
		return [
			'gptbot'          => [
				'name'    => 'GPTBot',
				'company' => 'OpenAI',
				'agent'   => 'GPTBot',
			],
			'chatgpt_user'    => [
				'name'    => 'ChatGPT-User',
				'company' => 'OpenAI',
				'agent'   => 'ChatGPT-User',
			],
			'claude_web'      => [
				'name'    => 'Claude-Web',
				'company' => 'Anthropic',
				'agent'   => 'Claude-Web',
			],
			'google_extended' => [
				'name'    => 'Google-Extended',
				'company' => 'Google AI',
				'agent'   => 'Google-Extended',
			],
			'bytespider'      => [
				'name'    => 'Bytespider',
				'company' => 'ByteDance',
				'agent'   => 'Bytespider',
			],
			'ccbot'           => [
				'name'    => 'CCBot',
				'company' => 'Common Crawl',
				'agent'   => 'CCBot',
			],
			'perplexitybot'   => [
				'name'    => 'PerplexityBot',
				'company' => 'Perplexity AI',
				'agent'   => 'PerplexityBot',
			],
			'cohere_ai'       => [
				'name'    => 'Cohere-AI',
				'company' => 'Cohere',
				'agent'   => 'cohere-ai',
			],
		];
	}

	/**
	 * Get crawler user agents only.
	 *
	 * @return array<string, string> Key => User-Agent mapping.
	 */
	public static function get_agents(): array {
		$agents = [];
		foreach ( self::get_all() as $key => $crawler ) {
			$agents[ $key ] = $crawler['agent'];
		}
		return $agents;
	}
}
