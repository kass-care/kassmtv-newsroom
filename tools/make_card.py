#!/usr/bin/env python3
"""Make a KASS-mtv news picture (1280x720 JPG) for a story.

Usage:
  python3 tools/make_card.py --title "Headline" --category Politics \
      --date 2026-11-20 --out images/2026-11-20-story.jpg

The picture uses only KASS-mtv's own design (colours, logo mark, headline
text and the Kenya flag stripe). It never uses photos from other websites.
Fonts are in tools/fonts (SIL Open Font License).
"""

import argparse
import datetime as dt
import os

import numpy as np
from PIL import Image, ImageDraw, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
FONTS = os.path.join(HERE, "fonts")

W, H = 1280, 720
SAFE = 96  # keep text inside the 16:10 card crop used on the homepage

NAVY = (7, 11, 31)
NAVY_2 = (19, 32, 79)
WHITE = (255, 255, 255)
SOFT = (195, 203, 230)
RED = (224, 38, 43)

CATEGORY_COLOURS = {
    "politics": ((225, 29, 72), WHITE),
    "diaspora": ((34, 211, 238), (4, 18, 26)),
    "business": ((245, 158, 11), (26, 16, 0)),
    "development": ((16, 185, 129), (2, 24, 16)),
    "sports": ((99, 102, 241), WHITE),
    "technology": ((168, 85, 247), WHITE),
}


def font(name, size):
    return ImageFont.truetype(os.path.join(FONTS, name), size)


def logo_mark(draw, x, y, scale, colour, width):
    """The rounded play-triangle from the KASS-mtv logo."""
    pts = [(185, 22.3), (152, 8), (141, 3.3), (140.6, 15), (138.3, 63),
           (137.8, 73.8), (148, 68.6), (197, 43.6), (206, 39), (199, 31.3)]
    # Smooth it a little by sampling quadratic curves like the SVG path.
    def quad(p0, p1, p2, n=10):
        return [((1 - t) ** 2 * p0[0] + 2 * (1 - t) * t * p1[0] + t * t * p2[0],
                 (1 - t) ** 2 * p0[1] + 2 * (1 - t) * t * p1[1] + t * t * p2[1])
                for t in [i / n for i in range(n + 1)]]
    path = [pts[0], pts[1]]
    path += quad(pts[1], pts[2], pts[3])[1:]
    path += [pts[4]]
    path += quad(pts[4], pts[5], pts[6])[1:]
    path += [pts[7]]
    path += quad(pts[7], pts[8], pts[9])[1:]
    path = [(x + (px - 136) * scale, y + (py - 4) * scale) for px, py in path]
    draw.line(path, fill=colour, width=width, joint="curve")
    r = width / 2
    for px, py in (path[0], path[-1]):
        draw.ellipse([px - r, py - r, px + r, py + r], fill=colour)


def wrap(draw, text, fnt, max_w):
    words, lines, line = text.split(), [], ""
    for w in words:
        test = (line + " " + w).strip()
        if draw.textlength(test, font=fnt) <= max_w:
            line = test
        else:
            if line:
                lines.append(line)
            line = w
    if line:
        lines.append(line)
    return lines


def fit_headline(draw, text, max_w, max_h):
    for size in range(72, 37, -2):
        fnt = font("ArchivoBlack-Regular.ttf", size)
        lines = wrap(draw, text, fnt, max_w)
        lh = int(size * 1.12)
        if len(lines) <= 4 and lh * len(lines) <= max_h:
            return fnt, lines, lh
    fnt = font("ArchivoBlack-Regular.ttf", 38)
    lines = wrap(draw, text, fnt, max_w)[:4]
    lines[-1] = lines[-1].rstrip(".,;:") + "…"
    return fnt, lines, int(38 * 1.12)


def make(title, category, date, out):
    img = Image.new("RGB", (W, H), NAVY)

    # Background: soft glow from the bottom-left corner, navy to blue.
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    d = np.sqrt(((xx - 0.15 * W) / W) ** 2 + ((yy - 1.05 * H) / H) ** 2)
    t = np.clip(1.0 - d / 1.05, 0.0, 1.0) ** 1.6
    mask = Image.fromarray((t * 230).astype(np.uint8), "L")
    img = Image.composite(Image.new("RGB", (W, H), NAVY_2), img, mask)

    # Big faint logo mark on the right for depth (drawn on its own layer
    # so overlapping line pieces do not show as stripes).
    layer = Image.new("L", (W, H), 0)
    logo_mark(ImageDraw.Draw(layer), 830, 110, 6.4, 255, 34)
    img = Image.composite(Image.new("RGB", (W, H), (60, 78, 150)), img, layer.point(lambda v: v * 22 // 255))
    draw = ImageDraw.Draw(img, "RGBA")

    # Logo: mark + KASS-mtv + DIASPORA NEWS.
    logo_mark(draw, SAFE, 64, 0.86, RED, 6)
    draw.text((SAFE + 72, 58), "KASS-mtv", font=font("Inter-ExtraBold.otf", 40), fill=WHITE)
    draw.text((SAFE + 74, 104), "DIASPORA NEWS", font=font("Inter-Bold.otf", 16), fill=SOFT)

    # Category pill.
    bg, fg = CATEGORY_COLOURS.get(category.lower(), ((225, 29, 72), WHITE))
    cat_font = font("Inter-ExtraBold.otf", 22)
    label = category.upper()
    tw = draw.textlength(label, font=cat_font)
    top = 196
    draw.rounded_rectangle([SAFE, top, SAFE + tw + 36, top + 44], radius=22, fill=bg)
    draw.text((SAFE + 18, top + 8), label, font=cat_font, fill=fg)

    # Headline.
    max_w = W - SAFE * 2
    fnt, lines, lh = fit_headline(draw, title, max_w, 330)
    y = top + 74
    for line in lines:
        draw.text((SAFE, y), line, font=fnt, fill=WHITE)
        y += lh

    # Footer: date and website.
    small = font("Inter-SemiBold.otf", 22)
    try:
        when = dt.date.fromisoformat(date[:10]).strftime("%-d %B %Y").upper()
    except ValueError:
        when = date.upper()
    draw.text((SAFE, H - 92), when, font=small, fill=SOFT)
    site = "kassmtvusa.com"
    draw.text((W - SAFE - draw.textlength(site, font=small), H - 92), site, font=small, fill=SOFT)

    # Kenya flag stripe along the bottom.
    y0 = H - 30
    for colour, h in ((( 0, 0, 0), 8), (WHITE, 3), ((187, 0, 0), 8), (WHITE, 3), ((0, 102, 0), 8)):
        draw.rectangle([0, y0, W, y0 + h], fill=colour)
        y0 += h

    os.makedirs(os.path.dirname(os.path.abspath(out)), exist_ok=True)
    img.save(out, "JPEG", quality=88, optimize=True, progressive=True)
    return out


if __name__ == "__main__":
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--title", required=True)
    ap.add_argument("--category", default="News")
    ap.add_argument("--date", default=dt.date.today().isoformat())
    ap.add_argument("--out", required=True)
    a = ap.parse_args()
    print(make(a.title, a.category, a.date, a.out))
