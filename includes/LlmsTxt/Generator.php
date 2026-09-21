<?php
/**
 * LLMS.txt generator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Builds /llms.txt and /llms-full.txt from the collected sections.
 */
final class Generator {

	/**
	 * Section collector.
	 *
	 * @var SectionCollector
	 */
	private SectionCollector $collector;

	/**
	 * Constructor.
	 *
	 * @param SectionCollector|null $collector Collector (injectable).
	 */
	public function __construct( ?SectionCollector $collector = null ) {
		$this->collector = $collector ?? new SectionCollector();
	}

	/**
	 * The llms.txt document.
	 *
	 * @return string
	 */
	public function index(): string {
		$markdown = (bool) Options::get( 'llms_txt_markdown_links' );
		$sections = [];

		foreach ( $this->collector->posts() as $heading => $posts ) {
			$sections[ $heading ] = array_map(
				static fn( \WP_Post $post ): array => SectionCollector::link( $post, $markdown ),
				$posts
			);
		}

		$sections[ Document::OPTIONAL ] = $this->optional_links();

		return Document::render(
			[
				'title'    => (string) get_bloginfo( 'name' ),
				'summary'  => $this->summary(),
				'intro'    => (string) Options::get( 'llms_txt_intro' ),
				'sections' => $sections,
			]
		);
	}

	/**
	 * The llms-full.txt document.
	 *
	 * @return string
	 */
	public function full(): string {
		return FullText::assemble( (string) get_bloginfo( 'name' ), $this->chunks() );
	}

	/**
	 * Markdown chunks of every listed post, rendered on demand.
	 *
	 * @return \Generator<int, string>
	 */
	private function chunks(): \Generator {
		foreach ( $this->collector->posts() as $posts ) {
			foreach ( $posts as $post ) {
				yield FullText::chunk( $post );
			}
		}
	}

	/**
	 * Custom summary, falling back to the tagline.
	 *
	 * @return string
	 */
	private function summary(): string {
		$summary = (string) Options::get( 'llms_txt_summary' );

		return '' !== trim( $summary ) ? $summary : (string) get_bloginfo( 'description' );
	}

	/**
	 * Custom links plus the sitemap and llms-full.txt.
	 *
	 * @return array<int, array{title: string, url: string, description: string}>
	 */
	private function optional_links(): array {
		$links = SectionCollector::parse_links( (string) Options::get( 'llms_txt_optional_links' ) );

		if ( Options::get( 'sitemap_enabled' ) ) {
			$links[] = [
				'title'       => 'XML Sitemap',
				'url'         => Sitemap::get_index_url(),
				'description' => '',
			];
		}

		if ( Options::get( 'llms_full_txt_enabled' ) ) {
			$links[] = [
				'title'       => 'Full content (llms-full.txt)',
				'url'         => home_url( '/llms-full.txt' ),
				'description' => '',
			];
		}

		return $links;
	}
}
