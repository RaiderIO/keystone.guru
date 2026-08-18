---
name: create-release
description: >
  Use when the user asks to "create a release", "cut a release", "make a new release",
  or generate a release changelog from commit history. Composes the changelog from
  squash-merged commits since the last release, creates the GitHub release issue and a
  draft GitHub Release, and cuts the `v*` git tag. NOT the legacy JSON-seeder or
  PHP-Deployer flows.
---

# Create a release from commit history

Procedural runbook for cutting a new release. GitHub Releases are the single source of
truth for release notes (#3480): there is **no release JSON, no DB seeding, no hardcoded
ids, and nothing to commit to the repo** for a release. A release consists of:

1. a `release`-labelled GitHub **issue** (full changelog, including non-public changes),
2. a **draft GitHub Release** carrying the public changelog body,
3. the annotated `v*` **git tag**, whose push triggers the `release-deploy` pipeline
   (staging first).

The draft Release is published from the **infra project** when the version rolls out to
production — never publish it from here. `release:report` (manual, Discord) reads the
published Release body and skips releases whose body is empty.

## Conventions

- Repo for `gh`: always pass `--repo RaiderIO/keystone.guru`.
- Nothing in this flow modifies the working tree; there is nothing to commit.
- Keep a running summary of the commits included and the category assigned to each.

## Step 1 — Determine the target version

- If the user gave an explicit `vX.X.X`, use it.
- Otherwise propose the next semver from the previous version (Step 2) and the nature of
  the **public** changes only: **patch** if they are only bugfixes/maintenance, **minor**
  if they include user-facing features/changes, **major** only if the user says so.
  Non-public changes (CI, tests, tooling) do not influence semver. **Confirm the version
  with the user before creating anything.**

## Step 2 — Find the previous release

```
git fetch --tags
git describe --tags --abbrev=0 origin/master
```

Cross-check against the latest GitHub Release:
`gh release view --repo RaiderIO/keystone.guru --json tagName,isDraft`. If the latest
Release is still a **draft**, the previous version has not rolled out to production yet —
flag this to the user before stacking another release on top.

### Stacking on an undeployed previous release

If the user proceeds anyway (e.g. a fix needs to go out now), know what will actually
happen: pushing the new tag starts a new `release-deploy` run against the same
`environment: production` gate, and GitHub auto-cancels the previous run's pending
`Deploy to Production` job the moment that happens (a waiting environment deployment is
voided when a newer run targets the same environment) — even if staging fully passed for
the older version. **The previous release will never reach production and will never get
published/announced on its own.** This happened for real with v15.8.0 → v15.8.1 (issue
#3725/#3726, 2026-07-28).

Since the new release is now the one that actually ships, its changelog must cover
*everything* since the last **published** release, not just the delta since the
now-superseded one — otherwise the superseded release's changes go out silently, unannounced.
Concretely:

1. Compose the new release's changelog as `<last published release>..origin/master`
   (folding the superseded release's entries in), not `<superseded release>..origin/master`.
2. Mark the superseded release's draft so nobody publishes it later and double-announces
   things: `gh release edit v<old> --title "v<old> (SUPERSEDED by v<new> - do not publish)"
   --notes-file <a short note explaining why + linking the new release>`.
3. Leave the superseded **tag** in place — **do not delete it**. A version-number gap is
   normal and expected (every project has tags that never shipped). The superseded
   **draft Release** itself gets cleaned up later, once the superseding version reaches
   production — see "Cleaning up a superseded draft" in the `release-watch` skill. Don't
   delete it here at cut time: it stays until the new release is actually confirmed live,
   in case the new release also fails to ship and this one needs reviving.

## Step 3 — Collect the commits since the previous release

Commits are squash-merged onto `master`, one per issue, formatted `#NNNN <description>`
(often with a trailing `(#PR)`).

```
git log "v<prev>..origin/master" --no-merges --pretty=format:'%H%x09%s'
```

For each commit, parse the **leading `#NNNN`** as the ticket and the subject (minus the
trailing `(#PR)`) as the raw change line. Commits with no `#NNNN` have no ticket; surface
them to the user, as they usually need a manual decision.

### A squash commit can carry more than one issue — check the body too

"One per issue" is the convention, not a guarantee. A branch sometimes carries a commit for
a different issue (a small adjacent fix made while in there), or — worse — a stacked child
gets merged before its parent and swallows the parent's whole diff. Either way the squash
subject keeps exactly **one** leading `#NNNN`, and every other issue in that commit would
silently never get a changelog line.

The repo's squash setting is `squash_merge_commit_message: COMMIT_MESSAGES`, so the squash
body already lists each inner commit as `* #NNNN <subject>`. Harvest those:

```bash
git log "v<prev>..origin/master" --no-merges --pretty=format:'%H' | while read -r sha; do
  extra=$(git show -s --format=%b "$sha" | grep -oE '^\* #[0-9]+' | grep -oE '[0-9]+' | sort -u)
  echo "$sha $(git show -s --format=%s "$sha") | inner: ${extra:-none}"
done
```

Any inner issue number that isn't the subject's leading one is an **extra ticket in that
commit**. Do not decide alone what to do with it: enrich it like any other ticket (Step 4)
and show it in the Step 5 approval table, marked as coming from the commit body rather than
the subject, so the user can confirm it belongs in the changelog or drop it. Most of the
time it is a genuine second change that deserves its own line; occasionally it is work that
already shipped in an earlier release under its own PR, in which case it is a duplicate and
gets dropped.

This is the counterpart to the untagged-commit check further down: that one catches commits
carrying **no** issue number, this one catches commits carrying **two**.

## Step 4 — Enrich each line, assign a category, and decide public/non-public

For each ticket:

```
gh issue view <NNNN> --repo RaiderIO/keystone.guru --json title,labels,body
```

Fetch all issues in parallel for speed.

**Include ALL commits** — but split them:

- **Public** — user-facing changes that appear in the GitHub Release body (and from there
  on Discord). Write a clear, descriptive change line (full sentence ending with a
  period). Longer and more explanatory is better. E.g.:
  > "For dungeons that have multiple entrances, you can now select which entrance you want
  > your route to start at."

- **Non-public** — internal changes that appear only in the release issue (for internal
  tracking). These include:
  - Test fixes or new test infrastructure
  - Dependency upgrades (Node, PHP packages, PHPStan level, etc.)
  - Internal refactors with no visible behaviour change
  - Developer tooling / CI / deployment pipeline changes

  For non-public changes, a short change line is fine (the commit subject is acceptable).

**#3877 is always non-public — no judgement call.** It is the permanent anchor issue for
agent-tooling commits (`.claude/skills/**`, `CLAUDE.md`, `.claude/agents/**`, agent/MCP
config). Anything tagged to it goes in the release issue and **never** in the public
GitHub Release body, so it never reaches Discord. Don't try to write a user-facing line
for one — changes to how Claude works are not product changes, however substantial the
diff. Group them into a single non-public line when a release contains several.

This replaces what used to be an implicit rule: these commits carried no issue number at
all, and that absence was what kept them out of the public changelog while also making
`create-release` flag them as untagged on every release. If you see an agent-tooling
commit with no ticket, it predates #3877 — treat it as non-public too.

Assign **one category** per change. Categories are plain markdown section headers now; use
the established names:

General changes, Route changes, Map changes, Mapping changes, Bugfixes,
MDT Importer changes, Team changes, MDT Exporter changes, Live Session changes,
Simulation Craft changes, Auto Route changes, API changes, Heatmap changes.

Infer from the issue's labels/title (e.g. a `bug` label → Bugfixes; API work → API
changes; MDT import → MDT Importer changes; mapping data → Mapping changes). When nothing
fits, default to General changes. A genuinely new category is just a new header — mention
it to the user.

**Before creating anything, present the user with a table** of all proposed changes
(ticket, public/non-public, category, change line) and ask them to confirm or request
edits.

## Step 5 — Compose the changelog bodies

Both bodies group changes by category, matching the format of existing releases:

```
Bugfixes:
* #3321 redis:clearidlekeys no longer removes sessions from localhost.

General changes:
* #3320 Updated CI workflows to use OIDC for AWS credentials.
```

**No leading whitespace before any `*`.** Discord's markdown renderer treats a bullet's
indentation relative to the previous line as significant: the first bullet after a
`Category:` header (no leading space) renders fine, but if it's indented at all, every
*following* bullet in that category reads as a nested sub-list and renders offset. This
bit us for real — a discord release announcement had every bullet but the first one in
each category visibly inset. Keep every `*` flush against the left margin, in the
template above and in the actual body text written to the notes files.

- **Public body** (for the draft GitHub Release): public changes only. If there are **no
  public changes, the body is empty** — that is what makes the release silent
  (`release:report` skips empty-body releases).
- **Full body** (for the release issue): all changes, public and non-public.

Write both to temp files so they can be passed with `--notes-file` / `--body-file`
(avoids shell-escaping issues).

## Step 6 — Create the GitHub release issue

```
gh issue create --repo RaiderIO/keystone.guru \
  --title "Release vX.X.X - <short summary>" \
  --label release --assignee Wotuu \
  --body-file <full body file>
```

The ` - <short summary>` suffix is optional — omit it if the release has no meaningful
one-liner. Don't close the issue yourself.

## Step 7 — Create the draft GitHub Release and cut the tag

**Re-fetch and re-derive the SHA right here, immediately before this step** — do not reuse
a SHA captured back in Step 2. Any real time gap since then (the Step 4 changelog-table
confirmation in particular) is a window where `origin/master` can move, and a stale SHA
tags the release one or more commits short of what you actually meant to ship. This is
not hypothetical: it happened for real on v15.13.0 — the SHA was captured before PR #4112
actually merged (the user had said it "will very soon" and asked to include it on that
promise), and by the time the tag was pushed minutes later, master had moved *twice*
past the recorded SHA. Re-fetching right before this command is the only point in the
flow close enough to the actual tag push to close that window:

```
git fetch origin --quiet
git rev-parse origin/master
```

If the user has told you to include a change on the promise that its PR "will merge
soon" (rather than confirming it has already merged), treat that as a hint to re-fetch
again right before the tag/push commands below, not just once — the same race can recur
if the merge lands during Step 6/7's `gh` calls.

Create the **draft** Release first (a draft does not create the tag and notifies nobody),
then cut the tag. Both must point at the same `master` commit — use the freshly-derived
SHA for both:

```
gh release create vX.X.X --repo RaiderIO/keystone.guru \
  --draft --title vX.X.X --target <sha> \
  --notes-file <public body file>

git tag -a vX.X.X -m "vX.X.X" <sha>
git push origin vX.X.X
```

**Do not pass `--latest` and never publish the draft.** The tag push is what triggers the
`release-deploy` pipeline (staging first). Publishing the Release is the **infra
project's** production-rollout step; once published, `release:report` can announce it on
Discord.

Summarise to the user: the version, each included change with its category and ticket, the
issue URL, and the draft Release URL, and any commits without an issue number, any commit
that carried a second issue number in its body (Step 3), or any new category you introduced.

## Step 8 — Watch the pipeline through staging, automatically

The changelog confirmation in Step 4 is the only manual gate in this flow — **do not** wait
to be separately asked to "run the watcher". As soon as the tag is pushed, launch
`sh/release-watch.sh <version> --watch-only` yourself (see the `release-watch` skill for
flags and output format):

```
LOG=<scratchpad>/release-watch-<version>.log
nohup sh/release-watch.sh <version> --watch-only > "$LOG" 2>&1 &
disown
```

then attach a persistent `Monitor` to that log filtering for its milestone lines (`FAILED`,
`SUMMARY: (SUCCESS|FAILED)`, `GATE:`, `references compiled/`) so staging verification,
failures, and the production gate opening all surface as they happen without you polling.

`--watch-only` never approves the production gate on its own (see that skill's hard safety
rule) — it only gates on a human typing the version string at an interactive prompt, which
this flow never runs. So driving this automatically through staging is safe: the moment
staging verification passes (`staging ... references compiled/<version>/`), stop there and
hand off. Report the staging result to the user and remind them production approval is
theirs to trigger (in the browser, or by running the script interactively without
`--watch-only`) — never approve or drive that gate yourself, and don't spawn a second
watcher process for the same version if one is already running.

When attaching the `Monitor`, anchor the filter exactly as `release-watch`'s own "Attaching
a Monitor to the log" section specifies (`^\[[0-9:]+\] EVENT:` plus the terminal
`SUMMARY: (SUCCESS|FAILED)`) — a looser filter that matches bare `SUMMARY:` or `GATE:`
substrings re-fires every 15s poll cycle for as long as that state holds (the production
gate can sit for hours) and gets the Monitor auto-throttled/stopped by the harness. Confirmed
live on the v15.13.0 release watch.

## Step 9 — Hand off a staging browser-test runbook

**Default for every release — do this without being asked**, the moment Step 8's watcher
reports staging verification passed (`references compiled/<version>/`). Wotuu wants to click
around on staging himself before approving production, so give him a concrete list of what to
check rather than making him reconstruct it from the changelog.

Build the runbook from the **same confirmed changelog table** from Step 4/5 — one entry per
**public** change only (non-public/internal changes have no UI surface to click through, skip
them). For each:

- The `staging.keystone.guru` page/URL to open.
- Concrete steps to reproduce the change (which page, which element, what to click/hover/resize).
- What to expect to see.

Group by the same category headers as the changelog. Post this as a normal chat message (not a
file) so it's immediately actionable — this is a live checklist for a human, not documentation.

**Flag anything not actually present in the deployed build.** If a change was included in the
changelog on the understanding that its PR would merge before/around cut time, but the tag was
actually cut against a commit that doesn't contain that PR's diff (e.g. cutting ahead of an
imminent merge), say so explicitly next to that item — "not testable on this staging build,
PR #NNNN hadn't merged when the tag was cut" — instead of listing steps that won't show
anything and leaving Wotuu to wonder why.
