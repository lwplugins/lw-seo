# WP-CLI + Yoast SEO Migrator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `wp lw-seo …` WP-CLI command surface and a full-parity Yoast SEO
migrator to the lw-seo plugin, wired into the existing admin Import tab.

**Architecture:** The Yoast migrator mirrors the existing `Migration\RankMath`
sub-migrator structure under a new `Migration\Yoast` namespace, reusing shared
helpers (`MetaCoerce`, `Options`, `Redirects\Manager`, `SlugCollisionDetector`).
A shared `Migration\MigratorInterface` lets the provider-aware AJAX handler and
CLI dispatch to either migrator. CLI commands are thin wrappers over existing
services registered behind a `WP_CLI` guard.

**Tech Stack:** PHP 8.1+, WordPress 6.0+, WP-CLI, PHPUnit (pure-logic only —
bootstrap loads Composer autoloader, no WP test framework), WPCS.

## Global Constraints

- `declare(strict_types=1);` at the top of every PHP file.
- PSR-4: file path mirrors namespace `LightweightPlugins\SEO\…`; PascalCase
  filenames; no `class-` prefix; no manual `require_once`.
- File-size limits: class ≤200 lines, trait ≤80, interface ≤30, method ≤30.
- Text domain `lw-seo`; meta prefix `_lw_seo_` (`Options::META_PREFIX`); option
  name `lw_seo_options` (`Options::OPTION_NAME`).
- Existing LW SEO data is NEVER overwritten by any migrator.
- `composer phpcs` must pass with zero errors before every commit; run
  `composer phpcbf` first to auto-fix.
- Translator comments (`/* translators: … */`) directly precede any `sprintf`
  with a placeholder. No short ternary `?:`. Tabs for indentation.

## Verified Yoast storage facts (from Yoast/wordpress-seo trunk, 2026-07-17)

These are authoritative — do not second-guess from memory:

- **Post meta prefix:** `_yoast_wpseo_`. Fields: `title`, `metadesc`,
  `canonical`, `opengraph-title`, `opengraph-description`, `opengraph-image`,
  `opengraph-image-id`, `twitter-title`, `twitter-description`, `twitter-image`,
  `meta-robots-noindex`, `meta-robots-nofollow`, `bctitle`, `focuskw`,
  `schema_page_type`, `schema_article_type`, `is_cornerstone`.
- **Post `meta-robots-noindex` values:** `'0'` = post-type default, `'2'` =
  index, **`'1'` = noindex**. (Yes — 1 is noindex, 2 is index.)
- **Post `meta-robots-nofollow`:** `'1'` = nofollow.
- **Primary term:** `_yoast_wpseo_primary_<taxonomy>` — e.g.
  `_yoast_wpseo_primary_category` (stores the term_id).
- **Term SEO:** option `wpseo_taxonomy_meta`, keyed
  `[taxonomy][term_id][field]`. Fields: `wpseo_title`, **`wpseo_desc`** (this is
  the description — NOT `wpseo_metadesc`), `wpseo_canonical`, `wpseo_bctitle`,
  `wpseo_noindex`, `wpseo_opengraph-title`, `wpseo_opengraph-description`,
  `wpseo_opengraph-image`, `wpseo_twitter-title`, `wpseo_twitter-description`,
  `wpseo_twitter-image`, plus focuskw/scores.
- **Term `wpseo_noindex` values:** STRINGS `'default'` / `'index'` /
  **`'noindex'`** (different from the post-level numeric scheme).
- **Options `wpseo_titles`:** `separator`, `title-home-wpseo`,
  `metadesc-home-wpseo`, `title-<posttype>`, `metadesc-<posttype>`,
  `noindex-<posttype>` (bool), `title-tax-<taxonomy>`,
  `metadesc-tax-<taxonomy>`, `noindex-tax-<taxonomy>` (bool),
  `title-author-wpseo`, `noindex-author-wpseo`, `title-archive-wpseo`,
  `noindex-archive-wpseo`, `title-search-wpseo`, `title-404-wpseo`, and the
  knowledge-graph keys `company_or_person` (`'company'`|`'person'`),
  `company_name`, `company_logo`, `person_name`.
- **Options `wpseo_social`:** `facebook_site`, `twitter_site`, `instagram_url`,
  `linkedin_url`, `youtube_url`, `opengraph` (bool), `twitter` (bool),
  `twitter_card_type`, `og_default_image`, `og_frontpage_title`,
  `og_frontpage_desc`, `og_frontpage_image`.
- **Separator tokens** → char: `sc-dash`→`-`, `sc-mdash`→`—`, `sc-middot`→`·`,
  `sc-pipe`→`|`, `sc-raquo`→`»`, `sc-lt`→`>` (Yoast stores `&#062;` = `>`),
  everything else → fallback `-`. (LW SEO supports only `- | > » · — /`.)
- **Premium redirects:** option `wpseo-premium-redirects-base`, array of
  `[ 'origin' => string, 'url' => string, 'type' => int, 'format' =>
  'plain'|'regex' ]`. Absent on Yoast free.

## LW SEO target option keys (verified in Options::get_defaults())

`separator`, `title_home`, `desc_home`, `title_post`, `title_page`,
`title_product`, `title_category`, `title_post_tag`, `title_author`,
`title_date`, `title_search`, `title_404`, `noindex_post`, `noindex_page`,
`noindex_product`, `noindex_category`, `noindex_post_tag`, `noindex_author`,
`noindex_date`, `knowledge_type` (`'organization'`|`'person'`),
`knowledge_name`, `knowledge_logo`, `social_facebook`, `social_twitter`,
`social_instagram`, `social_linkedin`, `social_youtube`, `default_og_image`,
`twitter_card_type`, `opengraph_enabled`, `twitter_enabled`,
`breadcrumbs_enabled`, `sitemap_*`.

LW post/term meta fields (via `Options::META_PREFIX . <field>`): `title`,
`description`, `canonical`, `og_title`, `og_description`, `og_image`, `noindex`,
`nofollow`, `primary_category`.

## File Structure

```
includes/Migration/
├── MigratorInterface.php            (NEW, ≤30)
├── Ajax.php                         (MODIFY: provider-aware)
├── RankMath/Migrator.php            (MODIFY: implements MigratorInterface)
└── Yoast/                           (NEW)
    ├── Migrator.php
    ├── Mappings.php
    ├── SeparatorMap.php             (pure, unit-tested)
    ├── VariableConverter.php        (pure, unit-tested)
    ├── RobotsMigrator.php
    ├── PostMetaMigrator.php
    ├── TermMetaMigrator.php
    ├── PrimaryTermMigrator.php
    ├── OptionsMigrator.php
    ├── RedirectsMigrator.php
    └── WarningCollector.php
includes/CLI/                        (NEW)
├── Bootstrap.php
├── MigrateCommand.php
├── RedirectCommand.php
├── SitemapCommand.php
└── OptionCommand.php
includes/Plugin.php                  (MODIFY: register CLI)
includes/Admin/Settings/TabMigration.php   (MODIFY: Yoast block)
includes/Admin/SettingsPage.php      (MODIFY: localization)
assets/js/migration.js               (MODIFY: per-provider binding)
tests/Migration/Yoast/VariableConverterTest.php   (NEW)
tests/Migration/Yoast/SeparatorMapTest.php        (NEW)
```

**Testing note:** `tests/bootstrap.php` loads only the Composer autoloader — no
WordPress, no mocking. Only WP-function-free classes get isolated unit tests
(`VariableConverter`, `SeparatorMap`). WP-dependent migrators are verified on
the live croco2 test site (Task 18), matching the existing RankMath migrator
(which also ships without unit tests).

---

## Task 1: `Migration\MigratorInterface`

**Files:**
- Create: `includes/Migration/MigratorInterface.php`
- Modify: `includes/Migration/RankMath/Migrator.php:18`

**Interfaces:**
- Produces: `interface MigratorInterface { public function detect(): array; public function run(): array; }`

- [ ] **Step 1: Create the interface**

```php
<?php
/**
 * Shared contract for per-vendor SEO data migrators.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration;

/**
 * Contract implemented by each vendor migrator (RankMath, Yoast).
 */
interface MigratorInterface {

	/**
	 * Detect available source data without modifying anything.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array;

	/**
	 * Run the migration.
	 *
	 * @return array<string, mixed>
	 */
	public function run(): array;
}
```

- [ ] **Step 2: Make RankMath\Migrator implement it**

Change `final class Migrator {` to
`final class Migrator implements \LightweightPlugins\SEO\Migration\MigratorInterface {`
(add `use LightweightPlugins\SEO\Migration\MigratorInterface;` and
`implements MigratorInterface`). No behavior change — it already has
`detect()` and `run()`.

- [ ] **Step 3: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/MigratorInterface.php includes/Migration/RankMath/Migrator.php
git commit -m "Add Migration\\MigratorInterface; RankMath\\Migrator implements it"
```

---

## Task 2: `Yoast\Mappings`

**Files:**
- Create: `includes/Migration/Yoast/Mappings.php`

**Interfaces:**
- Produces: class constants `POST_META_MAP`, `PRIMARY_TERM_MAP`,
  `TITLE_OPTIONS_MAP`, `SOCIAL_OPTIONS_MAP`, `NON_MIGRATABLE_META`,
  `VARIABLE_NAME_MAP`, `SEPARATOR_TOKEN_MAP`.

- [ ] **Step 1: Create Mappings with verified keys**

```php
<?php
/**
 * Yoast SEO → LW SEO migration mapping constants.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

/**
 * Mapping constants for Yoast to LW SEO migration.
 *
 * Map order matters: primary (opengraph) keys precede twitter fallbacks so the
 * first non-empty source fills each still-empty LW SEO target.
 */
final class Mappings {

	/**
	 * Yoast post meta key (with _yoast_wpseo_ prefix) → LW SEO field (no prefix).
	 */
	public const POST_META_MAP = [
		'_yoast_wpseo_title'                 => 'title',
		'_yoast_wpseo_metadesc'              => 'description',
		'_yoast_wpseo_canonical'             => 'canonical',
		'_yoast_wpseo_opengraph-title'       => 'og_title',
		'_yoast_wpseo_opengraph-description' => 'og_description',
		'_yoast_wpseo_opengraph-image'       => 'og_image',
		'_yoast_wpseo_twitter-title'         => 'og_title',
		'_yoast_wpseo_twitter-description'   => 'og_description',
		'_yoast_wpseo_twitter-image'         => 'og_image',
	];

	/**
	 * Yoast term-meta field (inside wpseo_taxonomy_meta) → LW SEO field.
	 * NB: term description is 'wpseo_desc' (not 'wpseo_metadesc').
	 */
	public const TERM_META_MAP = [
		'wpseo_title'                 => 'title',
		'wpseo_desc'                  => 'description',
		'wpseo_canonical'             => 'canonical',
		'wpseo_opengraph-title'       => 'og_title',
		'wpseo_opengraph-description' => 'og_description',
		'wpseo_opengraph-image'       => 'og_image',
		'wpseo_twitter-title'         => 'og_title',
		'wpseo_twitter-description'   => 'og_description',
		'wpseo_twitter-image'         => 'og_image',
	];

	/**
	 * Yoast primary-term meta key → LW SEO meta field (no prefix).
	 */
	public const PRIMARY_TERM_MAP = [
		'_yoast_wpseo_primary_category'    => 'primary_category',
		'_yoast_wpseo_primary_product_cat' => 'primary_product_cat',
	];

	/**
	 * Yoast meta keys with no LW SEO equivalent (counted + reported).
	 */
	public const NON_MIGRATABLE_META = [
		'_yoast_wpseo_focuskw',
		'_yoast_wpseo_bctitle',
		'_yoast_wpseo_meta-robots-adv',
		'_yoast_wpseo_schema_page_type',
		'_yoast_wpseo_schema_article_type',
	];

	/**
	 * wpseo_titles option key → LW SEO option key.
	 * Robots/noindex handled separately (bool cast). Placeholders like
	 * title-<posttype> are resolved dynamically in OptionsMigrator.
	 */
	public const TITLE_OPTIONS_MAP = [
		'separator'          => 'separator',
		'title-home-wpseo'   => 'title_home',
		'metadesc-home-wpseo' => 'desc_home',
		'title-post'         => 'title_post',
		'title-page'         => 'title_page',
		'title-product'      => 'title_product',
		'title-tax-category' => 'title_category',
		'title-tax-post_tag' => 'title_post_tag',
		'title-author-wpseo' => 'title_author',
		'title-archive-wpseo' => 'title_date',
		'title-search-wpseo' => 'title_search',
		'title-404-wpseo'    => 'title_404',
	];

	/**
	 * wpseo_titles noindex key → LW SEO noindex option key (bool).
	 */
	public const NOINDEX_OPTIONS_MAP = [
		'noindex-post'         => 'noindex_post',
		'noindex-page'         => 'noindex_page',
		'noindex-product'      => 'noindex_product',
		'noindex-tax-category' => 'noindex_category',
		'noindex-tax-post_tag' => 'noindex_post_tag',
		'noindex-author-wpseo' => 'noindex_author',
		'noindex-archive-wpseo' => 'noindex_date',
	];

	/**
	 * wpseo_social option key → LW SEO option key.
	 */
	public const SOCIAL_OPTIONS_MAP = [
		'facebook_site'    => 'social_facebook',
		'twitter_site'     => 'social_twitter',
		'instagram_url'    => 'social_instagram',
		'linkedin_url'     => 'social_linkedin',
		'youtube_url'      => 'social_youtube',
		'og_default_image' => 'default_og_image',
		'twitter_card_type' => 'twitter_card_type',
	];

	/**
	 * Yoast %%variable%% name → LW SEO variable name. Unlisted names pass
	 * through unchanged; names in UNSUPPORTED_VARIABLES are stripped.
	 */
	public const VARIABLE_NAME_MAP = [
		'name'             => 'author',
		'primary_category' => 'category',
		'pt_single'        => '',
		'pt_plural'        => '',
		'focuskw'          => '',
		'sitedesc'         => 'sitedesc',
	];

	/**
	 * Yoast separator token → literal character (LW SEO supported set only).
	 */
	public const SEPARATOR_TOKEN_MAP = [
		'sc-dash'   => '-',
		'sc-mdash'  => '—',
		'sc-middot' => '·',
		'sc-pipe'   => '|',
		'sc-raquo'  => '»',
		'sc-lt'     => '>',
	];
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/Mappings.php
git commit -m "Add Yoast migration Mappings (verified against Yoast trunk)"
```

---

## Task 3: `Yoast\SeparatorMap` (pure, TDD)

**Files:**
- Create: `includes/Migration/Yoast/SeparatorMap.php`
- Test: `tests/Migration/Yoast/SeparatorMapTest.php`

**Interfaces:**
- Produces: `SeparatorMap::to_char(string $token): string` — returns a
  LW-supported separator char, `'-'` fallback for unknown tokens.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\SeparatorMap;
use PHPUnit\Framework\TestCase;

final class SeparatorMapTest extends TestCase {

	public function test_known_tokens_map_to_chars(): void {
		$this->assertSame( '—', SeparatorMap::to_char( 'sc-mdash' ) );
		$this->assertSame( '|', SeparatorMap::to_char( 'sc-pipe' ) );
		$this->assertSame( '»', SeparatorMap::to_char( 'sc-raquo' ) );
		$this->assertSame( '>', SeparatorMap::to_char( 'sc-lt' ) );
	}

	public function test_unknown_token_falls_back_to_dash(): void {
		$this->assertSame( '-', SeparatorMap::to_char( 'sc-bull' ) );
		$this->assertSame( '-', SeparatorMap::to_char( '' ) );
		$this->assertSame( '-', SeparatorMap::to_char( 'garbage' ) );
	}

	public function test_literal_char_passes_through_if_supported(): void {
		$this->assertSame( '|', SeparatorMap::to_char( '|' ) );
	}
}
```

- [ ] **Step 2: Run test — expect fail**

Run: `composer test -- --filter SeparatorMapTest`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement**

```php
<?php
/**
 * Yoast separator token → LW SEO character mapper.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Options;

/**
 * Converts a Yoast separator token (or literal char) into an LW-SEO-supported
 * separator character, defaulting to a dash.
 */
final class SeparatorMap {

	/**
	 * Map a Yoast separator token to a supported character.
	 *
	 * @param string $token Yoast token (e.g. 'sc-mdash') or literal char.
	 * @return string A separator character LW SEO supports.
	 */
	public static function to_char( string $token ): string {
		if ( isset( Mappings::SEPARATOR_TOKEN_MAP[ $token ] ) ) {
			return Mappings::SEPARATOR_TOKEN_MAP[ $token ];
		}

		$supported = Options::get_separators();
		if ( isset( $supported[ $token ] ) ) {
			return $token;
		}

		return '-';
	}
}
```

- [ ] **Step 4: Run test — expect pass**

Run: `composer test -- --filter SeparatorMapTest`
Expected: PASS.

> Note: `Options::get_separators()` is pure (returns a literal array, no WP
> calls) — safe under the autoloader-only test bootstrap.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/SeparatorMap.php tests/Migration/Yoast/SeparatorMapTest.php
git commit -m "Add Yoast SeparatorMap with unit tests"
```

---

## Task 4: `Yoast\VariableConverter` (pure, TDD)

**Files:**
- Create: `includes/Migration/Yoast/VariableConverter.php`
- Test: `tests/Migration/Yoast/VariableConverterTest.php`

**Interfaces:**
- Produces: `VariableConverter::convert(mixed $value): mixed` — rewrites Yoast
  `%%var%%` names to LW SEO names; strips unsupported names; non-strings pass
  through unchanged.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\VariableConverter;
use PHPUnit\Framework\TestCase;

final class VariableConverterTest extends TestCase {

	public function test_passthrough_names_unchanged(): void {
		$this->assertSame( '%%title%% %%sep%% %%sitename%%', VariableConverter::convert( '%%title%% %%sep%% %%sitename%%' ) );
	}

	public function test_renamed_names(): void {
		$this->assertSame( '%%author%%', VariableConverter::convert( '%%name%%' ) );
		$this->assertSame( '%%category%%', VariableConverter::convert( '%%primary_category%%' ) );
	}

	public function test_unsupported_names_stripped(): void {
		$this->assertSame( 'Buy %%title%%', VariableConverter::convert( 'Buy %%pt_single%%%%title%%' ) );
	}

	public function test_non_string_passthrough(): void {
		$this->assertSame( 42, VariableConverter::convert( 42 ) );
		$this->assertSame( '', VariableConverter::convert( '' ) );
	}
}
```

- [ ] **Step 2: Run test — expect fail**

Run: `composer test -- --filter VariableConverterTest`
Expected: FAIL.

- [ ] **Step 3: Implement**

```php
<?php
/**
 * Yoast template-variable converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

/**
 * Normalizes Yoast %%variable%% names to LW SEO names.
 *
 * Yoast already uses the %%name%% syntax, so this only renames diverging
 * variables (name → author, primary_category → category) and strips variables
 * LW SEO has no equivalent for (mapped to '' in VARIABLE_NAME_MAP).
 */
final class VariableConverter {

	/**
	 * Convert Yoast variables in a value to LW SEO format.
	 *
	 * @param mixed $value The value to convert.
	 * @return mixed
	 */
	public static function convert( mixed $value ): mixed {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		return preg_replace_callback(
			'/%%([a-z_]+)%%/',
			static function ( array $matches ): string {
				$var = $matches[1];
				if ( ! array_key_exists( $var, Mappings::VARIABLE_NAME_MAP ) ) {
					return '%%' . $var . '%%';
				}
				$mapped = Mappings::VARIABLE_NAME_MAP[ $var ];
				return '' === $mapped ? '' : '%%' . $mapped . '%%';
			},
			$value
		);
	}
}
```

- [ ] **Step 4: Run test — expect pass**

Run: `composer test -- --filter VariableConverterTest`
Expected: PASS.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/VariableConverter.php tests/Migration/Yoast/VariableConverterTest.php
git commit -m "Add Yoast VariableConverter with unit tests"
```

---

## Task 5: `Yoast\RobotsMigrator`

**Files:**
- Create: `includes/Migration/Yoast/RobotsMigrator.php`

**Interfaces:**
- Consumes: `Options::META_PREFIX`.
- Produces: `new RobotsMigrator(bool $dry_run)`;
  `migrate_post(int $post_id): array{migrated:bool,target_full:bool}`;
  `apply_flags(string $type, int $id, bool $noindex, bool $nofollow): array{migrated:bool,target_full:bool}`.

**Design:** Unlike RankMath (one array meta), Yoast splits noindex/nofollow into
two post-meta keys with different value schemes, and terms store a string
noindex inside the option (handled by TermMetaMigrator, which calls
`apply_flags`). This class exposes a post path (reads Yoast meta) and a generic
`apply_flags` used by both post and term migrators. Reuse the RankMath
`set_flag` pattern for the "never overwrite" check.

- [ ] **Step 1: Implement**

```php
<?php
/**
 * Yoast robots meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Options;

/**
 * Migrates Yoast robots flags into _lw_seo_noindex / _lw_seo_nofollow.
 *
 * Post level: _yoast_wpseo_meta-robots-noindex '1' = noindex (0=default,
 * 2=index); _yoast_wpseo_meta-robots-nofollow '1' = nofollow.
 * Term level: the string 'noindex' — resolved by TermMetaMigrator, which passes
 * booleans to apply_flags().
 */
final class RobotsMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Migrate robots flags for a post from Yoast post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array{migrated: bool, target_full: bool}
	 */
	public function migrate_post( int $post_id ): array {
		$noindex  = '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
		$nofollow = '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );

		return $this->apply_flags( 'post', $post_id, $noindex, $nofollow );
	}

	/**
	 * Apply resolved noindex/nofollow booleans to LW SEO meta.
	 *
	 * @param string $type     'post' or 'term'.
	 * @param int    $id       Entity ID.
	 * @param bool   $noindex  Whether noindex should be set.
	 * @param bool   $nofollow Whether nofollow should be set.
	 * @return array{migrated: bool, target_full: bool}
	 */
	public function apply_flags( string $type, int $id, bool $noindex, bool $nofollow ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( [ 'noindex' => $noindex, 'nofollow' => $nofollow ] as $flag => $wanted ) {
			if ( ! $wanted ) {
				continue;
			}
			$result      = $this->set_flag( $type, $id, $flag );
			$migrated    = $migrated || $result['migrated'];
			$target_full = $target_full || $result['target_full'];
		}

		return [
			'migrated'    => $migrated,
			'target_full' => $target_full,
		];
	}

	/**
	 * Set a single flag without overwriting existing LW SEO data.
	 *
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 * @param string $flag 'noindex' or 'nofollow'.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function set_flag( string $type, int $id, string $flag ): array {
		$meta_key = Options::META_PREFIX . $flag;
		$getter   = 'get_' . $type . '_meta';
		$existing = $getter( $id, $meta_key, true );

		if ( '' !== $existing && false !== $existing ) {
			return [
				'migrated'    => false,
				'target_full' => true,
			];
		}

		if ( ! $this->dry_run ) {
			$updater = 'update_' . $type . '_meta';
			$updater( $id, $meta_key, '1' );
		}

		return [
			'migrated'    => true,
			'target_full' => false,
		];
	}
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/RobotsMigrator.php
git commit -m "Add Yoast RobotsMigrator (post numeric + term boolean flags)"
```

---

## Task 6: `Yoast\PostMetaMigrator`

**Files:**
- Create: `includes/Migration/Yoast/PostMetaMigrator.php`

**Interfaces:**
- Consumes: `Mappings::POST_META_MAP`, `MetaCoerce`, `VariableConverter`,
  `Options::META_PREFIX`, `RobotsMigrator::migrate_post`.
- Produces: `migrate(): array{migrated:int,skipped_already_present:int,skipped_no_data:int}`.

**Design:** Mirror `includes/Migration/RankMath/PostMetaMigrator.php` exactly,
with these changes: (a) `find_post_ids()` queries `meta_key LIKE '_yoast_wpseo_%'`;
(b) use `Mappings::POST_META_MAP` (Yoast keys); (c) title/description convert via
`Yoast\VariableConverter`; (d) robots via `Yoast\RobotsMigrator::migrate_post`.
The `bucket()`, `migrate_fields()` og_image/target-full logic is identical.

- [ ] **Step 1: Implement (mirror RankMath, changed methods shown in full)**

```php
	/**
	 * Find all post IDs with any _yoast_wpseo_* meta worth examining.
	 *
	 * @return array<string>
	 */
	private function find_post_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		return (array) $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE '_yoast_wpseo_%'"
		);
	}
```

The constructor stores `$this->robots = new RobotsMigrator( $dry_run );`,
`migrate_post()` calls `$this->robots->migrate_post( $post_id )` (not
`migrate('post', …)`), and `migrate_fields()` iterates
`Mappings::POST_META_MAP`, using `MetaCoerce::is_writable_string`,
`MetaCoerce::as_url` for `og_image`, the empty-existing guard, and
`VariableConverter::convert` for `title`/`description` — copy these method
bodies verbatim from `RankMath\PostMetaMigrator::migrate_fields()`, swapping the
`use` for `LightweightPlugins\SEO\Migration\Yoast\VariableConverter`.

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/PostMetaMigrator.php
git commit -m "Add Yoast PostMetaMigrator"
```

---

## Task 7: `Yoast\TermMetaMigrator` (reads the option, not termmeta)

**Files:**
- Create: `includes/Migration/Yoast/TermMetaMigrator.php`

**Interfaces:**
- Consumes: option `wpseo_taxonomy_meta`, `Mappings::TERM_META_MAP`, `MetaCoerce`,
  `VariableConverter`, `Options::META_PREFIX`, `RobotsMigrator::apply_flags`.
- Produces: `migrate(): array{migrated:int,skipped_already_present:int,skipped_no_data:int}`.

**Design:** THE structural difference. Iterate the nested
`wpseo_taxonomy_meta` option `[taxonomy][term_id][field]`. For each term: write
mapped fields to `_lw_seo_*` term meta (never overwriting), convert
title/description variables, coerce og_image to URL, and apply robots from the
string `wpseo_noindex === 'noindex'` via `RobotsMigrator::apply_flags('term',
…)`.

- [ ] **Step 1: Implement**

```php
<?php
/**
 * Yoast term-meta migrator (reads the wpseo_taxonomy_meta option).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Migrates Yoast term SEO from the wpseo_taxonomy_meta option into LW SEO term
 * meta. Yoast stores taxonomy SEO as [taxonomy][term_id][field], NOT in the
 * termmeta table.
 */
final class TermMetaMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Robots sub-migrator.
	 *
	 * @var RobotsMigrator
	 */
	private RobotsMigrator $robots;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
		$this->robots  = new RobotsMigrator( $dry_run );
	}

	/**
	 * Run term meta migration.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$counts = [
			'migrated'                => 0,
			'skipped_already_present' => 0,
			'skipped_no_data'         => 0,
		];

		$data = get_option( 'wpseo_taxonomy_meta', [] );
		if ( ! is_array( $data ) ) {
			return $counts;
		}

		foreach ( $data as $terms ) {
			if ( ! is_array( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term_id => $fields ) {
				if ( ! is_array( $fields ) || ! get_term( (int) $term_id ) ) {
					continue;
				}
				$this->bucket( $counts, $this->migrate_term( (int) $term_id, $fields ) );
			}
		}

		return $counts;
	}

	/**
	 * Bucket a per-term result into running counts.
	 *
	 * @param array{migrated:int,skipped_already_present:int,skipped_no_data:int} $counts Counts (by ref).
	 * @param array{migrated:bool,target_full:bool}                               $result Per-term result.
	 * @return void
	 */
	private function bucket( array &$counts, array $result ): void {
		if ( $result['migrated'] ) {
			++$counts['migrated'];
		} elseif ( $result['target_full'] ) {
			++$counts['skipped_already_present'];
		} else {
			++$counts['skipped_no_data'];
		}
	}

	/**
	 * Migrate a single term's Yoast fields.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $fields  Yoast field array for this term.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_term( int $term_id, array $fields ): array {
		$result = $this->migrate_fields( $term_id, $fields );

		$noindex = isset( $fields['wpseo_noindex'] ) && 'noindex' === $fields['wpseo_noindex'];
		$robots  = $this->robots->apply_flags( 'term', $term_id, $noindex, false );

		return [
			'migrated'    => $result['migrated'] || $robots['migrated'],
			'target_full' => $result['target_full'] || $robots['target_full'],
		];
	}

	/**
	 * Migrate mapped simple fields for a term.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $fields  Yoast field array.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_fields( int $term_id, array $fields ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( Mappings::TERM_META_MAP as $yoast_key => $lw_field ) {
			$value = $fields[ $yoast_key ] ?? '';
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}

			if ( 'og_image' === $lw_field ) {
				$value = MetaCoerce::as_url( $value );
				if ( '' === $value ) {
					continue;
				}
			}

			$lw_key   = Options::META_PREFIX . $lw_field;
			$existing = get_term_meta( $term_id, $lw_key, true );
			if ( '' !== $existing && false !== $existing ) {
				$target_full = true;
				continue;
			}

			if ( in_array( $lw_field, [ 'title', 'description' ], true ) ) {
				$value = VariableConverter::convert( $value );
			}

			if ( ! $this->dry_run ) {
				update_term_meta( $term_id, $lw_key, $value );
			}
			$migrated = true;
		}

		return [
			'migrated'    => $migrated,
			'target_full' => $target_full,
		];
	}
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/TermMetaMigrator.php
git commit -m "Add Yoast TermMetaMigrator (wpseo_taxonomy_meta option)"
```

---

## Task 8: `Yoast\PrimaryTermMigrator`

**Files:**
- Create: `includes/Migration/Yoast/PrimaryTermMigrator.php`

**Design:** Mirror `RankMath\PrimaryTermMigrator` exactly, swapping the map to
`Mappings::PRIMARY_TERM_MAP` and the SQL to
`meta_key LIKE '_yoast_wpseo_primary_%'`. Same `migrate()` / `migrate_one()` /
`find_post_ids()` shape and "never overwrite" guard.

- [ ] **Step 1: Implement (changed pieces)**

`find_post_ids()`:

```php
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		return (array) $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE '_yoast_wpseo_primary_%'"
		);
```

Copy `migrate()` and `migrate_one()` verbatim from `RankMath\PrimaryTermMigrator`,
changing `use` and the `Mappings::PRIMARY_TERM_MAP` reference to the Yoast
namespace.

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/PrimaryTermMigrator.php
git commit -m "Add Yoast PrimaryTermMigrator"
```

---

## Task 9: `Yoast\OptionsMigrator`

**Files:**
- Create: `includes/Migration/Yoast/OptionsMigrator.php`

**Interfaces:**
- Consumes: options `wpseo_titles`, `wpseo_social`; `Mappings`, `SeparatorMap`,
  `VariableConverter`, `Options`.
- Produces: `migrate(): array{count:int, details:array<string>}`.

**Design:** Mirror `RankMath\OptionsMigrator` structure (never overwrite,
`$details` log, feature flags). Three source blobs → LW options:

1. Titles (`wpseo_titles`): iterate `TITLE_OPTIONS_MAP`. For `separator`, run
   value through `SeparatorMap::to_char()`. For `title_*`/`desc_home`, run
   `VariableConverter::convert()`. Then iterate `NOINDEX_OPTIONS_MAP` casting
   each to bool. Then knowledge graph: `company_or_person` →
   `knowledge_type` (`'company'`→`'organization'`, else `'person'`);
   `company_name`|`person_name` → `knowledge_name`; `company_logo` →
   `knowledge_logo`.
2. Social (`wpseo_social`): iterate `SOCIAL_OPTIONS_MAP` (plain copy;
   `og_default_image` and social URLs copy as-is). Set `opengraph_enabled` from
   `opengraph` bool, `twitter_enabled` from `twitter` bool.
3. Feature flags: if `wpseo_titles` non-empty and `sitemap_enabled` unset →
   default sitemap on (Yoast has sitemaps on by default).

Keep each method ≤30 lines; split into `migrate_titles()`,
`migrate_noindex()`, `migrate_knowledge()`, `migrate_social()`,
`set_feature_flags()`. Guard the "already set" case exactly like RankMath
(`isset( $lw[$k] ) && '' !== $lw[$k]` → skip).

- [ ] **Step 1: Implement per the design above** (use `RankMath\OptionsMigrator`
  as the structural template; the knowledge-graph and separator handling are the
  Yoast-specific additions).

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/OptionsMigrator.php
git commit -m "Add Yoast OptionsMigrator (titles/social/knowledge graph)"
```

---

## Task 10: `Yoast\RedirectsMigrator`

**Files:**
- Create: `includes/Migration/Yoast/RedirectsMigrator.php`

**Interfaces:**
- Consumes: option `wpseo-premium-redirects-base`, `Redirects\Manager::add`.
- Produces: `count(): int`; `migrate(): array{migrated:int,skipped:int,errors:array<string>}`.

**Design:** Read the `wpseo-premium-redirects-base` option (array of
`{origin, url, type, format}`). `format === 'regex'` → regex redirect, else
plain. Map `type` (int) straight to LW SEO code. `count()` returns
`count($option)` when it's a non-empty array, else 0. Absent option → 0/empty.

- [ ] **Step 1: Implement**

```php
<?php
/**
 * Yoast Premium redirects migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Redirects\Manager;

/**
 * Migrates Yoast Premium redirects (wpseo-premium-redirects-base) into LW SEO.
 * Yoast free has no redirects, so this is a no-op there.
 */
final class RedirectsMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Count migratable Yoast Premium redirects.
	 *
	 * @return int
	 */
	public function count(): int {
		$data = get_option( 'wpseo-premium-redirects-base', [] );
		return is_array( $data ) ? count( $data ) : 0;
	}

	/**
	 * Run redirects migration.
	 *
	 * @return array{migrated: int, skipped: int, errors: array<string>}
	 */
	public function migrate(): array {
		$result = [
			'migrated' => 0,
			'skipped'  => 0,
			'errors'   => [],
		];

		$data = get_option( 'wpseo-premium-redirects-base', [] );
		if ( ! is_array( $data ) ) {
			return $result;
		}

		foreach ( $data as $entry ) {
			$this->migrate_entry( $entry, $result );
		}

		return $result;
	}

	/**
	 * Migrate a single redirect entry.
	 *
	 * @param mixed                                                     $entry  Redirect entry.
	 * @param array{migrated:int,skipped:int,errors:array<string>}      $result Accumulator (by ref).
	 * @return void
	 */
	private function migrate_entry( mixed $entry, array &$result ): void {
		if ( ! is_array( $entry ) || empty( $entry['origin'] ) ) {
			++$result['skipped'];
			return;
		}

		$source = (string) $entry['origin'];
		$dest   = (string) ( $entry['url'] ?? '' );
		$type   = (int) ( $entry['type'] ?? 301 );
		$regex  = isset( $entry['format'] ) && 'regex' === $entry['format'];

		if ( $this->dry_run ) {
			++$result['migrated'];
			return;
		}

		if ( false !== Manager::add( $source, $dest, $type, $regex ) ) {
			++$result['migrated'];
		} else {
			++$result['skipped'];
		}
	}
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/RedirectsMigrator.php
git commit -m "Add Yoast RedirectsMigrator (Premium redirects)"
```

---

## Task 11: `Yoast\WarningCollector`

**Files:**
- Create: `includes/Migration/Yoast/WarningCollector.php`

**Design:** Mirror `RankMath\WarningCollector::collect()` shape (array of
`{code, severity, message}`). Warnings: (a) count posts with
`_yoast_wpseo_schema_*` / `schema_page_type` (no LW SEO per-post schema import);
(b) count `NON_MIGRATABLE_META` keys present. Same
`$wpdb->prepare` counting and translator-comment discipline as the RankMath
version. Return `array_filter([...])`.

- [ ] **Step 1: Implement** (copy the `non_migratable_warning()` and
  `schema_warning()` methods from `RankMath\WarningCollector`, changing meta-key
  prefixes to `_yoast_wpseo_schema_%` and `Mappings::NON_MIGRATABLE_META`).

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/WarningCollector.php
git commit -m "Add Yoast WarningCollector"
```

---

## Task 12: `Yoast\Migrator` (orchestrator)

**Files:**
- Create: `includes/Migration/Yoast/Migrator.php`

**Interfaces:**
- Implements: `Migration\MigratorInterface`.
- Produces: `detect(): array`, `run(): array` — SAME output shape as
  `RankMath\Migrator` so the shared AJAX/JS renders both. `users` and
  `primary_terms` present (users always 0 — Yoast has no per-user robots meta).

- [ ] **Step 1: Implement**

```php
<?php
/**
 * Yoast Migrator orchestrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\WooCommerce\SlugCollisionDetector;

/**
 * Wires together all Yoast sub-migrators and surfaces detection counts +
 * warnings for the migration UI. Output shape matches RankMath\Migrator.
 */
final class Migrator implements MigratorInterface {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Run the full migration.
	 *
	 * @return array<string, mixed>
	 */
	public function run(): array {
		$options       = ( new OptionsMigrator( $this->dry_run ) )->migrate();
		$posts         = ( new PostMetaMigrator( $this->dry_run ) )->migrate();
		$terms         = ( new TermMetaMigrator( $this->dry_run ) )->migrate();
		$primary_terms = ( new PrimaryTermMigrator( $this->dry_run ) )->migrate();
		$redirects     = ( new RedirectsMigrator( $this->dry_run ) )->migrate();
		$warnings      = ( new WarningCollector() )->collect();

		if ( ! $this->dry_run ) {
			SlugCollisionDetector::invalidate();
			flush_rewrite_rules( false );
		}

		return [
			'dry_run'          => $this->dry_run,
			'options_migrated' => $options['count'],
			'options_details'  => $options['details'],
			'posts'            => $posts,
			'terms'            => $terms,
			'users'            => [ 'migrated' => 0 ],
			'primary_terms'    => $primary_terms,
			'redirects'        => $redirects,
			'warnings'         => $warnings,
		];
	}

	/**
	 * Detect available Yoast data for migration.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array {
		global $wpdb;

		$has_options = ! empty( get_option( 'wpseo_titles', [] ) ) || ! empty( get_option( 'wpseo_social', [] ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time detection.
		$post_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key LIKE '_yoast_wpseo_%'"
		);

		$tax_meta   = get_option( 'wpseo_taxonomy_meta', [] );
		$term_count = 0;
		if ( is_array( $tax_meta ) ) {
			foreach ( $tax_meta as $terms ) {
				$term_count += is_array( $terms ) ? count( $terms ) : 0;
			}
		}

		$redirects_count = ( new RedirectsMigrator() )->count();
		$warnings        = ( new WarningCollector() )->collect();

		return [
			'found'           => $has_options || $post_count > 0 || $term_count > 0 || $redirects_count > 0,
			'has_options'     => $has_options,
			'post_count'      => $post_count,
			'term_count'      => $term_count,
			'user_count'      => 0,
			'redirects_count' => $redirects_count,
			'warnings'        => $warnings,
		];
	}
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/Migration/Yoast/Migrator.php
git commit -m "Add Yoast Migrator orchestrator implementing MigratorInterface"
```

---

## Task 13: Provider-aware AJAX + admin Yoast block

**Files:**
- Modify: `includes/Migration/Ajax.php`
- Modify: `includes/Admin/Settings/TabMigration.php`
- Modify: `includes/Admin/SettingsPage.php:195-230` (localization strings)
- Modify: `assets/js/migration.js`

**Interfaces:**
- Consumes: `RankMath\Migrator`, `Yoast\Migrator`, `MigratorInterface`.

- [ ] **Step 1: Ajax — dispatch by provider**

Add a resolver and read `provider` in both handlers:

```php
	private const PROVIDERS = [
		'rankmath' => \LightweightPlugins\SEO\Migration\RankMath\Migrator::class,
		'yoast'    => \LightweightPlugins\SEO\Migration\Yoast\Migrator::class,
	];

	private function resolve_provider(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'rankmath';
		return isset( self::PROVIDERS[ $provider ] ) ? $provider : 'rankmath';
	}
```

In `detect()`: `$class = self::PROVIDERS[ $this->resolve_provider() ]; $migrator = new $class(); $result = $migrator->detect();`
In `run()`: `$class = self::PROVIDERS[ $this->resolve_provider() ]; $migrator = new $class( $dry_run ); $result = $migrator->run();`

- [ ] **Step 2: TabMigration — refactor to a reusable per-provider block**

Extract the detect/results markup into `render_provider_block( string $provider,
string $heading, string $detect_desc )` scoped with wrapper
`<div class="lw-migration-provider" data-provider="…">` and class-based selectors
(`.lw-migration-detect`, `.lw-migration-results`, etc.) instead of IDs. Render it
twice: `rankmath` (RankMath SEO) and `yoast` (Yoast SEO).

- [ ] **Step 3: migration.js — per-block binding**

Refactor the IIFE to `querySelectorAll('.lw-migration-provider')` and, for each
block, bind its own detect/preview/run buttons (scoped via
`block.querySelector('.lw-migration-detect')` etc.), reading
`block.dataset.provider` and sending it as the `provider` field in
`ajaxRequest`. Keep all render helpers; scope their target containers to the
block.

- [ ] **Step 4: SettingsPage — provider-neutral strings**

Change RankMath-specific localized strings ("No RankMath SEO data found",
"skipped (no actionable RankMath data)") to provider-neutral copy
("No data found for this plugin", "skipped (no actionable data)"). Keep the
`lw_seo_migration` nonce.

- [ ] **Step 5: Verify in browser, phpcs, commit**

Load `wp-admin/…?page=lw-seo&tab=migration`, confirm both blocks render and
detect independently (full functional check happens in Task 18).

```bash
composer phpcs
git add includes/Migration/Ajax.php includes/Admin/Settings/TabMigration.php includes/Admin/SettingsPage.php assets/js/migration.js
git commit -m "Make migration UI provider-aware; add Yoast block"
```

---

## Task 14: CLI Bootstrap + registration

**Files:**
- Create: `includes/CLI/Bootstrap.php`
- Modify: `includes/Plugin.php:120-134` (init_components)

**Interfaces:**
- Produces: `CLI\Bootstrap::register(): void` calling `WP_CLI::add_command()`
  for `lw-seo migrate|redirect|sitemap|option`.

- [ ] **Step 1: Implement Bootstrap**

```php
<?php
/**
 * WP-CLI command registration.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

/**
 * Registers all `wp lw-seo …` commands. Only loaded under WP-CLI.
 */
final class Bootstrap {

	/**
	 * Register command groups.
	 *
	 * @return void
	 */
	public static function register(): void {
		\WP_CLI::add_command( 'lw-seo migrate', MigrateCommand::class );
		\WP_CLI::add_command( 'lw-seo redirect', RedirectCommand::class );
		\WP_CLI::add_command( 'lw-seo sitemap', SitemapCommand::class );
		\WP_CLI::add_command( 'lw-seo option', OptionCommand::class );
	}
}
```

- [ ] **Step 2: Register in Plugin::init_components()**

At the end of `init_components()`:

```php
		// WP-CLI commands.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			CLI\Bootstrap::register();
		}
```

Add `use LightweightPlugins\SEO\CLI;` is unnecessary (fully-qualified via
`CLI\Bootstrap` from the plugin namespace works). Confirm no fatal when WP-CLI
absent (guard prevents referencing `WP_CLI`).

- [ ] **Step 3: phpcs + commit**

```bash
composer phpcs
git add includes/CLI/Bootstrap.php includes/Plugin.php
git commit -m "Register WP-CLI commands under WP_CLI guard"
```

---

## Task 15: `CLI\MigrateCommand`

**Files:**
- Create: `includes/CLI/MigrateCommand.php`

**Interfaces:**
- Consumes: `RankMath\Migrator`, `Yoast\Migrator`.
- Produces subcommands `rankmath`, `yoast` (`--dry-run`, `--yes`).

- [ ] **Step 1: Implement**

```php
<?php
/**
 * `wp lw-seo migrate` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Migration\RankMath\Migrator as RankMathMigrator;
use LightweightPlugins\SEO\Migration\Yoast\Migrator as YoastMigrator;

/**
 * Migrate SEO data from another plugin into LW SEO.
 */
final class MigrateCommand {

	/**
	 * Migrate RankMath SEO data.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing any data.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function rankmath( array $args, array $assoc_args ): void {
		$this->execute( new RankMathMigrator( isset( $assoc_args['dry-run'] ) ), $assoc_args, 'RankMath' );
	}

	/**
	 * Migrate Yoast SEO data.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing any data.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function yoast( array $args, array $assoc_args ): void {
		$this->execute( new YoastMigrator( isset( $assoc_args['dry-run'] ) ), $assoc_args, 'Yoast' );
	}

	/**
	 * Run a migrator and print the result.
	 *
	 * @param object                $migrator   A MigratorInterface instance.
	 * @param array<string, string> $assoc_args Associative args.
	 * @param string                $label      Human label.
	 * @return void
	 */
	private function execute( object $migrator, array $assoc_args, string $label ): void {
		$dry_run = isset( $assoc_args['dry-run'] );
		if ( ! $dry_run ) {
			\WP_CLI::confirm( sprintf( 'Migrate %s data into LW SEO? Existing LW SEO data is preserved.', $label ), $assoc_args );
		}

		$result = $migrator->run();

		$rows = [
			[ 'metric' => 'Options migrated', 'count' => (string) $result['options_migrated'] ],
			[ 'metric' => 'Posts migrated', 'count' => (string) $result['posts']['migrated'] ],
			[ 'metric' => 'Terms migrated', 'count' => (string) $result['terms']['migrated'] ],
			[ 'metric' => 'Primary terms migrated', 'count' => (string) $result['primary_terms']['migrated'] ],
			[ 'metric' => 'Redirects migrated', 'count' => (string) $result['redirects']['migrated'] ],
		];
		\WP_CLI\Utils\format_items( 'table', $rows, [ 'metric', 'count' ] );

		foreach ( $result['warnings'] as $warning ) {
			\WP_CLI::warning( $warning['message'] );
		}

		\WP_CLI::success( $dry_run ? 'Dry run complete — no data modified.' : sprintf( '%s migration complete.', $label ) );
	}
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/CLI/MigrateCommand.php
git commit -m "Add wp lw-seo migrate command"
```

---

## Task 16: `CLI\RedirectCommand`

**Files:**
- Create: `includes/CLI/RedirectCommand.php`

**Interfaces:**
- Consumes: `Redirects\Manager` (get_all/add/delete/delete_all/import_csv/export_csv).
- Produces subcommands `list`, `add`, `delete`, `import`, `export`.

- [ ] **Step 1: Implement** (keep each method ≤30 lines; if the file exceeds 200
  lines, extract `import`/`export` into `RedirectIoTrait`)

```php
<?php
/**
 * `wp lw-seo redirect` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Redirects\Manager;

/**
 * Manage LW SEO redirects.
 */
final class RedirectCommand {

	/**
	 * List all redirects.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Options: table, csv, json, count. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$items  = Manager::get_all();
		$format = $assoc_args['format'] ?? 'table';
		\WP_CLI\Utils\format_items( $format, $items, [ 'id', 'source', 'destination', 'type', 'regex', 'hits' ] );
	}

	/**
	 * Add a redirect.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Source path or pattern.
	 *
	 * <destination>
	 * : Destination URL.
	 *
	 * [--type=<type>]
	 * : HTTP status code (301, 302, 307, 410, 451). Default: 301.
	 *
	 * [--regex]
	 * : Treat the source as a regex pattern.
	 *
	 * @param array<int, string>    $args       [source, destination].
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function add( array $args, array $assoc_args ): void {
		$type   = (int) ( $assoc_args['type'] ?? 301 );
		$result = Manager::add( $args[0], $args[1], $type, isset( $assoc_args['regex'] ) );
		if ( false === $result ) {
			\WP_CLI::error( 'Failed to add redirect (invalid data or duplicate source).' );
		}
		\WP_CLI::success( sprintf( 'Redirect added (#%d): %s → %s [%d]', (int) $result, $args[0], $args[1], $type ) );
	}

	/**
	 * Delete a redirect by ID, or all redirects.
	 *
	 * ## OPTIONS
	 *
	 * [<id>]
	 * : Redirect ID to delete.
	 *
	 * [--all]
	 * : Delete every redirect.
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * @param array<int, string>    $args       [id?].
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		if ( isset( $assoc_args['all'] ) ) {
			\WP_CLI::confirm( 'Delete ALL redirects?', $assoc_args );
			Manager::delete_all();
			\WP_CLI::success( 'All redirects deleted.' );
			return;
		}
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Provide a redirect ID or use --all.' );
		}
		if ( ! Manager::delete( (int) $args[0] ) ) {
			\WP_CLI::error( sprintf( 'No redirect with ID %d.', (int) $args[0] ) );
		}
		\WP_CLI::success( sprintf( 'Redirect #%d deleted.', (int) $args[0] ) );
	}

	/**
	 * Import redirects from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to a CSV file (source,destination,type,regex).
	 *
	 * @param array<int, string>    $args       [file].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		if ( ! is_readable( $args[0] ) ) {
			\WP_CLI::error( sprintf( 'Cannot read file: %s', $args[0] ) );
		}
		$result = Manager::import_csv( (string) file_get_contents( $args[0] ) );
		\WP_CLI::success( sprintf( 'Imported %d redirect(s), skipped %d.', (int) $result['imported'], (int) $result['skipped'] ) );
	}

	/**
	 * Export redirects to CSV (stdout or a file).
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Write CSV to this file instead of stdout.
	 *
	 * @param array<int, string>    $args       [file?].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		$csv = Manager::export_csv();
		if ( ! empty( $args[0] ) ) {
			file_put_contents( $args[0], $csv );
			\WP_CLI::success( sprintf( 'Exported to %s', $args[0] ) );
			return;
		}
		\WP_CLI::line( $csv );
	}
}
```

> Before implementing, confirm `Manager::import_csv()` return shape (keys
> `imported`/`skipped`) and `get_all()` row keys against
> `includes/Redirects/Manager.php`; adjust the `format_items` field list and the
> import success message to the actual keys.

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/CLI/RedirectCommand.php
git commit -m "Add wp lw-seo redirect command"
```

---

## Task 17: `CLI\SitemapCommand`

**Files:**
- Create: `includes/CLI/SitemapCommand.php`

**Interfaces:**
- Consumes: `Sitemap::get_index_url()`, `Sitemap::activate()`, `Options`.
- Produces subcommands `info`, `flush`.

- [ ] **Step 1: Implement**

```php
<?php
/**
 * `wp lw-seo sitemap` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Inspect and rebuild the LW SEO sitemap.
 */
final class SitemapCommand {

	/**
	 * Show sitemap status and index URL.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function info( array $args, array $assoc_args ): void {
		if ( ! Options::get( 'sitemap_enabled' ) ) {
			\WP_CLI::warning( 'Sitemap is disabled in LW SEO settings.' );
			return;
		}
		\WP_CLI::line( 'Sitemap index: ' . Sitemap::get_index_url() );
		\WP_CLI::success( 'Sitemap is enabled.' );
	}

	/**
	 * Flush rewrite rules so sitemap URLs resolve.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function flush( array $args, array $assoc_args ): void {
		Sitemap::activate();
		\WP_CLI::success( 'Sitemap rewrite rules flushed.' );
	}
}
```

> Confirm `Sitemap::activate()` registers rules + flushes (per
> `includes/Sitemap/Sitemap.php:235`); if it only flushes, call
> `flush_rewrite_rules()` as needed.

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/CLI/SitemapCommand.php
git commit -m "Add wp lw-seo sitemap command"
```

---

## Task 18: `CLI\OptionCommand`

**Files:**
- Create: `includes/CLI/OptionCommand.php`

**Interfaces:**
- Consumes: `Options` (get/set/get_all/reset/get_defaults).
- Produces subcommands `get`, `set`, `list`, `reset`.

- [ ] **Step 1: Implement**

```php
<?php
/**
 * `wp lw-seo option` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Options;

/**
 * Read and write LW SEO options.
 */
final class OptionCommand {

	/**
	 * Get a single option value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Option key (e.g. title_home).
	 *
	 * @param array<int, string>    $args       [key].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		$value = Options::get( $args[0] );
		if ( null === $value ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s', $args[0] ) );
		}
		\WP_CLI::line( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
	}

	/**
	 * Set an option value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Option key.
	 *
	 * <value>
	 * : New value. 'true'/'false'/'1'/'0' are cast to bool for boolean options.
	 *
	 * @param array<int, string>    $args       [key, value].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function set( array $args, array $assoc_args ): void {
		$defaults = Options::get_defaults();
		if ( ! array_key_exists( $args[0], $defaults ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s', $args[0] ) );
		}
		$value = $args[1];
		if ( is_bool( $defaults[ $args[0] ] ) ) {
			$value = in_array( strtolower( $args[1] ), [ 'true', '1', 'on', 'yes' ], true );
		}
		Options::set( $args[0], $value );
		\WP_CLI::success( sprintf( 'Set %s.', $args[0] ) );
	}

	/**
	 * List all options.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, json, yaml. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$rows = [];
		foreach ( Options::get_all() as $key => $value ) {
			$rows[] = [ 'key' => $key, 'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ];
		}
		\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, [ 'key', 'value' ] );
	}

	/**
	 * Reset all options to defaults.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function reset( array $args, array $assoc_args ): void {
		\WP_CLI::confirm( 'Reset ALL LW SEO options to defaults?', $assoc_args );
		Options::reset();
		\WP_CLI::success( 'Options reset to defaults.' );
	}
}
```

> Confirm `Options::get()` returns `null` for unknown keys (per
> `includes/Options.php:175`, default param is `null`) and that
> `get_defaults()` values carry correct PHP types for the bool cast.

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add includes/CLI/OptionCommand.php
git commit -m "Add wp lw-seo option command"
```

---

## Task 19: Version bump → 1.4.0

**Files:**
- Modify: `lw-seo.php` (header `Version:` + `LW_SEO_VERSION`)
- Modify: `readme.txt` (`Stable tag:` + `== Changelog ==`)
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Bump all five locations**

- `lw-seo.php` header `Version: 1.4.0`; `define( 'LW_SEO_VERSION', '1.4.0' );`
- `readme.txt` `Stable tag: 1.4.0` and a new changelog block:

```
= 1.4.0 =
* New: WP-CLI commands — wp lw-seo migrate|redirect|sitemap|option.
* New: Yoast SEO importer (options, post & term meta, primary terms, Premium redirects).
```

- `CHANGELOG.md` top entry:

```markdown
## [1.4.0] - 2026-07-17

### Added
- WP-CLI command surface: `wp lw-seo migrate|redirect|sitemap|option`.
- Yoast SEO migrator (options, post meta, term meta, primary terms, Premium redirects) in the Import tab and CLI.
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcs
git add lw-seo.php readme.txt CHANGELOG.md
git commit -m "Bump version to 1.4.0"
```

---

## Task 20: Live verification on croco2 test site

**Files:** none (verification only; may update `test-site.md`).

The site runs WP 7.0.1 + SEOPress (no Yoast, no lw-seo). Be non-destructive:
only scratch content, restore/clean afterward.

- [ ] **Step 1: Deploy**

```bash
cd lw-seo && composer install --no-dev && cd ..
rsync -az --delete --exclude '.git' --exclude 'node_modules' \
  ./lw-seo/ croco2-hellodevs-dev@node1.ssh.hellohost.io:/home/croco2-hellodevs-dev/webapps/croco2/wp-content/plugins/lw-seo/ -e 'ssh -p 2205'
ssh croco2-hellodevs-dev@node1.ssh.hellohost.io -p 2205 'cd /home/croco2-hellodevs-dev/webapps/croco2 && wp plugin activate lw-seo'
```

- [ ] **Step 2: CLI smoke tests** (via `ssh … 'cd … && wp <cmd>'`)

```
wp lw-seo option list --format=table
wp lw-seo option set title_home '%%sitename%% %%sep%% %%sitedesc%%' && wp lw-seo option get title_home
wp lw-seo redirect add /old-cli-test /new-cli-test --type=301
wp lw-seo redirect list
wp lw-seo redirect export /tmp/lw-redirects.csv && wp lw-seo redirect delete --all --yes && wp lw-seo redirect import /tmp/lw-redirects.csv
wp lw-seo sitemap info
wp lw-seo sitemap flush
```

Expected: each prints a success line; `option get` echoes the set template;
redirect round-trips; `redirect delete --all` then `import` restores the row.

- [ ] **Step 3: Yoast migrator with seeded scratch data**

Create a scratch post + term, seed Yoast-format meta and options, run the
migrator, assert `_lw_seo_*` written, then clean up:

```
PID=$(wp post create --post_title='Yoast Scratch' --post_status=draft --porcelain)
wp post meta add $PID _yoast_wpseo_title 'Yoast Title %%sep%% %%sitename%%'
wp post meta add $PID _yoast_wpseo_metadesc 'Yoast meta description'
wp post meta add $PID '_yoast_wpseo_meta-robots-noindex' 1
TID=$(wp term create category 'Yoast Scratch Cat' --porcelain)
wp option patch insert wpseo_taxonomy_meta category "$TID" --format=json '{"wpseo_title":"Cat Title","wpseo_desc":"Cat desc","wpseo_noindex":"noindex"}'
wp option update wpseo_titles --format=json '{"separator":"sc-mdash","title-home-wpseo":"%%sitename%% %%sep%% %%sitedesc%%","company_or_person":"company","company_name":"Acme"}'
wp lw-seo migrate yoast --dry-run
wp lw-seo migrate yoast --yes
wp post meta get $PID _lw_seo_title
wp post meta get $PID _lw_seo_noindex
wp term meta get $TID _lw_seo_title
wp term meta get $TID _lw_seo_noindex
wp lw-seo option get separator   # expect: —
wp lw-seo option get title_home  # expect vars intact
```

Expected: post `_lw_seo_title` = "Yoast Title — Site Name" (converted),
`_lw_seo_noindex` = 1; term `_lw_seo_title` = "Cat Title", `_lw_seo_noindex` = 1;
`separator` = `—`.

- [ ] **Step 4: Clean up scratch data & deactivate**

```
wp post delete $PID --force
wp term delete category $TID
wp option delete wpseo_taxonomy_meta wpseo_titles
wp lw-seo redirect delete --all --yes
wp plugin deactivate lw-seo
```

(Only delete the `wpseo_*` options if they did not pre-exist — the site has
SEOPress, not Yoast, so they are safe to remove.)

- [ ] **Step 5: Update `test-site.md`** to the real current environment (WP 7.0.1,
  loaded WooCommerce/Elementor/LearnDash, SEOPress active) and commit.

```bash
git add test-site.md
git commit -m "Update test-site.md to current environment"
```

---

## Self-Review

**Spec coverage:**
- CLI migrate/redirect/sitemap/option → Tasks 14–18. ✓
- Yoast options/post/term/primary/robots/redirects/warnings/variables/separators
  → Tasks 2–12. ✓
- Admin integration (MigratorInterface, provider AJAX, TabMigration, JS) →
  Tasks 1, 13. ✓
- Verification on test site → Task 20. ✓
- Versioning → Task 19. ✓

**Placeholder scan:** Migrators mirroring RankMath (Tasks 6, 8, 9, 11) reference
existing repo files as the pattern and show the changed methods in full — this is
"follow the existing pattern", not a forward task reference. Three tasks carry
explicit "confirm the real signature before implementing" notes (Redirect import
shape, Sitemap::activate, Options::get null) because those service contracts must
be read from source at implement time — deliberate, not vague.

**Type consistency:** All migrators return the three-bucket shape the shared JS
renderer expects; `Yoast\Migrator::run()` returns the same top-level keys as
`RankMath\Migrator::run()` (`options_migrated`, `posts`, `terms`, `users`,
`primary_terms`, `redirects`, `warnings`). `RobotsMigrator::apply_flags()`
signature is consumed identically by Post/Term migrators.
