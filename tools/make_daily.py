#!/usr/bin/env python3
"""Make the daily "Today on KASS-mtv" picture for the WhatsApp channel.

Usage:
  python3 tools/make_daily.py                 # today's stories (UTC date), else the newest 4
  python3 tools/make_daily.py --date 2026-11-22 --out images/daily/2026-11-22.png

It reads feed.json and draws up to 4 headlines with their sections on one
1080 x 1350 picture (good for WhatsApp channels, Status, Facebook and
Instagram). KASS-mtv's own design only: no photos from other websites.
"""

import argparse
import datetime as dt
import json
import os
import sys

import numpy as np
from PIL import Image, ImageDraw

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, HERE)

from make_card import CATEGORY_COLOURS, NAVY, NAVY_2, RED, SOFT, WHITE, font, logo_mark, wrap  # noqa: E402

W, H = 1080, 1350
PAD = 80
ICONS = {"politics": "POLITICS", "diaspora": "DIASPORA", "news": "WORLD", "sports": "SPORTS"}


def pick(stories, day):
    todays = [s for s in stories if str(s.get("id", "")).startswith(day)]
    chosen = todays if todays else stories
    order = ["politics", "diaspora", "news", "sports"]
    chosen = sorted(chosen[:4], key=lambda s: order.index(s.get("category", "").lower()) if s.get("category", "").lower() in order else 9)
    return chosen[:4]


def make(stories, day, out):
    img = Image.new("RGB", (W, H), NAVY)
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    d = np.sqrt(((xx - 0.5 * W) / W) ** 2 + ((yy - 0.12 * H) / H) ** 2)
    t = np.clip(1.0 - d / 1.0, 0.0, 1.0) ** 1.5
    img = Image.composite(Image.new("RGB", (W, H), NAVY_2), img, Image.fromarray((t * 255).astype(np.uint8), "L"))
    draw = ImageDraw.Draw(img, "RGBA")

    # Logo
    logo_mark(draw, PAD, 70, 1.0, RED, 8)
    draw.text((PAD + 84, 62), "KASS-mtv", font=font("Inter-ExtraBold.otf", 50), fill=WHITE)
    draw.text((PAD + 86, 118), "DIASPORA NEWS", font=font("Inter-Bold.otf", 19), fill=SOFT)

    # Title and date
    try:
        when = dt.date.fromisoformat(day).strftime("%A %-d %B %Y")
    except ValueError:
        when = day
    draw.text((PAD, 176), "TODAY'S TOP STORIES", font=font("ArchivoBlack-Regular.ttf", 58), fill=WHITE)
    draw.text((PAD, 250), when, font=font("Inter-SemiBold.otf", 30), fill=SOFT)

    # Stories
    y = 316
    box_h = 200
    gap = 16
    head = font("Inter-ExtraBold.otf", 32)
    for n, s in enumerate(stories):
        top = y + n * (box_h + gap)
        draw.rounded_rectangle([PAD, top, W - PAD, top + box_h], radius=26, fill=(255, 255, 255, 18), outline=(255, 255, 255, 40), width=2)
        cat = s.get("category", "News").lower()
        bg, fg = {"news": ((16, 185, 129), (2, 24, 16))}.get(cat) or CATEGORY_COLOURS.get(cat, ((225, 29, 72), WHITE))
        label = ICONS.get(cat, cat.upper())
        cf = font("Inter-ExtraBold.otf", 20)
        tw = draw.textlength(label, font=cf)
        draw.rounded_rectangle([PAD + 28, top + 22, PAD + 28 + tw + 28, top + 56], radius=17, fill=bg)
        draw.text((PAD + 42, top + 27), label, font=cf, fill=fg)
        draw.text((W - PAD - 70, top + 14), str(n + 1), font=font("ArchivoBlack-Regular.ttf", 46), fill=(255, 255, 255, 70))
        title = s.get("title", "")
        for prefix in ("World roundup: ", "Sports roundup: "):
            if title.startswith(prefix):
                title = title[len(prefix):]
        lines = wrap(draw, title, head, W - PAD * 2 - 56)
        if len(lines) > 3:
            lines = lines[:3]
            lines[-1] = lines[-1].rstrip(".,;:") + "…"
        ty = top + 70
        for line in lines:
            draw.text((PAD + 28, ty), line, font=head, fill=WHITE)
            ty += 39

    # Call to action
    cta_top = H - 168
    draw.rounded_rectangle([PAD, cta_top, W - PAD, cta_top + 72], radius=36, fill=(37, 211, 102))
    cta = "Tap the links below to read  ↓"
    cf = font("Inter-ExtraBold.otf", 34)
    draw.text(((W - draw.textlength(cta, font=cf)) / 2, cta_top + 16), cta, font=cf, fill=(4, 40, 31))
    site = "kassmtvusa.com"
    sf = font("Inter-SemiBold.otf", 24)
    draw.text(((W - draw.textlength(site, font=sf)) / 2, H - 76), site, font=sf, fill=SOFT)

    # Kenya flag stripe
    y0 = H - 30
    for colour, h in (((0, 0, 0), 8), (WHITE, 3), ((187, 0, 0), 8), (WHITE, 3), ((0, 102, 0), 8)):
        draw.rectangle([0, y0, W, y0 + h], fill=colour)
        y0 += h

    os.makedirs(os.path.dirname(os.path.abspath(out)), exist_ok=True)
    img.save(out, "PNG", optimize=True)
    return out


if __name__ == "__main__":
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--date", default=dt.datetime.now(dt.timezone.utc).date().isoformat())
    ap.add_argument("--out", default="")
    a = ap.parse_args()
    with open(os.path.join(ROOT, "feed.json"), encoding="utf-8") as f:
        feed = json.load(f)
    chosen = pick(feed.get("stories", []), a.date)
    if not chosen:
        sys.exit("STOP: no stories in feed.json")
    out = a.out or os.path.join(ROOT, "images", "daily", a.date + ".png")
    print(make(chosen, a.date, out))
