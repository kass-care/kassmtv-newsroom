#!/usr/bin/env python3
"""Add one story to the KASS-mtv newsroom box.

Usage:
  python3 tools/add_story.py stories/2026-11-21-politics.json

What it does:
  1. Checks the story file (headline, category, key points, story, sources).
  2. Makes the KASS-mtv news picture in images/<id>.jpg.
  3. Puts the story at the top of feed.json (keeps the newest 40).

The website (theme kassmtv 1.3.0+) reads feed.json every hour and
publishes new stories by itself.

Story file fields:
  title        Headline (required)
  category     Politics, Diaspora, Business, Development, Sports, Technology
  key_points   List of 2-5 short lines (required)
  content      Story in simple HTML: <p>, <h2>, <ul>/<ol>/<li>, <strong>, <em>, <a>
  sources      List of {"outlet", "title", "url", "date"} (at least 1, required)
  image_alt    Optional. Words that describe the picture.
  tags         Optional. List of short tags.
  breaking     Optional. true only for real breaking news (shows in the red strip).
  id           Optional. Made from the date and headline if missing.
  date         Optional. ISO time. Now (UTC) if missing.
"""

import datetime as dt
import html
import json
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, HERE)

from make_card import make  # noqa: E402

ALLOWED_TAGS = {"p", "h2", "h3", "ul", "ol", "li", "strong", "em", "b", "i", "a", "blockquote", "br"}
CATEGORIES = {"politics", "diaspora", "business", "development", "sports", "technology", "news"}


def fail(msg):
    sys.exit("STOP: " + msg)


def slugify(text, words=8):
    text = re.sub(r"[^a-z0-9\s-]", "", text.lower())
    return "-".join(text.split()[:words])


def check(story):
    title = (story.get("title") or "").strip()
    if not 20 <= len(title) <= 140:
        fail("title must be 20-140 characters")
    cat = (story.get("category") or "").strip()
    if cat.lower() not in CATEGORIES:
        fail("category must be one of: " + ", ".join(sorted(CATEGORIES)))
    story["category"] = cat[:1].upper() + cat[1:].lower()
    pts = [p.strip() for p in story.get("key_points") or [] if p and p.strip()]
    if not 2 <= len(pts) <= 5:
        fail("key_points must have 2-5 lines")
    story["key_points"] = pts

    body = story.get("content") or ""
    for tag in re.findall(r"</?\s*([a-zA-Z0-9]+)", body):
        if tag.lower() not in ALLOWED_TAGS:
            fail("content uses a tag that is not allowed: <%s>" % tag)
    text = html.unescape(re.sub(r"<[^>]+>", " ", body))
    words = len(text.split())
    if not 200 <= words <= 900:
        fail("story should be 200-900 words (it has %d)" % words)

    srcs = story.get("sources") or []
    good = [s for s in srcs if isinstance(s, dict) and str(s.get("url", "")).startswith(("https://", "http://")) and s.get("title")]
    if not good:
        fail("at least one source with a title and a link is needed")
    story["sources"] = good
    return story


def main(path):
    with open(path, encoding="utf-8") as f:
        story = json.load(f)
    story = check(story)

    now = dt.datetime.now(dt.timezone.utc).replace(microsecond=0)
    story.setdefault("date", now.isoformat().replace("+00:00", "Z"))
    day = story["date"][:10]
    story["id"] = story.get("id") or day + "-" + slugify(story["title"])
    story["id"] = re.sub(r"[^a-z0-9-]", "", story["id"].lower())[:90]

    image_rel = "images/%s.jpg" % story["id"]
    make(story["title"], story["category"], day, os.path.join(ROOT, image_rel))
    story["image"] = image_rel
    story.setdefault("image_alt", "KASS-mtv news graphic: " + story["title"])

    feed_path = os.path.join(ROOT, "feed.json")
    feed = {"version": 1, "stories": []}
    if os.path.exists(feed_path):
        with open(feed_path, encoding="utf-8") as f:
            feed = json.load(f)
    stories = [s for s in feed.get("stories", []) if s.get("id") != story["id"]]
    stories.insert(0, story)
    stories.sort(key=lambda s: s.get("date", ""), reverse=True)
    feed["stories"] = stories[:40]
    feed["updated"] = now.isoformat().replace("+00:00", "Z")

    with open(feed_path, "w", encoding="utf-8") as f:
        json.dump(feed, f, ensure_ascii=False, indent=2)
        f.write("\n")
    print("Added: %s\nPicture: %s" % (story["id"], image_rel))


if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    main(sys.argv[1])
