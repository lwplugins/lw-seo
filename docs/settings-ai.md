# AI/LLM Settings

Navigate to **LW Plugins → SEO → AI/LLM** to control AI content signals,
crawler access, and llms.txt generation.

## Content Signals

### What are Content Signals?

Content Signals tell AI agents how they may use your content: for search,
for AI input (RAG/grounding), and for AI training. They follow
Cloudflare's [Content Signals Policy](https://blog.cloudflare.com/content-signals-policy/)
and are sent in three places:

- **HTTP header**: `Content-Signal` on every response (e.g.
  `Content-Signal: search=yes, ai-input=no, ai-train=no`). The pre-1.6.0
  header, `X-Content-Signals`, is still sent alongside it for backward
  compatibility and will be removed in a later release.
- **HTML meta tag**: `<meta name="ai-content-signals" content="...">` in
  the `<head>`.
- **robots.txt line**: a `Content-Signal:` line inside the `User-agent: *`
  group, preceded by the Content Signals Policy comment block. See
  `docs/settings-advanced.md` for the robots.txt layout.

### Three States

Each signal (Search, AI Input, AI Training) has three states:

| State | Meaning |
|-------|---------|
| Not specified (default) | Neither grants nor restricts the use. Nothing is sent for that signal. |
| Allow | `yes` — the use is permitted. |
| Disallow | `no` — the use is not permitted. |

New installs default every signal to "Not specified". Sites upgrading
from a pre-1.6.0 version keep their existing on/off values, mapped to
"Allow"/"Disallow".

### Per-Content Override

Each post, page and taxonomy term has its own Content Signals fields in
the LW SEO meta box / term edit screen: Search, AI Input and AI Training,
each with the same three states. A per-content value overrides the global
one; "Not specified" at the content level falls through to the global
setting.

### Resolution Order

1. The global setting from this tab is the starting value.
2. A per-post/term value set to Allow or Disallow overrides it.
3. The `lw_seo_content_signals` filter (see `docs/developers.md`) runs
   last on the result and can change any value.

A signal that is "Not specified" at both levels (and not added by the
filter) is left out of the header, meta tag and robots.txt line
entirely.

## Markdown Endpoint (/md)

Every post, page, taxonomy term and WooCommerce product is available as
clean Markdown for AI agents. See `docs/markdown-endpoint.md` for the
full behaviour: URL forms, `Accept` negotiation, discovery `Link`
headers, and the access rules (noindex, AI Input = No, password
protection, private content).

## llms.txt

### What is it?

The `llms.txt` file provides structured information about your website
to AI systems, formatted per [llmstxt.org](https://llmstxt.org/): an H1,
an optional summary blockquote, optional free-text introduction, and one
`##` section per content type listing its pages as Markdown links.

When enabled, available at `https://yoursite.com/llms.txt`. The document
is cached (one day) and rebuilt after a published post of a listed type
is saved, a listed post is published, unpublished or trashed, a post is
deleted, an LW SEO field on a post changes, a term is edited, the site
name/tagline, site address or permalink structure changes, the plugin
settings change (also while llms.txt is off), or the plugin is updated.

### Fields

| Field | Description |
|-------|-------------|
| **llms.txt** | Master toggle. Also enables the rewrite rule. |
| **Summary** | One sentence rendered as the `>` blockquote under the title. Empty falls back to the site tagline. |
| **Introduction** | Optional Markdown free text shown after the summary, before the first section. Do not use headings — the generator adds its own `##` section headings. |
| **Content types** | Which public post types get their own `##` section (e.g. `## Pages`, `## Posts`, `## Products`), headed by the type's plural label. Every public post type is listed and on by default; untick to leave a type out. Can also be filtered with `lw_seo_llms_txt_post_types`. When two listed types share a label, or a type is labelled "Optional", the post type name is appended to its heading (`## Events (tribe_events)`), so every type keeps its own section and none merges into `## Optional`. |
| **Items per section** | Max entries per section, 1–500 (default 100). Pages are listed in menu order; every other type newest first. |
| **Markdown links** | When enabled, each entry links to the page's `/md` Markdown version instead of its normal HTML URL. |
| **Extra links** | Freeform links listed under a final `## Optional` section, one per line: `Title | https://url | optional description`. Lines that don't parse (missing title, or a URL not starting with `http(s)://`) are skipped. |
| **llms-full.txt** | Opt-in. When enabled, `/llms-full.txt` serves the full Markdown content of every listed page concatenated together, capped at 1 MB — output is truncated with a notice once the cap is reached. Also adds a link to it under `## Optional` in `/llms.txt`. See [Restricted Content](#restricted-content) below. |

The `## Optional` section always also includes the XML sitemap link (when
the sitemap is enabled) and the llms-full.txt link (when enabled), in
addition to any manually entered extra links.

### Content Left Out

A post is only listed in `/llms.txt` (and counted toward `llms-full.txt`)
when it is:

- Published, not password-protected, and its post type is publicly
  viewable.
- Not noindex — neither the post type nor the individual post/term.
- Not opted out of AI input (`ai-input` content signal is not "No").

This is the same eligibility gate the sitemap and Markdown endpoint use;
see `lw_seo_post_is_eligible` in `docs/developers.md` to further restrict
it.

### Restricted Content

Both files are built as a logged-out visitor, whoever triggers the
rebuild, and the cached copy is served to every visitor and AI crawler.
Content that WordPress, shortcodes or a membership plugin show only to
logged-in users is therefore rendered the way an anonymous visitor
sees it.

Content-restriction plugins that only act on singular pages or the main
query may still not apply to `/llms-full.txt`, because it renders each
post's content outside the main query. Exclude restricted posts with the
`lw_seo_post_is_eligible` filter (see `docs/developers.md`); this also
removes them from `/llms.txt`, the sitemap and the Markdown endpoint.

## AI Crawler Control

Block or allow AI crawlers via `robots.txt` rules, either by purpose or
individually.

### Block by Purpose

Three toggles cover every crawler with that purpose, including crawlers
added by the registry in future updates:

| Toggle | Covers |
|--------|--------|
| Block all AI training crawlers | Crawlers that collect content to train models. |
| Block all AI search crawlers | Crawlers that build an AI search index. |
| Block all user-triggered AI fetchers | Crawlers that fetch a page on a user's explicit request (e.g. "summarize this page for me"). |

### Built-in Crawlers

Crawlers are grouped in the admin UI by their primary purpose. Checking
an individual crawler's card blocks only that crawler; the purpose
toggles above block every crawler in the group regardless of individual
checkboxes.

| Crawler | Company | Purpose |
|---------|---------|---------|
| GPTBot | OpenAI | Training |
| OAI-SearchBot | OpenAI | Search |
| ChatGPT-User | OpenAI | User-triggered |
| ClaudeBot | Anthropic | Training |
| Claude-SearchBot | Anthropic | Search |
| Claude-User | Anthropic | User-triggered |
| Google-Extended | Google | Training |
| Applebot-Extended | Apple | Training |
| PerplexityBot | Perplexity | Search |
| Perplexity-User | Perplexity | User-triggered |
| meta-externalagent | Meta | Training |
| meta-webindexer | Meta | Search |
| meta-externalfetcher | Meta | User-triggered |
| Amazonbot | Amazon | Training |
| Amzn-SearchBot | Amazon | Search |
| Amzn-User | Amazon | User-triggered |
| MistralAI-Training | Mistral AI | Training |
| MistralAI-Index | Mistral AI | Search |
| MistralAI-User | Mistral AI | User-triggered |
| CCBot | Common Crawl | Training |
| AI2Bot | Allen Institute for AI | Training |
| Bytespider | ByteDance | Search |

The list can be extended (or entries adjusted) with the `lw_seo_ai_crawlers`
filter — see `docs/developers.md`.

### Blocking

Checking a crawler (or a purpose toggle) adds a `Disallow: /` group for
every matching agent to `robots.txt`:

```
User-agent: GPTBot
Disallow: /
```

### A Note on User-Triggered Fetchers

`robots.txt` is a request, not an enforcement mechanism. OpenAI,
Perplexity, Meta and Amazon each document that their user-triggered
fetchers (`ChatGPT-User`, `Perplexity-User`, `meta-externalfetcher`,
`Amzn-User`) may disregard `robots.txt` for a specific, user-initiated
fetch — the same way a human clicking a link in their browser would not
be stopped by it.

## Content Signals vs Crawler Blocking

| Feature | Scope | Enforcement |
|---------|-------|-------------|
| Content Signals | Per-content | Advisory (agent decides) |
| AI Crawler Blocking | Site-wide | Hard block (robots.txt, itself advisory) |

Content Signals are per-page/per-post permissions expressed in a
standard, machine-readable way. Crawler blocking is a site-wide
`robots.txt` rule per agent. Both are respected voluntarily by
well-behaved crawlers; neither can force a bad actor to comply.
