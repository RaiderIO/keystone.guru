---
name: toggl-timesheet-merge
description: Use when the user has two or more Toggl timesheet CSVs (typically one per machine) and wants to reconcile, combine, or deduplicate them into a single import file. Companion to the toggl-timesheet skill, which generates the per-machine CSVs.
---

# Toggl Timesheet Merge

Wotuu works across **two machines**, each running the `toggl-timesheet` skill against its own local `~/.claude/` history. Neither machine can see the other's sessions, so the two CSVs must be reconciled into one before importing to Toggl. This skill does that reconciliation on whichever machine has both files.

## Input

Two or more Toggl CSVs in the format the `toggl-timesheet` skill emits:

```
"User","Email","Client","Project","Task","Description","Billable","Start date","Start time","End date","End time","Duration","Tags"
```

Ask the user for the paths if not given. Treat **each input file as a distinct source** — the source identity is what makes overlap detection meaningful.

## The core rule: one human, one clock

A single person cannot work two machines at the same wall-clock moment. Therefore:

- **Overlap *across* sources = a conflict.** If a row from file A and a row from file B overlap in real time, at most one is real (or one is mislabelled). These must be surfaced and resolved — never silently kept both.
- **Overlap *within* a single source = fine.** Parallel Claude conversations on one machine are genuine simultaneous work; the generator intentionally keeps them. Do **not** flag or dedupe intra-source overlaps.

So overlap detection is strictly **file-A-row vs file-B-row**, never row-vs-row inside the same file.

## Step 1 — Load and tag

Parse every input CSV, keeping each row's source filename. Combine `Start date` + `Start time` into a start datetime and `End date` + `End time` into an end datetime (handle the cross-midnight case where end date > start date). Sort all rows by start datetime.

## Step 2 — Detect cross-source conflicts

Walk the sorted rows. Two rows from **different** sources conflict when their `[start, end)` intervals intersect:

```python
def overlaps(a_start, a_end, b_start, b_end):
    return a_start < b_end and b_start < a_end
```

Also flag **exact duplicates** — same issue/description on the same date across both files (the same work committed from one machine but discussed on the other). These are the most common real case: the same task legitimately appears in both histories.

## Step 3 — Present conflicts for resolution

Never auto-resolve. For each conflict, show both rows side by side and ask the user which to keep. Typical resolutions:

- **Same task, both machines** → keep one, drop the other (usually keep the machine where the actual work happened).
- **Genuinely different tasks that happen to overlap** → keep both but shift one to an adjacent free slot so the wall-clock is physically possible.
- **One is clearly stray** → drop it.

Present as a table:

```
| # | Source | Start | End | Issue | ⟂ conflicts with |
|---|--------|-------|-----|-------|------------------|
```

## Step 4 — Write the merged CSV

After the user resolves every conflict:

1. Concatenate the surviving rows.
2. Re-sort by start datetime.
3. Fix any remaining **identical rounded start times** (Toggl merges rows with the same start date+time) by shifting the later row to the next free 15-minute slot — same rule as the generator skill.
4. Write `~/timesheet_<startdate>_<enddate>_merged.csv`, byte-for-byte in the same quoted Toggl format.

Report the row count and total duration of each input and of the merged result, so the user can sanity-check that the merge removed duplicates rather than dropping real work.

## Caveats

- **The user is the final authority.** He knows what he actually worked. This skill flags physical impossibilities and likely duplicates; it does not decide. When unsure, surface it.
- **Do not re-derive times.** Trust the segment times the generator already computed; this skill only reconciles, it does not recompute active time from session files.
