---
name: combatlog-parse-failure-triage
description: Operational runbook to find, download, and reproduce real Staging/Production combat log parse failures before fixing them — the internal-team API endpoints, how to pull a failure's real Raider.IO log segments, the gzip/docker-cp reproduction recipe, and the safety checklist for shipping a fix. Use when triaging CombatLogParseFailure rows, chasing a "combat log parses are failing" report, or verifying a combatlog-parsing-internals fix against real production data. Pairs with combatlog-parsing-internals (how the parser itself works) and create-github-issue / worktree-docker (how to turn a diagnosis into a shipped MR).
---

# Combat Log Parse-Failure Triage

Runbook for turning "some combat logs are failing to parse in Staging/Production" into a diagnosed,
locally-reproduced, tested fix. Read `combatlog-parsing-internals` first if you haven't — this skill
assumes you know the parser architecture and focuses on the *operational* loop: find → download →
reproduce → fix → verify → ship.

## 0. Credentials — get them fresh, never persist them

The parse-failure API lives behind `api_role:admin` (HTTP Basic auth, Laratrust). **This repo is
public** — never write a real username/password into a skill file, a commit, a script, or anywhere
else that gets checked in. Each session, ask Wotuu for a current Staging (or Production, if that's
what's being triaged) admin API service-account credential, use it only for that session, and remind
him to rotate/delete it once you're done. Treat it like any other secret: environment/conversation
only, never `.env`, never a file under version control (see `feedback_no_env_file` in memory).

## 1. List unresolved failures

```
GET https://staging.keystone.guru/api/v1/combatlog/parse-failures
```

(swap the host for `keystone.guru` / whatever the production host is, for a prod triage). HTTP Basic
auth, `api_role:admin`. Returns up to 500 unresolved rows (`whereNull('resolved_at')`, newest first) —
`app/Http/Controllers/Api/V1/InternalTeam/Combatlog/APICombatLogParseFailureController.php`. Each row:

```json
{
  "id": 651, "runId": 40653052, "seasonId": 17, "combatLogVersion": 22012000005,
  "lineNumber": 8, "rawLine": "...", "message": "...", "exceptionClass": "Exception",
  "createdAt": "..."
}
```

```bash
curl -s -u 'user@example.com:PASSWORD' \
  'https://staging.keystone.guru/api/v1/combatlog/parse-failures' -o parse_failures.json
```

The response is wrapped in a Resource envelope — the rows are under `.data`, not the top level:

```python
import json
rows = json.load(open('parse_failures.json'))['data']
```

**Cluster first, don't fix the first row you see.** Group by `message` (strip the specific numeric ID
out of it) to find the dominant failure mode before picking a repro target — one root cause is
typically 90%+ of the rows. `exception_class` + the stable part of `message` is the cluster key.

## 2. Get a failing run's log segments

```
GET https://staging.keystone.guru/api/v1/combatlog/parse-failures/{id}/segments
```

Reuses `RaiderIOApiServiceInterface::getCombatLogSegmentsForRun()` (the same logic the admin panel
and the ingestion job use) to return **presigned S3 URLs, valid ~5 minutes** — download promptly,
don't cache the URLs. Response:

```json
{"segments": [{"id": 1, "type": "trash", "downloadUrl": "https://s3..."}, ...]}
```

If it 404s with `combatlog_parse_failure_no_segments`, or 422s with `..._no_season`, that run's
segments aren't available (Raider.IO expired them, or the failure predates season resolution) — pick
a different row from the cluster.

## 3. Download and decompress

The URLs point at `.txt.gz` bodies. `curl -s <url> -o file.gz` saves the raw gzip bytes (no
`Content-Encoding` transparent decoding without `--compressed`) — either add `--compressed`, or just
`gunzip` afterward:

```bash
mkdir -p run_<runId> && cd run_<runId>
python3 -c "
import json, subprocess
segs = json.load(open('../segments_<id>.json'))['segments']
for s in segs:
    subprocess.run(['curl', '-s', s['downloadUrl'], '-o', f\"{s['id']:02d}_{s['type']}.txt.gz\"], check=True)
"
gunzip -k *.gz   # -k keeps the .gz too, harmless
```

A single M+ run typically has 5-10 segments (trash/boss alternating), 10K-200K lines each.

## 4. Reproduce locally against the real segment

Use a worktree's `app` container (create one with `sh/worktree.sh create <issue>-<slug>` per
`worktree-docker` if you don't have one open already). Copy the decompressed `.txt` files in and
re-parse them with `CombatLogService::parseCombatLogToEvents()` — **read-only, no DB writes**, the
right tool for reproduction (see `combatlog-parsing-internals`):

```bash
docker cp run_<runId>/. <container>:/tmp/run_<runId>/
docker exec <container> php artisan tinker --execute '
$service = app(\App\Service\CombatLog\CombatLogServiceInterface::class);
$files = glob("/tmp/run_<runId>/*.txt");
sort($files);
$total = 0; $fail = 0;
foreach ($files as $f) {
    try {
        $events = $service->parseCombatLogToEvents($f);
        $total += $events->count();
        echo basename($f) . ": OK " . $events->count() . " events\n";
    } catch (\Throwable $e) {
        $fail++;
        echo basename($f) . ": FAIL " . get_class($e) . ": " . $e->getMessage() . "\n";
    }
}
echo "TOTAL: $total events, $fail failures\n";
'
```

This baseline-reproduces the exact failure from the API row. Once you have a candidate fix, edit the
file **in the worktree** (bind-mounted into the container — no rebuild needed) and re-run the same
command: a clean fix turns every segment `OK`.

**Do this against at least two distinct failing runs before trusting a fix** — one run passing could
be luck; the point is confidence that the fix generalizes, not just that it satisfies one sample line.

## 5. Diagnose, don't just patch the symptom

- Read the exception message/class carefully — `"Invalid parameter count ... wanted X-Y, got Z"`
  tells you exactly how many fields were present vs expected; compare the failing `raw_line` against a
  known-good line of the same event type to see what's structurally different (missing trailing
  fields → truncation; different field *count* patterns across failures of the same message → genuine
  truncation, not a clean version/format difference — different failures missing a *consistent* set of
  fields → an actual structural difference worth its own handler).
- For "unknown combat log version" failures specifically, follow the registration procedure in
  `combatlog-parsing-internals` — including its safety-check step (confirm the `default =>` fallback
  pattern still holds) before touching the registry.
- Don't assume all failures in a session share one cause. Re-cluster after fixing the dominant one —
  a secondary cluster (different `exceptionClass`/message shape) needs its own separate diagnosis and,
  usually, its own issue/MR rather than being bundled in.

## 6. Ship it

Follow the project's normal worktree/issue workflow (`worktree-docker`, `create-github-issue`,
root `CLAUDE.md`'s git conventions):

1. If the diagnosis is out of scope of whatever issue you were originally working (e.g. you were
   building the triage API and found an unrelated parser bug), **file a new issue** rather than
   scope-creeping the current MR — reference the real Staging data (failure counts, example raw
   lines, which runs you reproduced against) in the issue body so it's resumable cold.
2. `sh/worktree.sh create <new-issue>-<slug>`, make the fix there, add/update a unit test that
   encodes the specific case (see `combatlog-parsing-internals`'s test file pointers).
3. Re-run the real-data reproduction from step 4 against the properly-committed fix (not just your
   throwaway edit) as a final sanity check — cheap insurance against a copy-paste slip between
   experimentation and the real commit.
4. `composer run fix` / `composer run analyse`, run the affected test file plus the broader
   `--group=CombatLog` suite for regressions, commit, `sh/worktree.sh push`, open an MR with
   `Closes #<issue>` and a summary that includes the real-data verification numbers (segments/events
   tested, zero-failures claim) — that's the evidence a human reviewer needs to trust a parser change
   without re-deriving it themselves.

## Resolving `CombatLogParseFailure` rows

There is currently **no API endpoint** to mark a failure resolved — only the admin panel
(`POST /admin/tools/combatlog/parse-failures/{id}/resolve`, session-auth only). Marking rows resolved
is a production data mutation and doesn't by itself confirm anything was actually fixed (the failing
run doesn't get automatically reprocessed) — **don't do this without being explicitly told to**, and
even then only after the fix has actually shipped, not just been merged. This mirrors the project's
general "no unattended prod actions without a human saying so in the current conversation" norm.

## Toward full autonomy — what's still a manual gate

The user's end goal is running this loop unattended as new failures show up. What's still manual
today, and why it's not automated yet:

- **Credentials**: no durable secret storage in-repo (by design — public repo). A fully autonomous
  loop needs its own credential-provisioning story (e.g. a scheduled agent with its own vaulted
  secret) that doesn't yet exist here.
- **Shipping**: MRs still go through normal review per this repo's `CLAUDE.md` — nothing here should
  bypass that, even if the agent is confident in a fix.
- **Resolving failure rows**: a production data mutation; keep requiring an explicit go-ahead per the
  paragraph above until there's a clearer signal (e.g. automatic reprocessing) that a resolve is safe
  to make unattended.

If/when the user wants to close these gaps (e.g. via `/schedule` or a cron-based agent), that's a
separate, explicit setup step — don't wire it up implicitly while just doing triage.

## Gotchas

- `docker exec -T <container> ...` can fail with `unknown shorthand flag: 'T'` on some hosts' docker
  CLI — drop `-T` for direct `docker exec` (unlike `docker compose exec -T`, which is fine and used
  throughout this repo's `CLAUDE.md`).
- The scratchpad directory isn't guaranteed to already exist — `mkdir -p` it before first use.
- Presigned segment URLs expire in ~5 minutes — download all segments for a run in one batch
  immediately after fetching `/segments`, don't fetch-then-pause.
- A single M+ run's segments can total 50-200K+ lines / tens of MB gzipped — fine to download, just
  don't be surprised by the size.
