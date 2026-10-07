---
title: "llms.txt, robots.txt and AI Crawlers: A Plain-English Setup Guide"
slug: llms-txt-robots-txt-ai-crawlers
date: 2026-09-17
category: Technical SEO
image: cover-ai-crawlers
excerpt: Which AI crawlers visit your site, what each one is for, how to allow or block them in robots.txt, and what llms.txt can and cannot do for you.
meta_title: "llms.txt, robots.txt and AI Crawlers: Setup Guide"
meta_description: A plain-English guide to AI crawlers like GPTBot, OAI-SearchBot, ClaudeBot and PerplexityBot, robots.txt rules, and what llms.txt really does.
answer_q: Should I allow AI crawlers on my website?
answer: If you want AI assistants to recommend and cite your business, allow their search and answer crawlers, such as OAI-SearchBot, Claude-SearchBot and PerplexityBot, in robots.txt. Training crawlers like GPTBot, ClaudeBot and CCBot are a separate choice: blocking them keeps your text out of future model training without removing you from search.
---

Every website now gets visits from crawlers that work for AI companies. Some collect text to train models. Some build search indexes that AI assistants use to answer questions. Some fetch a single page because a person asked an assistant to read it.

Treating them all the same is a mistake. Block everything and you may vanish from AI answers. Allow everything without thinking and you may hand your content to uses you did not intend. This guide explains the main crawlers, how robots.txt controls them, and where llms.txt fits.

## How robots.txt works, briefly

robots.txt is a plain text file at the root of your domain, for example `https://example.com/robots.txt`. It tells crawlers which parts of a site they may request. The format was standardised by the IETF as RFC 9309 in 2022.

Two things to keep in mind:

- **It is a request, not a lock.** Reputable crawlers follow it. It does not stop anyone determined to ignore it, so never rely on it to protect private data.
- **Rules are grouped by user agent.** A group starts with one or more `User-agent:` lines followed by `Allow:` and `Disallow:` rules. A crawler follows the most specific group that names it.

A minimal example that allows one crawler and blocks another looks like this:

| robots.txt lines | Meaning |
|---|---|
| `User-agent: OAI-SearchBot` then `Allow: /` | ChatGPT's search crawler may read the whole site |
| `User-agent: GPTBot` then `Disallow: /` | OpenAI's training crawler may not read any page |
| `User-agent: *` then `Disallow: /wp-admin/` | All other crawlers stay out of the admin area |

## The AI crawlers that matter

Each company documents its crawlers, and the roles have become clearer over the last two years. The main ones fall into three groups.

### Search and answer crawlers

These build the indexes AI assistants use to find and cite pages. Blocking them makes it much harder to appear in that assistant's answers.

- **OAI-SearchBot** (OpenAI): used to surface websites in ChatGPT's search features.
- **Claude-SearchBot** (Anthropic): improves search results for Claude users.
- **PerplexityBot** (Perplexity): indexes pages to show and link in Perplexity's answers.
- **Googlebot and Bingbot**: the classic search crawlers. Google's AI Overviews and AI Mode draw on Google's index, and Microsoft Copilot draws on Bing.

### User-triggered fetchers

These visit a page because a person asked the assistant to look at it, for example by pasting a link.

- **ChatGPT-User** (OpenAI), **Claude-User** (Anthropic) and **Perplexity-User** (Perplexity).

### Training crawlers and tokens

These collect content that may be used to train future models.

- **GPTBot** (OpenAI) and **ClaudeBot** (Anthropic).
- **CCBot** (Common Crawl), whose open dataset is widely used in AI research and training.
- **Google-Extended**: not a separate crawler but a robots.txt token. Google's documentation describes it as controlling whether content is used for Gemini models. It does not affect inclusion or ranking in Google Search.
- **Applebot-Extended**: a similar token for Apple's generative models. The main Applebot crawler powers features like Siri and Spotlight suggestions.

## A sensible default for most businesses

If your goal is to be found and recommended, the usual starting point is:

1. **Allow** all search and answer crawlers, including Googlebot, Bingbot, OAI-SearchBot, Claude-SearchBot and PerplexityBot.
2. **Allow** the user-triggered fetchers, so assistants can read your page when someone asks them to.
3. **Decide deliberately** about training crawlers and tokens. Allowing them may help models know your brand in the long run. Blocking them keeps your text out of future training. Neither choice removes you from search.
4. **Keep private areas private** with authentication, not robots.txt.

This theme, for example, generates a robots.txt that explicitly welcomes the main search and AI crawlers, keeps the admin area out of bounds and points to the XML sitemap and the llms.txt file.

::: note
Check your CDN and firewall too. Services such as Cloudflare offer one-click bot blocking that can silently stop AI crawlers, even when robots.txt allows them. If you want to be cited, make sure the firewall rules match your robots.txt.
:::

## What llms.txt is

llms.txt is a proposal published in September 2024 by Jeremy Howard of Answer.AI. The idea is a Markdown file at `/llms.txt` that gives language models a short, structured guide to a website: a title, a one-paragraph summary, and sections of links to the most useful pages, each with a short description. An optional `llms-full.txt` can carry fuller content.

It is easy to produce and can be helpful for tools that read it, especially developer documentation sites, where it began.

## What llms.txt is not

It is not a ranking factor, and it is not a standard that the large AI search systems have committed to. Google's John Mueller has compared it to the old keywords meta tag, a file where a site describes itself that search engines can verify by reading the site anyway. Google's May 2026 guide to generative AI in Search confirms that its crawler may find an llms.txt file but treats it like any other text file, with no special handling.

So treat llms.txt as a cheap, tidy extra. It will not rescue a site with blocked crawlers, thin pages or confusing structure.

| File | Purpose | Followed by |
|---|---|---|
| robots.txt | Tells crawlers what they may request | Reputable search and AI crawlers |
| XML sitemap | Lists the URLs you want discovered | Google, Bing and others |
| llms.txt | Summarises the site for language models | Some tools; not used by Google Search |
| Structured data | Describes entities on each page | Google, Bing and other systems |

## A quick audit you can run today

1. Open `yourdomain.com/robots.txt` and check for blanket `Disallow: /` rules or groups that name AI crawlers you actually want.
2. Check your CDN or firewall bot settings for rules that block AI or "unverified" bots.
3. Confirm your XML sitemap is listed and loads without errors.
4. Search your server logs or analytics for visits from OAI-SearchBot, PerplexityBot and Claude-SearchBot.
5. Add an llms.txt if you want one, with accurate descriptions of your key pages.
6. Re-check after every redesign, migration or security change.

## Frequently asked questions

::: faq
? Does blocking GPTBot remove us from ChatGPT search?
OpenAI documents GPTBot as its training crawler and OAI-SearchBot as the crawler used for ChatGPT's search features. Blocking GPTBot alone should not stop OAI-SearchBot from indexing your pages, so check that you have not blocked both by accident.

? Is robots.txt enough to protect confidential content?
No. It is a public file that asks crawlers to stay away. Anything confidential belongs behind a login.

? Do AI crawlers respect crawl-delay?
Some do. Anthropic, for example, says it supports the non-standard Crawl-delay directive. Google does not. If crawlers strain your server, rate limiting at the server or CDN is more reliable.

? How often should robots.txt be reviewed?
Whenever you change platforms, security settings or content strategy, and at least twice a year, because AI companies add and rename crawlers.
:::

Our [Technical SEO](service:technical-seo) service includes a full crawler access audit, and our [GEO service](service:generative-engine-optimization) builds on it.

## Sources

::: sources
- [RFC 9309: Robots Exclusion Protocol](https://www.rfc-editor.org/rfc/rfc9309.html). IETF, September 2022.
- [Overview of OpenAI crawlers](https://developers.openai.com/api/docs/bots). OpenAI.
- [Does Anthropic crawl data from the web, and how can site owners block the crawler?](https://support.claude.com/en/articles/8896518). Anthropic.
- [Perplexity crawlers](https://docs.perplexity.ai/docs/resources/perplexity-crawlers). Perplexity.
- [Google's common crawlers, including Google-Extended](https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers). Google Search Central.
- [About Applebot](https://support.apple.com/en-us/119829). Apple.
- [The llms.txt proposal](https://llmstxt.org/). Jeremy Howard, Answer.AI.
- [A new resource for optimizing for generative AI in Google Search](https://developers.google.com/search/blog/2026/05/a-new-resource-for-optimizing). Google Search Central Blog, May 2026.
- [Google says llms.txt is comparable to the keywords meta tag](https://www.searchenginejournal.com/google-says-llms-txt-comparable-to-keywords-meta-tag/544804/). Search Engine Journal.
:::
