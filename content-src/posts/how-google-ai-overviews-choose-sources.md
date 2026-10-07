---
title: How Google AI Overviews and AI Mode Choose Their Sources
slug: how-google-ai-overviews-choose-sources
date: 2026-09-08
category: AI Search
image: cover-ai-overviews
excerpt: What Google has actually said about how AI Overviews and AI Mode pick the pages they link to, what controls you have, how to measure it, and what to do about it.
meta_title: How Google AI Overviews and AI Mode Choose Sources
meta_description: What Google says about how AI Overviews and AI Mode pick sources: eligibility, query fan-out, snippet controls, Search Console reports and practical steps.
answer_q: How does Google choose sources for AI Overviews?
answer: Google says AI Overviews and AI Mode link to pages that are indexed and eligible to show a snippet in Search, chosen by its core ranking systems. They may run several related searches, a technique Google calls query fan-out, then link to pages that support the answer. There are no extra technical requirements beyond normal SEO.
---

AI Overviews now sit at the top of many Google results pages, and AI Mode offers a full conversational search experience. For businesses, the question is practical: why does Google link to some pages in these answers and not others?

There is a lot of speculation about this. This article sticks to what Google has published, what independent research has measured, and what follows from both.

## A short history, with India in focus

Google launched AI Overviews in the United States in May 2024. In August 2024 it expanded them to India and five other countries, offering them in English and Hindi with a toggle between the two. By late 2024 they were available in more than 100 countries.

AI Mode followed in 2025. Google launched it in India through Search Labs on 24 June 2025, in English, then opened it to all users. Hindi support arrived later in 2025, and in October 2025 Google expanded AI Mode to more Indian languages and brought Search Live, which lets people talk to Search using their camera and voice, to India.

The scale is now enormous. At Google I/O in May 2026, Google said AI Overviews reach more than 2.5 billion monthly users and AI Mode has passed one billion.

## What Google says about eligibility

Google's documentation page "AI features and your website" is the primary source here, and its message is consistent:

- To be shown as a supporting link in AI Overviews or AI Mode, a page must be **indexed and eligible to be shown in Google Search with a snippet**.
- There are **no additional technical requirements** and no special optimizations needed.
- The usual **SEO best practices** apply: technical requirements, Search policies and helpful, reliable, people-first content.

In other words, Google is not running a separate selection process with secret rules. AI features draw from the same index and lean on the same ranking systems as regular results.

Google repeated this in May 2026, when it published a dedicated guide to optimizing for its generative AI features. The guide says SEO best practices stay relevant because these features are rooted in Google's core ranking and quality systems, and that they use techniques such as retrieval-augmented generation and query fan-out to highlight content from the Search index. It puts the emphasis on valuable, unique, non-commodity content, and it busts a few popular myths: you do not need to rewrite pages specially for AI, you do not need to cut content into small chunks, and an llms.txt file gets no special treatment.

## Query fan-out: why you can appear for questions you do not rank for

One detail in Google's documentation changes how you should think about visibility. Google says AI Overviews and AI Mode may use a **query fan-out** technique: issuing multiple related searches across subtopics and data sources to develop a response.

A question like "best way to reduce rent costs for a small office in Bengaluru" might trigger separate searches about co-working pricing, lease negotiation, tax rules and neighbourhood comparisons. The answer is assembled from the best results for each.

This has two consequences:

1. **Depth on subtopics matters.** A page that clearly answers one narrow sub-question can be linked even if it would never rank for the full original query.
2. **Clarity beats fragmentation.** Google says its systems can find the relevant passage in a longer, multi-topic page, so there is no need to split articles into tiny pieces. Clear headings and direct answers still help readers, and they make the right passage easy to find.

![Diagram of five steps from question to citation](img:diagram-flow "A simplified model of how AI search builds an answer from many searches.")

## The controls you have

Because AI features use the same index, the same controls apply. Google documents these:

| Control | What it does | Use it when |
|---|---|---|
| `noindex` | Removes a page from Search, including AI features | The page should not appear anywhere in Search |
| `nosnippet` | Stops text snippets, which also stops use in AI features | You want a listing without any extracted text |
| `data-nosnippet` | Excludes a specific part of a page from snippets | Hide one section, such as pricing behind a login |
| `max-snippet` | Limits how much text can be used as a snippet | You want shorter extracts |
| Google-Extended | A robots.txt token for Gemini model use | Controls Gemini training and grounding, not Search ranking |

A common mistake is to add `nosnippet` or a very low `max-snippet` value to "protect" content, then wonder why the site never appears in AI answers. Another is assuming that blocking Google-Extended removes a site from AI Overviews; Google's documentation describes Google-Extended as a control for Gemini models, while Search features are governed by Googlebot and the snippet controls above.

## What tends to get linked

Google does not publish a ranking formula for AI features, but its guidance and the broader research point the same way. Pages that get linked tend to:

- **Answer a specific question clearly**, near the top of a section, in plain language.
- **Add something original**: data, first-hand experience, examples or expert judgement.
- **Show who is behind them**: a real business, named authors or reviewers, and contact details.
- **Cite their own sources**, so the claims can be checked.
- **Load fast and render cleanly**, with content in HTML rather than hidden behind scripts.

The research paper that coined the term Generative Engine Optimization, presented at KDD 2024, found that adding statistics, quotations and citations made content noticeably more visible in AI-generated answers, while keyword stuffing did not help. That lines up with Google's long-standing advice on helpful content.

## How to measure your visibility in AI features

Measurement has improved a lot. In June 2026 Google introduced **Search generative AI performance reports** in Search Console, and made them available worldwide at the end of August 2026. They show how often links to your site were shown in AI Overviews and AI Mode, with breakdowns by page, country, device and date. The report counts impressions, not clicks.

Combine that with:

- **Google Analytics 4** for visits and enquiries landing on the pages that appear in AI features.
- **Manual or tool-based checks** of your priority questions, recording which pages Google links to and which competitors appear.
- **Search Console's standard performance report**, which still shows clicks and queries for the same pages.

## What about clicks?

AI answers change click behaviour. Pew Research Center analysed the real browsing activity of 900 US adults in 2025 and found that users who saw an AI summary clicked a traditional result on 8 percent of visits, compared with 15 percent without one, and clicked a link inside the summary on just 1 percent.

That does not make AI features worthless for businesses. It changes what winning looks like. Being linked in the overview puts your brand in front of the searcher at the moment they form a view, and the visits that do come through are often further along in their decision. The strategy is to be present in the answer and to make the pages people land on convert well.

## A practical checklist

1. Confirm your key pages are indexed in Search Console and have no `nosnippet` or restrictive `max-snippet` rules.
2. Check your robots.txt, CDN and firewall rules do not block Googlebot or render resources.
3. Rewrite priority pages so each section opens with a direct answer to one question.
4. Add evidence: data, examples, expert quotes and links to primary sources.
5. Cover the subtopics your buyers ask about, so you can match fan-out searches.
6. Keep business details, authorship and contact information clear and consistent.
7. Track the generative AI performance report monthly alongside conversions.

## Frequently asked questions

::: faq
? Can I pay to appear in AI Overviews?
Organic links in AI Overviews cannot be bought. Google does show ads in and around AI Overviews in some markets, but those are labelled ads managed through Google Ads, separate from the organic sources.

? Does structured data help me get into AI Overviews?
Google does not list structured data as a requirement. It helps Google understand your pages, so it supports eligibility indirectly, but visible, helpful content is what earns the link.

? Will blocking Google-Extended remove my site from AI Overviews?
Google describes Google-Extended as a control for its Gemini models, separate from Search. Appearance in AI Overviews and AI Mode is governed by normal Search indexing and snippet controls.

? Why does Google link to a page that does not rank on page one?
Because of query fan-out. Google may run related searches for subtopics of your question and link to the best page for each, even if that page does not rank for the original query.
:::

Our [Answer Engine Optimization](service:answer-engine-optimization) and [Generative Engine Optimization](service:generative-engine-optimization) services apply this checklist to your most valuable pages.

## Sources

::: sources
- [AI features and your website](https://developers.google.com/search/docs/appearance/ai-features). Google Search Central.
- [A new resource for optimizing for generative AI in Google Search](https://developers.google.com/search/blog/2026/05/a-new-resource-for-optimizing). Google Search Central Blog, May 2026.
- [Introducing Search generative AI performance reports in Search Console](https://developers.google.com/search/blog/2026/06/gen-ai-performance-reports). Google Search Central Blog, June 2026.
- [Google Search: Introducing AI Mode in India](https://blog.google/intl/en-in/products/google-search-introducing-ai-mode-in-india/). Google India Blog, 2025.
- [Expanding the helpfulness of AI Overviews](https://blog.google/intl/en-in/products/explore-communicate/expanding-the-helpfulness-of-ai-overviews/). Google India Blog, August 2024.
- [Google's common crawlers, including Google-Extended](https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers). Google Search Central.
- [Robots meta tag, data-nosnippet and X-Robots-Tag specifications](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag). Google Search Central.
- [Google users are less likely to click on links when an AI summary appears in the results](https://www.pewresearch.org/short-reads/2025/07/22/google-users-are-less-likely-to-click-on-links-when-an-ai-summary-appears-in-the-results/). Pew Research Center.
:::
