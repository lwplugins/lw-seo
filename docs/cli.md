# WP-CLI

Everything LW SEO's settings page can change can also be set from the
command line. `wp lw-seo option set` is validated the same way the
settings page validates it (`Admin\SettingsSanitizer`), so it can't be
used to store a broken value.

## `wp lw-seo option`

### `get <key>`

Print a single option's value.

```
wp lw-seo option get sitemap_enabled
wp lw-seo option get llms_txt_max_items
```

A map option (see below) also accepts a dot-path to read one entry:

```
wp lw-seo option get sitemap_post_types.case_study
```

If the entry has never been set, this prints the default a missing entry
resolves to — `true` for `sitemap_post_types` and `llms_txt_post_types`
(custom post types are included unless switched off), `false` for
`sitemap_taxonomies` (custom taxonomies are opt-in). This is the same
fallback `Sitemap\ProviderRegistry` and `LlmsTxt\SectionCollector` apply
when the option itself has no explicit entry for that name.

### `set <key> <value>`

Set a single option. The value is parsed by type, then run through the
same sanitizer the settings form uses:

| Default type | Accepted CLI value | Example |
|---|---|---|
| bool | `true`, `1`, `on`, `yes` → true; `false`, `0`, `off`, `no` → false (case-insensitive). Anything else errors — it is never silently stored as `false`. | `wp lw-seo option set sitemap_enabled yes` |
| int | Numeric string. | `wp lw-seo option set llms_txt_max_items 250` |
| map (array) | A JSON object; **replaces the whole map**. | `wp lw-seo option set sitemap_post_types '{"case_study":false,"event":true}'` |
| choice | One of a fixed list; an invalid value errors and lists the allowed values. | `wp lw-seo option set content_signals_ai_train no` |
| text / textarea / URL | Any string, sanitized (textarea keys keep newlines; URLs are cleaned with `esc_url_raw`). | `wp lw-seo option set title_home '%%sitename%% %%sep%% %%sitedesc%%'` |

Textarea options (`llms_txt_intro`, `llms_txt_optional_links`) keep
newlines. A literal `\n` typed on the command line is passed through as
the two characters backslash-n, not a line break — use your shell's
actual newline (e.g. `$'Line 1\nLine 2'` in bash) or a heredoc/file-based
approach if you need multiple lines.

**Map dot-path.** Update one entry of a map option and keep the rest:

```
wp lw-seo option set sitemap_post_types.case_study false
wp lw-seo option set llms_txt_post_types.event true
```

The three map options are `sitemap_post_types`, `sitemap_taxonomies` and
`llms_txt_post_types` — each maps a post type or taxonomy name to a
bool. Setting the whole option with JSON replaces it entirely; the
dot-path form only touches the one entry named.

Choice options and their allowed values:

| Key | Allowed values |
|---|---|
| `content_signals_search` | `''`, `yes`, `no` |
| `content_signals_ai_input` | `''`, `yes`, `no` |
| `content_signals_ai_train` | `''`, `yes`, `no` |

`''` (not specified) is the default; pass an empty string to clear a
previously set choice.

### `list [--format=<table|json|csv|yaml>]`

Print every option and its current value.

```
wp lw-seo option list
wp lw-seo option list --format=json
```

### `reset [--yes]`

Reset every option to its default.

```
wp lw-seo option reset --yes
```

## `wp lw-seo llms`

### `preview [--full]`

Print what LW SEO would serve at `/llms.txt` (or `/llms-full.txt` with
`--full`) right now, **without reading or writing the cache**. Useful to
check the generated content, or on a site where another plugin already
owns `/llms.txt` and the URL itself never reaches LW SEO.

```
wp lw-seo llms preview
wp lw-seo llms preview --full
```

### `flush`

Drop the cached `/llms.txt` and `/llms-full.txt`. Normally unnecessary —
the cache invalidates itself on the events listed in
`docs/settings-ai.md` — but useful after a manual database change or
while debugging.

```
wp lw-seo llms flush
```

### `info`

Show whether `llms.txt` is enabled, the `llms.txt` / `llms-full.txt`
URLs, and which post types currently get a section.

```
wp lw-seo llms info
```

## `wp lw-seo robots`

### `preview`

Print the `robots.txt` WordPress would serve right now, including LW
SEO's sitemap, `llms.txt`, Content Signals and AI-crawler-blocking
rules. Warns when a physical `robots.txt` file exists in the site root,
since that file takes priority over WordPress's virtual one and LW SEO's
rules would never be served.

```
wp lw-seo robots preview
```

## `wp lw-seo crawlers`

### `list [--format=<table|json|csv|yaml>]`

List every registered AI crawler (built-in plus any added via the
`lw_seo_ai_crawlers` filter) with its company, purpose(s), and current
block state.

```
wp lw-seo crawlers list
wp lw-seo crawlers list --format=json
```

The `reason` column is `individual` (blocked by its own `block_<key>`
option), `purpose:<name>` (blocked because a whole purpose is off), or
`-` (not blocked).

This command only reports state — it does not change it. Block a
crawler with `option set`:

```
wp lw-seo option set block_gptbot on
wp lw-seo option set block_purpose_training on
```

`<key>` is the first column from `crawlers list` (e.g. `gptbot`,
`oai_searchbot`, `claudebot`). `<purpose>` is one of `training`,
`search`, `user`.

## `wp lw-seo sitemap`

### `info`

Show whether the sitemap is enabled, its index URL, and the list of
per-type sitemaps it currently builds (one per enabled post type and
taxonomy).

```
wp lw-seo sitemap info
```

### `flush`

Flush rewrite rules so the sitemap URLs resolve (normally only needed
after activation or a permalink-affecting change).

```
wp lw-seo sitemap flush
```

## `wp lw-seo redirect`

```
wp lw-seo redirect list [--format=<table|csv|json|count>]
wp lw-seo redirect add <source> <destination> [--type=<301|302|307|410|451>] [--regex]
wp lw-seo redirect delete <id>
wp lw-seo redirect delete --all [--yes]
wp lw-seo redirect import <file>
wp lw-seo redirect export [<file>]
```

## `wp lw-seo migrate`

```
wp lw-seo migrate rankmath [--dry-run] [--yes]
wp lw-seo migrate yoast [--dry-run] [--yes]
```

## Other 1.6.0 option keys worth toggling

Beyond the tables above, these are the other 1.6.0 options most useful
to set from a script or deploy hook:

```
wp lw-seo option set robots_txt_enabled yes
wp lw-seo option set llms_txt_enabled yes
wp lw-seo option set llms_full_txt_enabled no
wp lw-seo option set sitemap_enabled yes
wp lw-seo option set sitemap_taxonomies.product_cat true
```
