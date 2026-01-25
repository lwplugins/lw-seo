# Redirect Manager

Navigate to **LW Plugins → SEO → Redirects** to create and manage URL redirects.

## Overview

The Redirect Manager helps you:
- Prevent 404 errors when URLs change
- Preserve SEO value when moving content
- Handle deleted pages gracefully
- Set up complex redirect patterns with regex

## Enable Redirects

Toggle redirect processing on/off. When disabled, no redirect rules are applied.

## Adding a Redirect

### Source URL

The old URL path that should redirect. Enter just the path without domain:

```
/old-page/
/blog/2023/old-post/
/products/discontinued-item/
```

### Destination URL

The new URL to redirect to. Can be:

- **Relative path:** `/new-page/`
- **Full URL:** `https://example.com/new-page/`
- **External URL:** `https://other-site.com/page/`

Leave empty for 410/451 status codes.

### Redirect Types

| Code | Name | Use Case |
|------|------|----------|
| **301** | Moved Permanently | Content permanently moved. Transfers SEO value. |
| **302** | Found (Temporary) | Content temporarily moved. Testing changes. |
| **307** | Temporary Redirect | Like 302 but preserves request method. API endpoints. |
| **410** | Content Deleted | Content intentionally removed. No destination. |
| **451** | Unavailable For Legal Reasons | Legal takedown. GDPR, DMCA. No destination. |

### Best Practices

- Use **301** for permanent moves (most common)
- Use **302** only for temporary changes
- Use **410** when content is intentionally deleted
- Use **451** for legal compliance

## Regex Redirects

Enable "Source is a regular expression" for pattern matching.

### Example Patterns

**Redirect category URLs:**
```
Source: ^/category/(.*)$
Destination: /blog/category/$1
```

**Redirect all blog posts:**
```
Source: ^/blog/(\d{4})/(\d{2})/(.*)$
Destination: /articles/$1-$2-$3
```

**Redirect product URLs:**
```
Source: ^/shop/(.*)$
Destination: /store/$1
```

### Capture Groups

Use `$1`, `$2`, etc. in destination to reference captured groups:

| Pattern | Input | Captures |
|---------|-------|----------|
| `^/old/(.*)$` | `/old/page` | `$1` = `page` |
| `^/(\d+)/(\w+)$` | `/123/abc` | `$1` = `123`, `$2` = `abc` |

## Managing Redirects

### Redirect Table

The table shows all configured redirects:

| Column | Description |
|--------|-------------|
| Source | The URL path to match |
| Destination | Where to redirect |
| Type | HTTP status code |
| Hits | Number of times triggered |
| Last Accessed | Most recent redirect time |

### Edit Redirect

Click **Edit** to modify an existing redirect. The form populates with current values.

### Delete Redirect

Click **Delete** to remove a redirect. Confirm the deletion when prompted.

## Import / Export

### Export to CSV

Click **Download CSV** to export all redirects. Format:

```csv
source,destination,type,regex
/old-page/,/new-page/,301,false
^/category/(.*)$,/blog/$1,301,true
/deleted-page/,,410,false
```

### Import from CSV

1. Prepare a CSV file with columns: `source`, `destination`, `type`, `regex`
2. Select the file
3. Click **Import CSV**

**CSV Format:**

| Column | Required | Values |
|--------|----------|--------|
| source | Yes | URL path or regex pattern |
| destination | No* | Target URL (empty for 410/451) |
| type | No | 301, 302, 307, 410, 451 (default: 301) |
| regex | No | true, false, 1, 0, yes, no (default: false) |

## How It Works

1. When a request comes in, the plugin checks for a matching redirect
2. Exact matches are checked first, then regex patterns
3. If found, the appropriate HTTP redirect is sent
4. Hit counter is incremented for analytics

## Performance

- Redirects are stored in WordPress options (no database table)
- Matching happens early in the request lifecycle
- Regex patterns are compiled once per request
- Minimal impact on non-matching URLs

## Troubleshooting

### Redirect Not Working

1. Check if "Enable Redirects" is on
2. Verify the source path is correct (starts with `/`)
3. Check for conflicting redirects
4. Clear any caching plugins
5. Test in incognito/private browsing

### Redirect Loop

A redirect pointing to itself or creating a chain that loops back:

```
/page-a/ → /page-b/
/page-b/ → /page-a/  ← Loop!
```

Remove one redirect to break the loop.

### Regex Not Matching

1. Test your pattern at [regex101.com](https://regex101.com/)
2. Ensure special characters are escaped: `\.`, `\?`, etc.
3. Use `^` for start and `$` for end anchors
4. Check the "Invalid regex pattern" error message

## Migration from Other Plugins

### From Yoast SEO Premium

Export redirects from Yoast as CSV and import into LW SEO. The format is compatible.

### From Redirection Plugin

1. Export from Redirection (Tools → Export → CSV)
2. Adjust columns to match: source, destination, type, regex
3. Import into LW SEO

### From .htaccess

Convert `.htaccess` rules to LW SEO redirects:

```apache
# .htaccess
Redirect 301 /old-page/ /new-page/
RedirectMatch 301 ^/blog/(.*)$ /articles/$1
```

Becomes:
```csv
source,destination,type,regex
/old-page/,/new-page/,301,false
^/blog/(.*)$,/articles/$1,301,true
```

## Tips

- Start with exact matches, use regex only when needed
- Test redirects immediately after creating them
- Review hit counts periodically to identify unused redirects
- Export redirects before major site changes
- Use 301 for SEO value transfer
