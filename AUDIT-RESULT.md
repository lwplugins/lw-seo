# LW SEO — Audit Result

Scope: a phased cleanup of `lw-seo` — test infrastructure, version
consistency, characterization tests for the core logic, and a security audit.
Every phase ended with `composer phpcs` and `composer test` green, and its own
commit prefixed with the phase number.

Baseline: `lw-seo` v1.4.0, branch `feature/cli-and-yoast-migrator`.

---

## What was done, per phase

### Phase 1 — Test infrastructure (`0db06f9`)

- Migrated `phpunit.xml.dist` to the PHPUnit 10 schema. The removed
  `convertErrorsToExceptions` / `convertNoticesToExceptions` /
  `convertWarningsToExceptions` attributes are replaced by
  `failOnWarning` / `failOnNotice` / `failOnDeprecation`, and the legacy
  `<coverage><include>` block by the `<source>` element.
- `brain/monkey` was already a dev dependency (`^2.6`) — confirmed, not
  re-added.
- Documented the WordPress-free bootstrap: `tests/bootstrap.php` loads only the
  Composer autoloader (which pulls in Brain Monkey); the setUp/tearDown
  lifecycle lives in `tests/Unit/MonkeyTestCase.php`. WordPress is never loaded.
- Added `tests/Unit/SmokeTest.php` proving PSR-4 autoloading of plugin classes
  and Brain Monkey stubbing both work.
- Ignored the PHPUnit 10 `.phpunit.cache/` directory.

### Phase 2 — Minimum PHP unified at 8.2 (`005ea2d`)

The project stated three different minimums. Standardized on **8.2** (the
lowest version the CI matrix tests):

- `composer.json`: `"php": ">=8.2"` (was `>=8.1`)
- `lw-seo.php` header: `Requires PHP: 8.2`
- `readme.txt`: `Requires PHP: 8.2`
- `README.md` (already `8.2+`) and `phpcs.xml.dist` (`testVersion 8.2-`) —
  verified, already correct.

### Phase 3 — Characterization tests (`f5badac`)

Pinned down the **current** behaviour of the three pure-logic classes before
any future refactor (per `.claude/rules/tests.md`). 47 new tests, array-level
asserts, data providers for the variants. Added `tests/Stubs/wp-classes.php`
(property-bag stubs for `WP_Post` / `WP_Term` / `WP_User` / `WP_Post_Type`) so
plugin type-hints resolve without WordPress.

- **`ReplaceVars`** — every `%%variable%%` (site / post / term / search / date)
  plus edge cases: empty input, unknown variable, half percent sign, empty
  excerpt fallback, missing category, whitespace collapse, trimming.
- **`Breadcrumbs`** — front page, search, 404, taxonomy archive, hierarchical
  page, post-with-category, and the REST `build_for_post()` position sequence.
- **`Schema\Schema`** — array-level asserts on the JSON-LD `@graph` via the
  public `build_graph_for_post()` (WebSite, Organization/Person, WebPage,
  Article node types and their key fields). No string comparison.

Quirks were left **unfixed** (characterization phase) and flagged with
`// TODO: gyanús viselkedés — szándékos?` — see "Open items" below.

### Phase 4 — Security audit + fix (`6e2d662`)

**Fixed**

- `Markdown/Endpoint.php` `send_response()`: added
  `X-Content-Type-Options: nosniff`. `$output` is user-controlled post content
  echoed unescaped as `text/markdown`; without `nosniff` a content-sniffing
  browser could reinterpret it as HTML (MIME-sniffing XSS). HTML-escaping is
  **not** the right fix here — it would corrupt the Markdown — so the response
  stays unescaped and the `phpcs:ignore` justification was rewritten to state
  the non-HTML context and the `nosniff` mitigation (replacing the weak
  "pre-built" note).

**Audited — no change needed**

- **`$wpdb`** across `Migration/` and `WooCommerce/` (42 call sites): every
  variable value already goes through `$wpdb->prepare()`; only internal
  `$wpdb->{postmeta,termmeta,usermeta,posts}` properties and the controlled
  `$wpdb->prefix . 'rank_math_redirections'` table name are interpolated, all
  carrying `phpcs:ignore` + justification. All `LIKE` patterns are literal
  strings. No injection surface.
- **REST** (`RestApi.php`): all 5 routes are `WP_REST_Server::READABLE`; the
  post-based callbacks guard drafts/private content via
  `post_status !== 'publish' && ! current_user_can( 'read_post', … )`.
  `permission_callback => '__return_true'` is acceptable for public,
  read-only SEO data. `SiteManager/SeoAbilities.php` uses real capability
  callbacks (`can_edit_posts` / `can_manage_options`).
- **AJAX** (`Redirects/Ajax.php`, `Migration/Ajax.php`): every handler runs
  `verify_request()` = `check_ajax_referer()` + `current_user_can( 'manage_options' )`;
  the migration provider is resolved through a whitelist map (no arbitrary
  class instantiation).
- **Meta boxes** (`MetaBox.php`, `TermMetaBox.php`): `save` handlers use an
  isset-guarded `wp_verify_nonce()` + `edit_post` / `edit_term` capability
  check + autosave guard + per-field sanitization callbacks.
- **Admin settings** (`Admin/SettingsPage.php`): standard Settings API
  (`register_setting` + `settings_fields()` core nonce), behind a
  `current_user_can( 'manage_options' )` guard.

---

## Test suite state

`composer test` → **79 tests, 118 assertions, green** on PHP 8.5 locally; the CI
matrix runs 8.2 / 8.3 / 8.4 / 8.5. `composer phpcs` → **clean**.

---

## Open items (not addressed — need a separate, explicit task)

### 1. Characterization TODOs — decide intended vs. bug

The Phase 3 tests locked in current behaviour and flagged quirks. Each needs a
product decision; if any is a bug, fix it **test-first** (red → green), never
by editing the characterization test to match broken code.

- `ReplaceVars`: an empty `%%title%%` (no post in context) collapses to an
  empty string, leaving a leading separator (e.g. `"- Site Name"`).
- `ReplaceVars`: the `%%var%%` regex is `[a-z_]+` only — an uppercased name like
  `%%Sitename%%` is left verbatim in the output.
- `ReplaceVars`: whitespace is collapsed to single spaces even when the input
  contains no variables at all.
- `Breadcrumbs`: the shortcode passes `show_current="false"` as the **string**
  `'false'`, which is truthy, so the current crumb still shows. (`render()`
  with a real boolean `false` works correctly.)

**Priority: medium** — these are correctness/UX papercuts, not security issues.

### 2. Legacy files over the hard line limit

Per `.claude/rules/MUST-READ.md`, these exceed the 400-line hard limit and are
**frozen** (no new logic; new behaviour goes into new classes). A split is a
separate, explicit refactor task, and only on the back of characterization
tests:

| File | Lines |
|------|-------|
| `includes/RestApi.php` | 607 |
| `includes/Plugin.php` | 552 |
| `includes/Admin/Settings/TabLocal.php` | 505 |
| `includes/Breadcrumbs.php` | 504 |

`Breadcrumbs.php` now has characterization coverage (Phase 3), so it is the
best-prepared candidate for a first refactor. `RestApi.php` has read-path
coverage via its callbacks' behaviour but no direct unit tests yet.

**Priority: low** — quality/maintainability, no functional impact.

### 3. No integration test environment

The suite is unit-only (Brain Monkey, no WordPress). Behaviour that genuinely
needs a database, rewrite rules, or real hook firing — the Markdown endpoint
routing, redirect persistence, the migration DB reads — is not exercised
end-to-end. A `wp-env` based integration suite (separate from the unit suite,
per `tests.md`) would close that gap.

**Priority: medium** — matters most for the migration and redirect paths, which
touch the database directly.

---

## Suggested next steps, in priority order

1. **Triage the Phase 3 TODOs** (medium) — turn each quirk into a
   product decision; fix real bugs test-first.
2. **Stand up a `wp-env` integration suite** (medium) — cover the Markdown
   endpoint, redirects, and migrations against a real database.
3. **Refactor `Breadcrumbs.php`** (low) — it is characterization-covered;
   split it along its natural `build_*` responsibilities under the hard limit.
4. **Add direct unit tests for `RestApi.php`** (low) — as the precondition for
   later splitting it.
