#!/usr/bin/env python3
"""Convert rendered PNG artwork into the theme's web formats."""

import json
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
BUILD = ROOT / "build" / "art"
ART = ROOT / "avenza" / "assets" / "img" / "art"
BRAND = ROOT / "avenza" / "assets" / "img" / "brand"


def main():
    ART.mkdir(parents=True, exist_ok=True)
    BRAND.mkdir(parents=True, exist_ok=True)
    jobs = json.loads((BUILD / "jobs.json").read_text())
    for job in jobs:
        src = BUILD / "png" / f"{job['name']}.png"
        if not src.exists():
            continue
        im = Image.open(src).convert("RGB")
        if job["name"].startswith("brand-"):
            name = job["name"][len("brand-"):]
            if job["format"] == "png":
                if name == "favicon":
                    im = im.resize((192, 192), Image.LANCZOS)
                im.save(BRAND / f"{name}.png", optimize=True)
            else:
                im.save(BRAND / f"{name}.jpg", quality=job["quality"], optimize=True, progressive=True)
            continue
        im.save(ART / f"{job['name']}.webp", "WEBP", quality=job["quality"], method=6)
    total = sum(p.stat().st_size for p in ART.glob("*.webp"))
    print(f"art: {len(list(ART.glob('*.webp')))} files, {total / 1024:.0f} KB")


if __name__ == "__main__":
    main()
