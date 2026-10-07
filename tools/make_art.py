#!/usr/bin/env python3
"""Generate AVENZA's engraved artwork as HTML pages for rendering.

Every plate is drawn from code: guilloche rosettes, woven rings and banknote
style wave fields in emerald and white, with a central medallion carrying a
line symbol. Writing it this way keeps the artwork original, license-clean and
consistent, and lets one script redraw everything when the palette changes.

Output: build/art/*.html plus build/art/jobs.json, rendered to images by
tools/render_art.js.
"""

import json
import math
import random
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
BUILD = ROOT / "build" / "art"
FONTS = ROOT / "avenza" / "assets" / "fonts"

EMERALD_950 = "#041F14"
EMERALD_900 = "#062A1B"
EMERALD_800 = "#0A3A26"
EMERALD_700 = "#0E4A31"
GREEN_600 = "#0F5C3B"
GREEN_500 = "#167A4E"
MINT = "#8FE3B5"
MINT_300 = "#A8EBC5"
MINT_50 = "#F2F9F4"
WHITE = "#FFFFFF"

# ------------------------------------------------------------------ geometry


def fmt(v):
    return f"{v:.1f}"


def path_from(points, close=True):
    d = "M" + " L".join(f"{fmt(x)} {fmt(y)}" for x, y in points)
    return d + (" Z" if close else "")


def hypotrochoid(cx, cy, R, r, d, rot=0.0, steps=1400):
    """Closed spirograph curve."""
    g = math.gcd(int(R), int(r))
    turns = int(r) // g
    pts = []
    total = 2 * math.pi * turns
    for i in range(steps + 1):
        t = total * i / steps
        x = (R - r) * math.cos(t) + d * math.cos((R - r) / r * t)
        y = (R - r) * math.sin(t) - d * math.sin((R - r) / r * t)
        xr = x * math.cos(rot) - y * math.sin(rot)
        yr = x * math.sin(rot) + y * math.cos(rot)
        pts.append((cx + xr, cy + yr))
    return path_from(pts)


def wavy_ring(cx, cy, r0, amp, lobes, phase, steps=720):
    pts = []
    for i in range(steps + 1):
        t = 2 * math.pi * i / steps
        r = r0 + amp * math.sin(lobes * t + phase)
        pts.append((cx + r * math.cos(t), cy + r * math.sin(t)))
    return path_from(pts)


def rosette(cx, cy, radius, seed, color, opacity, width=0.7, layers=3):
    rnd = random.Random(seed)
    out = []
    for layer in range(layers):
        R = rnd.choice([96, 105, 112, 120, 126, 132, 140])
        r = rnd.choice([7, 9, 11, 13, 15, 17, 19, 21, 23])
        while math.gcd(R, r) not in (1, 2, 3):
            r += 2
        d = rnd.uniform(0.55, 0.95) * r * rnd.uniform(1.6, 2.6)
        scale = radius / (R - r + d) * (1 - 0.14 * layer)
        rot = rnd.uniform(0, math.pi)
        dpath = hypotrochoid(0, 0, R, r, d, rot, steps=1600)
        out.append(
            f'<path d="{dpath}" transform="translate({fmt(cx)} {fmt(cy)}) scale({scale:.4f})" '
            f'fill="none" stroke="{color}" stroke-opacity="{opacity * (1 - 0.18 * layer):.3f}" '
            f'stroke-width="{width / scale:.3f}"/>'
        )
    return "\n".join(out)


def woven_band(cx, cy, r0, amp, lobes, lines, color, opacity, width=0.8):
    out = []
    for k in range(lines):
        phase = 2 * math.pi * k / lines
        out.append(
            f'<path d="{wavy_ring(cx, cy, r0, amp, lobes, phase)}" fill="none" stroke="{color}" '
            f'stroke-opacity="{opacity:.3f}" stroke-width="{width}"/>'
        )
    return "\n".join(out)


def wave_field(w, h, seed, color, opacity, spacing=14, width=0.6):
    rnd = random.Random(seed)
    lam = rnd.uniform(160, 260)
    amp = rnd.uniform(8, 16)
    out = []
    y = -20.0
    i = 0
    while y < h + 20:
        phase = i * 0.22
        pts = []
        x = -10.0
        while x <= w + 10:
            pts.append((x, y + amp * math.sin(2 * math.pi * x / lam + phase) + 4 * math.sin(2 * math.pi * x / (lam * 0.37) - phase)))
            x += 8
        out.append(f'<path d="{path_from(pts, close=False)}" fill="none" stroke="{color}" stroke-opacity="{opacity:.3f}" stroke-width="{width}"/>')
        y += spacing
        i += 1
    return "\n".join(out)


def frame(w, h, color, inset=26, gap=12):
    a = inset
    b = inset + gap
    diamonds = []
    for x, y in ((a, a), (w - a, a), (a, h - a), (w - a, h - a)):
        diamonds.append(f'<rect x="{x - 6}" y="{y - 6}" width="12" height="12" transform="rotate(45 {x} {y})" fill="{color}" fill-opacity=".85"/>')
    return (
        f'<rect x="{a}" y="{a}" width="{w - 2 * a}" height="{h - 2 * a}" fill="none" stroke="{color}" stroke-opacity=".6" stroke-width="1.4"/>'
        f'<rect x="{b}" y="{b}" width="{w - 2 * b}" height="{h - 2 * b}" fill="none" stroke="{color}" stroke-opacity=".3" stroke-width="1"/>'
        + "".join(diamonds)
    )


# --------------------------------------------------------------------- icons
# Line symbols on a 100 unit grid, centred on 50,50.

ICONS = {
    "spark": '<path d="M50 8 C53 34 66 47 92 50 C66 53 53 66 50 92 C47 66 34 53 8 50 C34 47 47 34 50 8 Z"/><path d="M80 14 C81 21 83 23 90 24 C83 25 81 27 80 34 C79 27 77 25 70 24 C77 23 79 21 80 14 Z"/><path d="M20 70 C21 75 22 76 27 77 C22 78 21 79 20 84 C19 79 18 78 13 77 C18 76 19 75 20 70 Z"/>',
    "answer": '<path d="M14 22 H86 V66 H44 L26 82 V66 H14 Z"/><path d="M34 44 L46 55 L68 33"/>',
    "search": '<circle cx="42" cy="42" r="26"/><path d="M61 61 L88 88"/><path d="M28 50 L38 40 L46 46 L58 32"/>',
    "gear": '<path d="M50 14 L56 14 L58 24 L66 27 L74 21 L79 26 L73 34 L76 42 L86 44 L86 56 L76 58 L73 66 L79 74 L74 79 L66 73 L58 76 L56 86 L44 86 L42 76 L34 73 L26 79 L21 74 L27 66 L24 58 L14 56 L14 44 L24 42 L27 34 L21 26 L26 21 L34 27 L42 24 L44 14 Z"/><path d="M42 42 L34 50 L42 58"/><path d="M58 42 L66 50 L58 58"/>',
    "pin": '<path d="M50 90 C50 90 20 60 20 38 A30 30 0 0 1 80 38 C80 60 50 90 50 90 Z"/><circle cx="50" cy="38" r="11"/>',
    "bag": '<path d="M18 32 H82 L76 88 H24 Z"/><path d="M36 40 V26 A14 14 0 0 1 64 26 V40"/>',
    "quill": '<path d="M84 12 C52 18 32 40 24 76 L30 78 C40 52 58 34 84 12 Z"/><path d="M24 76 L16 90"/><path d="M40 52 L60 50"/><path d="M12 90 H60"/>',
    "chain": '<rect x="10" y="38" width="44" height="24" rx="12" transform="rotate(-35 32 50)"/><rect x="46" y="38" width="44" height="24" rx="12" transform="rotate(-35 68 50)"/>',
    "target": '<circle cx="46" cy="54" r="34"/><circle cx="46" cy="54" r="20"/><circle cx="46" cy="54" r="5"/><path d="M46 54 L86 14"/><path d="M74 14 H86 V26"/>',
    "share": '<circle cx="24" cy="50" r="11"/><circle cx="76" cy="22" r="11"/><circle cx="76" cy="78" r="11"/><path d="M34 45 L66 27"/><path d="M34 55 L66 73"/>',
    "browser": '<rect x="10" y="16" width="80" height="68"/><path d="M10 30 H90"/><circle cx="19" cy="23" r="2"/><circle cx="27" cy="23" r="2"/><path d="M20 42 H52 V72 H20 Z"/><path d="M60 42 H80"/><path d="M60 52 H80"/><path d="M60 62 H74"/>',
    "funnel": '<path d="M10 16 H90 L60 52 V84 L40 74 V52 Z"/><path d="M24 30 H76"/>',
    "monogram": '<path d="M50 12 L84 84"/><path d="M50 12 L16 84"/><path d="M38 76 L62 62"/><circle cx="50" cy="12" r="4"/>',
    "compass": '<circle cx="50" cy="50" r="38"/><path d="M50 18 L58 50 L50 82 L42 50 Z"/><path d="M18 50 L50 44 L82 50 L50 56 Z"/><circle cx="50" cy="50" r="4"/>',
    "steps": '<path d="M12 86 H34 V66 H54 V46 H74 V26 H90"/><path d="M12 86 H90"/><path d="M74 26 L84 14"/><path d="M78 14 H84 V20"/>',
    "grid": '<rect x="14" y="14" width="20" height="20" transform="rotate(45 24 24)"/><rect x="40" y="14" width="20" height="20" transform="rotate(45 50 24)"/><rect x="66" y="14" width="20" height="20" transform="rotate(45 76 24)"/><rect x="14" y="40" width="20" height="20" transform="rotate(45 24 50)"/><rect x="40" y="40" width="20" height="20" transform="rotate(45 50 50)"/><rect x="66" y="40" width="20" height="20" transform="rotate(45 76 50)"/><rect x="14" y="66" width="20" height="20" transform="rotate(45 24 76)"/><rect x="40" y="66" width="20" height="20" transform="rotate(45 50 76)"/><rect x="66" y="66" width="20" height="20" transform="rotate(45 76 76)"/>',
    "envelope": '<rect x="12" y="24" width="76" height="52"/><path d="M12 24 L50 54 L88 24"/><path d="M12 76 L40 48"/><path d="M88 76 L60 48"/>',
    "question": '<circle cx="50" cy="50" r="38"/><path d="M38 38 A12 12 0 1 1 56 48 C51 51 50 54 50 60"/><circle cx="50" cy="72" r="2.5"/>',
    "venn": '<circle cx="38" cy="40" r="24"/><circle cx="62" cy="40" r="24"/><circle cx="50" cy="62" r="24"/>',
    "lens": '<circle cx="42" cy="42" r="26"/><path d="M61 61 L88 88"/><path d="M42 26 C43 36 47 40 57 42 C47 44 43 48 42 58 C41 48 37 44 27 42 C37 40 41 36 42 26 Z"/>',
    "document": '<path d="M22 10 H64 L80 26 V90 H22 Z"/><path d="M64 10 V26 H80"/><path d="M32 42 L40 50 L32 58"/><path d="M46 58 H62"/><path d="M32 72 H70"/>',
    "gauge": '<path d="M14 70 A38 38 0 1 1 86 70"/><path d="M50 62 L72 36"/><circle cx="50" cy="62" r="5"/><path d="M22 52 L28 54"/><path d="M50 26 V32"/><path d="M78 52 L72 54"/>',
}


def icon(name, cx, cy, size, color, width):
    s = size / 100.0
    return (
        f'<g transform="translate({fmt(cx - size / 2)} {fmt(cy - size / 2)}) scale({s:.4f})" fill="none" '
        f'stroke="{color}" stroke-width="{width / s:.3f}" stroke-linecap="round" stroke-linejoin="round">{ICONS[name]}</g>'
    )


# -------------------------------------------------------------------- plates


def font_css():
    return f"""
@font-face {{ font-family: 'Cormorant Garamond'; font-style: normal; font-weight: 500 700; src: url('file://{FONTS}/cormorant-garamond-normal-latin.woff2') format('woff2'); }}
@font-face {{ font-family: 'Cormorant Garamond'; font-style: italic; font-weight: 500 700; src: url('file://{FONTS}/cormorant-garamond-italic-latin.woff2') format('woff2'); }}
@font-face {{ font-family: 'Manrope'; font-style: normal; font-weight: 400 800; src: url('file://{FONTS}/manrope-normal-latin.woff2') format('woff2'); }}
html, body {{ margin: 0; padding: 0; background: transparent; }}
svg {{ display: block; }}
.num {{ font-family: 'Cormorant Garamond'; font-style: italic; font-weight: 500; }}
.cap {{ font-family: 'Manrope'; font-weight: 700; letter-spacing: .32em; text-transform: uppercase; }}
.serif {{ font-family: 'Cormorant Garamond'; font-weight: 600; }}
.sans {{ font-family: 'Manrope'; }}
"""


def dark_plate(w, h, seed, symbol, number=None, caption=None, roman=None):
    cx, cy = w / 2, h / 2 + (8 if caption else 0)
    rnd = random.Random(seed)
    lobes = rnd.choice([18, 22, 24, 28, 32])
    lobes2 = rnd.choice([12, 14, 16, 20])
    parts = [
        f'<defs><radialGradient id="bg" cx="50%" cy="46%" r="70%"><stop offset="0" stop-color="{EMERALD_700}"/>'
        f'<stop offset=".55" stop-color="{EMERALD_800}"/><stop offset="1" stop-color="{EMERALD_950}"/></radialGradient>'
        f'<radialGradient id="glow" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="{MINT}" stop-opacity=".22"/>'
        f'<stop offset="1" stop-color="{MINT}" stop-opacity="0"/></radialGradient>'
        f'<clipPath id="inner"><rect x="40" y="40" width="{w - 80}" height="{h - 80}"/></clipPath></defs>',
        f'<rect width="{w}" height="{h}" fill="url(#bg)"/>',
        f'<g clip-path="url(#inner)">{wave_field(w, h, seed, MINT, .10, spacing=13)}</g>',
        f'<circle cx="{cx}" cy="{cy}" r="{min(w, h) * 0.46}" fill="url(#glow)"/>',
        woven_band(cx, cy, min(w, h) * 0.40, 9, lobes, 7, MINT_300, .38, 0.7),
        woven_band(cx, cy, min(w, h) * 0.33, 6, lobes2, 5, WHITE, .26, 0.6),
        rosette(cx, cy, min(w, h) * 0.30, seed, MINT_300, .55, 0.65, layers=3),
        f'<circle cx="{cx}" cy="{cy}" r="{min(w, h) * 0.165}" fill="{EMERALD_900}" stroke="{MINT}" stroke-opacity=".85" stroke-width="1.6"/>',
        f'<circle cx="{cx}" cy="{cy}" r="{min(w, h) * 0.150}" fill="none" stroke="{MINT}" stroke-opacity=".45" stroke-width="1"/>',
        woven_band(cx, cy, min(w, h) * 0.185, 3, 36, 3, MINT, .55, 0.6),
        icon(symbol, cx, cy, min(w, h) * 0.17, WHITE, 3.2),
        frame(w, h, MINT),
    ]
    if number:
        parts.append(f'<text x="{cx}" y="104" text-anchor="middle" class="num" font-size="44" fill="{MINT}">N° {number}</text>')
        parts.append(f'<path d="M{cx - 120} 122 H{cx - 40} M{cx + 40} 122 H{cx + 120}" stroke="{MINT}" stroke-opacity=".6"/>')
    elif roman:
        parts.append(f'<text x="{cx}" y="104" text-anchor="middle" class="num" font-size="44" fill="{MINT}">{roman}</text>')
    if caption:
        parts.append(f'<text x="{cx}" y="{h - 82}" text-anchor="middle" class="cap" font-size="22" fill="{WHITE}" fill-opacity=".92">{caption}</text>')
    return parts


def light_plate(w, h, seed, symbol, label=None):
    rnd = random.Random(seed)
    cx, cy = w * 0.70, h * 0.52
    ex, ey = w * 0.70, h * 0.52
    parts = [
        f'<defs><linearGradient id="lbg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{WHITE}"/>'
        f'<stop offset="1" stop-color="#E4F3EA"/></linearGradient>'
        f'<clipPath id="inner"><rect x="40" y="40" width="{w - 80}" height="{h - 80}"/></clipPath></defs>',
        f'<rect width="{w}" height="{h}" fill="url(#lbg)"/>',
        f'<g clip-path="url(#inner)">{wave_field(w, h, seed + 7, GREEN_500, .13, spacing=12)}</g>',
        f'<g clip-path="url(#inner)">',
        woven_band(cx, cy, h * 0.52, 10, rnd.choice([20, 24, 30]), 8, GREEN_500, .30, 0.8),
        woven_band(cx, cy, h * 0.43, 7, rnd.choice([14, 18]), 6, GREEN_600, .26, 0.7),
        rosette(cx, cy, h * 0.38, seed, GREEN_500, .55, 0.75, layers=3),
        '</g>',
        f'<circle cx="{ex}" cy="{ey}" r="{h * 0.17}" fill="{EMERALD_800}" stroke="{GREEN_500}" stroke-width="2"/>',
        f'<circle cx="{ex}" cy="{ey}" r="{h * 0.155}" fill="none" stroke="{MINT}" stroke-opacity=".55" stroke-width="1.2"/>',
        icon(symbol, ex, ey, h * 0.17, WHITE, 3.2),
        frame(w, h, GREEN_500),
    ]
    # Left engraved emblem: a small rosette with the AVENZA monogram.
    lx, ly = w * 0.20, h * 0.50
    parts.append(woven_band(lx, ly, h * 0.20, 4, 26, 5, GREEN_500, .40, 0.7))
    parts.append(rosette(lx, ly, h * 0.15, seed + 3, GREEN_600, .55, 0.7, layers=2))
    parts.append(f'<circle cx="{lx}" cy="{ly}" r="{h * 0.07}" fill="{WHITE}" stroke="{GREEN_500}" stroke-width="1.4"/>')
    parts.append(icon("monogram", lx, ly, h * 0.08, GREEN_600, 3))
    if label:
        parts.append(f'<text x="{lx}" y="{h * 0.84}" text-anchor="middle" class="cap" font-size="20" fill="{GREEN_600}">{label}</text>')
    return parts


def svg_doc(w, h, parts):
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}">'
        + "\n".join(parts)
        + "</svg>"
    )


def html_doc(svg):
    return f"<!doctype html><html><head><meta charset='utf-8'><style>{font_css()}</style></head><body>{svg}</body></html>"


# ------------------------------------------------------------------ diagrams


def diagram_stack(w=1200, h=1180):
    layers = [
        ("V", "AI visibility", ["Named and cited in ChatGPT, Gemini,", "Perplexity, Copilot and AI Overviews"]),
        ("IV", "Answer formatting", ["Direct answers, tables and FAQs", "that a model can quote cleanly"]),
        ("III", "Authority and mentions", ["Links, reviews, press and the same", "facts about you across the web"]),
        ("II", "Content and entities", ["Pages that cover a topic fully, with", "clear people, products and places"]),
        ("I", "Technical foundation", ["Crawlable, indexable, fast pages", "with structured data and no blockers"]),
    ]
    parts = [
        f'<rect width="{w}" height="{h}" fill="{WHITE}"/>',
        f'<g opacity=".9">{wave_field(w, h, 11, GREEN_500, .06, spacing=16)}</g>',
        f'<text x="60" y="96" class="serif" font-size="60" fill="{EMERALD_800}">The AVENZA visibility stack</text>',
        f'<text x="60" y="146" class="sans" font-size="27" fill="#33463C">Each layer rests on the one beneath it. AI answers sit at the top.</text>',
    ]
    top = 196
    lh = 176
    for i, (num, title, text) in enumerate(layers):
        y = top + i * (lh + 18)
        inset = (len(layers) - 1 - i) * 34
        x0, x1 = 60 + inset, w - 60 - inset
        fill = [EMERALD_800, EMERALD_700, GREEN_600, GREEN_500, "#2E8A60"][i]
        parts.append(f'<rect x="{x0}" y="{y}" width="{x1 - x0}" height="{lh}" fill="{fill}"/>')
        parts.append(f'<rect x="{x0 + 8}" y="{y + 8}" width="{x1 - x0 - 16}" height="{lh - 16}" fill="none" stroke="{MINT}" stroke-opacity=".45"/>')
        parts.append(f'<text x="{x0 + 40}" y="{y + 106}" class="num" font-size="60" fill="{MINT}">{num}</text>')
        parts.append(f'<text x="{x0 + 150}" y="{y + 66}" class="serif" font-size="44" fill="{WHITE}">{title}</text>')
        for j, line in enumerate(text):
            parts.append(f'<text x="{x0 + 150}" y="{y + 110 + j * 34}" class="sans" font-size="26" fill="#E8F3EC">{line}</text>')
    return parts


def diagram_flow(w=1200, h=1320):
    steps = [
        ("I", "Question", ["A person asks an assistant, or types a", "long and specific query into search."]),
        ("II", "Retrieval", ["The system searches an index for pages", "and passages that match the intent."]),
        ("III", "Selection", ["It keeps sources that are clear, current,", "specific and from trusted sites."]),
        ("IV", "Synthesis", ["A language model writes one answer", "from the passages it kept."]),
        ("V", "Citation", ["Sources whose facts were used are", "named and linked in the answer."]),
    ]
    parts = [
        f'<rect width="{w}" height="{h}" fill="{WHITE}"/>',
        f'<g opacity=".9">{wave_field(w, h, 19, GREEN_500, .06, spacing=16)}</g>',
        f'<text x="60" y="96" class="serif" font-size="60" fill="{EMERALD_800}">From question to citation</text>',
        f'<text x="60" y="146" class="sans" font-size="27" fill="#33463C">How AI search assistants generally build an answer.</text>',
    ]
    top = 200
    rh = 168
    gap = 32
    lx = 130
    parts.append(f'<path d="M{lx} {top + 60} V{top + 4 * (rh + gap) + 60}" stroke="{GREEN_500}" stroke-width="2.5" stroke-dasharray="2 10" stroke-linecap="round"/>')
    for i, (num, title, text) in enumerate(steps):
        y = top + i * (rh + gap)
        dark = i in (0, 4)
        parts.append(f'<circle cx="{lx}" cy="{y + 60}" r="56" fill="{EMERALD_800 if dark else WHITE}" stroke="{GREEN_500}" stroke-width="2.5"/>')
        parts.append(f'<circle cx="{lx}" cy="{y + 60}" r="47" fill="none" stroke="{MINT if dark else GREEN_500}" stroke-opacity=".5"/>')
        parts.append(f'<text x="{lx}" y="{y + 78}" text-anchor="middle" class="num" font-size="48" fill="{MINT if dark else GREEN_500}">{num}</text>')
        bx = 230
        parts.append(f'<rect x="{bx}" y="{y}" width="{w - bx - 60}" height="{rh - 30}" fill="{EMERALD_800 if dark else WHITE}" stroke="{EMERALD_800 if dark else "#B9D3C3"}" stroke-width="1.5"/>')
        tcol = WHITE if dark else EMERALD_800
        bcol = "#E8F3EC" if dark else "#33463C"
        parts.append(f'<text x="{bx + 34}" y="{y + 54}" class="serif" font-size="42" fill="{tcol}">{title}</text>')
        for j, line in enumerate(text):
            parts.append(f'<text x="{bx + 34}" y="{y + 92 + j * 32}" class="sans" font-size="25" fill="{bcol}">{line}</text>')
    parts.append(f'<text x="60" y="{h - 50}" class="sans" font-size="22" fill="#56695E">Simplified from Google Search Central and OpenAI documentation, and Aggarwal et al., KDD 2024.</text>')
    return parts


# ------------------------------------------------------------------- brand


MARK = (
    '<defs>'
    '<linearGradient id="avzA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFA412"/><stop offset="1" stop-color="#F26A2E"/></linearGradient>'
    '<linearGradient id="avzL" x1="100" y1="22" x2="30" y2="150" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#FFA412"/><stop offset=".45" stop-color="#F0703A"/><stop offset=".72" stop-color="#A8297F"/><stop offset="1" stop-color="#F0602F"/></linearGradient>'
    '<linearGradient id="avzP" x1="74" y1="140" x2="128" y2="112" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6A1FC9"/><stop offset="1" stop-color="#DE4B5A"/></linearGradient>'
    '</defs>'
    '<path d="M100 24 L168 140" stroke="url(#avzA)" stroke-width="30" stroke-linecap="round" fill="none"/>'
    '<path d="M100 24 L32 140" stroke="url(#avzL)" stroke-width="30" stroke-linecap="round" fill="none"/>'
    '<path d="M80 139 L122 115" stroke="url(#avzP)" stroke-width="27" stroke-linecap="round" fill="none"/>'
)


def brand_logo(w=600, h=600):
    return [
        f'<rect width="{w}" height="{h}" fill="{WHITE}"/>',
        f'<g transform="translate({w / 2 - 170} 70) scale(1.7)">{MARK}</g>',
        f'<text x="{w / 2}" y="470" text-anchor="middle" class="sans" font-weight="800" font-size="104" letter-spacing="10" fill="#1A2245">AVENZA</text>',
    ]


def brand_share(w=1200, h=630):
    cx, cy = w * 0.80, h * 0.5
    return [
        f'<defs><radialGradient id="bg" cx="80%" cy="0%" r="90%"><stop offset="0" stop-color="{EMERALD_700}"/><stop offset="1" stop-color="{EMERALD_900}"/></radialGradient></defs>',
        f'<rect width="{w}" height="{h}" fill="url(#bg)"/>',
        wave_field(w, h, 5, MINT, .08, spacing=14),
        woven_band(cx, cy, 250, 8, 28, 7, MINT_300, .30, 0.7),
        rosette(cx, cy, 200, 77, MINT_300, .45, 0.6, layers=3),
        f'<circle cx="{cx}" cy="{cy}" r="96" fill="{EMERALD_900}" stroke="{MINT}" stroke-opacity=".8" stroke-width="1.6"/>',
        f'<g transform="translate({cx - 62} {cy - 58}) scale(0.62)">{MARK}</g>',
        frame(w, h, MINT, inset=22, gap=10),
        f'<text x="90" y="235" class="sans" font-weight="800" font-size="40" letter-spacing="8" fill="{WHITE}">AVENZA</text>',
        f'<text x="88" y="340" class="serif" font-size="78" fill="{WHITE}">Be the answer,</text>',
        f'<text x="88" y="425" class="num" font-size="78" fill="{MINT}">not just a result.</text>',
        f'<text x="90" y="500" class="sans" font-size="24" fill="#DCEBE2">GEO, AEO, SEO and growth marketing</text>',
    ]


def favicon(w=512, h=512):
    return [
        f'<rect width="{w}" height="{h}" fill="{EMERALD_800}"/>',
        f'<rect x="18" y="18" width="{w - 36}" height="{h - 36}" fill="none" stroke="{MINT}" stroke-opacity=".5" stroke-width="4"/>',
        f'<g transform="translate(56 92) scale(2)">{MARK}</g>',
    ]


# --------------------------------------------------------------------- jobs


SERVICES = [
    ("01", "geo", "spark", "Generative Engine Optimization"),
    ("02", "aeo", "answer", "Answer Engine Optimization"),
    ("03", "seo", "search", "Search Engine Optimization"),
    ("04", "technical-seo", "gear", "Technical SEO"),
    ("05", "local-seo", "pin", "Local SEO"),
    ("06", "ecommerce-seo", "bag", "E-commerce SEO"),
    ("07", "content", "quill", "Content Marketing"),
    ("08", "digital-pr", "chain", "Link Building and Digital PR"),
    ("09", "google-ads", "target", "Google Ads"),
    ("10", "social", "share", "Social Media Marketing"),
    ("11", "web-design", "browser", "Website Design"),
    ("12", "cro", "funnel", "Conversion Optimization"),
]

PAGES = [
    ("home", "monogram", "I"),
    ("about", "compass", "II"),
    ("approach", "steps", "III"),
    ("services", "grid", "IV"),
    ("contact", "envelope", "V"),
    ("faq", "question", "VI"),
]

COVERS = [
    ("what-is-generative-engine-optimization", "spark", "GEO"),
    ("seo-vs-aeo-vs-geo", "venn", "SEO / AEO / GEO"),
    ("how-google-ai-overviews-choose-sources", "lens", "AI Overviews"),
    ("llms-txt-robots-txt-ai-crawlers", "document", "AI crawlers"),
    ("local-seo-for-indian-businesses", "pin", "Local SEO"),
    ("core-web-vitals-explained", "gauge", "Core Web Vitals"),
]


def caption_case(text):
    return text.upper()


def main():
    BUILD.mkdir(parents=True, exist_ok=True)
    jobs = []

    def add(name, w, h, parts, fmt="webp", quality=84):
        (BUILD / f"{name}.html").write_text(html_doc(svg_doc(w, h, parts)), encoding="utf-8")
        jobs.append({"name": name, "width": w, "height": h, "format": fmt, "quality": quality})

    for number, slug, symbol, title in SERVICES:
        add(f"plate-{number}-{slug}", 1200, 900, dark_plate(1200, 900, int(number) * 101, symbol, number=number, caption=caption_case(title)))

    for slug, symbol, roman in PAGES:
        add(f"hero-{slug}", 1200, 1000, dark_plate(1200, 1000, 900 + len(slug) * 37, symbol, roman=roman))

    for slug, symbol, label in COVERS:
        add(f"cover-{slug}", 1600, 900, light_plate(1600, 900, 300 + len(slug) * 13, symbol, label=caption_case(label)))

    add("diagram-visibility-stack", 1200, 1180, diagram_stack())
    add("diagram-question-to-citation", 1200, 1320, diagram_flow())

    add("brand-avenza-logo", 600, 600, brand_logo(), fmt="png")
    add("brand-avenza-share", 1200, 630, brand_share(), fmt="jpg", quality=86)
    add("brand-favicon", 512, 512, favicon(), fmt="png")

    (BUILD / "jobs.json").write_text(json.dumps(jobs, indent=1), encoding="utf-8")
    print(f"Wrote {len(jobs)} artwork pages to {BUILD}")


if __name__ == "__main__":
    main()
