# LW Site Manager - SEO Abilities

LW SEO registers 5 abilities with LW Site Manager, enabling AI agents and automation tools to manage SEO settings programmatically.

These abilities are only active when LW Site Manager is also installed and activated. No hard dependency - the integration is a no-op otherwise.

## Abilities

| Ability | Type | Description |
|---------|------|-------------|
| `lw-seo/get-meta` | readonly | Get SEO metadata for a post or term |
| `lw-seo/set-meta` | write | Set SEO metadata |
| `lw-seo/get-content-signals` | readonly | Get resolved AI Content Signals |
| `lw-seo/get-markdown` | readonly | Get content as markdown |
| `lw-seo/get-options` | readonly | Get global SEO settings |

## Authentication

All requests require a WordPress Application Password:

```bash
curl -u "user@example.com:XXXX XXXX XXXX XXXX XXXX XXXX" <URL>
```

## Access Control

Every ability has a base `permission_callback` (see the Permissions table
below), and the read/write abilities additionally check the **target
object**:

- **`get-meta`, `get-content-signals`, `get-markdown`** (read): allowed
  when the object is publicly readable anyway — a published,
  non-password-protected post of a viewable post type, or a term in a
  public taxonomy — **or** the requesting user can edit it
  (`edit_post` / `edit_term`). Otherwise the ability returns a 403
  `WP_Error`, so a caller who only has `edit_posts` still can't read a
  draft, a private post, a password-protected post, or a term in a
  private taxonomy that isn't theirs to edit.
- **`set-meta`** (write): requires `edit_post` / `edit_term` on the
  target object specifically, on top of the base `can_edit_posts`
  permission.

## lw-seo/get-meta

Retrieve SEO meta fields for a post or taxonomy term.

**Method:** GET

```bash
# Post meta
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-meta/run?input[post_id]=123"

# Term meta
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-meta/run?input[term_id]=5"
```

**Response:**
```json
{
  "success": true,
  "type": "post",
  "id": 123,
  "meta": {
    "title": "Custom SEO Title",
    "description": "Meta description...",
    "noindex": "",
    "og_title": "Social Title",
    "og_description": "Social description...",
    "og_image": "https://example.com/image.jpg",
    "ai_train": "",
    "ai_input": "no",
    "search": "",
    "markdown_content": ""
  }
}
```

**Available fields:**

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | SEO title (overrides template) |
| `description` | string | Meta description |
| `noindex` | string | "1" if noindex, "" if not |
| `og_title` | string | Open Graph title |
| `og_description` | string | Open Graph description |
| `og_image` | string | Open Graph image URL |
| `ai_train` | string | AI training signal: "" (not specified) / "yes" / "no" |
| `ai_input` | string | AI input signal: "" (not specified) / "yes" / "no" |
| `search` | string | AI search signal: "" (not specified) / "yes" / "no" |
| `markdown_content` | string | Custom markdown for the /md endpoint |

## lw-seo/set-meta

Set SEO meta fields. Only the provided fields are updated, others remain
unchanged. The caller must be able to edit the target post/term
(`edit_post` / `edit_term`), or the ability returns a 403 `WP_Error`.

Fields the caller isn't allowed to set are silently left unchanged and
listed under `skipped` in the response instead of `updated` — currently
this applies to `markdown_content`, which requires the `unfiltered_html`
capability because it is served unescaped at the `/md` endpoint (see
`docs/markdown-endpoint.md`).

**Method:** POST

```bash
curl -u "user:app-password" \
  -X POST -H "Content-Type: application/json" \
  -d '{
    "input": {
      "post_id": 123,
      "meta": {
        "title": "New SEO Title",
        "description": "New meta description for search engines.",
        "og_title": "Share Title",
        "og_description": "Share description.",
        "ai_train": "no"
      }
    }
  }' \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/set-meta/run"
```

**Setting term meta:**
```bash
curl -u "user:app-password" \
  -X POST -H "Content-Type: application/json" \
  -d '{
    "input": {
      "term_id": 5,
      "meta": {
        "title": "Category SEO Title",
        "description": "Category description for search engines."
      }
    }
  }' \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/set-meta/run"
```

**Response:**
```json
{
  "success": true,
  "message": "5 SEO fields updated.",
  "updated": ["title", "description", "og_title", "og_description", "ai_train"],
  "skipped": []
}
```

`skipped` lists fields that were provided but left unchanged because the
caller lacks the capability to set them (currently only
`markdown_content` without `unfiltered_html`):
```json
{
  "success": true,
  "message": "1 SEO fields updated.",
  "updated": ["title"],
  "skipped": ["markdown_content"]
}
```

## lw-seo/get-content-signals

Get the resolved Content Signals for a post or term. Resolution order: per-post/term meta > `lw_seo_content_signals` filter > global setting.

**Method:** GET

```bash
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-content-signals/run?input[post_id]=123"
```

**Response:**
```json
{
  "success": true,
  "signals": {
    "ai-train": "yes",
    "ai-input": "no",
    "search": "yes"
  }
}
```

If neither `post_id` nor `term_id` is provided, returns the global
values. If the object is given but not publicly readable and the caller
can't edit it (see Access Control above), the ability returns a 403
`WP_Error` instead.

## lw-seo/get-markdown

Get content as markdown with YAML frontmatter. Returns the same output as the `/md` endpoint.

**Method:** GET

```bash
# Post/page markdown
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-markdown/run?input[post_id]=123"

# Taxonomy term markdown
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-markdown/run?input[term_id]=5"
```

**Response:**
```json
{
  "success": true,
  "markdown": "---\ntitle: \"Hello World\"\nurl: \"https://example.com/hello-world/\"\ndate: \"2026-03-22\"\nauthor: \"Admin\"\nlanguage: \"en_US\"\n---\n\n# Hello World\n\nContent here...\n",
  "tokens": 125
}
```

If the post/term has a custom `markdown_content` meta field, it takes
precedence over the auto-generated conversion.

Either `post_id` or `term_id` is required — without one, the ability
returns a 404 `WP_Error`. As with the other read abilities, an object
that is not publicly readable and not editable by the caller returns a
403 `WP_Error` (see Access Control above).

## lw-seo/get-options

Get all global LW SEO settings.

**Method:** GET

```bash
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-options/run"
```

**Response (excerpt):**
```json
{
  "success": true,
  "options": {
    "separator": "-",
    "title_home": "%%sitename%% %%sep%% %%sitedesc%%",
    "sitemap_enabled": true,
    "llms_txt_enabled": true,
    "content_signals_ai_train": "",
    "content_signals_ai_input": "",
    "content_signals_search": "",
    "schema_enabled": true,
    "breadcrumbs_enabled": true
  }
}
```

## Examples

### Bulk SEO meta update

```bash
for id in 100 101 102 103; do
  curl -s -u "user:app-password" \
    -X POST -H "Content-Type: application/json" \
    -d "{\"input\":{\"post_id\":$id,\"meta\":{\"ai_train\":\"no\",\"ai_input\":\"yes\"}}}" \
    "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/set-meta/run"
  echo ""
done
```

### Export all posts as markdown

```bash
ids=$(curl -s -u "user:app-password" \
  "https://example.com/wp-json/wp/v2/posts?per_page=100&_fields=id" \
  | python3 -c "import sys,json; [print(p['id']) for p in json.load(sys.stdin)]")

for id in $ids; do
  curl -s -u "user:app-password" \
    "https://example.com/wp-json/wp-abilities/v1/abilities/lw-seo/get-markdown/run?input[post_id]=$id" \
    | python3 -c "import sys,json; d=json.load(sys.stdin); open(f'post-{$id}.md','w').write(d.get('markdown',''))" 2>/dev/null
  echo "Exported post $id"
done
```

## Permissions

Base capability required to call the ability at all. The read/write
abilities also check the specific target object — see Access Control
above.

| Ability | Required capability |
|---------|-------------------|
| `lw-seo/get-meta` | `edit_posts`, and read access to the target object |
| `lw-seo/set-meta` | `edit_posts`, and edit access to the target object |
| `lw-seo/get-content-signals` | `edit_posts`, and read access to the target object |
| `lw-seo/get-markdown` | `edit_posts`, and read access to the target object |
| `lw-seo/get-options` | `manage_options` |
