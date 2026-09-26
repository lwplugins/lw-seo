# Changelog

## [1.7.4] - 2026-09-26

### Changed
- The LW Plugins overview page is now a searchable table showing each LW plugin's status and version, with one-click activation for installed plugins; it always uses the newest version shipped by any active LW plugin.

### Fixed
- LW Site Manager's MCP server now lists this plugin's abilities (they were only reachable through REST).

## [1.7.3] - 2026-09-26

### Fixed
- Descriptions and other generated outputs no longer bypass content restriction plugins. The meta, Open Graph and Twitter descriptions (also in the REST API), the product schema description, the `%%excerpt%%` variable, the Markdown (`/md`) excerpt and product short description, and the llms.txt descriptions now read the excerpt through `get_the_excerpt()` instead of the raw post content, so a membership or paywall plugin that hides the excerpt hides it there too. Password-protected posts get no generated description.

### Added
- Developer filter `lw_seo_meta_description` (`$description`, `$post`, `$context`) to change or empty any post description before output, including ones typed in the editor. Contexts: `meta`, `og`, `twitter`, `schema`, `markdown`, `llms`.

### Changed
- The REST API post description is now the same as the head meta description (the excerpt, cut to 30 words when automatic) instead of the first 160 characters of the content.

## [1.7.2] - 2026-09-25

### Fixed
- `Requires at least` raised to WordPress 6.6: the React settings screen needs the `react-jsx-runtime` script that core registers from 6.6, so on older versions the page stayed blank without an error (verified on 6.5 and 6.6)

## [1.7.1] - 2026-09-25

### Fixed
- Notices from themes and other plugins (for example a theme's purchase-code or recommended-plugins notice) could show on the LW SEO screen. They are now kept off every LW Plugins screen, whatever their markup.
- The "settings screen files are missing" notice is no longer hidden by the notice isolation.

## [1.7.0] - 2026-09-24

### Added
- New settings screen built with WordPress components: side navigation for every section, a top bar with Save/Discard and a Cmd/Ctrl+S shortcut, loading skeletons and a mobile layout. Only the settings you changed are saved, so saving one tab never resets another.
- "LW SEO" panel in the block editor's document sidebar (SEO title and description with character counters, noindex/nofollow, social title/description/image, canonical URL, content signals, markdown override). It saves with the post through a new `lw_seo` REST field. The classic meta box stays for the classic editor.
- Redirect manager rebuilt as a filterable, searchable table with add/edit forms, CSV import/export and inline validation. Every redirect gets a stable ID (existing redirects get one the first time the list is read).
- Admin REST API under `lw-seo/v1/admin/` (settings, redirects, migration) for users with `manage_options`.
- Bricks compatibility: while LW SEO renders the head, the Bricks theme's own meta description/robots/document title tags are turned off, and its Open Graph tags too when LW SEO's Open Graph is enabled.
- Hungarian translation of the whole new interface, including the block editor panel, the term fields and the FAQ block (JavaScript translation files shipped in `languages/`).

### Changed
- The term edit screen's SEO fields are rendered by the new interface, with the social and AI sections collapsible. They still save with the core term form.
- Settings are no longer registered through the Settings API, so migrators and WP-CLI that write `lw_seo_options` no longer have settings they did not send reset to off.

### Fixed
- The per-day opening hours on the Local SEO tab were never saved; they are now stored as `local_hours_{day}_closed|open|close` and appear in the LocalBusiness schema and shortcodes.
- Title templates containing `%%date%%`, `%%category%%` or other variables that start like a percent-encoded character were damaged on save.
- A regex redirect source starting with `^` (e.g. `^/old/(\d+)$`) got a slash in front of it and never matched. Regex sources are now stored as written, and patterns saved by earlier versions are repaired when matched, including `$1` in the destination.

### Removed
- The classic settings page, its stylesheet and the redirects/migration AJAX handlers.

## [1.6.2] - 2026-09-22

### Added
- Shop title template: `title_ptarchive_product` ("Shop Title" on the WooCommerce tab, default `%%title%% %%sep%% %%sitename%%`). Post type archives use a `title_ptarchive_{post_type}` template when one is set, and `%%title%%` there is the archive title (the shop page's title on the WooCommerce shop).
- `lw_seo_canonical_url( $url, $queried_object )` filter for the canonical URL LW SEO prints; `og:url` follows it. Returning an empty string prints no canonical tag.
- `lw_seo_sitemap_excluded_ids( $ids, $post_type )` filter for post IDs to leave out of the XML sitemap.

### Fixed
- Paginated term and author archives, and a front page listing posts, pointed their canonical and `og:url` at page 1; `/page/2/` and later now point at themselves, like post type archives (shop) and the posts page already did. Paginated posts (`<!--nextpage-->`) and comment pages take WordPress's own canonical for that page.
- Singular pages carried two `rel="canonical"` tags, LW SEO's and WordPress core's (disagreeing when a custom canonical was set). Core's is now removed whenever LW SEO prints one, and `wp_get_canonical_url()` returns the custom canonical.
- A custom SEO title on a post, page, product or the posts page kept WordPress's "- Site Name" suffix; it now replaces the whole title, the same as a custom term title.
- Post type archives, including the WooCommerce shop, got no title template.
- The WooCommerce Product schema could list reviews without `aggregateRating` when WooCommerce's cached rating count was stale; the rating is now recounted from the approved rated reviews in that case.
- The XML sitemap listed the WooCommerce cart, checkout and my account pages, which WooCommerce marks noindex.
- With "Redirect 404 to homepage" on, a renamed post or product's old URL redirected to the homepage (302) instead of its new URL (301); the homepage redirect now runs after WordPress's own 404 redirects.

### Changed
- GitHub Actions use `actions/checkout` v7; PHPStan runs against the WooCommerce 11.1 stubs (`php-stubs/woocommerce-stubs` ^11.1).

## [1.6.1] - 2026-09-22

### Fixed
- llms-full.txt and the Markdown endpoint (`/md`) now include the content of pages built with Bricks. They came out as a URL and a title only, because Bricks keeps a page's content in its own data, not in `post_content`. The content is rendered the way the Bricks theme's own Rank Math integration renders it.
- Every llms-full.txt entry now carries the post's SEO description (falling back to its excerpt) under its URL, so no entry has less than its llms.txt line, also for pages whose text comes from a theme template or custom fields.

### Added
- robots.txt lists `/llms-full.txt` next to `/llms.txt` (as a comment) when it is enabled.

## [1.6.0] - 2026-09-21

### Upgrade notes
- Content Signals now default to "not specified" (neither grants nor restricts) instead of allowing everything. If you never explicitly saved these settings, your site now sends no `Content-Signal` / `ai-content-signals` value until you set one on the AI/LLM tab.
- robots.txt is now built entirely through WordPress's core `robots_txt` filter. A physical `robots.txt` file in the site root always took priority over WordPress's virtual one and still does — the Advanced tab and `wp lw-seo robots preview` now warn when one exists.
- Blocking "Claude-Web" no longer has any effect (Anthropic retired that token); the setting auto-migrates to blocking `ClaudeBot` on upgrade.
- `cohere-ai` was removed from the crawler list; any block toggle you had for it is dropped on upgrade.
- The custom Markdown override field (post/term) can now only be set by users with the `unfiltered_html` capability. Other roles see it read-only; their previously saved values are kept as-is — review overrides saved by non-admin roles before relying on them.
- The Markdown endpoint (`/md`) now answers 404, not 403, for a private post the visitor can't read (so it no longer confirms the post exists), and never serves a password-protected post's Markdown even with a valid password cookie.
- `X-Content-Signals` is deprecated; `Content-Signal` is now sent alongside it and will become the only header in a later release. Update anything reading the old header.
- llms.txt now has one section per post type (keyed by post type, not by its label) and lists every listed post type, not just posts and pages — re-check the AI/LLM tab's per-type toggles and item limit after upgrading.

### Added

**XML sitemap**
- Every public custom post type is included automatically; each can be switched off individually on the Sitemap tab. Custom taxonomies are opt-in (off by default); tags now have their own toggle alongside categories.
- One `PostProvider` / `TaxonomyProvider` is built per enabled type, lazily, so a post type registered later on `init` (ACF, CPT UI, etc.) is still picked up.
- Per-type sitemap URLs now accept hyphens and digits in the type/taxonomy name — `sitemap-case-study.xml`, `sitemap-top-10.xml` — not just letters and underscores.
- Noindex post types/taxonomies, and individually noindexed or password-protected posts, are left out of every sitemap.
- The documented filters `lw_seo_sitemap_post_types`, `lw_seo_sitemap_exclude_post` and `lw_seo_sitemap_urls( $items, $name, $page )` are now actually implemented.

**llms.txt**
- Rewritten to the llmstxt.org v2 structure: one `##` section per post type (pages in menu order, other types newest first). Two types that share a label, or a type literally named "Optional", each keep their own section — the post type name is appended to the heading to disambiguate.
- Admin-configurable summary (rendered as the document's blockquote) and a free-text introduction.
- Per-section item limit (`llms_txt_max_items`, default 100, clamped to 1-500).
- Section descriptions come from each post's SEO description, falling back to its excerpt.
- Optional "Extra links" textarea (`llms_txt_optional_links`, one `Title | URL | optional description` per line), always rendered last under its own "Optional" heading — it can never merge with a content section.
- Optional Markdown links (`llms_txt_markdown_links`): section links point at each post's `/md` Markdown URL instead of its HTML permalink.
- Opt-in `/llms-full.txt` (`llms_full_txt_enabled`), concatenating the Markdown of every listed post, capped at 1 MiB with a truncation notice.
- Both documents are cached in a transient (see Fixed for the invalidation rules).
- New filter `lw_seo_llms_txt_post_types` to add or remove post types from the section list.

**Markdown endpoint**
- Accept-header negotiation now honours q-values: on the plain HTML URL, Markdown is served only when its q-value is above 0 and at least `text/html`'s (both default to `q=1` when omitted).
- Subdirectory-aware request-path handling.
- The static front page's Markdown is now served at `/md/` (site root + suffix), the same way any other page's is at `{page-url}/md/`.
- Discovery links per the llms.txt v2 convention: `<link rel="alternate" type="text/markdown">` and `<link rel="describedby">` in `<head>` on every AI-visible singular page, mirrored as HTTP `Link` header entries; the Markdown response itself sends `Link: <canonical-html-url>; rel="canonical"` back.
- HTML-to-Markdown converter rewritten: Gutenberg images/tables/captions render correctly, nested and ordered lists no longer duplicate, blockquotes survive, `<hr>`/`<br>` are preserved, fenced code blocks carry a language and size their backtick fence to the content, lazy-loaded images are handled, and navigation/form/script/style noise is stripped.

**AI crawlers**
- Crawler registry rebuilt from each vendor's current documentation, 22 tokens in total: `GPTBot` / `OAI-SearchBot` / `ChatGPT-User` (OpenAI), `ClaudeBot` / `Claude-SearchBot` / `Claude-User` (Anthropic), `Google-Extended` (Google), `Applebot-Extended` (Apple), `PerplexityBot` / `Perplexity-User` (Perplexity), `meta-externalagent` / `meta-webindexer` / `meta-externalfetcher` (Meta), `Amazonbot` / `Amzn-SearchBot` / `Amzn-User` (Amazon), `MistralAI-Training` / `MistralAI-Index` / `MistralAI-User` (Mistral AI), `CCBot` (Common Crawl), `AI2Bot` (Allen Institute for AI), `Bytespider` (ByteDance).
- Each crawler is tagged with a purpose — training, search, or user-triggered — and the AI/LLM tab groups them by purpose with a "block all training / search / user-triggered" toggle (`block_purpose_training`, `block_purpose_search`, `block_purpose_user`), on top of the existing per-crawler `block_<key>` toggle.
- New filter `lw_seo_ai_crawlers` to add third-party crawlers to the registry; filtered-in crawlers are governed by the purpose toggles only, with no individual setting.
- Admin note that OpenAI, Perplexity, Meta and Amazon each document that their user-triggered fetchers may disregard `robots.txt` for a specific, user-initiated request.

**Content Signals**
- Three-state values (not specified / allow / disallow) for `search`, `ai-input` and `ai-train`, matching the Cloudflare Content Signals Policy vocabulary.
- Standard `Content-Signal` HTTP header (directive syntax, e.g. `search=yes, ai-train=no`), alongside the existing `ai-content-signals` meta tag.

**robots.txt**
- `Content-Signal:` line inserted inside the `User-agent: *` group, plus the verbatim Cloudflare Content Signals Policy comment block, whenever at least one signal is set.
- Advanced tab: live preview of the robots.txt WordPress would actually serve, and a warning naming the file path when a physical `robots.txt` in the site root would take priority over it.

**Admin UI**
- Notice on the LW SEO settings pages when Yoast SEO, Rank Math or All in One SEO is also active.
- Sitemap tab: per-post-type toggles, a tags toggle, and per-taxonomy opt-in toggles.
- AI/LLM tab restructured into a Content Signals block (three selects), the llms.txt block (summary/intro/post types/limits/extra links/llms-full), and the crawler groups (purpose toggles + per-crawler checkboxes).

**WP-CLI**
- `wp lw-seo llms preview [--full]`, `wp lw-seo llms flush`, `wp lw-seo llms info`.
- `wp lw-seo robots preview` (same physical-file warning as the Advanced tab).
- `wp lw-seo crawlers list [--format=<table|json|csv|yaml>]`, listing each crawler's company, purpose(s) and block reason (`individual` / `purpose:<name>` / `-`).
- `wp lw-seo sitemap info` now also lists every currently-built per-type sitemap.
- `wp lw-seo option set` is validated through the same sanitizer the settings form uses: typed bool/int parsing (`true`/`1`/`on`/`yes` and their opposites, case-insensitive; anything else errors instead of silently storing `false`), map options accept a JSON object (or a `key.subkey` dot-path to update one entry without touching the rest), and choice options are validated against a fixed list with the allowed values in the error message.
- New `docs/cli.md` documents every `wp lw-seo` command.

**Internals**
- `Content\PostTypes`: shared helper for the public, viewable post types and taxonomies the sitemap, llms.txt and Markdown endpoint all rely on.
- `Content\Eligibility`: shared gate (`is_post_eligible()`, `is_ai_visible()`) used by the sitemap, llms.txt and the Markdown endpoint, backing the new `lw_seo_post_is_eligible` filter — restrict-only: it can drop a post that already passed the built-in checks, but can never add one that didn't.
- `Admin\SettingsSanitizer` extracted from the settings page, with `int`, `map`, `textarea` and `choice` option-type support, shared by both the settings form and `wp lw-seo option set`.
- `Upgrader`: runs per-version option migrations and schedules a rewrite-rules flush on every version change, tracked in the new `lw_seo_version` option.

**Filters**
- `lw_seo_post_is_eligible`, `lw_seo_llms_txt_post_types`, `lw_seo_ai_crawlers` are new; the previously documented `lw_seo_sitemap_post_types`, `lw_seo_sitemap_exclude_post` and `lw_seo_sitemap_urls` are implemented for the first time.

### Changed

**Content Signals**
- New installs default every signal to "not specified"; existing settings keep their saved values (stored booleans migrate to `yes`/`no` on upgrade).
- Sites that never saved these settings previously sent `ai-train=yes, ai-input=yes, search=yes` by default; they now send no signal until one is explicitly configured.
- The `lw_seo_content_signals` filter now receives only the signals that are actually set — callbacks must read keys with `isset()` / `??` rather than assuming all three keys are present.
- The header is now `Content-Signal`; `X-Content-Signals` is still sent as a deprecated alias and will be removed in a later release.

**Markdown endpoint**
- Private posts the visitor can't read now answer 404 instead of 403 (existence not revealed); password-protected posts still answer 403, and a post entered via a valid password cookie is still never served at `/md` — a password-protected post is never AI-visible, cookie or not.

**robots.txt**
- Built entirely through WordPress's core `robots_txt` filter instead of a separate `^robots\.txt$` rewrite rule that bypassed it, dropping other plugins' contributions and hard-coding `/wp-admin/` itself.

**Docs**
- `docs/developers.md`: removed 13 hook sections for hooks nothing in `includes/` ever fired (`lw_seo_title`, `lw_seo_title_separator`, `lw_seo_meta_description`, `lw_seo_description_length`, `lw_seo_og_tags`, `lw_seo_default_social_image`, `lw_seo_schema`, `lw_seo_local_schema`, `lw_seo_breadcrumb_items`, `lw_seo_breadcrumb_separator`, `lw_seo_before_meta`, `lw_seo_after_meta`, `lw_seo_settings_saved`); every remaining hook's name and arguments were re-checked against the code, and the `lw_seo_content_signals` docblock now says it runs last and receives only the set signals.
- `docs/settings-sitemap.md`, `docs/settings-ai.md`, `docs/settings-advanced.md`, `docs/markdown-endpoint.md` and `docs/site-manager-abilities.md` rewritten for the 1.6.0 behaviour; new `docs/cli.md`.

**Translations**
- `lw-seo.pot` regenerated (531 strings); the Hungarian (`hu_HU`) translation updated to 100% (92 new strings translated, 3 duplicate entries collapsed, obsolete strings dropped).

### Fixed
- Blocking "Claude-Web" did not block Anthropic's crawler; the setting now migrates to `ClaudeBot`.
- llms.txt linked the draft Privacy Policy page, listed the static front page twice, included noindex and password-protected posts, and showed HTML entities instead of decoded text.
- Markdown on the HTML URL (Accept negotiation) had no `Vary: Accept`, so page caches could serve the Markdown response to browsers; q-values are now honoured instead of a plain substring match.
- Markdown frontmatter was invalid YAML for titles containing apostrophes.
- HTML to Markdown lost Gutenberg images and tables, duplicated nested lists, flattened blockquotes, and dropped `<hr>`/`<br>`.
- `<strong>Note: </strong>text` lost the space after the bold text (rendered glued to the following word, and CommonMark would have ignored the emphasis markers entirely since whitespace sat just inside them).
- robots.txt was generated outside the `robots_txt` filter, dropping other plugins' rules and hard-coding `/wp-admin/`.
- Noindex post types and taxonomies were still listed in the sitemap.
- llms-full.txt generation no longer leaves the global `$post` changed after it runs.
- robots.txt's Content-Signal insertion now normalizes CRLF line endings before matching the `User-agent: *` group.
- YAML frontmatter stays parseable when a value contains C1 control characters, the U+FFFE/U+FFFF noncharacters, or invalid UTF-8 (previously PyYAML/libyaml rejected the block, or the value came out empty).
- llms.txt: two post types with the same plural label, or a type literally labelled "Optional", no longer lose a section to the other.
- LW Site Manager `set-meta` collapsed the Markdown override onto one line (`sanitize_text_field` folds newlines); it now uses `sanitize_textarea_field`, like the meta box, and keeps its newlines.
- llms.txt cache invalidation is far more precise: it now rebuilds only for a published post of a listed type (a scheduled post going live, an unpublish, or a trash all count), for the plugin's own post meta keys, for a term edit, for a site address/title/permalink-structure change, after a plugin version change, and after settings are saved (registered even while llms.txt is off, so re-enabling it never serves a copy cached from before). Saving a revision, autosave, draft, or a post of an unlisted type no longer clears the cache.

### Security
- The Markdown endpoint no longer serves noindex content or content with AI Input set to "No"; private posts use the `read_post` capability (so custom capability setups are respected) and answer 404 instead of 403 when access is denied.
- Category/tag Markdown honours a site-wide or per-term `ai-input=no`; a term's "## Posts" list only includes posts eligible under the same gate as the sitemap and llms.txt.
- `javascript:`, `vbscript:` and `data:` URLs are dropped from Markdown link/image/iframe destinations, including entity-encoded, `<…>`-wrapped and backslash-obfuscated variants of the scheme.
- Markdown URLs percent-encode control characters, backtick and backslash so an obfuscated destination can no longer break out of the link syntax and let the next piece of Markdown be read as a live link or raw HTML.
- Inline code fences are sized to one backtick longer than the longest backtick run in their content (with CommonMark's padding rule), and every point that concatenates rendered Markdown pieces (inline text, block/list/container children) inserts a space when it would otherwise fuse two backtick fences or leave a trailing backslash escaping the next piece — closing off ways a decoded `<img onerror>`-style payload could end up read as live HTML by a downstream CommonMark renderer.
- Text nodes, image `alt` text, and heading/list-item titles (post titles, product attribute labels/values, term names) are Markdown-escaped so post content can no longer inject a live link, emphasis, autolink or raw HTML tag into the output; WooCommerce product attribute values and labels go through the same escaping before being placed in the attribute table.
- YAML frontmatter values are `\u`-escaped for `<`, `>`, `[`, `]` and backtick, so a hostile title, author name, category/tag, excerpt or price can't be read as a heading, link or raw HTML by a CommonMark renderer without a frontmatter extension.
- llms.txt and llms-full.txt are now built as a logged-out visitor (user ID 0, restored afterwards even if the builder throws), so the shared cached copy no longer contains whatever `the_content`, a shortcode, or a membership/LMS plugin showed the user — often an admin — who happened to trigger the rebuild.
- llms.txt escapes Markdown syntax in the site title, summary, section headings, link titles and descriptions (SEO description or excerpt), and filters and percent-encodes link URLs through the same scheme filter as the Markdown endpoint, so an author-controlled title or excerpt can no longer inject a link or raw HTML.
- Per-post and per-term content signal values are whitelisted to `yes`/`no`; any other submitted value deletes the meta row instead of being stored.
- The custom Markdown override (post and term) can only be set by users with the `unfiltered_html` capability, because it is served unescaped at `/md`; other users see the field read-only and their existing value is preserved untouched (neither overwritten nor deleted). This applies to the post editor meta box, the term edit screen, and the LW Site Manager `set-meta` ability alike.
- LW Site Manager `set-meta` now requires `edit_post` / `edit_term` on the specific target object (previously only the generic `edit_posts` capability), and reports fields it silently left unchanged as `skipped` in the response (currently only `markdown_content` without `unfiltered_html`).
- LW Site Manager `get-meta`, `get-content-signals` and `get-markdown` now require the target object to be publicly readable, or the caller to have `edit_post` / `edit_term` on it — previously any caller with the generic `can_edit_posts` permission could read another user's drafts, private posts, password-protected posts, or terms of a non-public taxonomy.

### Removed
- `cohere-ai` removed from the crawler list (not documented by Cohere).

## [1.5.1] - 2026-09-06

### Fixed
- The release package and the Composer/Packagist dist no longer ship tests, docs or development configuration (`.gitattributes` export-ignore plus unified release excludes). A hosting malware scanner had flagged a unit-test fixture on a customer site

## [1.5.0] - 2026-08-24

### Added
- Canonical URL on post type archives (including the WooCommerce shop page); without it every filter/sort parameter became a separate indexable URL.
- Self-referencing canonical on paged post type archives and on the paged posts page.

### Fixed
- The blog posts page (`is_home()` with a static front page) received the front page's canonical, `og:url` and title. It now uses its own page meta.

### Changed
- Head meta output and document title filtering extracted from `Plugin` into `Meta\HeadMeta`, `Meta\SingularMeta`, `Meta\ArchiveMeta`, `Meta\TagRenderer`, `Meta\ArchiveContext` and `Meta\TitleFilter`.
- PHPStan baseline reduced 7 -> 5 entries.

## [1.4.2] - 2026-08-20

### Fixed
- Activation now registers the sitemap, robots.txt and llms.txt rewrite rules before flushing (`Activator`), so `/sitemap.xml` no longer 404s until the next permalink save. Covered by `ActivatorTest`.
- Virtual endpoints (`/sitemap.xml`, `/sitemap-*.xml`, `/llms.txt`, `/{post}/md`) were 301-redirected by `redirect_canonical` to a trailing-slash variant before the handler ran; `CanonicalGuard` now disables the canonical redirect for them. Covered by `CanonicalGuardTest`.

### Added
- `RewriteFlusher`: toggling sitemap / robots.txt / llms.txt schedules a rewrite flush for the next request (the current one cannot see the newly enabled rules). Rewrites are also flushed on deactivation. Covered by `RewriteFlusherTest`.

### Changed
- Tested up to WordPress 7.1.

## [1.4.1] - 2026-07-17

### Added
- PHPStan level 5 static analysis (`composer analyse`) with WordPress, WooCommerce, and WP-CLI stubs, plus a CI job that enforces it.

### Fixed
- Type-safety corrections flagged by static analysis: integer types for WordPress API parameters (author/user/comment/attachment ids), plus removal of redundant null-coalesce and dead code. All behaviour-preserving — no functional changes.

## [1.4.0] - 2026-07-17

### Added
- WP-CLI command surface under `wp lw-seo`:
  - `migrate rankmath|yoast` (`--dry-run`, `--yes`) — run an importer from the command line.
  - `redirect list|add|delete|import|export` — manage redirects (CSV round-trip).
  - `sitemap info|flush` — show the sitemap index URL / re-flush rewrite rules.
  - `option get|set|list|reset` — read and write LW SEO options.
- Yoast SEO migrator (`Migration\Yoast`) at parity with the RankMath importer:
  - Options from `wpseo_titles` / `wpseo_social` (titles, meta descriptions, per-type noindex, separator, social profiles, default OG image) and the knowledge-graph identity (`company_or_person` → `knowledge_type`, name, logo).
  - Post meta (`_yoast_wpseo_*`): title, description, canonical, OpenGraph/Twitter, robots (`meta-robots-noindex` `1` = noindex), primary category.
  - Term SEO read from the `wpseo_taxonomy_meta` option (`[taxonomy][term_id][field]`), including the string `wpseo_noindex` (`'noindex'`).
  - Yoast Premium redirects (`wpseo-premium-redirects-base`) when present.
  - Yoast `%%var%%` templates normalized to LW SEO variables; separator tokens (`sc-mdash`, …) mapped to characters.
- `Migration\MigratorInterface` shared by both migrators; the Import tab and AJAX handler are now provider-aware and render a Yoast block alongside RankMath.

### Security
- Markdown endpoint (`/md`, `/markdown`) now sends `X-Content-Type-Options: nosniff` on the response, preventing browsers from MIME-sniffing the plain-text Markdown body (which contains unescaped post content) as HTML.

## [1.3.14] - 2026-05-21

### Fixed
- **Fatal `TypeError` on every frontend singular page after a 1.3.13 RankMath migration.** `Plugin::get_og_image(): string` was receiving the `rank_math_og_content_image` cache array (`['check' => md5, 'images' => [...]]`) verbatim from `_lw_seo_og_image`. Root cause: 1.3.13's `Mappings::POST_META_MAP` included `'rank_math_og_content_image' => 'og_image'` based on the user-reported field count in #5, without checking that RankMath stores this key as a content-scan cache, not a user-selected URL.
- All OG-image read paths (`Plugin::get_og_image`, `Plugin::output_taxonomy_meta`, `RestApi::get_post_og`, `RestApi::get_post_twitter`, `MetaBox::render_meta_box`, `Admin\TermFields::render_social_section`) now coerce array values via `MetaCoerce::as_url()` — defensive against any already-bad row written by 1.3.13.
- One-time `CleanupV1314` pass on `init` (priority 99) scans `_lw_seo_og_image` post/term meta whose `meta_value` starts with `a:` (serialized array) and either normalizes it to a single URL or deletes the row. Guarded by `lw_seo_cleanup_v1314_done` option so it runs at most once.

### Changed
- `PostMetaMigrator` and `TermMetaMigrator` now call `MetaCoerce::is_writable_string()` before copying; for `og_image` they additionally extract a URL via `MetaCoerce::as_url()` and skip when none is found. Future RankMath cache shapes cannot land in string-typed `_lw_seo_*` slots.
- Removed `rank_math_og_content_image` from the migration map entirely.

## [1.3.13] - 2026-05-21

### Added
- WooCommerce slug-only permalinks (RankMath feature parity). Three new options on the WC settings tab:
  - `wc_remove_category_base` — strips `/product-category/`
  - `wc_remove_category_parent_slugs` — exposes leaf-only category URLs
  - `wc_remove_product_base` — strips `/product/`
- `SlugCollisionDetector` — checks each category's root slug against published root-level pages, WP reserved slugs, taxonomy / public-CPT rewrite bases, and WooCommerce special pages (shop / cart / checkout / my-account / terms). Colliding categories are silently skipped and listed in a notice on the WC tab. RankMath does not perform this check.
- `PermalinkRuleBuilder` — emits 5 rewrite rules per non-skipped category (root, embed, two feed variants, paginated), plus 2 product rules when `%product_cat%` is in the product permalink structure and `wc_remove_product_base` is on.
- `PermalinkWatcher` — orchestrates `rewrite_rules_array`, `term_link`, and `post_type_link` filters; soft-flushes on category CRUD, page CRUD (so a newly created page can reclaim its slug), and option change.
- RankMath migrator auto-copies `wc_remove_category_base`, `wc_remove_category_parent_slugs`, `wc_remove_product_base` from `rank-math-options-general` and flushes rewrites at the end of the migration run.

### Changed
- Migration "Woo permalink" warning is now severity `warning` (was `error`) and only fires when the LW SEO parity flag is OFF for a RankMath flag that is ON. Once the migrator copies the flag the warning clears.

## [1.3.12] - 2026-05-21

### Added
- RankMath migrator imports primary terms (`rank_math_primary_category`, `rank_math_primary_product_cat`, `rank_math_primary_product_brand`) into `_lw_seo_primary_{taxonomy}` post meta
- RankMath redirects DB table (`{prefix}rank_math_redirections`) is migrated into the LW SEO Redirects module; `sources` arrays are unserialized and each source becomes a separate LW SEO redirect entry, with `comparison` modes (`exact`, `regex`, `contains`, `start`, `end`) translated to LW SEO's regex flag
- RankMath Twitter overrides (`rank_math_twitter_title`/`_description`/`_image`) and `rank_math_og_content_image` are now migrated as OpenGraph fallbacks (LW SEO renders Twitter Cards from OG values)
- Migration UI warns when RankMath's WooCommerce permalink rewrites are active (`wc_remove_category_base`, `wc_remove_category_parent_slugs`, `wc_remove_product_base`) — disabling RankMath without preparing redirects 404s the affected URLs (LW SEO has no parity feature yet)
- Migration UI counts `rank_math_schema_*` post meta and other non-migratable keys (`rank_math_advanced_robots`, `rank_math_breadcrumb_title`, `rank_math_focus_keyword`, `rank_math_news_sitemap_robots`, `rank_math_pillar_content`, `rank_math_lock_modified_date`) so users see what was inspected but not moved

### Changed
- Post/term migration results split `skipped` into `skipped_already_present` (LW SEO target was filled) and `skipped_no_data` (no actionable RankMath data, e.g. default robots `["index","follow"]`); fixes the misleading "0 migrated, 730 skipped" reporting on real RankMath installs
- `MetaMigrator` split into atomic sub-migrators: `PostMetaMigrator`, `TermMetaMigrator`, `UserMetaMigrator`, `PrimaryTermMigrator`, `RedirectsMigrator`, `RobotsMigrator`, and `WarningCollector`

## [1.3.11] - 2026-05-08

### Fixed
- Open Graph and Twitter title on the homepage now uses the Homepage Title setting when a static page is configured as the front page (previously `output_meta_tags()` checked `is_singular()` before `is_front_page()`, so the singular meta path took over and rendered the page title instead)
- Default Social Image is now used as a fallback for Open Graph / Twitter on the homepage, taxonomy archives, and author archives (previously only applied to single posts)

## [1.3.10] - 2026-05-08

### Fixed
- Homepage Title setting (`title_home`) was ignored — now applied to the document `<title>` and Open Graph / Twitter titles on the front page and blog page

## [1.3.9] - 2026-04-30

### Changed
- Added missing `'default' => []` to top-level `input_schema` of `lw-seo/get-meta`, `lw-seo/get-content-signals`, and `lw-seo/get-markdown` so they can be invoked without arguments via the Abilities API

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
