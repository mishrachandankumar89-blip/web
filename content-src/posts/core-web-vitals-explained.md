---
title: "Core Web Vitals Explained for Business Owners: LCP, INP and CLS"
slug: core-web-vitals-explained
date: 2026-10-05
category: Technical SEO
image: cover-cwv
excerpt: What Core Web Vitals measure, the thresholds Google uses, why speed affects revenue as well as rankings, and the fixes that make the biggest difference.
meta_title: "Core Web Vitals Explained: LCP, INP and CLS Made Simple"
meta_description: Core Web Vitals for business owners: what LCP, INP and CLS measure, Google's good thresholds, how to check your site, and the fixes that matter most.
answer_q: What are Core Web Vitals?
answer: Core Web Vitals are three Google metrics that measure real visitors' experience of a page. Largest Contentful Paint measures loading and is good at 2.5 seconds or less. Interaction to Next Paint measures responsiveness and is good at 200 milliseconds or less. Cumulative Layout Shift measures visual stability and is good at 0.1 or less.
---

Most business owners have been told their website is "slow" at some point, usually with a red score from a speed test and no explanation of what it means. Core Web Vitals turn that vague complaint into three clear measurements, each tied to something a visitor actually feels.

This guide explains each one, how Google uses them, how to check your own site, and which fixes matter most.

## Why speed is a business issue first

Search rankings are only part of the story. A study commissioned by Google and carried out by Deloitte Digital, called "Milliseconds Make Millions", analysed 30 million mobile sessions across retail, travel, luxury and lead generation brands. It found that improving mobile site speed by just 0.1 seconds raised conversion rates by 8.4 percent for retail sites and 10.1 percent for travel sites.

In India, where most browsing happens on mobile phones and network quality varies widely between cities and towns, a heavy page loses visitors before they see your offer.

## The three Core Web Vitals

Google assesses each metric at the 75th percentile of real page loads, split by mobile and desktop. That means three out of four visits need to meet the "good" threshold for a page to pass.

| Metric | What it measures | Good | Needs improvement | Poor |
|---|---|---|---|---|
| Largest Contentful Paint (LCP) | How quickly the main content appears | 2.5 s or less | 2.5 to 4 s | Over 4 s |
| Interaction to Next Paint (INP) | How quickly the page responds to taps and clicks | 200 ms or less | 200 to 500 ms | Over 500 ms |
| Cumulative Layout Shift (CLS) | How much the layout jumps while loading | 0.1 or less | 0.1 to 0.25 | Over 0.25 |

### Largest Contentful Paint: does it load?

LCP marks the moment the largest visible element, usually a hero image or a large heading, finishes rendering. Slow LCP is usually caused by a slow server, a heavy hero image, render-blocking CSS and JavaScript, or web fonts that hold back text.

### Interaction to Next Paint: does it respond?

INP replaced First Input Delay as a Core Web Vital in March 2024. It looks at the delay between a visitor's interactions, such as tapping a menu or a button, and the next visual update, across the whole visit. Poor INP is almost always caused by too much JavaScript running on the main thread: heavy themes, page builders, chat widgets, trackers and third-party scripts.

### Cumulative Layout Shift: does it stay still?

CLS adds up unexpected movements of content while the page loads. You have felt it when you go to tap a link and the page jumps, so you tap an advert instead. Causes include images without set dimensions, ads and embeds without reserved space, late-loading fonts and banners inserted above content.

## How much do Core Web Vitals affect rankings?

Core Web Vitals are part of Google's page experience signals. Google has been clear that relevance and helpful content matter more: a fast page with thin content will not outrank a slower page that answers the question better. Think of good Core Web Vitals as removing a handicap rather than winning the race on their own. The commercial benefit, more visitors staying and converting, is usually the bigger prize.

## How to check your site

1. **PageSpeed Insights** (pagespeed.web.dev) shows field data from real Chrome users, when Google has enough of it, plus a lab test with specific suggestions.
2. **Search Console's Core Web Vitals report** groups your URLs into good, needs improvement and poor, for mobile and desktop.
3. **The Chrome UX Report** offers deeper historical field data for developers.
4. **Real user monitoring** in your own analytics can show performance for specific pages and audiences.

Always prioritise field data, which reflects your real visitors, over a single lab score.

## The fixes that matter most

### For LCP

- Serve images in modern formats such as WebP or AVIF, sized for the screen.
- Do not lazy-load the hero image; mark it as high priority instead.
- Use fast hosting and page caching, with a CDN for visitors across regions.
- Self-host or preload critical fonts and use `font-display: swap`.
- Remove render-blocking CSS and scripts that are not needed above the fold.

### For INP

- Remove plugins, widgets and trackers you do not truly need.
- Defer non-essential JavaScript and load third-party scripts after interaction where possible.
- Avoid heavy page builders on key templates; native blocks are far lighter.
- Break up long tasks so the browser can respond between them.

### For CLS

- Set width and height on every image and video.
- Reserve space for ads, embeds and cookie banners.
- Load fonts in a way that does not reflow text dramatically.
- Never insert banners or notices above content after the page has started rendering.

## A quick win list for WordPress sites

| Problem | Fix |
|---|---|
| Ten or more plugins loading scripts on every page | Remove unused plugins; load the rest only where needed |
| Page builder on every page | Rebuild key templates with native blocks |
| Large JPEG hero images | Convert to WebP and size correctly |
| Google Fonts loaded from an external server | Self-host the two or three weights you use |
| No page caching | Enable server or plugin caching |
| Chat widget loading on every page instantly | Load it after the first interaction or a short delay |

## Frequently asked questions

::: faq
? Why is my lab score bad when field data passes?
Lab tests simulate a mid-range phone on a slow network, which is deliberately harsh. Field data reflects your real visitors. If field data passes, you are in good shape, though the lab report can still point to useful improvements.

? Does Core Web Vitals data exist for every site?
No. Field data appears only when enough Chrome users visit a page or site. Smaller sites may see data only at the site level, or only lab results.

? Will a faster site automatically rank higher?
Not automatically. Speed removes a disadvantage, but relevance, content quality and authority decide most rankings. The bigger benefit of speed is usually better conversion.

? Is INP important for simple brochure websites?
Yes, because menus, forms and accordions all depend on it. Simple sites often pass easily, unless a heavy theme or third-party scripts slow every interaction.
:::

If your site is struggling with any of these, our [Technical SEO](service:technical-seo) and [Website Design and Development](service:website-design-development) services can help.

## Sources

::: sources
- [Web Vitals](https://web.dev/articles/vitals). web.dev, Google.
- [Interaction to Next Paint becomes a Core Web Vital](https://web.dev/blog/inp-cwv-launch). web.dev, March 2024.
- [Milliseconds make millions](https://web.dev/case-studies/milliseconds-make-millions). Deloitte Digital for Google.
- [Understanding Core Web Vitals and Google search results](https://developers.google.com/search/docs/appearance/core-web-vitals). Google Search Central.
- [Core Web Vitals report](https://support.google.com/webmasters/answer/9205520). Search Console Help.
:::
