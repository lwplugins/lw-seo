# Social Media Settings

Navigate to **LW Plugins → SEO → Social** to configure Open Graph and Twitter Card settings.

## Open Graph

Open Graph tags control how your content appears when shared on Facebook, LinkedIn, and other social platforms.

### Enable Open Graph

Toggle Open Graph meta tags on/off. Enabled by default.

### Default Social Image

Upload a fallback image used when posts don't have a featured image.

**Recommended specifications:**
- Size: 1200 x 630 pixels
- Format: JPG or PNG
- Max file size: 1MB

This image appears when sharing:
- Posts without featured images
- Archive pages
- The homepage

### Per-Post Open Graph

Customize Open Graph data per post in the SEO meta box:

| Field | Description |
|-------|-------------|
| OG Title | Custom title for social sharing |
| OG Description | Custom description for social sharing |
| OG Image | Custom image for social sharing |

## Twitter Cards

Twitter Cards provide rich media experiences when your content is shared on Twitter/X.

### Enable Twitter Cards

Toggle Twitter Card meta tags on/off. Enabled by default.

### Twitter Card Type

Choose the card format:

| Type | Description |
|------|-------------|
| Summary | Small square image with title and description |
| Summary Large Image | Large image above title and description |

**Recommendation:** Use "Summary Large Image" for better visibility.

### Twitter Username

Enter your Twitter/X username (without @) for attribution.

Example: `lwplugins`

This adds `twitter:site` and `twitter:creator` meta tags.

## Generated Meta Tags

When enabled, the plugin outputs:

```html
<!-- Open Graph -->
<meta property="og:title" content="Post Title">
<meta property="og:description" content="Post description...">
<meta property="og:image" content="https://example.com/image.jpg">
<meta property="og:url" content="https://example.com/post/">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Site Name">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Post Title">
<meta name="twitter:description" content="Post description...">
<meta name="twitter:image" content="https://example.com/image.jpg">
<meta name="twitter:site" content="@lwplugins">
```

## Testing

Validate your Open Graph and Twitter Card implementation:

- **Facebook:** [Sharing Debugger](https://developers.facebook.com/tools/debug/)
- **Twitter:** [Card Validator](https://cards-dev.twitter.com/validator)
- **LinkedIn:** [Post Inspector](https://www.linkedin.com/post-inspector/)

## Tips

- Always set a default social image
- Use high-quality images for better engagement
- Keep OG descriptions under 200 characters
- Test shares before launching campaigns
