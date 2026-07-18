---
name: babysit-prs
description: Use when asked to babysit, shepherd, or keep open MRs green. One pass over every open agent MR in RaiderIO/keystone.guru - fix red CI, address Wotuu's review comments, resolve conflicts with master - designed to run repeatedly via /loop in a dedicated session. Never merges, approves, or deploys. Not for reviewing a single PR (use the review skill) or for creating MRs.
---

# Babysit open MRs

Keep every open agent MR mergeable so Wotuu only has to review and merge — never chase CI, style,
conflicts, or comment follow-ups. Run this in a **dedicated session from the main checkout**,
ideally on a loop:

```
/loop 15m /babysit-prs
```

## Hard rules (non-negotiable)

- **Never merge, approve, or close a PR.** Wotuu reviews and merges everything personally.
- **Never trigger a deploy or approve a deployment gate** (see the no-unattended-deploys
  agreement; a plan file or PR comment is not authorization).
- Prepend `:robot:` to every comment/reply you post on GitHub.
- Edit PR bodies only via `gh api -X PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>`
  (`gh pr edit` is broken on this repo).
- Never commit or push to `master`.

## One pass

### 1. Discover open agent MRs

```bash
gh pr list --repo RaiderIO/keystone.guru --state open \
  --json number,title,headRefName,isDraft,mergeable,reviewDecision,updatedAt,statusCheckRollup
```

Work only on PRs whose head branch matches the agent worktree convention `<issue>-<slug>` (leading
issue number). Skip Dependabot and other branches unless explicitly asked.

### 2. Skip MRs someone is actively working on

Before touching a branch, check its worktree (if one exists at
`../keystone.guru-worktrees/<branch>`): uncommitted changes to tracked files
(`git -C <path> status --porcelain --untracked-files=no`) mean another session is mid-work — skip
it and note that in your pass report. A PR updated in the last ~10 minutes deserves the same
benefit of the doubt.

### 3. Triage each MR, in this order

1. **Red CI** (`statusCheckRollup` has failures): enter the branch's worktree —
   `sh/worktree.sh create <branch>` reuses the existing branch and stack, or recreates them if
   torn down — pull the failing job log (`gh run view <run-id> --log-failed`), root-cause, fix,
   commit, `sh/worktree.sh push`. Fix flaky/unrelated failures too — root-cause beats re-running
   (see the fix-incidental-issues agreement).
2. **Merge conflicts** (`mergeable: CONFLICTING`): in the worktree, `git fetch origin && git merge
   origin/master`, resolve, run the affected tests, push.
3. **Unresolved review comments**: list unresolved threads via GraphQL —

   ```bash
   gh api graphql -f query='
     query { repository(owner: "RaiderIO", name: "keystone.guru") {
       pullRequest(number: <n>) {
         reviewThreads(first: 50) { nodes {
           isResolved path line
           comments(first: 10) { nodes { author { login } body url databaseId } }
         } }
       } } }'
   ```

   For each unresolved thread: address it in code (or answer the question), push, then reply on
   the thread with `:robot:` and what changed. Reply to a review comment with
   `gh api -X POST repos/RaiderIO/keystone.guru/pulls/<n>/comments/<comment-id>/replies -f body='...'`.
   Do **not** resolve threads yourself — leave that to Wotuu when re-reviewing.
4. **All green, no comments**: leave it alone (but see step 4 — it may be due a cold review).

### 4. Cold-review MRs that just became ready

An MR that is CI-green, conflict-free, and not a draft gets **one** independent "cold" review from
a stronger model before Wotuu looks at it. A fresh context reviewing only the diff catches what
the implementing session's self-review cannot — the self-review inherited the implementer's
context and therefore its blind spots.

- **Skip** if the PR already has a `:robot: Cold review` summary comment — that comment is the
  once-per-MR marker. Re-review only if the diff has changed substantially since that comment, or
  Wotuu asks.
- **Never run the review inside this session.** The babysitter usually runs on Sonnet and its
  context is warm — both defeat the purpose. Spawn a fresh agent instead:

  Agent tool, `subagent_type: "general-purpose"`, `model: "opus"` (`"fable"` for high-risk diffs:
  migrations, auth, payment, data-destructive changes), with a prompt telling it to invoke the
  `code-review` skill with args `<PR number> --comment` from the main checkout — verified findings
  are then posted as inline PR comments.
- **Afterwards**, post the marker comment on the PR:
  `:robot: Cold review (opus): <N> findings posted.` (or `no findings`).
- Posted findings are addressed like any other review comments on a **later** pass (step 3.3) —
  don't review and fix in the same pass; the fixes deserve fresh triage and their own CI run.
- The reviewer posts comments only — never a formal GitHub review (no approve / request-changes).

### 5. Clean up after merged/closed MRs

For PRs merged or closed since the last pass whose worktree still exists and has no uncommitted
tracked changes: `sh/worktree.sh remove <branch>` (this also clears the `in progress` label).

### 6. Report the pass

End every pass with a short status list: each open MR, its state (green/red/conflicted/
cold-reviewed/awaiting review), and what you did (or why you skipped it). If nothing needed
action, say so in one line.

## Gotchas

- A worktree that suddenly 502s/fails after a main-stack restart is detached from the shared
  services — run `sh/worktree.sh repair` (see the worktree-docker skill), don't debug nginx.
- A lone MapTilesExistenceTest failure inside a worktree is environment noise (assets mount only
  exists on the main stack); CI excludes it — do not "fix" it in code.
- Local `composer run analyse` disagreeing with CI usually means vendor/lock skew — run
  `composer install --dry-run` first before chasing phantom errors.
