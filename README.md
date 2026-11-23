# KASS-mtv newsroom box

This folder feeds **kassmtvusa.com**. The website (theme `kassmtv` 1.3.0 or newer)
reads `feed.json` every hour. Each new story is published with its picture,
key points, category and sources, as set under **Posts → Newsroom autopilot**
in WordPress (ON / drafts only / OFF).

## Add a story

1. Write the story as a JSON file in `stories/` (see the fields in `tools/add_story.py`).
2. Run `python3 tools/add_story.py stories/<file>.json`.
   This checks the story, makes the KASS-mtv news picture in `images/` and puts
   the story at the top of `feed.json`.
3. Commit and push to `main`.

## Daily picture for the WhatsApp channel

`python3 tools/make_daily.py --out /tmp/daily.png` draws "Today's top stories"
(up to 4 headlines from today's stories in `feed.json`) on one 1080 × 1350 picture.
Post it in the WhatsApp channel with the story links as the caption.

## Rules

- Stories are original writing. No copied sentences; at most one short quote
  (under 15 words) per source. Every story lists its sources.
- Pictures are KASS-mtv's own news graphics made by `tools/make_card.py`.
  Never add photos copied from other websites.
- The website only imports stories from the last 3 days, imports each story
  once, and never brings back a story the editor deleted.

Fonts in `tools/fonts` are under the SIL Open Font License 1.1 (see the OFL files).
