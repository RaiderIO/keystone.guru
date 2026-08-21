---
name: release-watch
disable-model-invocation: true
description: Watch or drive a v* release through build, staging and the production gate after the tag is pushed (sh/release-watch.sh).
---

# Release watch

`sh/release-watch.sh` tracks (and can drive) a full release across both repos from one
terminal, instead of the manual back-and-forth of watching `release-deploy.yml` here, then
guessing which `keystoneguru-infra` "Deploy" run corresponds to which stage, then manually
curling the verification endpoints.

## Usage

```
sh/release-watch.sh [vX.Y.Z] [--watch-only] [--interval <seconds>] [--run-id <id>]
```

- No version → newest `release-deploy.yml` run's tag.
- `--watch-only` → never prompts for or performs production gate approval (pure observer).
- `--interval <seconds>` → poll interval, default 15.
- `--run-id <id>` → explicit release-deploy run id (skips the tag → run lookup).

Only depends on bash, `gh`, `jq`, `curl`, `date` — no extra tooling.

## What it does, per polling cycle

1. Fetches the release-deploy run's jobs (dynamically — build job names are not
   hardcoded, since they change over time) and prints status + elapsed time for each. A
   failed job's URL is printed immediately.
2. Once `Deploy to Staging` succeeds, correlates the matching `keystoneguru-infra` "Deploy"
   run (by stage + version, preferring the latest match if a stage was re-dispatched after
   an earlier infra failure) and tracks its current step.
3. Once that infra run succeeds, runs staging verification: the three asset HEAD checks
   plus polling `staging.keystone.guru`'s HTML until it references the tag (own ~5 minute
   timeout).
4. When `Deploy to Production` is sitting on the environment gate (job `status: waiting` —
   the overall run status parks on `waiting` forever, so job status is what's polled) and
   staging verification has passed, it shows `GATE: waiting for approval`.
5. Same correlation + verification for production once the gate opens and the dispatch
   succeeds.
6. Prints a final summary once every job/infra-run/verification item has concluded; exits 0
   on full success, 1 if anything failed.

Re-entrant: every cycle re-derives all of this from the GitHub API, so starting or
restarting the script mid-release just picks up wherever the release actually is.

## Production gate approval — hard safety rule

The script can approve the production gate (`GET`/`POST`
`repos/RaiderIO/keystone.guru/actions/runs/<id>/pending_deployments`), but **only** when
all of these hold:

- not run with `--watch-only`,
- stdin is a real TTY,
- staging verification has passed, and
- the user types the **exact version string** in response to the prompt that cycle.

Anything else (wrong input, no TTY, `--watch-only`) skips approval for that cycle — it will
prompt again next cycle. This mirrors the project's "no unattended production deploys" rule:
never invoke or script around the approval path without a human typing the version in the
moment. If the API rejects the approval (e.g. self-review not allowed), the script prints
the run URL for manual approval in the browser instead of retrying blindly.

## Watching non-interactively (from an agent)

The script writes two interleaved output streams:

- A **re-rendered dashboard** each cycle (the `STAGING` / `PRODUCTION` blocks) whose per-job
  lines carry mutating elapsed times — do **not** tail these raw, they repeat and change
  every poll.
- **Append-style event lines**: `log()` prints `[HH:MM:SS] <message>`, and
  `log_event_once()` prints a given message exactly once per run. These are the milestones.

Milestone messages worth surfacing:

- `FAILED: …` — any failed job / infra run / verification, printed with its URL.
- `<stage> html now references compiled/<version>/` — staging (then production) verification
  passed. **This** (staging variant) is the real "ready to approve" signal.
- `GATE: waiting for approval - <url>` — the `Deploy to Production` job parks on the
  environment gate from the *start* of the run, so this line appears **early**, while the
  build/staging deploy is still running and long before staging is verified. It is re-emitted
  every cycle. Treat it as "a gate exists", **not** as "ready to approve" — the genuine cue is
  this line together with the staging `now references compiled/<version>/` verification.
- `SUMMARY: SUCCESS …` / `SUMMARY: FAILED …` — terminal.
- `SUMMARY: in progress...` — a per-cycle heartbeat, **not** a milestone; ignore it.

**`--watch-only` never exits.** It parks at the gate cycling `SUMMARY: in progress...`
indefinitely (a human approves in the browser / interactive run), so a "run in background
until it exits" approach won't notify you until production actually completes or fails.
Instead redirect its output to a log and watch that log for the milestone lines above.
A clean filter (strip the timestamp prefix, keep milestones, dedupe):

```
tail -n +1 -F release-watch.log \
  | sed -E 's/^\[[0-9:]+\] //' \
  | grep -E 'FAILED|SUMMARY: (SUCCESS|FAILED)|GATE:|references compiled/' \
  | awk '!seen[$0]++'
```

**Attaching a `Monitor` to the log**: anchor the filter to the timestamp-prefixed one-time lines
only — `^\[[0-9:]+\] EVENT:` plus the terminal `SUMMARY: (SUCCESS|FAILED)`. Do **not** match bare
`GATE:`/`[PASS]`/`SUMMARY: in progress` substrings: those repeat every 15s cycle for as long as
the state holds (minutes to hours at the human gate), so the Monitor fires every cycle and gets
auto-throttled/stopped by the harness. Proven on the v15.8.3 release watch.

## Wrap-up: cleaning up a superseded draft

Once `SUMMARY: SUCCESS` confirms the new version is live in production, check whether it
superseded an earlier release that never shipped (see "Stacking on an undeployed previous
release" in the `create-release` skill — this is the v15.10.0 → v15.10.1 situation).

```
gh release list --repo RaiderIO/keystone.guru --json tagName,isDraft,name \
  --jq '.[] | select(.isDraft and (.name | test("SUPERSEDED")))'
```

For each match confirmed superseded by the version that just went live:

```
gh release delete v<old> --repo RaiderIO/keystone.guru --yes
```

This deletes only the draft **Release** object — pass no `--cleanup-tag`, so the git tag
stays (`git ls-remote --tags origin | grep v<old>` to confirm). The tag plus the
superseding release's issue body ("Superseded release" section, written at cut time) are
the permanent record; the draft Release served no purpose once it can never be published,
and left alone it accumulates on the Releases page forever. Do this as a normal part of
wrapping up a release, not just when the user notices the clutter and asks.

Only delete a draft once the version that supersedes it is the one just confirmed live —
never delete a draft for a release that might still be revived (e.g. the *new* release
also fails before production and the old one becomes relevant again).

## Related

- `create-release` cuts the tag that starts the pipeline this script watches, and its own
  final step launches this script in `--watch-only` mode automatically (plus a `Monitor` on
  its log) as soon as the tag is pushed — no need to be asked separately to "run the
  watcher". That auto-drive stops at staging verification; production approval is still a
  human action per the hard safety rule above.
- `deployment-pipeline` is the map of how the pipeline works; this script is the preferred
  way to verify a release end-to-end rather than doing it by hand.
