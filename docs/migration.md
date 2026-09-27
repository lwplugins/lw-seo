# Importing from Other SEO Plugins

LW SEO imports the SEO data of **Yoast SEO**, **Rank Math**, **SEOPress** and
**All in One SEO**. The other plugin does not have to be active: its data stays
in the database after you deactivate it, and that data is what the importer reads.

## How to run an import

- **Admin:** go to **LW Plugins → SEO → Import**. The tab looks for data from
  each plugin and shows a block only for the plugins it found data for.
  **Preview Migration** is a dry run that changes nothing. **Run Migration**
  imports the data.
- **WP-CLI:** `wp lw-seo migrate yoast|rankmath|seopress|aioseo [--dry-run] [--yes]`,
  see [WP-CLI](cli.md).

The same rules apply to every import:

- **Existing LW SEO data is never overwritten.** A post, term or setting that
  already has an LW SEO value keeps it, and the result counts it as "skipped
  (LW SEO data already present)". A setting that still has its LW SEO default
  value counts as not set, so the import can fill it.
- **The source data is read only.** Nothing is written to or deleted from the
  other plugin's meta, options or tables.
- **Running an import twice is safe.** The second run imports nothing, and
  redirects are not added twice.
- **Variables are converted.** In title templates, the source plugin's
  variables become LW SEO variables (tables below). LW SEO shows a post's or
  term's own SEO title, description and social text, and the home meta
  description, exactly as saved. Variables in those texts are therefore
  filled in at import time with that post's or term's values, for example
  `%%post_title%% | Shop` becomes `My post | Shop`.
- **Data with no LW SEO equivalent is reported, not dropped silently.** The
  import result lists it as warnings. This covers meta keys, robots flags,
  schema, keyphrases, and template variables that were removed from titles.

## SEOPress

Supported: SEOPress free. SEOPress PRO redirects are imported as well. Tested
with SEOPress 10.2 and SEOPress PRO 10.2.1.

### Posts and terms (`_seopress_*` meta)

| SEOPress | LW SEO |
|---|---|
| `_seopress_titles_title` | SEO title (variables converted) |
| `_seopress_titles_desc` | Meta description (variables converted) |
| `_seopress_robots_canonical` | Canonical URL (posts only) |
| `_seopress_social_fb_title` / `_desc` / `_img` | Social title / description / image |
| `_seopress_social_twitter_title` / `_desc` / `_img` | Used for the social fields when the Facebook value is empty (LW SEO builds Twitter Cards from the social fields) |
| `_seopress_robots_index` = `yes` | noindex |
| `_seopress_robots_follow` = `yes` | nofollow (posts only) |
| `_seopress_robots_primary_cat` | Primary category (`product_cat` for products). `none` or empty means no primary category. |
| `_seopress_redirections_*` (per-post / per-term redirect) | A redirect from the post or term URL, in the Redirects tab |

In SEOPress, a robots flag stores the *exclusion*: only the value `yes` means
noindex or nofollow.

### Settings

| SEOPress | LW SEO |
|---|---|
| Separator (`seopress_titles_sep`) | Separator. Only when LW SEO offers the same character. |
| Home title / description | Home title / description |
| Post, page and product title templates, and their noindex setting | `title_post` / `title_page` / `title_product`, `noindex_*` |
| Category and tag title templates, and their noindex setting | `title_category` / `title_post_tag`, `noindex_*` |
| Product archive title | `title_ptarchive_product` |
| Author, date, search and 404 titles, and author/date noindex | `title_author` / `title_date` / `title_search` / `title_404`, `noindex_author` / `noindex_date` |
| Open Graph and Twitter Card on/off, card size, default image | `opengraph_enabled`, `twitter_enabled`, `twitter_card_type`, `default_og_image` |
| Facebook, LinkedIn, Instagram and YouTube URLs, Twitter handle | Social profiles. `@handle` becomes `https://x.com/handle`. |
| Knowledge graph type, name and logo | `knowledge_type` (Person, or Organization for every other type), `knowledge_name`, `knowledge_logo` |
| XML sitemap on/off, included post types and taxonomies | `sitemap_enabled` and the post / page / product / category / tag sitemap toggles |

### Redirects

- **SEOPress PRO:** enabled redirects (301, 302, 307, 410, 451), including
  regex redirects.
- **SEOPress free:** the redirect set on a single post or term.

Disabled redirects are not imported. Neither are redirects limited to
logged-in users, because LW SEO redirects apply to every visitor.

### Template variables

| SEOPress | LW SEO |
|---|---|
| `%%sitetitle%%`, `%%sitename%%` | `%%sitename%%` |
| `%%tagline%%`, `%%sitedesc%%` | `%%sitedesc%%` |
| `%%sep%%` | `%%sep%%` |
| `%%post_title%%`, `%%title%%`, `%%cpt_plural%%` | `%%title%%` |
| `%%post_excerpt%%`, `%%excerpt%%`, `%%wc_single_short_desc%%` | `%%excerpt%%` |
| `%%post_date%%`, `%%date%%` / `%%post_modified_date%%` | `%%date%%` / `%%modified%%` |
| `%%post_author%%` | `%%author%%` |
| `%%post_category%%` / `%%post_tag%%` | `%%category%%` / `%%tag%%` |
| `%%_category_title%%`, `%%tag_title%%`, `%%term_title%%` | `%%term_title%%` |
| `%%_category_description%%`, `%%tag_description%%`, `%%term_description%%` | `%%term_description%%` |
| `%%search_keywords%%` | `%%searchphrase%%` |
| `%%current_pagination%%`, `%%page%%` | `%%pagenumber%%` ("Page 2 of 5" on paged archives, empty on page 1) |
| `%%currentday%%`, `%%currentmonth%%`, `%%currentyear%%`, `%%currentdate%%`, `%%archive_date%%` | `%%currentday%%`, `%%currentmonth%%`, `%%currentyear%%`, `%%currentdate%%` |

Other variables are removed from the imported text and listed in the warnings.
This includes the WooCommerce price and SKU variables, the author detail
variables, custom fields (`%%_cf_*%%`), custom taxonomies (`%%_ct_*%%`) and
`%%target_keyword%%`.

### Not imported

| SEOPress data | Why |
|---|---|
| `_seopress_analysis_target_kw` (target keywords) | LW SEO has no content analysis |
| `_seopress_robots_snippet`, `_seopress_robots_imageindex` | LW SEO has no per-post nosnippet / noimageindex |
| `_seopress_robots_breadcrumbs` | LW SEO has no per-post breadcrumb title |
| Term canonical URL and term nofollow | LW SEO terms have no such field |
| Per post type description templates | LW SEO generates descriptions from the excerpt (only the home description is a setting) |
| Other SEOPress PRO features (schemas, local business, …) | Out of scope |

## All in One SEO

Supported: All in One SEO free (Lite). Tested with All in One SEO 5.0.2.

All in One SEO keeps per-post data in its own table, `{prefix}aioseo_posts`,
not in post meta. The importer reads that table directly and never writes to
it. The free plugin has no term SEO and no redirects, so neither is imported.
If the Pro tables (`aioseo_terms`, `aioseo_redirects`) exist, the import shows
a warning, because their data is not imported.

### Posts (`aioseo_posts` columns)

| All in One SEO | LW SEO |
|---|---|
| `title` | SEO title (smart tags converted) |
| `description` | Meta description (smart tags converted) |
| `canonical_url` | Canonical URL |
| `og_title` / `og_description` | Social title / description |
| `og_image_custom_url` when `og_image_type` is `custom_image` | Social image |
| `twitter_title` / `twitter_description` / `twitter_image_custom_url` | Used for the social fields when they are empty, but only if the post does not reuse the Open Graph data (`twitter_use_og` = 0) |
| `robots_noindex` / `robots_nofollow` | noindex / nofollow, only when `robots_default` = 0 |
| `primary_term` (`{"category": 12}`) | Primary category / product category / brand |

When `robots_default` is 1, the post follows its post type's default settings.
Its robots columns are then ignored, just as All in One SEO ignores them.

`og_image_url` and `twitter_image_url` are not imported: All in One SEO fills
them automatically from the featured or content image.

### Settings (`aioseo_options`, `aioseo_options_dynamic`)

| All in One SEO | LW SEO |
|---|---|
| `searchAppearance.global.separator` | Separator (only when LW SEO offers the same character) |
| `searchAppearance.global.siteTitle` / `metaDescription` | Home title / description |
| Post, page and product title templates, and their robots settings | `title_*`, `noindex_*` |
| Category and tag title templates, and their robots settings | `title_category` / `title_post_tag`, `noindex_*` |
| Product archive title | `title_ptarchive_product` |
| Author, date and search archive titles, author/date robots | `title_author` / `title_date` / `title_search`, `noindex_author` / `noindex_date` |
| `social.facebook.general.enable`, `social.twitter.general.enable` | `opengraph_enabled`, `twitter_enabled` |
| `social.twitter.general.defaultCardType` | `twitter_card_type` |
| `social.facebook.general.defaultImagePosts` | `default_og_image` |
| `social.profiles.urls.*` (Facebook, X, Instagram, LinkedIn, YouTube) | Social profiles |
| `searchAppearance.global.schema.*` (organization or person, name, logo) | `knowledge_type`, `knowledge_name`, `knowledge_logo` |
| `sitemap.general.*` | `sitemap_enabled` and the sitemap toggles |

The noindex settings are resolved the way All in One SEO resolves them:

- A content type hidden from search results is noindex.
- A content type left on "use default" follows the global robots settings.

Translated settings (`aioseo_options_localized`) take precedence over the
stored ones, as in All in One SEO.

### Smart tags

| All in One SEO | LW SEO |
|---|---|
| `#site_title`, `#blog_title` | `%%sitename%%` |
| `#tagline`, `#site_description` | `%%sitedesc%%` |
| `#separator_sa` | `%%sep%%` |
| `#post_title`, `#archive_title` | `%%title%%` |
| `#post_excerpt`, `#post_excerpt_only` | `%%excerpt%%` |
| `#post_date` / `#post_modified_date` | `%%date%%` / `%%modified%%` |
| `#author_name` | `%%author%%` |
| `#categories`, `#category` | `%%category%%` |
| `#taxonomy_title` / `#taxonomy_description` | `%%term_title%%` / `%%term_description%%` |
| `#search_term` | `%%searchphrase%%` |
| `#page_number` | `%%page%%` |
| `#current_year`, `#current_month`, `#current_day`, `#current_date`, `#archive_date` | `%%currentyear%%`, `%%currentmonth%%`, `%%currentday%%`, `%%currentdate%%` |

Other smart tags are removed and listed in the warnings. This includes
`#custom_field-…`, `#tax_name-…`, `#permalink`, `#post_content`, the author
detail tags and the post day/month/year tags. A `#word` that is not an All in
One SEO smart tag is left as it is.

### Not imported

| All in One SEO data | Why |
|---|---|
| `robots_noarchive`, `robots_nosnippet`, `robots_noimageindex`, `robots_noodp`, `robots_notranslate`, `robots_max_*` | LW SEO has noindex / nofollow only |
| `focus_keyword`, `keyphrases` | LW SEO has no content analysis |
| `schema` (graphs added in the editor) | LW SEO generates schema automatically |
| Per post type description templates | LW SEO generates descriptions from the excerpt |
| Pro tables (`aioseo_terms`, `aioseo_redirects`) | Pro only; recreate that data in LW SEO |

## Yoast SEO and Rank Math

The Yoast SEO and Rank Math importers work the same way. They import
options, post and term meta, primary categories and redirects (Yoast SEO
Premium, Rank Math). Their mappings are documented in the
[changelog](../CHANGELOG.md) (1.3.x and 1.4.0).
