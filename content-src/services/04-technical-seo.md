---
title: Technical SEO
slug: technical-seo
number: 04
menu_order: 4
category: seo
image: plate-04
excerpt: Crawling, indexing, Core Web Vitals, structured data and site architecture, fixed so search engines and AI crawlers can read every page that matters.
meta_title: Technical SEO Audit and Fixes | AVENZA
meta_description: Technical SEO audits and fixes for crawling, indexing, Core Web Vitals, structured data, JavaScript and site architecture, with clear developer tickets.
hero_eyebrow: N° 04 · Foundations
hero_title: Fix what search engines *cannot see*
hero_lead: Great content cannot rank if crawlers cannot reach it, render it or trust it. Technical SEO removes the hidden blockers that cap your growth, then keeps them from coming back.
hero_image: plate-04
hero_primary: Book a Strategy Call | page:contact
hero_secondary: Core Web Vitals guide | post:core-web-vitals-explained
hero_facts: Crawl, index and render checks | Core Web Vitals and speed | Developer-ready tickets
answer_q: What is technical SEO?
answer: Technical SEO is the work of making a website easy for search engines and AI crawlers to discover, render, understand and index. It covers crawl access, robots rules, sitemaps, canonical tags, redirects, page speed, Core Web Vitals, mobile layout, structured data, JavaScript rendering and internal linking, so content can compete on merit.
cta_heading: Get a technical audit your *developers* can use
cta_text: We will crawl your site, check how Google actually sees it, and hand you a prioritised list of fixes with the evidence and the exact change for each one.
---

## Why technical problems quietly cost rankings

> Most technical SEO problems are invisible to visitors. They only show up as pages that never rank.

A product page blocked by a stray robots rule, a service page canonicalised to the wrong URL, a blog that loads its text with JavaScript the crawler never runs: none of these break the site for people, so nobody notices. They simply keep good pages out of the index or split their strength across duplicates.

The stakes rose with AI search. Google says a page must be indexed and eligible for a snippet to appear as a link in AI Overviews and AI Mode. OpenAI, Anthropic and Perplexity run their own crawlers, and many of them do not execute JavaScript the way a browser does. A page that only exists after scripts run may be invisible to the systems your buyers now ask.

## What we check

### Crawling and indexing

We crawl the site the way a search engine does and compare it with what Google reports in Search Console. That shows pages that are discovered but not indexed, pages blocked by robots.txt or noindex, orphan pages with no internal links, redirect chains, soft 404s and duplicate URLs created by filters, parameters or tracking codes.

### Rendering and JavaScript

We compare the raw HTML with the rendered page to find content, links or structured data that only appear after JavaScript runs. Where it matters, we move critical content into server-rendered HTML so every crawler gets it on the first request.

### Speed and Core Web Vitals

Google's Core Web Vitals measure loading (Largest Contentful Paint, good at 2.5 seconds or less), responsiveness (Interaction to Next Paint, good at 200 milliseconds or less, which replaced First Input Delay in March 2024) and visual stability (Cumulative Layout Shift, good at 0.1 or less), measured at the 75th percentile of real visits. Speed also pays commercially: a Google-commissioned Deloitte study found that a 0.1 second improvement in mobile speed lifted retail conversions by 8.4 percent and travel conversions by 10.1 percent.

### Structure and signals

Site architecture, internal linking, canonical tags, hreflang for multi-language sites, XML sitemaps, pagination, structured data validity and HTTPS. These tell search engines which pages matter most and how they relate.

| Area | Common problem we find | Typical fix |
|---|---|---|
| Indexing | Important pages "crawled, currently not indexed" | Consolidate thin duplicates, strengthen internal links, improve content |
| Canonicals | Variants pointing to the wrong master URL | Correct canonical tags and internal links to one URL |
| Speed | Large hero images and render-blocking scripts | Modern formats, preload the hero, defer non-critical JavaScript |
| Layout shift | Images and ads without reserved space | Set width and height, reserve slots for embeds |
| JavaScript | Text and links injected late | Server-side rendering or static HTML for key content |
| AI crawlers | GPTBot or PerplexityBot blocked by a firewall rule | Allow listed crawlers, keep sensitive paths private |

## What the technical SEO service includes {wide alt}

^ Deliverables

::: grid 3
- **Full crawl audit.** Every URL checked for status, indexability, canonicals, duplicates and depth.
- **Search Console review.** Page indexing, Core Web Vitals and enhancement reports interpreted and prioritised.
- **Speed plan.** Field data from the Chrome UX Report plus lab tests, with fixes ranked by impact.
- **Structured data.** Organization, LocalBusiness, Service, Product, Article, FAQPage and BreadcrumbList markup, validated.
- **AI crawler access.** robots.txt, firewall and CDN rules checked so search and answer crawlers can read your pages.
- **Developer tickets.** Each issue written as a ticket with evidence, the fix, and how to test it.
:::

## How we work with your team {wide}

^ The process

::: steps
- **Access.** Search Console, analytics and, where possible, server logs and a staging site.
- **Crawl and compare.** Our crawl, Google's view in Search Console and real-user speed data side by side.
- **Prioritise.** Issues ranked by how many important pages they affect and how hard they are to fix.
- **Fix or hand over.** We make the change ourselves on WordPress and similar platforms, or brief your developers.
- **Verify.** We re-crawl, request indexing and confirm the fix holds after each release.
:::

## Platforms we work on

We work on WordPress, WooCommerce, Shopify, Webflow, custom PHP and JavaScript frameworks such as Next.js. Each has its own habits. WordPress sites tend to collect plugin bloat and duplicate archive pages. Shopify creates duplicate product URLs through collections. JavaScript frameworks often need server-side rendering checked carefully. We adjust the audit to the platform rather than running a generic checklist.

For sites that change often, we set up a monthly technical health check so that a new release or plugin update cannot undo months of work without anyone noticing.

## Questions about technical SEO {wide alt}

^ FAQ

::: faq
? How often should we run a technical SEO audit?
A full audit once a year, or after any redesign, migration or platform change. A lighter monthly health check catches new problems early, especially on sites that publish or release often.

? Do Core Web Vitals really affect rankings?
They are part of Google's page experience signals, but relevance and content quality matter more. A fast page with weak content will not outrank a slower, better page. Speed matters most for conversions, which is why we treat it as a business metric too.

? Can you help with a site migration?
Yes. Migrations are where technical SEO matters most. We map every old URL to its new home, test redirects before launch, and monitor indexing and rankings closely for the following weeks.

? Do you need access to our server?
Not always. Search Console and a crawl cover most issues. Server logs help on large sites because they show exactly what crawlers request and how often.
:::

## Related services

::: grid 3
- **[SEO Services](service:seo-services)** The full programme that technical work supports.
- **[Website Design and Development](service:website-design-development)** Build new sites that start technically clean.
- **[E-commerce SEO](service:ecommerce-seo)** Large catalogues need careful crawl and index control.
:::

## Sources and further reading

::: sources
- [Web Vitals](https://web.dev/articles/vitals). web.dev, Google.
- [Interaction to Next Paint becomes a Core Web Vital](https://web.dev/blog/inp-cwv-launch). web.dev, March 2024.
- [Milliseconds make millions](https://web.dev/case-studies/milliseconds-make-millions). Deloitte Digital for Google.
- [Understand the JavaScript SEO basics](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics). Google Search Central.
- [How to specify a canonical URL](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls). Google Search Central.
- [AI features and your website](https://developers.google.com/search/docs/appearance/ai-features). Google Search Central.
:::
