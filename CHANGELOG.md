# Changelog

## [1.3.8] - 2026-03-22

### Added
- LW Site Manager integration - SEO abilities for AI agents
- `lw-seo/get-meta` ability - get SEO meta for posts and terms
- `lw-seo/set-meta` ability - set SEO meta for posts and terms
- `lw-seo/get-content-signals` ability - get resolved AI content signals
- `lw-seo/get-markdown` ability - get markdown representation of content
- `lw-seo/get-options` ability - get global SEO settings

## [1.3.7]

### Added
- Full SEO settings for taxonomy archives (title, description, noindex, social, AI signals)
- Custom markdown content field for posts, pages, products, and taxonomy terms
- Markdown endpoint support for all custom taxonomies (product_cat, product_tag, etc.)
- Per-term Content Signals override (ai-train, ai-input, search)
- Per-term social meta (OG title, OG description, OG image)
- `Options::get_term_meta()` / `set_term_meta()` for term meta management

### Fixed
- Markdown endpoint `/md` URL now works for WooCommerce product categories and custom taxonomies
- Product add-to-cart URL no longer contains `/md/` path in markdown output

## [1.3.6]

### Fixed
- Add `X-Robots-Tag: noindex` to markdown endpoint responses to prevent search indexing

## [1.3.5]

### Added
- `/markdown/` endpoint alias for `/md/`

## [1.3.4]

### Fixed
- Smarter autoloader fallback - supports root Composer dependency installs

## [1.3.3]

### Fixed
- HTML entities in product price markdown output (e.g. `&amp;nbsp;`)

## [1.3.2]

### Added
- Add to cart link in WooCommerce product markdown output

### Fixed
- Markdown endpoint now uses `add_rewrite_endpoint` for correct slug resolution

## [1.3.0]

### Added
- Content Signals - AI content usage HTTP headers and meta tags
- Markdown endpoint (`/md`) for AI agent content consumption
- Per-post AI content signal override (ai-train, ai-input, search)
- `Accept: text/markdown` content negotiation support
- WooCommerce product markdown rendering
- Taxonomy/category markdown rendering
- Hook system for extending markdown output

## [1.2.6]

### Fixed
- Graceful error when autoloader is missing (admin notice instead of fatal error)

## [1.2.5]

### Fixed
- WooCommerce products, product categories and product tags now included in XML sitemap

## [1.2.4]

### Fixed
- Minor fix

## [1.2.3]

### Added
- Hash-based tab navigation on settings page
- Updated ParentPage with SVG icon support from registry

## [1.2.2]

### Fixed
- Admin notice isolation for notices relocated by WordPress core JS

## [1.2.1]

### Changed
- Isolate third-party admin notices on LW plugin pages

## [1.2.0]

### Added
- Fresh POT file and Hungarian (hu_HU) translation

## [1.1.9]

### Added
- Central plugin registry from GitHub JSON

## [1.1.8]

### Added
- FAQ Gutenberg block with FAQPage schema
- LW Memberships and LW LMS in plugin registry

### Fixed
- Include missing Blocks files in release

## [1.1.7]

### Added
- RankMath SEO data migrator (Import tab)
- Migrate global options, post meta, term meta, user meta
- Dry-run preview before migration
- Template variable conversion (`%var%` to `%%var%%`)

## [1.1.6]

### Added
- REST API for headless WordPress support
- `/wp-json/lw-seo/v1/meta/{id}` - Get SEO meta data by post ID
- `/wp-json/lw-seo/v1/meta/term/{id}` - Get SEO meta data by term ID
- `/wp-json/lw-seo/v1/meta/author/{id}` - Get SEO meta data by author ID
- `/wp-json/lw-seo/v1/schema/{id}` - Get Schema.org JSON-LD by post ID
- `/wp-json/lw-seo/v1/breadcrumbs/{id}` - Get breadcrumbs by post ID

## [1.1.5]

### Added
- Media library picker for Social Image in post meta box
- Image preview in Social Image field
- Remove button for Social Image
- Priority info text (Social Image > Featured Image > Default Image)

## [1.1.3]

### Changed
- Minimum PHP version lowered to 8.1

## [1.1.2]

### Changed
- Unified LW Plugins overview page with centralized plugin registry
- Dynamic plugin cards with active/inactive status detection

## [1.1.1]

### Added
- 404 settings tab with redirect to homepage option

## [1.1.0]

### Added
- Redirect Manager for creating and managing URL redirects
- Support for 301, 302, 307, 410, and 451 redirect types
- Regex support for advanced redirect patterns
- CSV import/export for bulk redirect management
- Hit counter and last accessed tracking for redirects

## [1.0.12]

### Fixed
- Early translation loading in Local SEO shortcodes (WordPress 6.7+)

## [1.0.11]

### Fixed
- Early translation loading warning on WordPress 6.7+

## [1.0.10]

### Fixed
- Remove obsolete `require_once` from main plugin file

## [1.0.9]

### Changed
- PSR-4 autoloading with PascalCase file/folder names
- Composer autoloader now handles all class loading
- Updated `phpcs.xml.dist` for PSR-4 compatibility

## [1.0.8]

### Added
- Local SEO with LocalBusiness Schema.org markup
- Business type selection (100+ Schema.org types)
- Address, phone, email settings for structured data
- Opening hours with OpeningHoursSpecification schema
- Geo coordinates for location data
- Shortcodes: `[lw_address]`, `[lw_phone]`, `[lw_email]`, `[lw_hours]`, `[lw_map]`

## [1.0.7]

### Added
- WooCommerce SEO integration (auto-detects WooCommerce)
- Product-specific OpenGraph tags (price, availability, brand, condition)
- Product Schema.org markup with reviews and offers
- WooCommerce settings tab for product SEO configuration
- Sitemap settings for products and product taxonomies

## [1.0.6]

### Fixed
- Custom title separator now applies to document title

## [1.0.5]

### Added
- Default social image setting for posts without featured image
- Image upload field with WordPress media library

### Fixed
- Sitemap tab icon now displays correctly

## [1.0.4]

### Fixed
- PHPCS/WPCS coding standards compliance

### Changed
- Move template function to separate `functions.php`
- Update `phpcs.xml.dist` configuration

## [1.0.3]

### Added
- Unified "LW Plugins" admin menu for all LW plugins
- Plugin overview dashboard page
- Tabbed settings interface with vertical navigation
- AI/LLM section to control AI crawler access (GPTBot, Claude-Web, etc.)
- Block/allow individual AI crawlers via `robots.txt`

### Changed
- Settings moved from Settings > LW SEO to LW Plugins > SEO

## [1.0.2]

### Fixed
- Add rewrite rules for `robots.txt` to work independently of server config

## [1.0.1]

### Fixed
- Remove `final` keyword from `Post_Provider` to allow `Page_Provider` extension

## [1.0.0]

### Added
- Initial release
- Meta titles and descriptions
- Open Graph and Twitter Cards
- XML Sitemap
- Schema.org JSON-LD
- Breadcrumbs
- `robots.txt` optimization
- `llms.txt` generation
