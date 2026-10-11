#!/usr/bin/env python3
"""Generate the service catalogue pages from compact data.

The twelve flagship services are written by hand in content-src/services/.
The rest of the catalogue lives in content-src/catalog/*.py as short, specific
records (hero, answer, intro, deliverables, FAQs). This script expands each
record into a full Markdown page, adds the shared category sections (why it
matters, process, sources) and writes content-src/services/NNN-slug.md.
It also writes the service categories and their plate images into site.json.

Run before tools/build_content.py:  python3 tools/gen_catalog.py
"""

import importlib.util
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "content-src"
OUT = SRC / "services"

# Order matters: it decides menu order and category numbering.
CATEGORIES = [
    {
        "slug": "ai-search",
        "name": "AI Search and GEO",
        "label": "AI Search",
        "icon": "spark",
        "short": "Get your brand found, trusted and cited in ChatGPT, Gemini, Perplexity, Copilot and Google AI Overviews.",
        "description": "AVENZA's AI search services make your business easy for AI assistants to find, trust and quote. We combine technical access for AI crawlers, consistent facts about your brand, answer-first pages backed by evidence, and mentions on trusted sites, then measure how often assistants cite you against competitors.",
        "facts": "ChatGPT, Gemini, Perplexity, Copilot | Google AI Overviews and AI Mode | Citation tracking every month",
        "why": "At Google I/O in May 2026, Google said AI Overviews reach more than 2.5 billion people a month and AI Mode has passed one billion monthly users. Pew Research Center found that when Google showed an AI summary, people clicked a traditional result on 8 percent of visits instead of 15 percent. Being named inside the answer now matters as much as ranking below it.",
        "steps": [
            "**Question map.** The buyer questions that matter, grouped by stage and value.",
            "**Baseline.** Each question run across the main assistants, with every brand and source cited.",
            "**Foundation.** Crawler access, indexing, structured data and one set of brand facts.",
            "**Evidence.** Answer-first pages, original data and expert input that deserve citation.",
            "**Track.** The same questions re-run monthly, reported as share of citations.",
        ],
        "sources": [
            "[AI features and your website](https://developers.google.com/search/docs/appearance/ai-features). Google Search Central.",
            "[A new resource for optimizing for generative AI in Google Search](https://developers.google.com/search/blog/2026/05/a-new-resource-for-optimizing). Google Search Central Blog, May 2026.",
            "[GEO: Generative Engine Optimization](https://arxiv.org/abs/2311.09735). Aggarwal and others, KDD 2024.",
            "[Google users are less likely to click on links when an AI summary appears in the results](https://www.pewresearch.org/short-reads/2025/07/22/google-users-are-less-likely-to-click-on-links-when-an-ai-summary-appears-in-the-results/). Pew Research Center.",
        ],
    },
    {
        "slug": "seo",
        "name": "SEO",
        "label": "Search",
        "icon": "search",
        "short": "Rank for the searches that bring revenue, with technical, content, local and platform-specific SEO.",
        "description": "AVENZA's SEO services help your pages rank for the searches that lead to enquiries and sales. The work covers research into what buyers search for, technical fixes so pages can be crawled and indexed, content that answers each search fully, internal links and authority, all reported against leads rather than traffic alone.",
        "facts": "Intent-led research | Technical, content and authority | Reported against leads",
        "why": "Google's own guide to hiring an SEO says improvements usually take four months to a year to show their full benefit. Since its March 2024 core update, Google says it has cut low-quality, unoriginal content in results by about 45 percent, so the pages that win now are specific, useful and technically sound.",
        "steps": [
            "**Discovery.** Goals, margins and best customers, so we chase the right searches.",
            "**Audit.** Technical, content and authority review against your top competitors.",
            "**Roadmap.** A 90-day plan with the highest-impact fixes first.",
            "**Execution.** Fixes, rewrites, new pages and outreach shipped every week.",
            "**Review.** Monthly reporting and a quarterly reset of priorities.",
        ],
        "sources": [
            "[Do you need an SEO?](https://developers.google.com/search/docs/fundamentals/do-i-need-seo). Google Search Central.",
            "[Search Engine Optimization (SEO) Starter Guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide). Google Search Central.",
            "[What web creators should know about our March 2024 core update](https://developers.google.com/search/blog/2024/03/core-update-spam-policies). Google Search Central Blog.",
            "[Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content). Google Search Central.",
        ],
    },
    {
        "slug": "content",
        "name": "Content Marketing",
        "label": "Content",
        "icon": "quill",
        "short": "Research-led writing built with your experts, so people trust it, search engines rank it and AI tools quote it.",
        "description": "AVENZA's content services plan, write and refresh the pages, articles and assets your buyers read before they decide. Every piece starts from a real question, draws on interviews with your specialists, cites its sources and is structured so readers, search engines and AI assistants can use it easily.",
        "facts": "Expert interviews | Sources for every claim | Editorial review on every piece",
        "why": "Google's guidance on helpful content asks who created a page, how and why, and its spam policies now target content produced at scale mainly to rank. Research presented at KDD 2024 found that adding statistics, quotations and citations made content noticeably more visible in AI-generated answers. Substance beats volume.",
        "steps": [
            "**Brief.** Search intent, questions to answer, sources and the expert to interview.",
            "**Interview.** A short call to capture real experience and examples.",
            "**Draft.** Answer first, evidence after, sources linked.",
            "**Edit and fact-check.** Every claim checked, every number dated.",
            "**Publish and refresh.** Performance reviewed after 90 days, then updated.",
        ],
        "sources": [
            "[Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content). Google Search Central.",
            "[Google Search's guidance about AI-generated content](https://developers.google.com/search/blog/2023/02/google-search-and-ai-content). Google Search Central Blog.",
            "[GEO: Generative Engine Optimization](https://arxiv.org/abs/2311.09735). Aggarwal and others, KDD 2024.",
        ],
    },
    {
        "slug": "digital-pr",
        "name": "Digital PR and Reputation",
        "label": "Authority",
        "icon": "chain",
        "short": "Earn the links, mentions and reviews that prove your expertise to buyers, search engines and AI assistants.",
        "description": "AVENZA's digital PR and reputation services build the outside proof that search engines and AI assistants rely on. We earn coverage and links through data stories and expert commentary, fix listings and citations, and help you collect and answer reviews, always within the rules of Google and each platform.",
        "facts": "Earned coverage only | Reviews within platform rules | Mentions AI tools can read",
        "why": "Google says local prominence draws on information from across the web, such as links, articles and directories, and that review count and rating factor in. Its spam policies name link schemes, including bought links that pass ranking credit, as a reason to demote a site. Earned authority is the only kind that lasts.",
        "steps": [
            "**Gap analysis.** Where competitors are mentioned, linked and reviewed and you are not.",
            "**Story and asset.** The data, tool or expert angle that deserves coverage.",
            "**Outreach.** Personal pitches to relevant writers and site owners.",
            "**Support.** Fast replies, extra data and quotes when journalists ask.",
            "**Report.** Coverage, links, mentions and reviews tracked monthly.",
        ],
        "sources": [
            "[Spam policies for Google web search](https://developers.google.com/search/docs/essentials/spam-policies). Google Search Central.",
            "[Qualify your outbound links to Google](https://developers.google.com/search/docs/crawling-indexing/qualify-outbound-links). Google Search Central.",
            "[How to improve your local ranking on Google](https://support.google.com/business/answer/7091). Google Business Profile Help.",
        ],
    },
    {
        "slug": "paid-media",
        "name": "Paid Media and PPC",
        "label": "Paid Media",
        "icon": "target",
        "short": "Search, Shopping, video and marketplace ads run against tracked leads and revenue, not clicks.",
        "description": "AVENZA's paid media services plan, build and manage advertising on Google, Microsoft, YouTube, Amazon and programmatic networks. Every account starts with accurate conversion tracking, then campaigns are structured by intent, written to match each search and tuned every week against cost per lead or return on ad spend.",
        "facts": "Tracking before spend | Weekly search term reviews | You own every account",
        "why": "Google's Quality Score, rated from 1 to 10, reflects expected click-through rate, ad relevance and landing page experience compared with other advertisers on the same keyword. Accounts that score well usually pay less per click. Performance Max now runs one budget across Search, YouTube, Display, Discover, Gmail and Maps, which rewards clean data and good creative.",
        "steps": [
            "**Track first.** No budget increase until conversions are measured accurately.",
            "**Structure for intent.** Brand, high-intent and research campaigns kept apart.",
            "**Write to the search.** Ads and landing pages that answer the exact query.",
            "**Prune weekly.** Search terms and placements reviewed, waste excluded.",
            "**Scale what works.** Budget moved to campaigns with proven returns.",
        ],
        "sources": [
            "[About Quality Score for Search campaigns](https://support.google.com/google-ads/answer/6167118). Google Ads Help.",
            "[About Performance Max campaigns](https://support.google.com/google-ads/answer/10724817). Google Ads Help.",
            "[About negative keywords](https://support.google.com/google-ads/answer/2453972). Google Ads Help.",
        ],
    },
    {
        "slug": "social",
        "name": "Social Media Marketing",
        "label": "Social",
        "icon": "share",
        "short": "Organic content, short video, creators and paid social on the platforms your buyers actually use.",
        "description": "AVENZA's social media services build demand where buyers first discover brands. We plan content around your audience, produce short video and posts with your team, manage communities and run paid campaigns on Meta, LinkedIn, YouTube and Pinterest, with tracking that ties results to enquiries and sales.",
        "facts": "Platforms chosen by audience | Real people, real work on camera | Leads, not likes",
        "why": "The IAMAI and Kantar Internet in India 2025 report found that around 588 million Indian internet users, 61 percent of the total, watched short-video content. A buyer who has seen your brand on social is more likely to search for you, choose your result and trust what they read.",
        "steps": [
            "**Listen.** Audience research, competitor review and the questions buyers ask.",
            "**Plan.** Content pillars, formats and a calendar you approve in advance.",
            "**Create.** Video, design and copy in your team's real voice.",
            "**Promote.** Paid campaigns for the content that already works.",
            "**Measure.** Leads and sales tracked back to platform and creative.",
        ],
        "sources": [
            "[Internet in India 2025](https://www.iamai.in/research-category/strategic-research). IAMAI and Kantar.",
            "[Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api). Meta for Developers.",
            "[LinkedIn Insight Tag](https://business.linkedin.com/advertise/ads/insight-tag). LinkedIn.",
        ],
    },
    {
        "slug": "web",
        "name": "Websites and Development",
        "label": "Websites",
        "icon": "browser",
        "short": "Fast, accessible websites and stores, built clean for search and AI crawlers and easy for your team to edit.",
        "description": "AVENZA's website services design, build, speed up and look after websites and online stores on WordPress, WooCommerce, Shopify and modern frameworks. Every build is mobile-first, accessible to WCAG 2.2, technically clean for search and AI crawlers, and handed over with training so your team can edit it.",
        "facts": "WordPress, Shopify, custom | WCAG 2.2 and Core Web Vitals | SEO-safe launches",
        "why": "A Google-commissioned Deloitte study found that a 0.1 second improvement in mobile site speed raised retail conversions by 8.4 percent and travel conversions by 10.1 percent. WCAG 2.2, published by the W3C in October 2023, sets the current accessibility standard, including minimum target sizes for buttons and links.",
        "steps": [
            "**Discover.** Goals, audiences, analytics and the questions buyers ask.",
            "**Structure.** Sitemap, user journeys and wireframes.",
            "**Design.** Visual design for key templates, mobile first.",
            "**Build and test.** Speed, accessibility, forms and tracking checked.",
            "**Launch and care.** Redirects, monitoring and ongoing improvements.",
        ],
        "sources": [
            "[Web Content Accessibility Guidelines (WCAG) 2.2](https://www.w3.org/TR/WCAG22/). W3C.",
            "[Milliseconds make millions](https://web.dev/case-studies/milliseconds-make-millions). Deloitte Digital for Google.",
            "[Web Vitals](https://web.dev/articles/vitals). web.dev, Google.",
        ],
    },
    {
        "slug": "cro-analytics",
        "name": "CRO and Analytics",
        "label": "Conversion",
        "icon": "funnel",
        "short": "Measure what matters, find where visitors leave, and turn more of them into leads and customers.",
        "description": "AVENZA's conversion and analytics services make sure every enquiry and sale is measured correctly, then find and fix what stops visitors from acting. The work covers GA4 and tag setup, dashboards, call and conversion tracking, attribution, user research, landing page and checkout improvements, and honest A/B testing.",
        "facts": "Tracking you can trust | Research before redesign | Honest A/B statistics",
        "why": "Baymard Institute's running average across 50 studies puts online cart abandonment at about 70 percent. Not all of it is fixable, but slow pages, surprise costs, long forms and missing proof are. Every improvement in conversion makes every other marketing channel cheaper per lead.",
        "steps": [
            "**Measure.** Fix tracking and set a reliable baseline.",
            "**Research.** Analytics, recordings, surveys and expert review.",
            "**Hypothesise.** What to change, why, and the result we expect.",
            "**Test.** A/B tests where traffic allows, careful comparison where it does not.",
            "**Roll out.** Ship winners, record learning, move to the next idea.",
        ],
        "sources": [
            "[Cart abandonment rate statistics](https://baymard.com/lists/cart-abandonment-rate). Baymard Institute.",
            "[About key events in Google Analytics 4](https://support.google.com/analytics/answer/9267568). Google Analytics Help.",
            "[Milliseconds make millions](https://web.dev/case-studies/milliseconds-make-millions). Deloitte Digital for Google.",
        ],
    },
    {
        "slug": "email-automation",
        "name": "Email and Automation",
        "label": "Email",
        "icon": "envelope",
        "short": "Email, SMS and CRM automation that nurtures leads and brings customers back, sent with consent.",
        "description": "AVENZA's email and automation services set up and run the messages that turn enquiries into customers and customers into repeat buyers. We plan journeys, write and design emails, connect your CRM and forms, handle deliverability and consent, and report on revenue and pipeline rather than open rates alone.",
        "facts": "Consent-first lists | Deliverability set up properly | Revenue-based reporting",
        "why": "Since February 2024, Google has required bulk senders to authenticate email with SPF, DKIM and DMARC, offer one-click unsubscribe and keep spam complaint rates low. In India, the Digital Personal Data Protection Act, 2023 and its 2025 Rules make clear, recorded consent the basis for marketing messages.",
        "steps": [
            "**Audit.** Lists, consent records, deliverability and current journeys.",
            "**Map journeys.** Welcome, nurture, purchase and win-back flows.",
            "**Build.** Templates, automations, CRM fields and forms.",
            "**Send and test.** Subject lines, timing and content tested steadily.",
            "**Report.** Pipeline and revenue from each journey, monthly.",
        ],
        "sources": [
            "[Email sender guidelines](https://support.google.com/a/answer/81126). Google Workspace Admin Help.",
            "[Digital Personal Data Protection Act, 2023](https://www.meity.gov.in/). Ministry of Electronics and IT.",
        ],
    },
    {
        "slug": "ecommerce-growth",
        "name": "E-commerce and Marketplaces",
        "label": "Commerce",
        "icon": "bag",
        "short": "Grow sales on your own store and on Amazon and Flipkart, with clean feeds, strong listings and sharp offers.",
        "description": "AVENZA's e-commerce growth services help brands sell more on their own store and on marketplaces such as Amazon and Flipkart. We manage listings, product feeds, store setup, pricing visibility and customer journeys, and connect marketplace data with your own analytics so you can see what truly drives profit.",
        "facts": "Own store and marketplaces | Feeds and listings that convert | Profit-focused reporting",
        "why": "The IAMAI and Kantar Internet in India 2025 report counted 958 million active internet users, and Baymard Institute puts average online cart abandonment at about 70 percent. Reach is large, but so is the drop-off, which is why clean listings, accurate feeds and a smooth checkout matter as much as traffic.",
        "steps": [
            "**Audit.** Store, listings, feeds, pricing and reviews against competitors.",
            "**Fix the catalogue.** Titles, images, attributes and feed errors.",
            "**Grow traffic.** Marketplace ads, SEO and free listings working together.",
            "**Convert.** Offers, reviews, content and checkout improvements.",
            "**Report.** Sales, margin and return on ad spend by channel.",
        ],
        "sources": [
            "[Free listings for products](https://support.google.com/merchants/answer/13889434). Google Merchant Center Help.",
            "[Cart abandonment rate statistics](https://baymard.com/lists/cart-abandonment-rate). Baymard Institute.",
            "[Internet in India 2025](https://www.iamai.in/research-category/strategic-research). IAMAI and Kantar.",
        ],
    },
]

# Hand-written flagship services, by slug, and the category each belongs to.
FLAGSHIP = {
    "generative-engine-optimization": "ai-search",
    "answer-engine-optimization": "ai-search",
    "seo-services": "seo",
    "technical-seo": "seo",
    "local-seo": "seo",
    "ecommerce-seo": "seo",
    "content-marketing": "content",
    "link-building-digital-pr": "digital-pr",
    "google-ads-management": "paid-media",
    "social-media-marketing": "social",
    "website-design-development": "web",
    "conversion-rate-optimization": "cro-analytics",
}

ICON_NAMES = {
    "spark": "a spark", "search": "a magnifying glass", "quill": "a quill pen", "chain": "linked chain loops",
    "target": "a target and arrow", "share": "connected share nodes", "browser": "a browser window",
    "funnel": "a funnel", "envelope": "an envelope", "bag": "a shopping bag",
}


def load_records():
    records = []
    for path in sorted((SRC / "catalog").glob("*.py")):
        spec = importlib.util.spec_from_file_location(path.stem, path)
        mod = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(mod)
        records.extend(mod.SERVICES)
    return records


def meta_title(title):
    plain = title.endswith(("Services", "Management", "Setup", "Writing", "Design", "Development", "Marketing", "Audit", "Ads", "Tracking", "Testing", "Research", "Dashboards", "Integration", "Recovery", "Building"))
    options = ([] if plain else [f"{title} Services | AVENZA"]) + [f"{title} | AVENZA", title]
    for cand in options:
        if len(cand) <= 60:
            return cand
    return title[:60]


def page(rec, number, cat):
    plate = f"plate-{number:03d}"
    d_items = "\n".join(f"- **{d.split('. ', 1)[0]}.** {d.split('. ', 1)[1]}" for d in rec["d"])
    d_items += "\n- **Monthly report.** What changed, what it delivered and what we will do next, in plain language."
    faq = "\n\n".join(f"? {q}\n{a}" for q, a in rec["f"])
    steps = "\n".join(f"- {s}" for s in cat["steps"])
    sources = "\n".join(f"- {s}" for s in cat["sources"])
    return f"""---
title: {rec['t']}
slug: {rec['s']}
number: {number}
menu_order: {number}
category: {cat['slug']}
image: {plate}
excerpt: {rec['e']}
meta_title: {meta_title(rec['t'])}
meta_description: {rec['e']}
hero_eyebrow: N° {number} · {cat['label']}
hero_title: {rec['h']}
hero_lead: {rec['l']}
hero_image: {plate}
hero_primary: Book a Strategy Call | page:contact
hero_secondary: All {cat['name']} services | term:{cat['slug']}
hero_facts: {cat['facts']}
answer_q: {rec['q']}
answer: {rec['a']}
---

## What {rec['t']} covers

> {rec['i'][0]}

{rec['i'][1]}

{cat['why']}

## What you get {{wide alt}}

^ Deliverables

::: grid 3
{d_items}
:::

## How we deliver it {{wide}}

^ The process

::: steps
{steps}
:::

## Questions about {rec['t']} {{wide alt}}

^ FAQ

::: faq
{faq}
:::

## More {cat['name']} services {{wide}}

^ Related

[[services category=current limit=3 exclude_current=1]]

::: buttons
[See all {cat['name']} services](term:{cat['slug']}){{outline}}
:::

## Sources and further reading

::: sources
{sources}
:::
"""


def main():
    cats = {c["slug"]: c for c in CATEGORIES}
    records = load_records()
    seen = set()
    for old in OUT.glob("[0-9][0-9][0-9]-*.md"):
        old.unlink()

    # Flagship pages get their category added to front matter.
    for path in sorted(OUT.glob("[0-9][0-9]-*.md")):
        text = path.read_text(encoding="utf-8")
        slug = re.search(r"^slug: (.+)$", text, re.M).group(1).strip()
        cat = FLAGSHIP[slug]
        if re.search(r"^category: ", text, re.M):
            text = re.sub(r"^category: .*$", f"category: {cat}", text, count=1, flags=re.M)
        else:
            text = text.replace("\nimage: ", f"\ncategory: {cat}\nimage: ", 1)
        path.write_text(text, encoding="utf-8")
        seen.add(slug)

    images = []
    number = 12
    by_cat = {c["slug"]: [] for c in CATEGORIES}
    for rec in records:
        by_cat[rec["c"]].append(rec)
    for cat in CATEGORIES:
        for rec in by_cat[cat["slug"]]:
            if rec["s"] in seen:
                raise SystemExit(f"duplicate slug {rec['s']}")
            seen.add(rec["s"])
            number += 1
            (OUT / f"{number:03d}-{rec['s']}.md").write_text(page(rec, number, cat), encoding="utf-8")
            images.append({
                "key": f"plate-{number:03d}",
                "file": f"art/svc/plate-{number:03d}.webp",
                "title": f"N° {number} {rec['t']}",
                "alt": f"Engraved emerald plate numbered {number} with {ICON_NAMES[cat['icon']]}, for {rec['t']}",
                "_icon": cat["icon"],
                "_caption": rec["t"],
            })

    for cat in CATEGORIES:
        images.append({
            "key": f"cat-{cat['slug']}",
            "file": f"art/cat-{cat['slug']}.webp",
            "title": f"{cat['name']} plate",
            "alt": f"Engraved emerald plate with {ICON_NAMES[cat['icon']]} for the {cat['name']} services",
            "_icon": cat["icon"],
            "_caption": cat["name"],
        })

    site_path = SRC / "site.json"
    site = json.loads(site_path.read_text(encoding="utf-8"))
    base = [i for i in site["images"] if not re.match(r"plate-\d{3}$|cat-", i["key"])]
    site["images"] = base + [{k: v for k, v in i.items() if not k.startswith("_")} for i in images]
    site["service_categories"] = [
        {"slug": c["slug"], "name": c["name"], "short": c["short"], "description": c["description"], "image": f"cat-{c['slug']}"}
        for c in CATEGORIES
    ]
    site_path.write_text(json.dumps(site, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")
    (ROOT / "build").mkdir(exist_ok=True)
    (ROOT / "build" / "catalog-art.json").write_text(json.dumps(images, indent=1), encoding="utf-8")
    print(f"{len(records)} catalogue services written ({number} services in total), {len(CATEGORIES)} categories")


if __name__ == "__main__":
    main()
