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
