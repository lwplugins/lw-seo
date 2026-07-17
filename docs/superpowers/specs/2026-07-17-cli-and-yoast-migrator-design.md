# LW SEO — WP-CLI + Yoast SEO Migrator — Design

- **Date:** 2026-07-17
- **Plugin:** lw-seo (target release: 1.4.0)
- **Status:** Approved (design), pending implementation plan

## Goal

Add two capabilities to LW SEO, matching the plugin's existing conventions
(PSR-4, atomic ≤200-line classes, single responsibility, WPCS):

1. A **WP-CLI** command surface (`wp lw-seo …`) covering migration, redirects,
   sitemap and options — thin commands delegating to existing services.
2. A **Yoast SEO migrator** at full parity with the existing RankMath migrator,
   wired into both the admin **Import** tab and the new CLI.

Non-goals: no new SEO features, no changes to frontend output, no refactor
beyond what these two features require.

## Constraints

- PHP 8.1+, WordPress 6.0+, `declare(strict_types=1);` everywhere.
- PSR-4: file path mirrors namespace; PascalCase filenames (no `class-` prefix).
- File-size limits: class ≤200 lines, trait ≤80, interface ≤30, method ≤30.
- `composer phpcs` must pass before every commit.
- Existing LW SEO data is **never overwritten** by any migrator (parity with
  RankMath migrator's honest-accounting model).

---

## Part 1 — WP-CLI

### Registration

New namespace `LightweightPlugins\SEO\CLI`. Commands registered from
`Plugin::init_components()` behind a guard:

```php
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    CLI\Bootstrap::register();
}
```

`Bootstrap::register()` calls `WP_CLI::add_command()` for each group. All
command classes are thin: they parse args, call an existing service, and format
output with `WP_CLI` helpers. No business logic lives in the CLI layer.

### Commands

| File | Command | Delegates to | Notes |
|------|---------|--------------|-------|
| `CLI/Bootstrap.php` | — | — | registers the 4 command groups |
| `CLI/MigrateCommand.php` | `wp lw-seo migrate rankmath`, `… yoast` | `RankMath\Migrator`, `Yoast\Migrator` | `--dry-run`, `--yes`; prints a result table + warnings |
| `CLI/RedirectCommand.php` | `wp lw-seo redirect list\|add\|delete\|import\|export` | `Redirects\Manager` | `import <file>` / `export [<file>]` via `import_csv`/`export_csv`; `delete --all` and `--all` imports confirm |
| `CLI/SitemapCommand.php` | `wp lw-seo sitemap info\|flush` | `Sitemap` | `info` prints index URL + enabled providers; `flush` re-registers rules |
| `CLI/OptionCommand.php` | `wp lw-seo option get\|set\|list\|reset` | `Options` | `set` coerces `true/false/1/0` to bool for boolean keys; `reset` confirms |

- Subcommands use the standard WP-CLI "method = subcommand" pattern with
  `## OPTIONS` docblocks so `wp help lw-seo …` works.
- Destructive operations (`redirect delete --all`, `option reset`,
  `migrate … --yes` absent) use `WP_CLI::confirm()`.
- Tables use `WP_CLI\Utils\format_items()`.
- If `RedirectCommand` exceeds 200 lines, split import/export into a
  `RedirectIoTrait` (≤80).

### Files

```
includes/CLI/
├── Bootstrap.php
├── MigrateCommand.php
├── RedirectCommand.php
├── SitemapCommand.php
└── OptionCommand.php
```

---

## Part 2 — Yoast SEO migrator

Mirrors `Migration\RankMath` under `Migration\Yoast`, reusing shared helpers
(`MetaCoerce`, `Options`, `Redirects\Manager`, `SlugCollisionDetector`).

### Files

```
includes/Migration/Yoast/
├── Migrator.php            # orchestrator: run() / detect()
├── Mappings.php            # all key + variable + separator maps
├── OptionsMigrator.php     # wpseo_titles / wpseo_social / wpseo
├── PostMetaMigrator.php    # _yoast_wpseo_* post meta
├── TermMetaMigrator.php    # reads wpseo_taxonomy_meta OPTION (not termmeta)
├── PrimaryTermMigrator.php # _yoast_wpseo_primary_category etc.
├── RobotsMigrator.php      # noindex/nofollow (Yoast value semantics)
├── RedirectsMigrator.php   # wpseo-premium-redirects-base (Premium only)
├── VariableConverter.php   # Yoast %%var%% name normalization
└── WarningCollector.php    # non-migratable notes
```

### Three structural differences from RankMath (must be handled)

1. **Term SEO lives in one option, not termmeta.** Yoast stores taxonomy SEO in
   the `wpseo_taxonomy_meta` option as a nested array
   `[ taxonomy => [ term_id => [ 'wpseo_title', 'wpseo_desc', … ] ] ]`.
   `Yoast\TermMetaMigrator` iterates this option and writes `_lw_seo_*` term
   meta — it does **not** scan `wp_termmeta`.
2. **Robots noindex is a tri-state string, not an array.**
   `_yoast_wpseo_meta-robots-noindex` uses `0`/`1`/`2`; only the "noindex"
   value maps to `_lw_seo_noindex`. Exact value semantics **to be verified**
   against Yoast source before coding.
3. **Variables are already `%%…%%`.** Unlike RankMath (`%var%` → `%%var%%`),
   Yoast needs only name normalization + dropping unsupported vars.
   `Yoast\VariableConverter` maps Yoast names to LW SEO `ReplaceVars` names.

### Mapping plan (exact keys VERIFIED at implementation, not from memory)

**Post meta → LW SEO field:**

| Yoast key | LW SEO field |
|-----------|--------------|
| `_yoast_wpseo_title` | title |
| `_yoast_wpseo_metadesc` | description |
| `_yoast_wpseo_canonical` | canonical |
| `_yoast_wpseo_opengraph-title` | og_title |
| `_yoast_wpseo_opengraph-description` | og_description |
| `_yoast_wpseo_opengraph-image` | og_image |
| `_yoast_wpseo_twitter-title/-description/-image` | og_* (fallback) |
| `_yoast_wpseo_meta-robots-noindex` | noindex (value-mapped) |
| `_yoast_wpseo_meta-robots-nofollow` | nofollow |
| `_yoast_wpseo_primary_category` | primary_category |
| `_yoast_wpseo_focuskw` | (non-migratable, reported) |

**Options** (`wpseo_titles`: `title-home-wpseo`, `metadesc-home-wpseo`,
`title-<posttype>`, `title-tax-<taxonomy>`, `title-author-wpseo`,
`title-search-wpseo`, `title-404-wpseo`, `separator`, `noindex-<posttype>`,
`noindex-tax-<taxonomy>`, `noindex-author-wpseo`; `wpseo_social`:
`facebook_site`, `twitter_site`, `og_default_image`, `og_frontpage_title/-desc`;
`wpseo`: `company_or_person`, `company_name`, `company_logo`, `person_name`)
map to the same LW SEO option keys the RankMath `OptionsMigrator` targets.

**Separators:** Yoast stores a token (`sc-dash`, `sc-ndash`, `sc-mdash`,
`sc-middot`, `sc-bull`, `sc-pipe`, `sc-lt`, `sc-gt`, …). Map token → literal
char, keep only chars LW SEO supports (`Options::get_separators()`:
`- | > » · — /`), else fall back to `-`.

**Variables:** most Yoast `%%…%%` names already match LW SEO
(`sep`, `sitename`, `title`, `excerpt`, `date`, `category`, `tag`,
`searchphrase`, `page`, `currentyear/month/date`). Renames: `%%name%%` →
`%%author%%`, `%%primary_category%%` → `%%category%%`; unsupported Yoast vars
(e.g. `%%pt_single%%`, `%%focuskw%%`) are stripped.

**Redirects (Premium only):** `wpseo-premium-redirects-base` (array of
`{ origin, url, type, format }`) → `Redirects\Manager::add()`. Absent on Yoast
free — silently 0.

### Honest accounting & warnings

Same three-bucket model as RankMath (`migrated`,
`skipped_already_present`, `skipped_no_data`). `Yoast\WarningCollector` reports:
Yoast per-post schema/social overrides with no LW SEO target, presence of
`_yoast_wpseo_focuskw`, and any detected but non-migratable data.

---

## Part 3 — Admin integration (shared contract)

- New `Migration\MigratorInterface` (`detect(): array`, `run(): array`),
  implemented by both `RankMath\Migrator` and `Yoast\Migrator`.
- `Migration\Ajax` becomes **provider-aware**: reads a `provider`
  (`rankmath`|`yoast`) POST param and dispatches to the matching Migrator via a
  small provider→class map. Nonce/capability checks unchanged.
- `Admin\Settings\TabMigration` renders a second (Yoast) block mirroring the
  RankMath block, each scoped with `data-provider="…"`.
- `assets/js/migration.js` refactored from single-element IDs to **per-block
  binding**: each provider block is a scoped container; detect/preview/run send
  their block's `provider`. Localized strings become provider-neutral
  ("plugin" instead of "RankMath").
- `SettingsPage` localization: add per-provider labels; `users` row shown only
  when a provider reports it (Yoast free has none).

---

## Part 4 — Verification (croco2 test site)

The test site (`https://croco2.hellodevs.dev`, SSH + WP-CLI 2.12.0) is **not**
the clean env described in `test-site.md`: it is now WP 7.0.1, a loaded
WooCommerce/Elementor/LearnDash install with **SEOPress** active and lw-seo
absent. Testing must be non-destructive.

Plan:

1. `composer install --no-dev` locally, rsync-deploy lw-seo, `wp plugin
   activate lw-seo`.
2. **Yoast migrator** (no Yoast on site): seed Yoast-format data on a **scratch
   post + scratch term + `wpseo_titles`/`wpseo_taxonomy_meta` options** via
   WP-CLI (the migrator reads DB keys — Yoast need not be active). Run
   `wp lw-seo migrate yoast --dry-run` then `--yes`; assert `_lw_seo_*`
   meta/options written. **Clean up seeded data afterward.**
3. **CLI:** exercise `redirect add/list/export/import`, `sitemap info/flush`,
   `option get/set/list`.
4. Do not touch existing content or active plugins; deactivate lw-seo when done.
5. Update `test-site.md` to reflect the real current environment.

## Versioning (release 1.4.0)

Update all five locations: `lw-seo.php` header `Version:`, `LW_SEO_VERSION`
constant, `readme.txt` `Stable tag:` + `== Changelog ==`, `CHANGELOG.md`.

## Test strategy (unit)

PHPUnit tests for the pure mapping/coercion logic where feasible:
`Yoast\VariableConverter`, separator-token mapping, robots value mapping, and
`Yoast\OptionsMigrator` "never overwrite" behavior. DB-touching migrators are
validated via the live test-site run (step 2 above).
