---
name: toggl-timesheet
description: Use when the user asks to build a timesheet, log hours, create a Toggl import, or reconstruct work done between two dates. Derives work sessions from git commits and Claude conversation history, rounds to 15-minute intervals, and produces a Toggl-compatible CSV at ~/timesheet_<range>.csv.
---

# Toggl Timesheet Builder

Reconstruct billable hours between two dates from git history and Claude session files, then write a Toggl import CSV.

## Toggl account constants

| Field   | Value                        |
|---------|------------------------------|
| User    | Wotuu                        |
| Email   | toggl.com@clearbits.nl       |
| Client  | Ludicrous Speed LLC          |
| Project | Keystone.guru Maintenance    |
| Billable| No                           |
| Task    | (empty)                      |
| Tags    | (empty)                      |

## Step 1 — Collect git commits

Run **both** of the following (the second catches merge commits that carry PR/issue numbers):

```bash
git log --since="YYYY-MM-DD" --until="YYYY-MM-DD 23:59:59" --format="%H|%ai|%s" --no-merges
git log --since="YYYY-MM-DD" --until="YYYY-MM-DD 23:59:59" --format="%H|%ai|%s"
```

Note: `--until` with a bare date stops at midnight of that day, dropping all commits made on the end date. Always append ` 23:59:59`.

Commit timestamps are in local time (the `%ai` format includes the offset). Extract all issue numbers from commit subjects (`#NNNN`).

## Step 2 — Fetch GitHub issue titles

```bash
gh issue view NNNN --repo RaiderIO/keystone.guru --json title,number
```

Always pass `--repo RaiderIO/keystone.guru` explicitly — without it `gh` resolves to whatever the cwd repo is, which may differ. Batch all unique issue numbers in a single loop. Keep each full title for the Description column.

Sessions from a related repo reference **that repo's** issue numbers (e.g. an infra session citing `keystoneguru-infra#22`). Fetch those with `--repo RaiderIO/keystoneguru-infra` and prefix the description with the repo name so the two issue-number spaces don't collide.

## Step 3 — Extract Claude session timestamps

Find all `.jsonl` files (excluding `subagents/` subdirectories) modified within the date range across **all** project paths. Use `find -newermt` rather than parsing `ls` output — it is robust and needs no month/day string-matching:

```bash
find ~/.claude/projects/ -name "*.jsonl" -not -path "*/subagents/*" \
  -newermt "YYYY-MM-DD" ! -newermt "YYYY-MM-DD 23:59:59" \
  | xargs ls -la | sort -k6,7
```

The lower bound is the start date; the upper bound is the day **after** the end date (or the end date with ` 23:59:59`). This is an mtime pre-filter only — the authoritative session start/end comes from the JSONL content parsed below, so a file touched late but started earlier is still placed correctly.

`~/.claude/projects/` may contain **several repos** for the same client (e.g. the app repo and an `-infra-` CDK repo). Include them all in the scan; in Step 4, ask the user whether related repos should be folded under the same Toggl project or split out.

For each matching file, extract the first meaningful user message plus **every** event timestamp (not just first/last) using Python (jq is not installed). The full timestamp list is what Step 4 uses to measure active time:

```python
import json
from datetime import datetime

def extract_session(path):
    """Return (first_user_text, events) where events is a sorted list of
    datetimes for every message/tool event in the session."""
    first_user_text = None
    events = []
    with open(path) as f:
        for line in f:
            try:
                obj = json.loads(line)
            except json.JSONDecodeError:
                continue
            ts = obj.get('timestamp', '')
            if ts:
                events.append(datetime.fromisoformat(ts.replace('Z', '+00:00')))
            if obj.get('type') == 'user' and first_user_text is None:
                content = obj.get('message', {}).get('content', '')
                if isinstance(content, list):
                    text = next((c.get('text', '') for c in content
                                 if isinstance(c, dict) and c.get('type') == 'text'), '')
                else:
                    text = str(content)
                # Skip system caveat / slash-command-only messages
                if not text.strip().startswith(('<local-command', '<command-name', '/')):
                    first_user_text = text[:200]
    events.sort()
    return first_user_text, events
```

All JSONL timestamps are UTC (suffix `Z`). Determine the local UTC offset from `date +%z` and apply it before displaying times to the user.

Use the **first user message text** to identify which issue was being worked on. Do not deduplicate overlapping sessions from the *same* machine — parallel open conversations represent real simultaneous work.

## Step 4 — Split sessions into active-work segments

The wall-clock span of a session (`first → last`) is **not** time worked — it includes gaps where the user finished the task, walked away, sat in a meeting, or worked in another session. Logging the full span overstates hours; a flat cap (e.g. "2 h max") is a crude patch that mismeasures the real cause. Measure the **gaps** directly instead.

Rule: **a gap longer than `GAP` (~15 min) between consecutive events means the user stepped away.** Split the session at every such gap. Each resulting segment contains only sub-`GAP` intervals, so `end − start` of a segment *is* active time — no cap, no arbitrary discount. A continuous 3-hour session (events every few minutes) stays one honest 3-hour segment; a 15-minute task smeared across an afternoon becomes one 15-minute segment, not an hour.

```python
from datetime import timedelta

GAP = timedelta(minutes=15)  # tunable: longest pause still counted as continuous work

def segments(events, gap=GAP):
    """Split sorted event datetimes into continuous work segments.
    Returns a list of (start, end); end - start is active time."""
    if not events:
        return []
    segs = []
    seg_start = prev = events[0]
    for cur in events[1:]:
        if cur - prev > gap:
            segs.append((seg_start, prev))
            seg_start = cur
        prev = cur
    segs.append((seg_start, prev))
    return segs
```

Emit **one timesheet row per segment**, each with its true start/end (converted to local time). This also disposes of two former special cases for free:

- **Cross-day / resumed sessions** produce a huge gap, so they split into separate same-day segments automatically — no `???`, no manual cap.
- **The trailing idle gap** (task done at 10:15, user returns at 11:00) exceeds `GAP` and is simply never inside a segment, so it contributes nothing. Time spent in *another* session during that gap is captured by that session's own segments — nothing real is lost, nothing idle is invented.

Assign each segment the issue from its session's first user message, correlating with commits from Step 1. A segment with only one or two events and no substantive user text (a lone `ping`, keepalive, or cache-poke) is **not work** — drop it rather than letting it round up to 15 min.

Present as a markdown table for review:

```
| Start | End | Date | Issue |
|-------|-----|------|-------|
| 09:29 | 10:07 | 2026-06-20 | #3240 Increase PhpStan level (level 5 exploration) |
```

Use `???` only when an issue **number** cannot be determined; segment times always come from the data.

## Step 5 — Round to nearest 15 minutes

Round each start and end time independently to the nearest 15-minute mark:

- `:00–:07` → `:00`
- `:08–:22` → `:15`
- `:23–:37` → `:30`
- `:38–:52` → `:45`
- `:53–:59` → `:00` (next hour)

If rounding makes start == end, set end to start + 15 min.

## Step 6 — Write the Toggl CSV

Output file: `~/timesheet_<startdate>_<enddate>.csv`

Match the Toggl export format exactly — all fields quoted, columns in this order:

```
"User","Email","Client","Project","Task","Description","Billable","Start date","Start time","End date","End time","Duration","Tags"
"Wotuu","toggl.com@clearbits.nl","Ludicrous Speed LLC","Keystone.guru Maintenance","","#NNNN Issue title (optional note)","No","YYYY-MM-DD","HH:MM:SS","YYYY-MM-DD","HH:MM:SS","HH:MM:SS",""
```

- **End date** differs from Start date for sessions crossing midnight — compute it correctly.
- **Duration** = End − Start in `HH:MM:SS`. For cross-midnight entries (End time < Start time), add 24 hours to the end before subtracting — e.g. 23:45 → 00:00 is 15 min, not −23:45.
- **Description** = `#NNNN Full issue title (optional parenthetical note)`.

## Caveats to surface to the user

- **Existing Toggl entries**: if the user provides an existing export, scan it for entries on the same dates and flag overlaps before importing.
- **Other machine**: the user works across two machines, each with its own `~/.claude/`. This skill only sees the local one. Commit-only work (a commit with no matching local session) was likely done on the other machine — generate a CSV on **each** machine, then combine them with the companion `toggl-timesheet-merge` skill, which reconciles cross-machine overlaps. Do not invent `???` rows for the other machine's work; let its own run capture it.
- **Subagent background work**: a long-running background subagent emits tool-event timestamps, so the gap logic in Step 4 already keeps those minutes inside a segment. Do not additionally pad a session for a subagent that ran while the user was away — if the user sent no messages during that span, the gap split handles it.
- **Duplicate start times**: Toggl merges entries with identical start date+time. After rounding, two short back-to-back sessions can collide on the same slot. Prefer resolving it **in the CSV** — shift the later row to the next free 15-minute slot (adjusting its end/duration to match) — rather than leaving it for a manual post-import fix. Only fall back to "offset by 1 minute after import" when no clean slot is available.
