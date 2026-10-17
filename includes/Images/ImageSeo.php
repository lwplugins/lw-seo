<?php
/**
 * Image SEO feature bootstrap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Images;

/**
 * Registers the image alt/title filters. Whether they act is decided per
 * request (options, front end, conflicting plugin).
 */
final class ImageSeo {

	/**
	 * Register the filters.
	 *
	 * @return void
	 */
	public function register(): void {
		( new AttachmentAttributes() )->register();
		( new ContentImages() )->register();
	}
}
