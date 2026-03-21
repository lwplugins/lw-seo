# AI/LLM Settings

Navigate to **LW Plugins > SEO > AI/LLM** to control AI content signals, crawler access, and llms.txt generation.

## Content Signals

### What are Content Signals?

Content Signals tell AI agents how they may use your content. They are sent as:
- **HTTP header** (`X-Content-Signals`) on every response
- **HTML meta tag** (`<meta name="ai-content-signals">`) in the `<head>`

Inspired by [Cloudflare - Markdown for Agents](https://blog.cloudflare.com/markdown-for-agents/).

### Global Settings

Three checkboxes, all enabled by default:

| Setting | Header key | Description |
|---------|-----------|-------------|
| AI Training | `ai-train` | Allow AI systems to use content for model training |
| AI Input (RAG) | `ai-input` | Allow AI systems to use content for generating responses |
| Search | `search` | Allow content to appear in AI search results |

### Per-Content Override

Each post/page has an **AI Content Signals** section in the LW SEO meta box (post editor). Three dropdowns:
- **Default** - uses the global setting
- **Yes** - explicitly allow
- **No** - explicitly deny

### Output Example

HTTP header (every response):
```
X-Content-Signals: ai-train=yes, ai-input=yes, search=yes
```

HTML meta tag:
```html
<meta name="ai-content-signals" content="ai-train=yes, ai-input=no, search=yes" />
```

### Resolution Order

1. Per-post meta value (if not "default")
2. `lw_seo_content_signals` filter (if applied)
3. Global setting from AI/LLM tab

## Markdown Endpoint (/md)

### What is it?

Every post, page, taxonomy, and WooCommerce product is available as clean markdown. AI agents can consume content without parsing HTML.

### Three Ways to Access

**1. URL suffix:**
```
https://yoursite.com/hello-world/md/
https://yoursite.com/category/news/md/
https://yoursite.com/product/shoes/md/
```

**2. Query parameter:**
```
https://yoursite.com/?p=123&format=md
```

**3. Accept header (singular pages only):**
```bash
curl -H "Accept: text/markdown" https://yoursite.com/hello-world/
```

### Response Format

**Headers:**
```
Content-Type: text/markdown; charset=UTF-8
X-Content-Signals: ai-train=yes, ai-input=yes, search=yes
X-Markdown-Tokens: 1250
```

**Body - YAML frontmatter + markdown:**
```markdown
---
title: "Hello World"
url: "https://yoursite.com/hello-world/"
date: "2026-03-21"
modified: "2026-03-21"
author: "Admin"
language: "hu_HU"
categories: ["WordPress"]
tags: ["example"]
featured_image: "https://yoursite.com/image.jpg"
---

# Hello World

Your post content converted to markdown...
```

### Security

- Only `publish` and `private` (with capability) posts are accessible
- Password-protected posts return 403
- Non-public taxonomies return 404
- Draft, pending, future posts return 404

### Flush Rewrite Rules

After activating the plugin, go to **Settings > Permalinks > Save Changes** to flush rewrite rules. Without this, the `/md/` URLs won't work.

## llms.txt

### What is llms.txt?

The `llms.txt` file provides structured information about your website to AI systems. Similar to robots.txt but designed for LLMs.

Learn more: [llmstxt.org](https://llmstxt.org/)

When enabled, available at: `https://yoursite.com/llms.txt`

### Content

The generated file includes:
- Site name and description
- Content summary (post/page/category counts)
- Important pages
- Recent posts
- Sitemap reference

## AI Crawler Control

Block or allow specific AI crawlers via robots.txt rules.

### Available Crawlers

| Crawler | Company | Purpose |
|---------|---------|---------|
| GPTBot | OpenAI | ChatGPT training data |
| ChatGPT-User | OpenAI | ChatGPT browsing |
| Claude-Web | Anthropic | Claude training |
| Google-Extended | Google | Gemini training |
| Bytespider | ByteDance | TikTok AI training |
| CCBot | Common Crawl | Open dataset |
| PerplexityBot | Perplexity | AI search engine |
| Cohere-AI | Cohere | AI training |

### Blocking

Check "Block" to add a `Disallow` rule to robots.txt:
```
User-agent: GPTBot
Disallow: /
```

## Content Signals vs Crawler Blocking

| Feature | Scope | Enforcement |
|---------|-------|-------------|
| Content Signals | Per-content | Advisory (agent decides) |
| AI Crawler Blocking | Site-wide | Hard block (robots.txt) |

Content Signals are advisory - the AI agent decides whether to respect them. Crawler blocking via robots.txt is a hard block (though also technically advisory).

## Technical Notes

- robots.txt blocking doesn't remove already-crawled content
- Content Signals follow the emerging web standard for AI content permissions
- The `/md` endpoint respects Content Signals but doesn't block content - it includes the signals in the response headers
- `X-Markdown-Tokens` is an approximate token count (`mb_strlen / 4`)
