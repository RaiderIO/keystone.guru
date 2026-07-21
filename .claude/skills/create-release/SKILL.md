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

## Step 3 — Collect the commits since the previous release

Commits are squash-merged onto `master`, one per issue, formatted `#NNNN <description>`
(often with a trailing `(#PR)`).

```
git log "v<prev>..origin/master" --no-merges --pretty=format:'%H%x09%s'
```

For each commit, parse the **leading `#NNNN`** as the ticket and the subject (minus the
trailing `(#PR)`) as the raw change line. Commits with no `#NNNN` have no ticket; surface
them to the user, as they usually need a manual decision.

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

Create the **draft** Release first (a draft does not create the tag and notifies nobody),
then cut the tag. Both must point at the same `master` commit — record
`git rev-parse origin/master` once and use it for both:

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
issue URL, the draft Release URL, and any commits without an issue number or any new
category you introduced. Remind them that the tag push deploys to staging and that the
draft Release gets published (and announced via `release:report`) when production rolls
out — see the deployment-pipeline roadmap (issues #3327–#3329).

Suggest running `sh/release-watch.sh <version>` next — it tracks the build jobs, both
staging/production infra deploys, and verification from one terminal (see the `release-watch`
skill).
