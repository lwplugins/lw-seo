<?php
/**
 * Robots.txt additions builder.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Robots;

/**
 * Adds LW SEO's lines to the robots.txt WordPress (and other plugins) built.
 */
final class Builder {

	/**
	 * Cloudflare Content Signals Policy text (CC0), verbatim.
	 *
	 * @see https://blog.cloudflare.com/content-signals-policy/
	 */
	public const POLICY_COMMENT = [
		'# As a condition of accessing this website, you agree to abide by the following content signals:',
		'# (a) If a content-signal = yes, you may collect content for the corresponding use.',
		'# (b) If a content-signal = no, you may not collect content for the corresponding use.',
		'# (c) If the website operator does not include a content signal for a corresponding use, the website operator neither grants nor restricts permission via content signal with respect to the corresponding use.',
		'# The content signals and their meanings are:',
		"# search: building a search index and providing search results (e.g., returning hyperlinks and short excerpts from your website's contents). Search does not include providing AI-generated search summaries.",
		'# ai-input: inputting content into one or more AI models (e.g., retrieval augmented generation, grounding, or other real-time taking of content for generative AI search answers).',
		'# ai-train: training or fine-tuning AI models.',
		'# ANY RESTRICTIONS EXPRESSED VIA CONTENT SIGNALS ARE EXPRESS RESERVATIONS OF RIGHTS UNDER ARTICLE 4 OF THE EUROPEAN UNION DIRECTIVE 2019/790 ON COPYRIGHT AND RELATED RIGHTS IN THE DIGITAL SINGLE MARKET.',
	];

	/**
	 * Build the final robots.txt.
	 *
	 * @param string                                                                                                             $output  robots.txt built so far.
	 * @param array{public: bool, sitemap: string, llms: string, llms_full: string, signal: string, blocked: array<int, string>} $context Additions.
	 * @return string
	 */
	public static function build( string $output, array $context ): string {
		$output = str_replace( [ "\r\n", "\r" ], "\n", $output );

		if ( '' !== $context['signal'] ) {
			$output = implode( "\n", self::POLICY_COMMENT ) . "\n\n" . self::add_signal( $output, $context['signal'] );
		}

		$lines = [];

		if ( $context['public'] && '' !== $context['sitemap'] && ! str_contains( $output, 'Sitemap: ' . $context['sitemap'] ) ) {
			array_push( $lines, 'Sitemap: ' . $context['sitemap'], '' );
		}

		foreach ( $context['blocked'] as $agent ) {
			array_push( $lines, 'User-agent: ' . $agent, 'Disallow: /', '' );
		}

		if ( '' !== $context['llms'] ) {
			$lines[] = '# llms.txt: ' . $context['llms'];
		}

		if ( '' !== $context['llms_full'] ) {
			$lines[] = '# llms-full.txt: ' . $context['llms_full'];
		}

		if ( [] === $lines ) {
			return $output;
		}

		return rtrim( $output ) . "\n\n# LW SEO\n" . rtrim( implode( "\n", $lines ) ) . "\n";
	}

	/**
	 * Put a Content-Signal line into the "User-agent: *" group.
	 *
	 * @param string $output robots.txt content.
	 * @param string $signal Directive value, e.g. "search=yes, ai-train=no".
	 * @return string
	 */
	public static function add_signal( string $output, string $signal ): string {
		$output = str_replace( [ "\r\n", "\r" ], "\n", $output );
		$line   = 'Content-Signal: ' . $signal;
		$count  = 0;
		$result = preg_replace( '/^(User-agent:[ \t]*\*[ \t]*)$/mi', '$1' . "\n" . $line, $output, 1, $count );

		if ( is_string( $result ) && $count > 0 ) {
			return $result;
		}

		// No star group: add one. "Allow: /" changes nothing, since without a
		// star group everything was allowed already.
		return rtrim( "User-agent: *\n" . $line . "\nAllow: /\n\n" . $output ) . "\n";
	}
}
