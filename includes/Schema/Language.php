<?php
/**
 * Site language as a schema.org language tag.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Schema;

/**
 * Schema.org inLanguage expects an IETF BCP 47 tag (hu-HU, zh-CN), not the
 * WordPress locale (hu_HU, zh_CN). WordPress already builds that tag for the
 * <html lang> attribute, so the schema uses the same value.
 */
final class Language {

	/**
	 * The site language as a BCP 47 tag.
	 *
	 * @return string
	 */
	public static function tag(): string {
		return (string) get_bloginfo( 'language' );
	}
}
