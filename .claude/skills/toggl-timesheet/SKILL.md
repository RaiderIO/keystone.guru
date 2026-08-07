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

Do **not** trust mtime as evidence of work. Claude Code periodically rewrites session files in bulk — a run of files all mtimed within the same second or two (e.g. five files at `00:01:32`–`00:01:34`) is housekeeping, not activity. Always derive start/end from the events *inside* the file; a date that looks busy by mtime and empty by content is genuinely empty.

For each matching file, extract the first meaningful user message, whether it is an unattended loop session, plus **every** event timestamp (not just first/last) using Python (jq is not installed). The full timestamp list is what Step 4 uses to measure active time:

```python
import json
from datetime import datetime

def extract_session(path):
    """Return (is_loop, first_user_text, events) where events is a sorted list
    of datetimes for every message/tool event in the session."""
    is_loop = False
    first_user_text = None
    events = []
    with open(path, errors='replace') as f:
        for line in f:
            # A /loop session re-fires itself on a timer with no human present.
            if '<command-name>/loop</command-name>' in line:
                is_loop = True
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
                if not text.strip().startswith(('<local-command', '<command-name',
                                                '<command-message', '/', '<system-reminder')):
                    first_user_text = text[:400]
    events.sort()
    return is_loop, first_user_text, events
```

All JSONL timestamps are UTC (suffix `Z`). Determine the local UTC offset from `date +%z` and apply it before displaying times to the user.

Use the **first user message text** to identify which issue was being worked on.

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

This disposes of two former special cases for free:

- **Cross-day / resumed sessions** produce a huge gap, so they split into separate same-day segments automatically — no `???`, no manual cap.
- **The trailing idle gap** (task done at 10:15, user returns at 11:00) exceeds `GAP` and is simply never inside a segment, so it contributes nothing.

Assign each segment the issue from its session's first user message, correlating with commits from Step 1. A segment with only one or two events and no substantive user text (a lone `ping`, keepalive, or cache-poke) is **not work** — drop it.

### Step 4a — Exclude unattended `/loop` sessions before anything else

**Classify sessions, not segments.** A session that contains `<command-name>/loop</command-name>` is a timer firing into an empty room. Exclude **every** segment of such a session; do not try to salvage "the part the user was watching" — you cannot distinguish it, and any interactive session running concurrently already captures the real time.

This is not a rounding nicety. A `/loop 15m /babysit-prs` re-fires *below* the 15-minute `GAP`, so the splitter can never break it: one such session produced a single unbroken **538-minute** segment. In the 2026-07-04→27 run, loop sessions accounted for **18.0 h** of pure phantom time.

After excluding them, **verify every day lands in a plausible 0–10 h band.** If a day still exceeds ~10 h, stop and find what else is emitting autonomous events before writing any CSV.

### Step 4b — One person cannot work in parallel: take the union

Overlapping interactive sessions are one person multiplexing between agents, not simultaneous work. Summing them is how you get impossible days.

> Measured on the 2026-07-04→27 range: **raw sum of all segments 116.3 h**, union 73.5 h, with **19.18 h on 2026-07-21 alone**. An earlier version of this skill said "do not deduplicate overlapping sessions — parallel open conversations represent real simultaneous work." That was written for a one-or-two-session world and is **wrong** at 5–8 concurrent agents. Take the union.

Build non-overlapping rows:

1. Cut the timeline at every point where the set of active segments changes.
2. Assign each slice to the segment that **started most recently** at or before it (the session the user most recently turned to); tie-break on event count.
3. Merge adjacent slices carrying the same issue.

### Step 4c — Absorb tiny blocks *before* rounding

Merge the slices into contiguous **work blocks** (union intervals). Blocks shorter than ~8 minutes are the single largest source of inflation: each becomes a full 15-minute Toggl row. In the reference run, **40 such blocks held 1.10 h of real time and would have been billed as 10.00 h.**

For each block under 8 minutes: absorb it into the nearest neighbouring block if the gap is ≤ 30 min (same sitting, longer pause); otherwise drop it as a trivial check-in. Applying this took the same data from 74.75 h billed down to 67.25 h against 66.20 h measured.

Present as a markdown table for review:

```
| Start | End | Date | Issue |
|-------|-----|------|-------|
| 09:29 | 10:07 | 2026-06-20 | #3240 Increase PhpStan level (level 5 exploration) |
```

Use `???` only when an issue **number** cannot be determined; times always come from the data.

## Step 5 — Round at the block level, then split the block by issue

Round each **work block** (not each row) to the nearest 15-minute mark:

- `:00–:07` → `:00`
- `:08–:22` → `:15`
- `:23–:37` → `:30`
- `:38–:52` → `:45`
- `:53–:59` → `:00` (next hour)

If rounding makes start == end, set end to start + 15 min.

Rounding per *row* instead of per *block* is what inflates a timesheet: with rapid session-switching you get dozens of sub-15-minute rows a day, each rounded up. Rounding the block is unbiased; only the block's outer edges move.

Then divide the block's whole 15-minute cells among the issues worked inside it, in proportion to their measured seconds, using **largest-remainder** allocation so the cells sum exactly to the block length. Order the issues by first appearance and lay them out back-to-back. If an issue with real time (≥ 5 min) rounds to zero cells, take one cell from the largest allocation.

**Verify the billed total sits within a few percent of the measured union.** A double-digit gap means rounding is being applied at the wrong granularity — go back, don't ship it.

Sequential layout means the minute-level ordering *within* a block is nominal rather than the true interleaving. Daily totals and per-issue splits stay accurate. Say so when handing over the CSV — it matters if the user diffs against calendar entries.

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

- **Report every exclusion, with hours.** Time you dropped on purpose is indistinguishable from time you lost to a bug unless you name it. State each bucket separately: unattended `/loop` sessions, non-project conversations, isolated tiny blocks. Give the raw sum and the union side by side so the user can see the size of the correction and overrule it.
- **Non-project sessions**: `~/.claude/projects/` holds whatever the user talked to Claude about, including personal conversations and throwaway `Test` sessions. Those are not billable project work — exclude them, and say which ones you excluded rather than deleting them silently.
- **Existing Toggl entries**: if the user provides an existing export, scan it for entries on the same dates and flag overlaps before importing.
- **Other machine**: the user worked across two machines until 2026-07-09, each with its own `~/.claude/`. This skill only sees the local one. Commit-only work (a commit with no matching local session) before that date was likely done on the other machine — generate a CSV on **each** machine, then combine them with the companion `toggl-timesheet-merge` skill, which reconciles cross-machine overlaps. Do not invent `???` rows for the other machine's work; let its own run capture it.
- **Days with commits but no sessions**: report them with the commit evidence and let the user decide — never fill them in. Before blaming the other machine, actually open the session files mtimed that day; they often contain events from a *different* day (a file mtimed 07-07 held only 07-05 events), which turns "probably elsewhere" into a fact. Post-consolidation, such days are usually GitHub-UI merges of already-built PRs.
- **Subagent background work**: a long-running background subagent emits tool-event timestamps, so the gap logic in Step 4 already keeps those minutes inside a segment. Do not additionally pad a session for a subagent that ran while the user was away — if the user sent no messages during that span, the gap split handles it.
- **Duplicate start times**: Toggl merges entries with identical start date+time. After rounding, two short back-to-back sessions can collide on the same slot. Prefer resolving it **in the CSV** — shift the later row to the next free 15-minute slot (adjusting its end/duration to match) — rather than leaving it for a manual post-import fix. Only fall back to "offset by 1 minute after import" when no clean slot is available.
