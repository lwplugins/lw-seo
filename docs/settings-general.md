# General Settings

Navigate to **LW Plugins → SEO → General** to configure basic SEO settings.

## Title Separator

Choose the character used between title parts. Default is `|`.

Available options: `|`, `-`, `–`, `—`, `•`, `»`, `/`

Example: "My Post Title | Site Name"

## Title Templates

Configure how page titles are generated using template variables.

### Homepage Title

Template for your site's front page.

**Default:** `%%sitename%% %%sep%% %%sitedesc%%`

**Example output:** "My Website | Your tagline here"

### Post Title

Template for single blog posts.

**Default:** `%%title%% %%sep%% %%sitename%%`

**Example output:** "How to Configure SEO | My Website"

### Page Title

Template for static pages.

**Default:** `%%title%% %%sep%% %%sitename%%`

### Archive Title

Template for category, tag, and date archives.

**Default:** `%%term_title%% %%sep%% %%sitename%%`

### Search Results Title

Template for search result pages.

**Default:** `%%searchphrase%% %%sep%% %%sitename%%`

**Example output:** "wordpress plugins | My Website"

## Available Template Variables

See [Template Variables](template-variables.md) for the complete list.

## Tips

- Keep titles under 60 characters for best display in search results
- Include your brand/site name for recognition
- Use the separator to create visual hierarchy
- Test how your titles appear using browser dev tools or SEO testing tools
