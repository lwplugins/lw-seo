<?php
/**
 * Markdown Renderer Interface.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Contract for markdown content renderers.
 */
interface RendererInterface {

	/**
	 * Get YAML frontmatter key-value pairs.
	 *
	 * @return array<string, mixed>
	 */
	public function frontmatter(): array;

	/**
	 * Get markdown body content.
	 *
	 * @return string
	 */
	public function body(): string;
}
