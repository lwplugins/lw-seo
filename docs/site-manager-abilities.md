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
    "ai_train": "default",
    "ai_input": "no",
    "search": "default",
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
| `ai_train` | string | AI training signal: "default" / "yes" / "no" |
| `ai_input` | string | AI input signal: "default" / "yes" / "no" |
| `search` | string | AI search signal: "default" / "yes" / "no" |
| `markdown_content` | string | Custom markdown for the /md endpoint |

## lw-seo/set-meta

Set SEO meta fields. Only the provided fields are updated, others remain unchanged.

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
  "updated": ["title", "description", "og_title", "og_description", "ai_train"]
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

If neither `post_id` nor `term_id` is provided, returns the global values.

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

If the post/term has a custom `markdown_content` meta field, it takes precedence over the auto-generated conversion.

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
    "content_signals_ai_train": true,
    "content_signals_ai_input": true,
    "content_signals_search": true,
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

| Ability | Required capability |
|---------|-------------------|
| `lw-seo/get-meta` | `edit_posts` |
| `lw-seo/set-meta` | `edit_posts` |
| `lw-seo/get-content-signals` | `edit_posts` |
| `lw-seo/get-markdown` | `edit_posts` |
| `lw-seo/get-options` | `manage_options` |
