# AVENZA WordPress Theme

Custom WordPress theme for **AVENZA**, a digital marketing agency for GEO, AEO, SEO, content, digital PR, paid media, websites and conversion work.

- Install file: [`dist/avenza.zip`](dist/avenza.zip)
- Handover and launch checklist (Hinglish): [`docs/HANDOVER.md`](docs/HANDOVER.md)
- Previews: [home, desktop](docs/preview-home-desktop.jpg), [home, phone](docs/preview-home-mobile.jpg), [service page](docs/preview-service-desktop.jpg)

## What is inside

| Folder | Purpose |
|---|---|
| `avenza/` | The theme: templates, `inc/` modules, CSS, JS, fonts, artwork, and the bundled content in `avenza/content/` |
| `content-src/` | Source of every page, service and article in Markdown with front matter, plus `site.json` (images, menus, calls to action) |
| `tools/` | Content builder and linter, artwork generator, QA and functional test scripts, packaging |
| `dist/` | Built, installable theme zip |
| `docs/` | Handover document |

## Build

```bash
python3 tools/build_content.py        # Markdown to block markup, with copy and SEO checks
bash tools/package.sh                 # build content, lint, version check, zip to dist/avenza.zip
bash tools/package.sh --art           # also redraw the artwork (needs Playwright and Pillow)
```

## Test against a running WordPress

```bash
SITE=http://127.0.0.1:9400 node tools/qa.js --import --editor --shots
SITE=http://127.0.0.1:9400 node tools/functional.js
```

`qa.js` renders every page at nine viewports from 320px to 1920px and fails on sideways scrolling, overflowing elements, WCAG AA contrast, tap targets under 44px, small text, H1 count, missing alt text, wrong template, JavaScript errors and a broken mobile menu. With `--editor` it opens every page in the block editor and fails on any invalid block. `functional.js` covers the contact form, enquiries, llms.txt, robots.txt, sitemap, schema, meta tags, the mobile menu keyboard flow and the admin screens.
