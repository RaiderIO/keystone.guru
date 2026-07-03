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

## Step 3 — Extract Claude session timestamps

Find all `.jsonl` files (excluding `subagents/` subdirectories) modified within the date range across **all** project paths:

```bash
find ~/.claude/projects/ -name "*.jsonl" -not -path "*/subagents/*" \
  | xargs ls -la \
  | awk '{print $6, $7, $8, $9}' \
  | grep -E "Mon DD|..." \
  | sort
```

(Adjust the `grep` pattern to match the target month/day abbreviations shown by `ls -la`, e.g. `"Jun 2[0-5]"` for June 20–25.)

For each matching file, extract the first meaningful user message and the last any-type message timestamp using Python (jq is not installed):

```python
import json

def extract_session(path):
    first_user_text = None
    first_ts = None
    last_ts = None
    with open(path) as f:
        for line in f:
            try:
                obj = json.loads(line)
            except json.JSONDecodeError:
                continue
            ts = obj.get('timestamp', '')
            if ts:
                last_ts = ts
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
                    first_ts = ts
    return first_ts, first_user_text, last_ts
```

All JSONL timestamps are UTC (suffix `Z`). Determine the local UTC offset from `date +%z` and apply it before displaying times to the user.

Use the **first user message text** to identify which issue was being worked on. Use **first_ts / last_ts** as the session start/end. Do not deduplicate overlapping sessions — parallel open conversations represent real simultaneous work.

## Step 4 — Build the raw timesheet

Correlate sessions with commits to assign issue numbers. Produce one row per logical work session per issue. Use `???` for any start/end time that cannot be determined from the data.

Present as a markdown table:

```
| Start | End | Date | Issue |
|-------|-----|------|-------|
| 09:29 | 10:07 | 2026-06-20 | #3240 Increase PhpStan level (level 5 exploration) |
```

Ask the user to supply values for every `???` before proceeding to Step 5.

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
- **Other machine**: the user has a second machine whose sessions are not in `~/.claude/`. Rows backed only by a commit (no matching local session) likely originated there — mark those start times `???`.
- **Subagent background work**: report long-running sessions conservatively — use the user's first/last active message time, not the wall-clock span of background subagents.
- **Duplicate start times**: Toggl merges entries with identical start date+time — tell the user to offset conflicting rows by 1 minute after import if needed.
