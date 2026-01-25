# Content SEO Settings

Navigate to **LW Plugins → SEO → Content** to configure content-related SEO options.

## Meta Descriptions

### Auto-generate Meta Descriptions

When enabled, the plugin automatically creates meta descriptions from post content when no custom description is set.

- Extracts the first ~155 characters from post content
- Strips HTML tags and shortcodes
- Falls back to excerpt if available

### Custom Meta Descriptions

Set custom meta descriptions per post/page using the SEO meta box in the editor:

1. Edit any post or page
2. Scroll to the **LW SEO** meta box
3. Enter your custom meta description
4. Save the post

## Canonical URLs

### Enable Canonical URLs

Adds `<link rel="canonical">` tags to prevent duplicate content issues.

- Automatically generates canonical URL for each page
- Points search engines to the preferred version of a page
- Handles pagination correctly

### Custom Canonical URLs

Override the automatic canonical URL per post/page:

1. Edit the post/page
2. In the **LW SEO** meta box, find "Canonical URL"
3. Enter the full URL you want as canonical
4. Save

## Robots Meta

### Default Robots Settings

Configure default robots meta tags for different content types.

### Per-Post Robots

Override robots settings per post/page:

1. Edit the post/page
2. In the **LW SEO** meta box, find "Robots"
3. Select desired options:
   - **noindex** - Don't show in search results
   - **nofollow** - Don't follow links on this page

## SEO Meta Box

The LW SEO meta box appears on post/page edit screens and includes:

| Field | Description |
|-------|-------------|
| SEO Title | Custom title (overrides template) |
| Meta Description | Custom meta description |
| Canonical URL | Custom canonical URL |
| Robots | noindex/nofollow settings |
| OG Title | Custom Open Graph title |
| OG Description | Custom Open Graph description |
| OG Image | Custom social sharing image |

## Tips

- Write unique meta descriptions for important pages
- Keep meta descriptions between 120-155 characters
- Include a call-to-action in descriptions
- Use noindex for thin content, thank you pages, or internal search results
