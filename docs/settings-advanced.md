# Advanced Settings

Navigate to **LW Plugins → SEO → Advanced** to configure advanced SEO options.

## Schema.org / JSON-LD

### Organization or Person

Choose how your site is represented in schema markup:

| Type | Use Case |
|------|----------|
| Organization | Business, company, brand |
| Person | Personal blog, portfolio, individual |

### Organization Name

Your organization's official name. Defaults to site name.

### Organization Logo

Upload your logo for schema markup. Recommended: 112x112 pixels minimum, square format.

## Head Cleanup

Remove unnecessary elements from the `<head>` section to clean up your HTML.

### Remove Shortlinks

Removes the shortlink `<link>` tag:
```html
<!-- Removed: -->
<link rel='shortlink' href='https://example.com/?p=123' />
```

### Remove RSD Link

Removes the Really Simple Discovery link (used by XML-RPC clients):
```html
<!-- Removed: -->
<link rel="EditURI" type="application/rsd+xml" href="https://example.com/xmlrpc.php?rsd" />
```

### Remove Windows Live Writer Manifest

Removes the WLW manifest link:
```html
<!-- Removed: -->
<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="https://example.com/wp-includes/wlwmanifest.xml" />
```

### Remove WordPress Generator Tag

Removes the WordPress version meta tag:
```html
<!-- Removed: -->
<meta name="generator" content="WordPress 6.4" />
```

**Security note:** Hiding the WordPress version provides minimal security benefit but reduces information disclosure.

## robots.txt

LW SEO adds its lines to WordPress's own virtual `robots.txt` through the
core `robots_txt` filter — it never writes a physical file, and other
plugins hooked to the same filter still run. Toggle **"Add sitemap URL
and AI rules to robots.txt"** under Features to enable it.

When enabled, LW SEO appends (site permitting — `Sitemap:` is only added
on a public site):

- A `Sitemap:` line pointing at `/sitemap.xml`, when the sitemap is
  enabled.
- One `User-agent: … / Disallow: /` group per blocked AI crawler (see
  **LW Plugins → SEO → AI/LLM**).
- A `# llms.txt: …` comment pointing at `/llms.txt`, when it is enabled.
- A `Content-Signal:` line inside the `User-agent: *` group (a new group
  is added if none exists), preceded by the Cloudflare Content Signals
  Policy comment block, whenever at least one global Content Signal is
  set to Allow or Disallow on the AI/LLM tab.

### Preview and Physical-File Warning

Under the robots.txt toggle:

- If a physical `robots.txt` file exists at the site root, the web
  server serves it directly and WordPress's virtual robots.txt (and
  therefore these settings) never runs. LW SEO shows a warning naming
  the file's path; delete it to let LW SEO manage robots.txt again.
- Otherwise, an expandable **Preview** shows exactly what
  `https://yoursite.com/robots.txt` currently returns, built the same
  way WordPress's `do_robots()` would build it.

## Verification Codes

Add search engine verification meta tags without editing theme files.

### Google Search Console

1. Go to [Search Console](https://search.google.com/search-console)
2. Add your property
3. Choose "HTML tag" verification
4. Copy the content value from the meta tag
5. Paste into the "Google Verification" field

### Bing Webmaster Tools

1. Go to [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Add your site
3. Choose "Meta tag" verification
4. Copy the content value
5. Paste into the "Bing Verification" field

### Generated Output

```html
<meta name="google-site-verification" content="your-google-code" />
<meta name="msvalidate.01" content="your-bing-code" />
```

## Redirects

### www/non-www Redirect

Ensure consistent URL format:

| Option | Description |
|--------|-------------|
| No redirect | Let server handle (default) |
| Force www | Redirect non-www to www |
| Force non-www | Redirect www to non-www |

**Note:** This is better handled at server/hosting level for performance.

### HTTPS Redirect

Force HTTPS for all pages:

| Option | Description |
|--------|-------------|
| No redirect | Allow both HTTP and HTTPS |
| Force HTTPS | Redirect HTTP to HTTPS |

**Note:** Ensure you have a valid SSL certificate before enabling.

## Additional Meta Tags

Add custom meta tags to all pages:

```html
<meta name="author" content="Your Name">
<meta name="copyright" content="Your Company">
```

Enter one meta tag per line. Tags are output in the `<head>` section.

## Performance

### Disable Plugin on Specific Pages

Disable LW SEO output on certain pages by ID:

```
42, 156, 789
```

Useful for landing pages with their own SEO setup.

## Debug Mode

### Enable Debug Output

When enabled, adds HTML comments showing:
- Which title template was used
- Meta description source (auto-generated vs custom)
- Active schema types

```html
<!-- LW SEO Debug: Title template: %%title%% %%sep%% %%sitename%% -->
<!-- LW SEO Debug: Meta desc: auto-generated from content -->
```

**Warning:** Only enable for debugging. Disable on production sites.

## Tips

- Remove unused head elements to reduce page size
- Always verify site ownership with search engines
- Use HTTPS for better SEO and security
- Test changes in staging before applying to production
