---
name: release-watch
description: >
  Use after a v* tag has been pushed and release-deploy.yml is running (or about to run),
  when the user wants to watch or drive a release to completion from one terminal instead
  of tracking release-deploy.yml, the keystoneguru-infra Deploy runs, and the manual
  production gate by hand. Runs sh/release-watch.sh, a re-entrant polling loop that tracks
  build jobs, correlates the staging/production infra runs, runs asset and HTML
  verification, and can approve the production gate. Use --watch-only for a pure observer
  with no approval prompt. Gate approval always requires the human typing the exact
  version string; the script (and this skill) must never approve unattended. Not for
  authoring the release changelog itself (create-release) or a general map of the pipeline
  (deployment-pipeline).
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

## Related

- `create-release` cuts the tag that starts the pipeline this script watches — its final
  step suggests running `sh/release-watch.sh <version>` next.
- `deployment-pipeline` is the map of how the pipeline works; this script is the preferred
  way to verify a release end-to-end rather than doing it by hand.
