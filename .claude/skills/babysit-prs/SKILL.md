---
name: babysit-prs
description: Use when asked to babysit, shepherd, or keep open MRs green — one pass over every open agent MR; fix red CI, address Wotuu's comments, rebase onto master, merge PRs labeled `pr can merge` once green. Designed for /loop in a dedicated session. Never approves, deploys, or merges anything Wotuu hasn't labeled. Not for reviewing a single PR (review skill) or creating MRs.
---

# Babysit open MRs

Keep every open agent MR mergeable so Wotuu only has to review and merge — never chase CI, style,
conflicts, or comment follow-ups. Run this in a **dedicated session from the main checkout**,
ideally on a loop:

```
/loop 15m /babysit-prs
```

## Quiet hours (1am–7am local)

If a `/loop`-triggered firing of this skill lands between 1am and 7am **local time** (check with
`date`) — e.g. the PC woke up overnight for an unrelated reason (cats walking on the keyboard have
done this before) — **do not run the pass**. Don't touch any PR. Instead:

1. Schedule a single one-time `/babysit-prs` run at 7am local (the `schedule` skill, or
   `CronCreate` directly) — not another recurring loop, just one firing.
2. Stop this loop (`ScheduleWakeup` with `stop: true`, or simply don't reschedule the next
   dynamic-pacing wakeup).

This only applies to a `/loop`-driven firing. If Wotuu invokes `/babysit-prs` directly during quiet
hours, run it normally — the whole point is skipping *unattended* overnight churn, not refusing to
work when he's actually at the keyboard.

## Hard rules (non-negotiable)

- **Never approve, or close a PR.** Wotuu reviews everything personally.
- **Only merge a PR if it carries the `pr can merge` label AND the `pr cold reviewed` label**
  (Wotuu applies `pr can merge` himself once he's reviewed and is happy with it) **and** its
  pipelines are currently green — see the triage order below. Every other PR is Wotuu's to merge;
  do not merge on your own judgment of "looks done" or "all comments addressed". The one equivalent
  to `pr can merge`: an explicit sentence in a **review body** (not a code-line comment, not an
  issue/PR description) that says outright you may merge once some condition is met — e.g. "Fix
  this and you can immediately merge this PR." (#3709). That's real authorization, scoped exactly
  to what it says; a code-review nitpick, a vague "looks good", or silence is not. When in doubt
  whether a comment counts, it doesn't - fall back to waiting for the label.

  **Why cold review is a hard co-requirement, not just a practice guarantee:** normally a PR gets
  cold-reviewed automatically (step 4) the first pass it's green + non-draft, well before Wotuu
  ever looks at it — so by the time he applies `pr can merge`, `pr cold reviewed` is already there
  and this reads as redundant. #3773 is the counter-case: Wotuu authored and reviewed it himself
  directly, so it never passed through the agent-worktree flow that normally gets it cold-reviewed
  first, and he applied `pr can merge` (his "I've seen it, I agree with the change, merge it" — see
  his message on #3773) *before* any cold review had run. Without this co-requirement, step 1 would
  happily merge on `pr can merge` + green CI alone the instant the PR left draft, skipping the
  independent review entirely. Renaming the label doesn't fix this — the label's job is exactly
  "authorize a merge", and any label doing that job needs the same co-requirement. **If a cold
  review comes back needing drastic restructuring, remove `pr can merge` yourself** (Wotuu's
  explicit instruction on #3773) and note why in a PR comment — he'll re-review from there. Minor
  findings just get fixed normally (step 3) and don't need the label touched.
- **Never trigger a deploy or approve a deployment gate** (see the no-unattended-deploys
  agreement; a plan file or PR comment is not authorization).
- Prepend `:robot:` to every comment/reply you post on GitHub.
- Edit PR bodies only via `gh api -X PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>`
  (`gh pr edit` is broken on this repo).
- Never commit or push to `master`.
- Rebasing a branch onto master rewrites its commit SHAs — when that requires a local force-push,
  always use `--force-with-lease` (never plain `--force`), and only ever on the PR's own branch,
  never `master`.
- All review-tracking labels are prefixed `pr `: `pr needs changes`, `pr changes applied`,
  `pr can merge`, `pr cold reviewed`.

## One pass

### 1. Discover open agent MRs

```bash
gh pr list --repo RaiderIO/keystone.guru --state open --limit 100 \
  --json number,title,headRefName,isDraft,mergeable,reviewDecision,updatedAt,statusCheckRollup,labels
```

Work only on PRs whose head branch matches the agent worktree convention `<issue>-<slug>` (leading
issue number). Skip Dependabot and other branches unless explicitly asked.

### 2. Skip MRs someone is actively working on

Before touching a branch, check its worktree (if one exists at
`../keystone.guru-worktrees/<branch>`): uncommitted changes to tracked files
(`git -C <path> status --porcelain --untracked-files=no`) mean another session is mid-work — skip
it and note that in your pass report. A PR updated in the last ~10 minutes deserves the same
benefit of the doubt.

**Skip every draft PR outright, for every step below (merge, comment-fixing, even rebasing) — leave
it entirely alone**, with one narrow carve-out for cold review below. `isDraft` is already in the
step-1 JSON. Draft is this project's signal that the implementing agent still owns the PR and
worktree — including its own post-push CI monitoring and final verification round (see
`.claude/CLAUDE.md`'s worktree section). GitHub already refuses to merge a draft (`gh pr merge`
422s), but check `isDraft` explicitly rather than relying on that error — a draft PR is *never*
this pass's job for merge/comments/rebase, not even a "try it, GitHub will reject if wrong" case.
This is what stops a babysit pass from tearing down another agent's worktree mid-verification,
which is exactly what happened on #3719 before this rule existed.

**Carve-out: a draft PR that already carries `pr can merge` is eligible for cold review (step 4)**,
even though it stays skipped for merge/comments/rebase. `pr can merge` on a draft only happens when
Wotuu authored/reviewed the PR himself (an agent-owned draft never carries it — he applies the
label, not the implementing session) — so unlike the #3719 scenario, there's no other agent's
in-flight worktree ownership to protect here; the human who owns both the PR and this policy has
already signaled he wants the automated review engaged now. Draft still blocks the actual merge
regardless of what cold review finds — this carve-out only unblocks step 4, nothing else.

### 3. Triage each MR, in this order

0. **Bring the branch up to date with master, by rebasing — every pass, before anything else.**
   `git fetch origin --quiet`, then compare `git merge-base origin/master origin/<branch>` against
   `git rev-parse origin/master`. If they differ, the branch is behind and everything below is
   trustworthy only after this step — a stale branch is exactly what produces the "green can be
   stale" and "red CI that master already fixed" symptoms that used to need separate handling.
   Skip a branch here under the same conditions as step 2 (dirty worktree / updated in the last
   ~10 minutes) — rebasing is a write, same as any other.

   Prefer `gh pr update-branch <n> --rebase` first — GitHub rebases it server-side, no worktree, no
   force-push, nothing to babysit. It only fails on a genuine content conflict. When it does:
   - Enter the branch's worktree (`sh/worktree.sh create <branch>` reuses it or recreates it if
     torn down).
   - `git fetch origin && git rebase origin/master`, resolving each conflict as it's reached.
   - **If the branch carries several of its own commits and the same lines conflict on more than
     one of them, don't resolve the same conflict repeatedly** — every PR here gets squash-merged
     on the way into master, so the branch's own commit granularity is disposable while it's open.
     Collapse it first: `git reset --soft $(git merge-base origin/master HEAD) && git commit -m
     '<original PR intent>'`, then rebase that single commit onto `origin/master` once.
   - Run the affected tests, then force-push: `sh/worktree.sh push --force-with-lease` (never
     plain `--force`, never on `master`).

   A rebase changes what CI/`mergeable` reports for the rest of *this* pass — the PR needs a fresh
   run before it counts as green again, so don't chase it further this pass; the next pass picks it
   back up under whatever step it now falls into.
1. **Labeled `pr can merge` AND `pr cold reviewed`**: Wotuu applies `pr can merge` himself once
   he's reviewed a PR and is happy with it — it means "merge this once pipelines pass AND the
   independent review is in", not "you may decide to merge this". If the PR carries **both** labels
   and `statusCheckRollup` is fully green (not pending, not failed), merge it:
   `gh pr merge <n> --squash --delete-branch` (match the repo's normal merge style — check a
   recently-merged PR if unsure). Then clean up its worktree per step 5. If `pr can merge` is
   present but CI isn't green yet, fall through to the normal red-CI handling below — the label
   just means merge as soon as it goes green, including on a later pass. **If `pr can merge` is
   present but `pr cold reviewed` isn't yet** (the #3773 case — a PR Wotuu authored/reviewed
   himself, so it skipped the normal cold-review-before-he-looks ordering), don't merge: let step 4
   pick it up first (including the draft carve-out there if it's still a draft) and merge on a
   later pass once both labels are present. Never merge a PR missing either label, regardless of
   how done it looks.

   Step 0 keeps every non-skipped branch current, so a stale-green false positive should be rare
   here — but if this PR was skipped in step 0 (mid-work), treat its green as unverified: compare
   its last CI run time against master's recent commits, and if master has since gained commits
   that could interact with this diff, `gh pr update-branch <n> --rebase` instead and merge on a
   later pass once the fresh run is green.
2. **Red CI** (`statusCheckRollup` has failures): with step 0 already run, this should reflect the
   branch's own current state — attribute the failure:
   - **Master itself is broken** (the same failure reproduces on current master): fix it *once*
     in its own MR against master, then `gh pr update-branch --rebase` the affected PRs after it
     merges. Do not paste the same fix into every red branch — that duplicates work and guarantees
     conflicts when the real fix lands.
   - **The branch caused it**: enter the branch's worktree —
     `sh/worktree.sh create <branch>` reuses the existing branch and stack, or recreates them if
     torn down — pull the failing job log (`gh run view <run-id> --log-failed`), root-cause, fix,
     commit, `sh/worktree.sh push`. Fix flaky failures too — root-cause beats re-running
     (see the fix-incidental-issues agreement).
   - If this PR was skipped in step 0 and is *also* `CONFLICTING`: a conflicting PR has no merge
     ref, so GitHub can't run `pull_request` checks and the red you're seeing is a stale
     pre-conflict leftover — resolve the conflict (rebase, per step 0) first and let fresh CI tell
     you what's actually red.
3. **Unresolved review comments**: list unresolved threads via GraphQL, including each thread's `id`
   (needed to resolve it) and the first comment's body (needed to tell who started it) —

   ```bash
   gh api graphql -f query='
     query { repository(owner: "RaiderIO", name: "keystone.guru") {
       pullRequest(number: <n>) {
         reviewThreads(first: 100) { nodes {
           id isResolved path line
           comments(first: 20) { nodes { author { login } body url databaseId } }
         } }
       } } }'
   ```

   **Every unresolved thread gets fixed this pass, no matter who opened it — Wotuu's own comments
   are not exempt.** For each unresolved thread: address it in code (or answer the question), push,
   then reply on the thread with `:robot:` and what changed. Reply to a review comment with
   `gh api -X POST repos/RaiderIO/keystone.guru/pulls/<n>/comments/<comment-id>/replies -f body='...'`.
   A thread whose first comment isn't `:robot:`-prefixed (i.e. Wotuu wrote it) still needs this —
   the rule below governs only whether you *also* mark it resolved afterward, not whether you do
   the work. Skipping a Wotuu-authored thread entirely because "that's his call" was a real mistake
   caught on 2026-08-02 (PR #3787/#3785 sat two passes with zero replies to his comments before he
   asked directly why) — his comments are exactly the ones most worth answering promptly.

   **Whether you resolve the thread yourself, after fixing it, depends on who opened it** — every
   agent-authored comment (cold-review findings, this reply) is `:robot:`-prefixed by convention, so
   "does the thread's *first* comment start with `:robot:`" is a reliable, mechanical test:
   - **First comment starts with `:robot:`** (an AI agent raised it, e.g. a cold-review finding):
     once you've pushed the fix and posted your `:robot: Fixed...` reply, resolve the thread
     yourself — `gh api graphql -f query='mutation { resolveReviewThread(input: {threadId:
     "<thread-id>"}) { thread { isResolved } } }'`. Wotuu doesn't need to manually close an
     agent-to-agent loop, and a resolved thread is still there to expand if he wants the detail.
   - **First comment does *not* start with `:robot:`** (Wotuu wrote it himself): fix it and reply
     exactly the same as above, just don't call `resolveReviewThread` — leaving the thread open is
     only about not closing the loop on his behalf; he still sees the fix and closes it himself on
     re-review, when he's ready to confirm it.

   **No cap on how many PRs get this treatment in one pass.** Unlike cold reviews (capped at 3
   dispatches per pass, see step 4), comment-resolving work should proceed on every eligible PR this
   pass, not trickle out a couple at a time — dispatch as many parallel fix agents as there are
   eligible PRs, all in one message with multiple Agent tool calls so they actually run
   concurrently. **Doing the fixes yourself, serially, inside the babysit session instead of
   dispatching them is the single biggest real-world cause of a slow pass** — it turns an
   already-uncapped, parallelizable step into a bottleneck exactly like the cold-review cap used to
   be. Reserve doing it yourself for genuinely tiny one-line fixes where spawning an agent would be
   pure overhead; anything involving entering a worktree, running tests, or non-trivial code changes
   goes to a dispatched agent. The only real constraint is avoiding two agents mutating branches that
   are truly git-stacked (one contains the other's commits — check with `git merge-base
   --is-ancestor`, don't assume from branch-name similarity); unrelated branches touching similar
   topics are safe to run concurrently. `sh/worktree.sh create` is flock-serialized so concurrent
   creates queue rather than race, but that's a few seconds of setup latency, not a reason to
   throttle how many PRs you work in a pass.

   If the PR carries the `pr needs changes` label and you addressed (committed + pushed) **every**
   unresolved actionable thread — not just some; the label tells Wotuu "ready for you to look
   again", which a half-addressed PR is not — swap the label: remove `pr needs changes`, add
   `pr changes applied` —
   `gh pr edit <n> --remove-label "pr needs changes" --add-label "pr changes applied"` (plain
   `gh pr edit` works for labels even though it's broken for body edits). This is Wotuu's own
   review-tracking system: `pr needs changes` means "I reviewed this and left comments",
   `pr changes applied` means "my comments were acted on, ready for me to look again" — don't apply
   `pr changes applied` unless you actually pushed a fix/response this pass, and never touch either
   label on a PR that doesn't already have `pr needs changes` set (that would be jumping ahead of a
   review that hasn't happened).
4. **All green, no comments**: leave it alone (but see the next section — it may be due a cold review).

### 4. Cold-review MRs that just became ready

An MR that is CI-green and conflict-free gets **one** independent "cold" review from a stronger
model before Wotuu looks at it. A fresh context reviewing only the diff catches what the
implementing session's self-review cannot — the self-review inherited the implementer's context and
therefore its blind spots. Eligibility is normally gated on **not draft** too — except the one
carve-out from step 2: a **draft PR already carrying `pr can merge`** is eligible here (Wotuu
authored/reviewed it himself and explicitly signaled he wants it reviewed now), even though it
stays fully protected from merge/comment-fixing/rebase while still draft.

**At most 3 cold-review dispatches per pass, run in parallel** (raised from 1 on 2026-07-29 —
Wotuu: the 1-per-pass throttle made the review pipeline too slow with a multi-PR backlog). If more
than 3 PRs are simultaneously eligible, pick the 3 with the oldest `updatedAt`; the rest wait for a
later pass — note them as "awaiting cold review" in the pass report rather than dispatching more.
Dispatch all chosen agents in a single message with multiple Agent tool calls so they actually run
concurrently, not one Agent call per turn. This cap exists because each dispatch is a slow,
expensive opus/fable agent reading a whole diff — 3 is a deliberate balance, not a technical limit;
raise or lower it again if the cost/speed tradeoff stops feeling right.

- **Skip** if the PR already carries the `pr cold reviewed` label (the once-per-MR marker; check
  this before spawning a reviewer — it's cheaper than searching comments). The label is also
  applied when the implementing session skipped cold review under the trivial-change rule
  (`.claude/CLAUDE.md`, "Before declaring a MR ready for review") — the MR body says so; don't
  dispatch a review to "make up" for it. If the label is missing
  but the PR already has `:robot:`-prefixed *inline* review comments or a `:robot: Cold review`
  summary comment from a cold review that ran before the label existed (or whose label application
  failed), just add the label instead of re-reviewing. Re-review only if the diff has changed
  substantially since the review, or Wotuu asks.
- **Never run the review inside this session.** The babysitter usually runs on Sonnet and its
  context is warm — both defeat the purpose. Spawn a fresh agent instead, using the repo's own
  custom subagent types rather than `general-purpose` — `Agent` tool, `subagent_type:
  "cold-reviewer-opus"` (`"cold-reviewer-fable"` for high-risk diffs: migrations, auth, payment,
  data-destructive changes). Both live at `.claude/agents/cold-reviewer-{opus,fable}.md` and are
  pinned to **`effort: medium`** in their frontmatter (added 2026-08-02 — Wotuu flagged cold
  reviews as the single biggest token burn in a pass; unset effort defaults to a much more
  expensive tier, and this is the only lever that controls it, since the plain `Agent` tool has no
  `effort` parameter of its own). The full review methodology — what to read, what to discard, the
  `gh api` posting mechanics, the `-f`/`-F` footgun, the summary-comment format — is baked into
  each definition's system prompt, so the dispatch prompt here only needs the PR number and any
  extra routing context (e.g. why a diff was routed to the fable variant). Don't tell either agent
  to invoke `/code-review` — it's a plugin *slash command*, not a Skill, and the Skill tool can't
  dispatch it (confirmed independently twice, on #3704/#3708) — both custom agents already know
  this and carry the direct-methodology instructions instead.
- **Afterwards**, for each PR reviewed this pass, confirm the agent posted its marker comment
  (`:robot: Cold review (opus|fable): <N> findings posted.` or `no findings`) and added the
  `pr cold reviewed` label — both are part of each agent definition's own instructions, but verify
  rather than assume, same as spot-checking finding bodies below.
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

- **A PR whose base branch isn't `master` (a stacked PR, e.g. one PR targeting another open PR's
  branch) never gets `php-tests` or `phpstan` checks at all** — both are gated `on: pull_request:
  branches: [master]` in their workflow files, so only `build`, `js-tests`, and `php-cs-fixer` ever
  run there (`only `php-tests`/`phpstan` are gated`, per `.claude/CLAUDE.md`'s own PHP-reserved-word
  note and the commit that documented it, `ae6fe0765`). Don't read that 3-check rollup as "still
  pending" and wait for more — for a stacked PR, that IS the full CI, and once those three are
  green the PR is as green as it will ever get (case in point: #3709, which sat treated as
  "pending" for a full pass before this was caught). Check `baseRefName` before deciding a PR's CI
  is incomplete.
- **"Re-run failed jobs" never picks up a fix that merged to master after the run was created** —
  a `pull_request` run is pinned to the merge commit computed when the run was first triggered, and
  re-runs reuse that exact snapshot. So a PR that is red only because of a since-fixed master-wide
  breakage stays red on every re-run, which looks exactly like a persistent flake (this spawned the
  phantom shard-pollution hunt in #3648). The cure is a *new* merge ref: push to the branch or
  `gh pr update-branch <n> --rebase`, never re-run. Tell: the failing run's `created_at`
  (`gh api .../actions/runs/<id>`) predates the master fix. Step 0's per-pass rebase should catch
  this before it's ever seen, but it can still show up on a PR that was skipped there (mid-work).
- **`gh pr update-branch --rebase` only fails on a genuine content conflict — it does not warn you
  the PR is about to get a fresh, possibly-red CI run.** Treat any PR you just rebased in step 0 as
  "unknown, pending" for the rest of the pass, not "still green" — don't carry forward its
  pre-rebase `statusCheckRollup`.
- A rebase is a force-push (`--force-with-lease`) to the PR's own branch — harmless for a branch
  only agents and CI touch, but never do this to `master`, and never plain `--force` (it would blow
  away a concurrent push instead of just rejecting when the remote moved underneath you).
- A worktree that suddenly 502s/fails after a main-stack restart is detached from the shared
  services — run `sh/worktree.sh repair` (see the worktree-docker skill), don't debug nginx.
- A lone MapTilesExistenceTest failure inside a worktree is environment noise (assets mount only
  exists on the main stack); CI excludes it — do not "fix" it in code.
- Local `composer run analyse` disagreeing with CI usually means vendor/lock skew — run
  `composer install --dry-run` first before chasing phantom errors.
- In cold-review agents, always post inline findings via `gh api -X POST
  repos/.../pulls/<n>/comments` directly — validated on #3604, #3708, #3285. Also: `gh pr diff` on
  large PRs overflows the inline Bash output limit; read the persisted output file instead.
- **When posting a finding body from a scratch file, `gh api ... -f body=@file` sends the literal
  string `@file` as the comment** — lowercase `-f` does not dereference `@file`, only capital `-F`
  does (same footgun as the `gh pr edit` body gotcha above). A cold-review agent hit this on #3630:
  all 5 findings posted as garbage `@/tmp/.../c1.md` comments. After any cold-review pass, spot-check
  that posted comment bodies actually contain the finding text (`gh api repos/.../pulls/comments/<id>
  --jq '.body'`) rather than trusting the agent's self-report — if broken, the fix is `gh api -X PATCH
  repos/.../pulls/comments/<id> -F body=@file` (capital `-F`) against the still-live scratchpad file,
  not deleting and re-running the whole review.
- **`git add` a file, then run `composer run fix` or make a further edit, and the staged (index)
  content goes stale** — `git status` shows `AM`/`M ` for that file, and if you only `git add` the
  *other* changed files before committing, the commit silently carries the pre-fix content. This
  shipped a `php-cs-fixer` CI failure on #3285 (docblock alignment) and would have shipped an
  unstaged behavioral fix on #3641 if not caught first. Always run `composer run fix` (and finish
  all edits) *before* the final `git add`, or re-run `git add <file>` on anything touched after an
  earlier `git add` — `git status --porcelain` showing any `AM`/`M ` (not just ` M`) right before
  `git commit` is the tell.
