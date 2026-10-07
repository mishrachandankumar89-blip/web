#!/usr/bin/env python3
"""Build AVENZA theme content from the Markdown sources in content-src/.

Each source file has a small front matter block and a Markdown-like body.
The script turns it into native Gutenberg block markup (core blocks only, so
the client edits everything in the normal editor), writes one .html file per
item into avenza/content/, and writes avenza/content/manifest.json, which the
theme importer reads.

It also lints every word of copy and refuses to build on:
  - em dashes or en dashes, emoji, banned AI-tell vocabulary
  - an answer block outside 40 to 60 words
  - meta titles over 60 characters or descriptions over 155
  - more than one H1, an image without alt text, an unknown image key
  - an internal link to a page, service or post that does not exist

Usage: python3 tools/build_content.py [--check]
"""

import html
import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "content-src"
OUT = ROOT / "avenza" / "content"

BANNED = [
    r"delve", r"delves", r"delving", r"tapestry", r"testament", r"realm", r"embark",
    r"seamless", r"seamlessly", r"robust", r"leverage", r"leverages", r"leveraging",
    r"comprehensive", r"elevate", r"elevates", r"unlock", r"unlocks", r"unleash",
    r"harness", r"foster", r"fosters", r"pivotal", r"game-changer", r"game changer",
    r"cutting-edge", r"ever-evolving", r"fast-paced", r"in today's", r"in conclusion",
    r"utilize", r"utilise", r"utilizes", r"facilitate", r"facilitates", r"synergy",
    r"holistic", r"paradigm", r"navigate the landscape", r"supercharge", r"skyrocket",
    r"revolutionize", r"revolutionise", r"world-class", r"best-in-class", r"look no further",
    r"needless to say", r"it's worth noting", r"it is worth noting", r"unparalleled",
    r"in the digital age", r"dive into", r"deep dive",
]
BANNED_RE = re.compile(r"(?<![a-z])(" + "|".join(BANNED) + r")(?![a-z])", re.I)
EMOJI_RE = re.compile("[\U0001F300-\U0001FAFF\U00002600-\U000027BF\U0001F000-\U0001F2FF]")
DASH_RE = re.compile("[—–]")

ERRORS = []


def err(where, msg):
    ERRORS.append(f"{where}: {msg}")


# ------------------------------------------------------------------ inline


def esc(text):
    return html.escape(text, quote=False)


def attr(text):
    return html.escape(text, quote=True)


def link_target(url, where, known):
    """Turn page:slug, service:slug, post:slug into importer tokens."""
    m = re.match(r"^(page|service|post):([a-z0-9/-]+)(#[\w-]+)?$", url)
    if m:
        kind, slug, frag = m.group(1), m.group(2), m.group(3) or ""
        if (kind, slug) not in known:
            err(where, f"link to unknown {kind} '{slug}'")
        return "{{link:%s:%s}}%s" % (kind, slug, frag)
    if url.startswith(("http://", "https://", "tel:", "mailto:", "#", "{{")):
        return url
    err(where, f"unsupported link target '{url}'")
    return url


def inline(text, where, known):
    """Escape and render **bold**, *italic*, `code` and [links](target).

    Links become placeholders first, so bold or italic markers can wrap a
    whole link without being split by it.
    """
    links = []

    def stash(m):
        label = _fmt(esc(m.group(1)))
        target = link_target(m.group(2), where, known)
        links.append(f'<a href="{attr(target)}">{label}</a>')
        return f"\x00{len(links) - 1}\x00"

    text = re.sub(r"\[([^\]]+)\]\(([^)\s]+)\)", stash, text)
    out = _fmt(esc(text))
    return re.sub("\x00(\\d+)\x00", lambda m: links[int(m.group(1))], out)


def _fmt(s):
    s = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", s)
    s = re.sub(r"(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])", r"<em>\1</em>", s)
    s = re.sub(r"`(.+?)`", r"<code>\1</code>", s)
    return s


# ------------------------------------------------------------------ blocks


def block(name, attrs, inner, void=False):
    a = (" " + json.dumps(attrs, separators=(",", ":"), ensure_ascii=False)) if attrs else ""
    if void:
        return f"<!-- wp:{name}{a} /-->"
    return f"<!-- wp:{name}{a} -->\n{inner}\n<!-- /wp:{name} -->"


def p(text_html, cls=None):
    attrs = {"className": cls} if cls else {}
    c = f' class="{cls}"' if cls else ""
    return block("paragraph", attrs, f"<p{c}>{text_html}</p>")


def heading(text_html, level=2, cls=None, anchor=None):
    attrs = {}
    if level != 2:
        attrs["level"] = level
    if cls:
        attrs["className"] = cls
    if anchor:
        attrs["anchor"] = anchor
    classes = "wp-block-heading" + (f" {cls}" if cls else "")
    idattr = f' id="{anchor}"' if anchor else ""
    return block("heading", attrs, f'<h{level} class="{classes}"{idattr}>{text_html}</h{level}>')


def list_block(items_html, ordered=False, cls=None):
    attrs = {}
    if ordered:
        attrs["ordered"] = True
    if cls:
        attrs["className"] = cls
    tag = "ol" if ordered else "ul"
    classes = "wp-block-list" + (f" {cls}" if cls else "")
    lis = "".join(block("list-item", {}, f"<li>{i}</li>") for i in items_html)
    return block("list", attrs, f'<{tag} class="{classes}">{lis}</{tag}>')


def table_block(headers, rows, cls="avz-table"):
    thead = "<thead><tr>" + "".join(f"<th>{h}</th>" for h in headers) + "</tr></thead>"
    tbody = "<tbody>" + "".join("<tr>" + "".join(f"<td>{c}</td>" for c in r) + "</tr>" for r in rows) + "</tbody>"
    return block("table", {"className": cls},
                 f'<figure class="wp-block-table {cls}"><table class="has-fixed-layout">{thead}{tbody}</table></figure>')


def group(inner_blocks, cls, align=None, tag=None):
    attrs = {}
    if tag:
        attrs["tagName"] = tag
    if align:
        attrs["align"] = align
    attrs["className"] = cls
    t = tag or "div"
    classes = "wp-block-group" + (f" align{align}" if align else "") + f" {cls}"
    inner = "".join(inner_blocks)
    return block("group", attrs, f'<{t} class="{classes}">{inner}</{t}>')


def columns(cols, cls):
    """cols: list of (width or None, class, [blocks])."""
    inner = []
    for width, ccls, blocks in cols:
        attrs = {}
        if width:
            attrs["width"] = width
        if ccls:
            attrs["className"] = ccls
        style = f' style="flex-basis:{width}"' if width else ""
        classes = "wp-block-column" + (f" {ccls}" if ccls else "")
        inner.append(block("column", attrs, f'<div class="{classes}"{style}>{"".join(blocks)}</div>'))
    return block("columns", {"className": cls}, f'<div class="wp-block-columns {cls}">{"".join(inner)}</div>')


def buttons(btns, cls=None, center=False):
    """btns: list of (label_html, url, outline)."""
    inner = []
    for label, url, outline in btns:
        battrs = {"className": "is-style-outline"} if outline else {}
        bcls = "wp-block-button" + (" is-style-outline" if outline else "")
        inner.append(block("button", battrs,
                           f'<div class="{bcls}"><a class="wp-block-button__link wp-element-button" href="{attr(url)}">{label}</a></div>'))
    attrs = {}
    if cls:
        attrs["className"] = cls
    if center:
        attrs["layout"] = {"type": "flex", "justifyContent": "center"}
    classes = "wp-block-buttons" + (f" {cls}" if cls else "")
    if center:
        classes += " is-content-justification-center"
    return block("buttons", attrs, f'<div class="{classes}">{"".join(inner)}</div>')


def image_block(key, alt, caption_html=None, cls="avz-figure", size="large"):
    attrs = {"id": "__ID__", "sizeSlug": size, "linkDestination": "none", "className": cls}
    a = json.dumps(attrs, separators=(",", ":")).replace('"__ID__"', "{{img_id:%s}}" % key)
    cap = f'<figcaption class="wp-element-caption">{caption_html}</figcaption>' if caption_html else ""
    inner = (f'<figure class="wp-block-image size-{size} {cls}"><img src="{{{{img_src:{key}}}}}" '
             f'alt="{attr(alt)}" class="wp-image-{{{{img_id:{key}}}}}"/>{cap}</figure>')
    return f"<!-- wp:image {a} -->\n{inner}\n<!-- /wp:image -->"


def details(summary_html, inner_blocks):
    return block("details", {}, f'<details class="wp-block-details"><summary>{summary_html}</summary>{"".join(inner_blocks)}</details>')


def shortcode(code):
    return block("shortcode", {}, code)


def quote_block(text_html, cite_html=None):
    cite = f"<cite>{cite_html}</cite>" if cite_html else ""
    return block("quote", {}, f'<blockquote class="wp-block-quote">{p(text_html)}{cite}</blockquote>')


# ------------------------------------------------------------- source parse


def read_source(path):
    raw = path.read_text(encoding="utf-8")
    if not raw.startswith("---\n"):
        raise SystemExit(f"{path}: missing front matter")
    _, fm, body = raw.split("---\n", 2)
    meta = {}
    for line in fm.strip().splitlines():
        if not line.strip() or line.strip().startswith("#"):
            continue
        key, _, value = line.partition(":")
        value = value.strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
            value = value[1:-1]
        meta[key.strip()] = value
    return meta, body


class Ctx:
    def __init__(self, where, known, images):
        self.where = where
        self.known = known
        self.images = images
        self.h1 = 0

    def i(self, text):
        return inline(text, self.where, self.known)


def parse_shortcode(line):
    m = re.match(r"^\[\[(\w+)(.*?)\]\]$", line.strip())
    if not m:
        return None
    name, args = m.group(1), m.group(2).strip()
    params = dict(re.findall(r'(\w+)=("[^"]*"|\S+)', args))
    attrs = " ".join(f'{k}="{v.strip(chr(34))}"' for k, v in params.items())
    allowed = {"services", "posts", "contact_form", "contact_details", "map"}
    if name not in allowed:
        return None
    return shortcode(f"[avenza_{name}{(' ' + attrs) if attrs else ''}]")


def parse_body(lines, ctx):
    """Parse a run of body lines into blocks. Returns list of block strings."""
    blocks = []
    i = 0
    para = []

    def flush():
        if para:
            text = " ".join(s.strip() for s in para)
            blocks.append(p(ctx.i(text)))
            para.clear()

    while i < len(lines):
        line = lines[i]
        s = line.strip()

        if not s:
            flush()
            i += 1
            continue

        if s.startswith("# "):
            err(ctx.where, "H1 in body; the hero carries the only H1")
            i += 1
            continue

        if s.startswith(":::"):
            flush()
            kind = s[3:].strip()
            j = i + 1
            depth = 1
            inner = []
            while j < len(lines):
                t = lines[j].strip()
                if t.startswith(":::") and t != ":::":
                    depth += 1
                elif t == ":::":
                    depth -= 1
                    if depth == 0:
                        break
                inner.append(lines[j])
                j += 1
            blocks.extend(directive(kind, inner, ctx))
            i = j + 1
            continue

        sc = parse_shortcode(s)
        if sc:
            flush()
            blocks.append(sc)
            i += 1
            continue

        if s.startswith("### "):
            flush()
            blocks.append(heading(ctx.i(s[4:]), 3))
            i += 1
            continue

        if s.startswith("#### "):
            flush()
            blocks.append(heading(ctx.i(s[5:]), 4))
            i += 1
            continue

        m = re.match(r"^!\[([^\]]*)\]\(img:([a-z0-9-]+)(?:\s+\"([^\"]*)\")?\)$", s)
        if m:
            flush()
            alt, key, cap = m.group(1), m.group(2), m.group(3)
            if key not in ctx.images:
                err(ctx.where, f"unknown image '{key}'")
            if not alt.strip():
                err(ctx.where, f"image '{key}' has no alt text")
            blocks.append(image_block(key, alt, ctx.i(cap) if cap else None))
            i += 1
            continue

        if s.startswith("|"):
            flush()
            rows = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                rows.append([c.strip() for c in lines[i].strip().strip("|").split("|")])
                i += 1
            header, body_rows = rows[0], [r for r in rows[2:]]
            blocks.append(table_block([ctx.i(h) for h in header], [[ctx.i(c) for c in r] for r in body_rows]))
            continue

        if re.match(r"^- ", s):
            flush()
            items = []
            while i < len(lines) and re.match(r"^- ", lines[i].strip()):
                items.append(ctx.i(lines[i].strip()[2:]))
                i += 1
            blocks.append(list_block(items))
            continue

        if re.match(r"^\d+\. ", s):
            flush()
            items = []
            while i < len(lines) and re.match(r"^\d+\. ", lines[i].strip()):
                items.append(ctx.i(re.sub(r"^\d+\. ", "", lines[i].strip())))
                i += 1
            blocks.append(list_block(items, ordered=True))
            continue

        if s.startswith("> "):
            flush()
            blocks.append(p(ctx.i(s[2:]), "avz-lead"))
            i += 1
            continue

        para.append(s)
        i += 1

    flush()
    return blocks


def items_of(lines):
    return [l.strip()[2:] for l in lines if l.strip().startswith("- ")]


def directive(kind, inner, ctx):
    parts = kind.split()
    name = parts[0] if parts else ""
    arg = " ".join(parts[1:])

    if name in ("grid", "steps"):
        inner = [re.sub(r"^(\s*- \*\*)(.+?)\.\*\*", r"\1\2**", l) for l in inner]

    if name == "grid":
        cls = "avz-grid-list" + (" is-three" if arg == "3" else "")
        return [list_block([ctx.i(t) for t in items_of(inner)], cls=cls)]

    if name == "checks":
        return [list_block([ctx.i(t) for t in items_of(inner)], cls="avz-checks")]

    if name == "steps":
        return [list_block([ctx.i(t) for t in items_of(inner)], ordered=True, cls="avz-steps")]

    if name == "sources":
        out = [heading(ctx.i(arg), 3, "avz-sources-title")] if arg else []
        return out + [list_block([ctx.i(t) for t in items_of(inner)], ordered=True, cls="avz-sources")]

    if name == "note":
        text = " ".join(l.strip() for l in inner if l.strip())
        return [p(ctx.i(text), "avz-note")]

    if name == "quote":
        text_lines = [l.strip() for l in inner if l.strip() and not l.strip().startswith("-- ")]
        cite = [l.strip()[3:] for l in inner if l.strip().startswith("-- ")]
        return [quote_block(ctx.i(" ".join(text_lines)), ctx.i(cite[0]) if cite else None)]

    if name == "faq":
        out = []
        q = None
        ans = []

        def push():
            if q is not None:
                blks = parse_body(ans, ctx)
                if not blks:
                    err(ctx.where, f"FAQ '{q}' has no answer")
                out.append(details(ctx.i(q), blks))

        for l in inner:
            if l.strip().startswith("? "):
                push()
                q = l.strip()[2:]
                ans = []
            else:
                ans.append(l)
        push()
        return [group(out, "avz-faq")]

    if name == "buttons":
        btns = []
        for l in inner:
            m = re.match(r"^\[([^\]]+)\]\(([^)]+)\)(\{outline\})?$", l.strip())
            if m:
                btns.append((ctx.i(m.group(1)), link_target(m.group(2), ctx.where, ctx.known), bool(m.group(3))))
        return [buttons(btns)]

    if name == "split":
        text = "\n".join(inner)
        left, _, right = text.partition("\n|||\n")
        return [columns([(None, "avz-split__a", parse_body(left.splitlines(), ctx)),
                         (None, "avz-split__b", parse_body(right.splitlines(), ctx))], "avz-split")]

    err(ctx.where, f"unknown directive '{name}'")
    return []


# --------------------------------------------------------------- sections


def split_sections(body):
    """Split the body at '## ' headings. Returns intro lines and sections."""
    lines = body.splitlines()
    sections = []
    current = None
    intro = []
    in_directive = 0
    for line in lines:
        s = line.strip()
        if s.startswith(":::") and s != ":::":
            in_directive += 1
        elif s == ":::":
            in_directive -= 1
        if s.startswith("## ") and in_directive == 0:
            m = re.match(r"^## (.+?)(?:\s*\{([^}]*)\})?$", s)
            current = {"title": m.group(1), "flags": (m.group(2) or "").split(), "lines": []}
            sections.append(current)
        elif current is None:
            intro.append(line)
        else:
            current["lines"].append(line)
    return intro, sections


def section_markup(sec, number, bg, ctx):
    flags = sec["flags"]
    title_html = ctx.i(sec["title"])
    eyebrow = None
    lines = []
    for l in sec["lines"]:
        if l.strip().startswith("^ ") and eyebrow is None:
            eyebrow = l.strip()[2:]
        else:
            lines.append(l)
    for f in ("alt", "dark", "white"):
        if f in flags:
            bg = f
    cls = "avz-section" + ("" if bg == "white" else f" avz-section--{bg}")
    num_html = "N&deg; %02d" % number

    if "wide" in flags:
        intro = []
        rest = []
        started = False
        for l in lines:
            if not started and (l.strip().startswith("> ") or not l.strip()):
                if l.strip():
                    intro.append(l.strip()[2:])
                continue
            started = True
            rest.append(l)
        head = [p(ctx.i(eyebrow) if eyebrow else num_html, "avz-eyebrow" if eyebrow else "avz-num"),
                heading(title_html, 2, "avz-section__title")]
        head += [p(ctx.i(t)) for t in intro]
        head_cls = "avz-section__head" + (" is-centered" if "center" in flags else "")
        inner = [group(head, head_cls)] + parse_body(rest, ctx)
        return group(inner, cls, align="full"), bg

    head = [p(num_html, "avz-num"), heading(title_html, 2)]
    if eyebrow:
        head.insert(0, p(ctx.i(eyebrow), "avz-eyebrow"))
    body_blocks = parse_body(lines, ctx)
    mag = columns([("33.33%", "avz-mag__head", head), ("66.66%", "avz-mag__body", body_blocks)], "avz-mag")
    return group([mag], cls, align="full"), bg


def hero_markup(meta, ctx):
    eyebrow = meta.get("hero_eyebrow", "")
    title = meta.get("hero_title") or meta["title"]
    lead = meta.get("hero_lead", "")
    img = meta.get("hero_image", "")
    text = []
    if eyebrow:
        text.append(p(ctx.i(eyebrow), "avz-eyebrow"))
    text.append(heading(ctx.i(title), 1, "avz-hero__title"))
    ctx.h1 += 1
    if lead:
        text.append(p(ctx.i(lead), "avz-hero__lead"))
    btns = []
    for key, outline in (("hero_primary", False), ("hero_secondary", True)):
        if meta.get(key):
            label, _, url = meta[key].partition("|")
            btns.append((ctx.i(label.strip()), link_target(url.strip(), ctx.where, ctx.known), outline))
    if btns:
        text.append(buttons(btns, "avz-hero__actions"))
    if meta.get("hero_facts"):
        facts = [ctx.i(f.strip()) for f in meta["hero_facts"].split("|")]
        text.append(list_block(facts, cls="avz-hero__facts"))

    if img:
        if img not in ctx.images:
            err(ctx.where, f"unknown hero image '{img}'")
        art = [image_block(img, ctx.images.get(img, {}).get("alt", ""), None, "avz-plate")]
        grid = columns([("56%", "avz-hero__text", text), ("44%", "avz-hero__art", art)], "avz-hero__grid")
        return group([grid], "avz-hero", align="full")
    return group([group(text, "avz-hero__text")], "avz-hero avz-hero--simple", align="full")


def answer_markup(meta, ctx):
    q = meta.get("answer_q")
    a = meta.get("answer")
    if not q or not a:
        err(ctx.where, "missing answer_q or answer")
        return ""
    words = len(a.split())
    if not 40 <= words <= 60:
        err(ctx.where, f"answer block is {words} words, needs 40 to 60")
    inner = [p("The short answer", "avz-eyebrow"), heading(ctx.i(q), 2), p(ctx.i(a))]
    return group([group(inner, "avz-answer")], "avz-answer-wrap", align="full")


def cta_markup(meta, ctx, site):
    if meta.get("cta") == "none":
        return ""
    head = meta.get("cta_heading") or site["cta"]["heading"]
    text = meta.get("cta_text") or site["cta"]["text"]
    inner = [
        p("Next step", "avz-eyebrow"),
        heading(ctx.i(head), 2),
        p(ctx.i(text)),
        buttons([(esc(site["cta"]["primary"]), link_target("page:contact", ctx.where, ctx.known), False),
                 (esc(site["cta"]["secondary"]), site["cta"]["secondary_url"], True)], center=True),
    ]
    return group([group(inner, "avz-cta")], "avz-cta-band", align="full")


def build_item(path, kind, known, images, site):
    meta, body = read_source(path)
    where = str(path.relative_to(ROOT))
    ctx = Ctx(where, known, images)

    for field in ("title", "slug", "meta_title", "meta_description"):
        if not meta.get(field):
            err(where, f"missing {field}")
    if len(meta.get("meta_title", "")) > 60:
        err(where, f"meta_title is {len(meta['meta_title'])} characters, max 60")
    md = meta.get("meta_description", "")
    if len(md) > 155 or len(md) < 70:
        err(where, f"meta_description is {len(md)} characters, needs 70 to 155")

    out = []
    if kind == "post":
        intro, sections = split_sections(body)
        if meta.get("answer"):
            words = len(meta["answer"].split())
            if not 40 <= words <= 60:
                err(where, f"answer block is {words} words, needs 40 to 60")
            out.append(group([p("The short answer", "avz-eyebrow"), heading(ctx.i(meta["answer_q"]), 2),
                              p(ctx.i(meta["answer"]))], "avz-answer"))
        out += parse_body(intro, ctx)
        for sec in sections:
            out.append(heading(ctx.i(sec["title"]), 2))
            out += parse_body([l for l in sec["lines"]], ctx)
    else:
        out.append(hero_markup(meta, ctx))
        out.append(answer_markup(meta, ctx))
        intro, sections = split_sections(body)
        if meta.get("layout") == "document":
            doc = parse_body(intro, ctx)
            for sec in sections:
                doc.append(heading(ctx.i(sec["title"]), 2))
                doc += parse_body(sec["lines"], ctx)
            out.append(group(doc, "avz-section avz-doc", align="full"))
            out.append(cta_markup(meta, ctx, site))
            sections = []
        elif any(l.strip() for l in intro):
            out.append(group(parse_body(intro, ctx), "avz-section", align="full"))
        bgs = ["white", "alt"]
        for n, sec in enumerate(sections, 1):
            markup, _ = section_markup(sec, n, bgs[(n - 1) % 2], ctx)
            out.append(markup)
        if meta.get("layout") != "document":
            out.append(cta_markup(meta, ctx, site))

    markup = "\n\n".join(x for x in out if x) + "\n"
    if markup.count("<h1") != 1 and kind != "post":
        err(where, f"{markup.count('<h1')} H1 elements, needs exactly 1")
    if kind == "post" and "<h1" in markup:
        err(where, "posts get their H1 from the template")
    return meta, markup


def lint_text(where, text):
    plain = re.sub(r"<[^>]+>", " ", text)
    plain = re.sub(r"<!--.*?-->", " ", plain)
    plain = re.sub(r"https?://\S+", " ", plain)
    if DASH_RE.search(plain):
        snippet = plain[max(0, DASH_RE.search(plain).start() - 30):DASH_RE.search(plain).start() + 30]
        err(where, f"em or en dash found near '{snippet.strip()}'")
    if EMOJI_RE.search(plain):
        err(where, "emoji found")
    for m in BANNED_RE.finditer(plain):
        err(where, f"banned word '{m.group(1)}' near '{plain[max(0, m.start()-30):m.end()+30].strip()}'")


def word_count(markup):
    plain = re.sub(r"<!--.*?-->", " ", markup)
    plain = re.sub(r"<[^>]+>", " ", plain)
    plain = re.sub(r"\{\{[^}]+\}\}", " ", plain)
    return len(re.findall(r"[A-Za-z0-9][A-Za-z0-9'%.-]*", plain))


def main():
    check_only = "--check" in sys.argv
    site = json.loads((SRC / "site.json").read_text(encoding="utf-8"))
    images = {img["key"]: img for img in site["images"]}

    files = {
        "service": sorted((SRC / "services").glob("*.md")),
        "page": sorted((SRC / "pages").glob("*.md")),
        "post": sorted((SRC / "posts").glob("*.md")),
    }
    known = set()
    for kind, paths in files.items():
        for path in paths:
            meta, _ = read_source(path)
            known.add((kind, meta["slug"]))

    manifest = {
        "version": site["version"],
        "site": site["site"],
        "front_page": site["front_page"],
        "posts_page": site["posts_page"],
        "images": [],
        "services": [],
        "pages": [],
        "posts": [],
        "menus": site["menus"],
    }
    for img in site["images"]:
        if not img.get("alt"):
            err("site.json", f"image '{img['key']}' has no alt")
        lint_text(f"site.json image {img['key']}", img.get("alt", ""))
        manifest["images"].append({k: img[k] for k in ("key", "file", "alt", "title")})

    report = []
    outputs = {}
    for kind, paths in files.items():
        for path in paths:
            meta, markup = build_item(path, kind, known, images, site)
            where = str(path.relative_to(ROOT))
            lint_text(where, markup)
            for key in ("title", "meta_title", "meta_description", "excerpt"):
                lint_text(f"{where} {key}", meta.get(key, ""))
            folder = {"service": "services", "page": "pages", "post": "posts"}[kind]
            rel = f"{folder}/{meta['slug']}.html"
            outputs[rel] = markup
            item = {
                "slug": meta["slug"],
                "title": meta["title"],
                "excerpt": meta.get("excerpt", ""),
                "meta_title": meta["meta_title"],
                "meta_description": meta["meta_description"],
                "image": meta.get("image", meta.get("hero_image", "")),
                "menu_order": int(meta.get("menu_order", 0) or 0),
                "content_file": rel,
            }
            if item["image"] and item["image"] not in images:
                err(where, f"unknown image '{item['image']}'")
            if kind == "service":
                item["number"] = meta.get("number", "")
            if kind == "post":
                item["category"] = meta.get("category", "")
                item["date"] = meta.get("date", "")
            manifest[{"service": "services", "page": "pages", "post": "posts"}[kind]].append(item)
            report.append((rel, word_count(markup)))

    for menu, items in site["menus"].items():
        for item in items:
            for it in [item] + item.get("children", []):
                if (it["type"], it["slug"]) not in known:
                    err("site.json", f"menu '{menu}' links to unknown {it['type']} '{it['slug']}'")

    if ERRORS:
        print("Content check failed:")
        for e in ERRORS:
            print("  -", e)
        sys.exit(1)

    total = 0
    for rel, words in report:
        total += words
        print(f"  {words:6d} words  {rel}")
    print(f"  {total:6d} words in total, {len(report)} items")

    if check_only:
        return

    for old in OUT.glob("*/*.html"):
        old.unlink()
    for rel, markup in outputs.items():
        target = OUT / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(markup, encoding="utf-8")
    (OUT / "manifest.json").write_text(json.dumps(manifest, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Wrote {len(outputs)} content files and manifest.json")


if __name__ == "__main__":
    main()
