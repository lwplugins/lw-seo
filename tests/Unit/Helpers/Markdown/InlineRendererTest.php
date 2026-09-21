<?php
/**
 * InlineRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Helpers\Markdown;

use LightweightPlugins\SEO\Helpers\Markdown\InlineRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class InlineRendererTest extends MonkeyTestCase {

	/**
	 * An invalid UTF-8 byte in the subject used to make the scheme-probe
	 * regex fail (with the /u modifier) and return null, which cast to ''
	 * skipped the scheme check entirely and let the raw URL through.
	 */
	public function test_url_rejects_unsafe_scheme_even_with_invalid_utf8_trailing_byte(): void {
		$this->assertSame( '', InlineRenderer::url( "javascript:alert(1)//\xff" ) );
	}
}
