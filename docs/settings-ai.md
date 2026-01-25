# AI/LLM Settings

Navigate to **LW Plugins → SEO → AI/LLM** to control AI crawler access and llms.txt generation.

## Overview

As AI systems increasingly crawl the web for training data, you may want to control which AI crawlers can access your content.

## llms.txt

### What is llms.txt?

The `llms.txt` file provides structured information about your website to AI systems. It's similar to robots.txt but specifically designed for Large Language Models.

Learn more: [llmstxt.org](https://llmstxt.org/)

### Enable llms.txt

Toggle llms.txt generation on/off.

When enabled, your file is available at:
```
https://yoursite.com/llms.txt
```

### llms.txt Content

The generated file includes:
- Site name and description
- Contact information
- Content guidelines for AI systems
- Preferred citation format

## AI Crawler Control

Block or allow specific AI crawlers via robots.txt rules.

### Available Crawlers

| Crawler | Company | Purpose |
|---------|---------|---------|
| GPTBot | OpenAI | ChatGPT training data |
| ChatGPT-User | OpenAI | ChatGPT browsing feature |
| Claude-Web | Anthropic | Claude training data |
| Google-Extended | Google | Gemini/Bard training |
| Bytespider | ByteDance | TikTok AI training |
| CCBot | Common Crawl | Open dataset |
| PerplexityBot | Perplexity | AI search engine |
| Cohere-AI | Cohere | AI training data |

### Blocking Crawlers

1. Find the crawler in the list
2. Check "Block" to add a Disallow rule
3. Save settings

This adds rules to your virtual robots.txt:

```
User-agent: GPTBot
Disallow: /

User-agent: Claude-Web
Disallow: /
```

### Allowing Crawlers

Uncheck "Block" to allow the crawler access.

## robots.txt Integration

The AI crawler rules are added to WordPress's virtual robots.txt.

View your robots.txt at:
```
https://yoursite.com/robots.txt
```

### Sample Output

```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

# AI Crawlers
User-agent: GPTBot
Disallow: /

User-agent: ChatGPT-User
Disallow: /

User-agent: Claude-Web
Allow: /

User-agent: Google-Extended
Disallow: /

Sitemap: https://yoursite.com/sitemap.xml
```

## Considerations

### Why Block AI Crawlers?

- Protect proprietary content from training data
- Reduce server load from crawling
- Maintain control over content usage
- Privacy and copyright concerns

### Why Allow AI Crawlers?

- Increase visibility in AI-powered search
- Help AI systems provide accurate information about your business
- Be included in AI assistants' knowledge base
- Support open web principles

### Selective Blocking

Consider your content type:
- **News sites:** May want to block to protect exclusive content
- **Educational sites:** May want to allow for knowledge sharing
- **E-commerce:** Consider blocking product descriptions
- **Personal blogs:** Personal preference

## Technical Notes

- robots.txt is advisory; crawlers may choose to ignore it
- Blocking doesn't remove already-crawled content
- Major AI companies generally respect robots.txt rules
- Rules apply site-wide; no per-page control via robots.txt

## Tips

- Review and update AI crawler settings periodically
- Monitor your server logs for AI crawler activity
- Consider your content licensing when deciding
- Major AI companies provide opt-out forms for existing training data
