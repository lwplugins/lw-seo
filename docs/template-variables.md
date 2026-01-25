# Template Variables

Use these variables in title templates to dynamically generate page titles.

## Available Variables

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `%%sitename%%` | Site name from Settings → General | My Website |
| `%%sitedesc%%` | Site tagline from Settings → General | Just another WordPress site |
| `%%title%%` | Post/page title | How to Configure SEO |
| `%%sep%%` | Separator character (configured in settings) | \| |
| `%%excerpt%%` | Post excerpt (first 155 chars) | This post explains... |
| `%%author%%` | Post author display name | John Doe |
| `%%category%%` | Primary category name | WordPress |
| `%%term_title%%` | Current taxonomy term title | Tutorials |
| `%%searchphrase%%` | Search query on search pages | wordpress seo |
| `%%currentdate%%` | Current date | January 15, 2024 |
| `%%currentyear%%` | Current year | 2024 |
| `%%currentmonth%%` | Current month name | January |
| `%%page%%` | Page number for paginated content | Page 2 |
| `%%pagetotal%%` | Total pages for paginated content | 5 |
| `%%pt_single%%` | Post type singular name | Post |
| `%%pt_plural%%` | Post type plural name | Posts |

## Usage Examples

### Homepage

**Template:** `%%sitename%% %%sep%% %%sitedesc%%`

**Output:** My Website | Your tagline here

---

### Single Post

**Template:** `%%title%% %%sep%% %%sitename%%`

**Output:** How to Configure SEO | My Website

---

### Category Archive

**Template:** `%%term_title%% %%sep%% %%sitename%%`

**Output:** Tutorials | My Website

---

### Search Results

**Template:** `Search: %%searchphrase%% %%sep%% %%sitename%%`

**Output:** Search: wordpress seo | My Website

---

### Author Archive

**Template:** `Posts by %%author%% %%sep%% %%sitename%%`

**Output:** Posts by John Doe | My Website

---

### Paginated Archives

**Template:** `%%term_title%% %%sep%% Page %%page%% of %%pagetotal%% %%sep%% %%sitename%%`

**Output:** Tutorials | Page 2 of 5 | My Website

---

### Date-based Content

**Template:** `%%title%% %%sep%% Published %%currentmonth%% %%currentyear%%`

**Output:** My Post | Published January 2024

## Context Availability

Not all variables work on every page type:

| Variable | Homepage | Posts | Pages | Archives | Search |
|----------|----------|-------|-------|----------|--------|
| `%%sitename%%` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `%%sitedesc%%` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `%%title%%` | ❌ | ✅ | ✅ | ❌ | ❌ |
| `%%sep%%` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `%%excerpt%%` | ❌ | ✅ | ✅ | ❌ | ❌ |
| `%%author%%` | ❌ | ✅ | ✅ | ✅* | ❌ |
| `%%category%%` | ❌ | ✅ | ❌ | ❌ | ❌ |
| `%%term_title%%` | ❌ | ❌ | ❌ | ✅ | ❌ |
| `%%searchphrase%%` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `%%currentdate%%` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `%%page%%` | ✅* | ✅* | ✅* | ✅* | ✅* |

*Only on paginated content

## Custom Title Override

Override the template for individual posts/pages:

1. Edit the post/page
2. Find the **LW SEO** meta box
3. Enter a custom title in the "SEO Title" field
4. This replaces the template entirely

## Tips

### Keep Titles Concise
- Google displays ~50-60 characters
- Important info should be at the beginning
- Include brand name for recognition

### Good Patterns

```
%%title%% %%sep%% %%sitename%%           # Standard post
%%sitename%% %%sep%% %%sitedesc%%        # Homepage
%%term_title%% Archives %%sep%% %%sitename%%  # Archives
```

### Avoid

```
%%sitename%% %%sep%% %%title%% %%sep%% %%category%% %%sep%% %%author%%
# Too long, keyword stuffing

%%sitename%%
# Too short, no context
```

### Test Your Templates

1. Preview pages in browser
2. Check title tag in page source
3. Use SEO testing tools
4. Monitor Search Console for title rewrites
