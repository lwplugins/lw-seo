<?php
/**
 * Page Sitemap Provider.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

/**
 * Provides pages for sitemap.
 */
final class Page_Provider extends Post_Provider {

    /**
     * Post type.
     *
     * @var string
     */
    protected string $post_type = 'page';

    /**
     * Option key.
     *
     * @var string
     */
    protected string $option_key = 'sitemap_pages';
}
