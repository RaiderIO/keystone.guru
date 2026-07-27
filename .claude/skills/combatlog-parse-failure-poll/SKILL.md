---
name: combatlog-parse-failure-poll
description: On-demand combat log parse-failure sweep — run manually via /combatlog-parse-failure-poll whenever you're at your machine. Checks Staging (and, once configured, Production) for new or worsening CombatLogParseFailure clusters and files/updates GitHub issues describing them. Read-only against the app: never writes code, never opens MRs, never marks failures resolved. Mirrors the update-mdt-package skill's "human-run check" pattern rather than being a scheduled/autonomous job. Use when the user runs /combatlog-parse-failure-poll, or asks to "check for new combat log failures" / "sweep Staging for parse failures".
---

# /combatlog-parse-failure-poll — Combat Log Parse-Failure Sweep

A lightweight, **human-initiated** check — you run it (`/combatlog-parse-failure-poll`) when you're around, the same way
someone would manually re-run `update-mdt-package`'s Step 1 to see if a new MDT release exists.
There is no cron job, no cloud routine, no unattended execution. Its only output is GitHub issues
(new ones, or a comment bumping an existing tracked one) — it never touches application code and
never opens an MR. That's deliberate: diagnosis is safe to run casually and often; a fix needs a
real worktree session using `combatlog-parse-failure-triage` + `combatlog-parsing-internals`,
which you (or a follow-up Claude session) picks up from the filed issue when you're ready.

## Credentials

Reads Staging admin API Basic-auth credentials from a **local file outside the repo**, never from
chat, never committed:

```
~/.config/keystone-guru/combatlog-staging-basic-auth
```

Format: a single line, `user@example.com:password` (exactly the value you'd pass to `curl -u`).
`chmod 600` it.

- If the file doesn't exist, **stop and tell the user** how to create it — don't ask them to paste
  the secret into chat, and don't create the file yourself from a chat-provided value unless they
  explicitly ask you to. Provisioning the actual admin service account (an app-level user with the
  `admin` role) is a separate, deliberate step the user does — see
  `combatlog-parse-failure-triage`'s credentials section for the general handling rules.
- Only check for existence (`test -f ... && echo present`) — never `cat`/print the file's contents
  back into the conversation.
- Production isn't wired up yet. If/when it is, the convention will be a second file
  (`~/.config/keystone-guru/combatlog-production-basic-auth`) against `https://keystone.guru`
  instead of `https://staging.keystone.guru` — don't build that until asked.

## Step 1 — Fetch unresolved failures

```bash
curl -s -u "$(cat ~/.config/keystone-guru/combatlog-staging-basic-auth)" \
  'https://staging.keystone.guru/api/v1/combatlog/parse-failures' -o /tmp/poll_failures.json
```

Rows are under `.data` (Resource envelope) — see `combatlog-parse-failure-triage` for the full
field shape and pagination caveat (caps at 500).

## Step 2 — Cluster

Group by `(exceptionClass, normalizedMessage)` where `normalizedMessage` replaces every digit run
in `message` with `#` (e.g. `Unable to find combat log version #!`). This intentionally treats
"same error shape, different specific number" as one cluster — good enough for a lightweight sweep.
**Known limitation**: if two *different* root causes ever produce the same normalized shape at the
same time (e.g. two distinct unregistered versions failing simultaneously), they'd merge into one
tracked issue — acceptable for a periodic check, not for the actual fix (that still needs the real
per-cluster diagnosis from `combatlog-parse-failure-triage`).

## Step 3 — Match against already-tracked clusters

```bash
gh issue list --repo RaiderIO/keystone.guru --label combatlog --state open --json number,title,body
```

Every issue this skill files embeds a hidden marker in its body:

```
<!-- poll-signature: <sha1 of "exceptionClass|normalizedMessage"> count:<N> -->
```

For each cluster from Step 2, compute its signature and check whether any open `combatlog`-labeled
issue already carries it.

## Step 4 — New cluster → file an issue

No matching open issue found. File one (label `combatlog`; add a second topic label only if
obviously fitting, same rules as `create-github-issue`):

- Title: short description of the error shape (e.g. `Combat log parse failures: unable to find
  combat log version <example>`), **not** prefixed with an issue number (this skill files it, no
  existing number to reference).
- Body: failure count, 1-2 example rows verbatim (`run_id`, `season_id`, `raw_line`, `message`),
  and — **only if directly inferable from the message/exceptionClass alone, not from a deep
  dive** — a one-line hypothesis pointing at the likely area (e.g. "likely an unregistered
  `CombatLogVersion` — see the `combatlog-parsing-internals` skill's registration procedure").
  Do **not** attempt the full local reproduction here; that's what a follow-up worktree session is
  for. End with the `<!-- poll-signature: ... -->` marker line.
- Report the new issue URL to the user in your summary.

## Step 5 — Existing cluster → bump only if it moved

Compare the cluster's current count to the `count:<N>` in the matched issue's marker. If unchanged,
say nothing (don't spam a comment every run). If it changed meaningfully, post a short comment
(`gh issue comment <n> --body "..."`) noting the new count and update the marker via
`gh issue edit <n> --body-file` (re-emit the full body with the `count:` value updated).

## Step 6 — Summarize

Tell the user, per cluster: new issue filed (with link), existing issue bumped (with link + old→new
count), or unchanged. If nothing changed at all, say so briefly — don't pad the response.

## Boundaries — what this skill never does

- Never writes/edits application code.
- Never opens or touches an MR.
- Never calls the admin `resolve` action or otherwise mutates `CombatLogParseFailure` rows.
- Never merges anything (not applicable here, but stating it for clarity — merging is always a
  human decision per this repo's `CLAUDE.md`).

When the user wants an issue this skill filed actually fixed, that's ordinary work: open a worktree
(`sh/worktree.sh create <issue>-<slug>`), follow `combatlog-parse-failure-triage` for the
download/reproduce loop and `combatlog-parsing-internals` for the parser architecture, and open an
MR the normal way — the same process used for #3632/#3633 this session. `/combatlog-parse-failure-poll`
only ever gets you to "here's a diagnosed issue," never further.
