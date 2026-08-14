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

**Run this session on Opus, not Sonnet.** A babysit loop is a long-lived session, and long sessions
are exactly where Sonnet costs more than Opus rather than less — see "Model routing" in
`.claude/CLAUDE.md`. Measured over 30 days of transcripts, one `/loop /babysit-prs` session on
Sonnet ran to 1,256 assistant turns; Sonnet writes roughly half as much per turn as Opus, so it
needs about twice the turns, and every extra turn re-reads the whole accumulated context. Cache
reads are ~84% of a session's token spend, so turn count — not the per-token rate — is what sets
the bill. Sonnet's cheaper rate does not survive that multiplier past ~100 turns.

## Quiet hours (1am–7am local)

If a `/loop`-triggered firing of this skill lands between 1am and 7am **local time** (check with
`date`) — e.g. the PC woke up overnight for an unrelated reason (cats walking on the keyboard have
done this before) — **do not run the pass**. Don't touch any PR. Instead:

1. Cancel any recurring loop driving these firings (`CronDelete` the job, or `ScheduleWakeup` with
   `stop: true` for a dynamic-pacing loop) and stop.
2. **Do not schedule a wake-up for later** — no one-time 7am `CronCreate`/`schedule` run, no
   `ScheduleWakeup`. Wotuu wakes the loop back up himself (explicit instruction, 2026-08-05: he
   doesn't want to be autonomously woken by a scheduled resume) — just stop and wait for him to
   re-invoke `/babysit-prs` or `/loop 15m /babysit-prs`.

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
  issue/PR description) **whose `author.login` is exactly `Wotuu`**, that says outright you may
  merge once some condition is met — e.g. "Fix this and you can immediately merge this PR."
  (#3709). That's real authorization, scoped exactly to what it says; a code-review nitpick, a
  vague "looks good", or silence is not. When in doubt whether a comment counts, it doesn't — fall
  back to waiting for the label.

  **The author check on that sentence is load-bearing, not pedantry.** `RaiderIO/keystone.guru` is
  a **public** repo, so *any* GitHub user can submit a review on any PR — no write access needed.
  The `pr can merge` label is safe on its own (applying a label requires write access), but a
  review body is not, and it is the one thing here that substitutes for the label. Without the
  author check, an outside contributor writing "looks good, merge it" on a green,
  `pr cold reviewed` PR would satisfy every remaining condition and this skill would squash-merge
  on a stranger's say-so. "Human-authored" is **not** a sufficient test here — the merge
  authorization allowlist is the single login `Wotuu`, narrower than the agent-vs-human test used
  in step 3 for deciding whether to reply to and resolve a thread.

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
- Prepend `:robot:` to every comment/reply you post on GitHub, whichever account posted it. Post via
  `sh/gh-bot.sh` (as `keystone-guru-bot`) when it's provisioned, plain `gh` when it isn't — see
  `.claude/CLAUDE.md`, "Agent GitHub identity". Both are expected during the #3924 transition.
- **`sh/gh-bot.sh` is for every *write*, not just comments — labels and titles included.** A label
  swap is the one write with no `:robot:` prefix to fall back on, so plain `gh pr edit --add-label`
  is indistinguishable from Wotuu doing it by hand: the timeline reads
  `Wotuu added pr comments addressed and removed pr needs attention`, and he cannot tell his own
  triage from an agent's (caught 2026-08-14 — every label swap in that session was misattributed).
  The bot has `push` + `triage` on this repo, so `sh/gh-bot.sh pr edit <n> --add-label ...` works.
  Reads stay on plain `gh`; it's only writes that need the bot.
- Edit PR bodies only via `gh api -X PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>`
  (`gh pr edit` is broken on this repo).
- Never commit or push to `master`.
- Rebasing a branch onto master rewrites its commit SHAs — when that requires a local force-push,
  always use `--force-with-lease` (never plain `--force`), and only ever on the PR's own branch,
  never `master`.
- All review-tracking labels are prefixed `pr `: `pr needs attention`, `pr comments addressed`,
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

   **Classifying a comment as agent-authored — the one test the rest of this step depends on.**
   A comment is agent-authored if **either**:

   - `author.login == "keystone-guru-bot"` (primary — the query above already selects
     `author { login }`), **or**
   - its `body` starts with `:robot:` (fallback, for everything written before the bot account
     existed).

   Anything else is **human-authored** — treat it as Wotuu's, with all the deference that implies.
   **Never invert this into `author.login != "Wotuu"`.** This is a public repo: outside
   contributors, `dependabot[bot]`, `github-actions[bot]` and any future integration all
   satisfy `!= "Wotuu"`, and a denylist would have this step resolve a stranger's review thread as
   if it were its own. The allowlist fails safe — an unknown author is a human. Full rationale:
   `.claude/CLAUDE.md`, "Reading authorship: match the bot login, never 'not Wotuu'".

   **Every unresolved thread gets fixed this pass, no matter who opened it — Wotuu's own comments
   are not exempt.** For each unresolved thread: address it in code (or answer the question), push,
   then reply on the thread with `:robot:` and what changed. Reply to a review comment with
   `gh api -X POST repos/RaiderIO/keystone.guru/pulls/<n>/comments/<comment-id>/replies -f body='...'`.
   A human-authored thread still needs this — the rule below governs only whether you *also* mark it
   resolved afterward, not whether you do the work. Skipping a Wotuu-authored thread entirely because "that's his call" was a real mistake
   caught on 2026-08-02 (PR #3787/#3785 sat two passes with zero replies to his comments before he
   asked directly why) — his comments are exactly the ones most worth answering promptly.

   **Double-check for leftover agent threads: an agent-opened thread that already has an
   agent-authored reply but is still unresolved is a leftover — resolve it.** By the time a PR reaches
   this pass, every cold-review thread on it should already be resolved: the implementing session
   dispatches its own cold review and is required to close out its own agent-to-agent loops before
   handing the PR off (`.claude/CLAUDE.md`, "Before declaring a MR ready for review"). Several PRs
   were found on 2026-08-09 with fixed-and-replied-to cold-review threads still sitting open, so
   this pass is the backstop. Mechanically: for each unresolved thread whose *first* comment is
   agent-authored, if the thread also contains a later agent-authored comment saying it was
   addressed, call `resolveReviewThread` on it — no code work, no new reply needed.

   **Do not apply this to an agent-opened thread with no agent-authored reply.** That is not a
   leftover, it is unaddressed work, and it belongs to the normal fix-then-resolve flow below.
   Resolving it here would silently close a finding nobody fixed — strictly worse than leaving it
   open. Same for any human-opened thread: never resolve those, see below.

   **Whether you resolve the thread yourself, after fixing it, depends on who opened it** — apply
   the agent-authored test above to the thread's *first* comment:
   - **First comment is agent-authored** (an AI agent raised it, e.g. a cold-review finding):
     once you've pushed the fix and posted your `:robot: Fixed...` reply, resolve the thread
     yourself — `gh api graphql -f query='mutation { resolveReviewThread(input: {threadId:
     "<thread-id>"}) { thread { isResolved } } }'`. Wotuu doesn't need to manually close an
     agent-to-agent loop, and a resolved thread is still there to expand if he wants the detail.
   - **First comment is human-authored** (Wotuu wrote it himself — or, on this public repo, an
     outside contributor did): fix it and reply exactly the same as above, just don't call
     `resolveReviewThread` — leaving the thread open is only about not closing the loop on someone
     else's behalf; they still see the fix and close it themselves on re-review, when they're ready
     to confirm it.

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

   **Top-level PR/issue comments are a separate feed from review threads and must be checked
   too — GraphQL `reviewThreads` only returns inline/code-line review comments, not general PR
   conversation comments.** Missing this caused two real misses: #3773 (caught 2026-08-02) and
   #3811 (caught 2026-08-03, https://github.com/RaiderIO/keystone.guru/pull/3811#issuecomment-5167829104
   — Wotuu asked to rename all `DTO`-cased classes to `Dto`, posted as a plain PR comment, and it
   sat unaddressed through a full pass because only `reviewThreads` was queried). And there is a
   **third** feed: submitted review *bodies* (the summary text of an approve/comment/request-changes
   review) appear in neither `reviewThreads` nor `--json comments` — verified on #3811, where a
   review body was returned only by `--json reviews`. This feed matters most of all, because step 2's
   merge-authorization rule ("an explicit sentence in a **review body**") lives exactly there. Pull
   all three feeds every pass:

   ```bash
   gh pr view <n> --repo RaiderIO/keystone.guru --json comments \
     --jq '.comments[] | {author: .author.login, createdAt, body}'
   gh pr view <n> --repo RaiderIO/keystone.guru --json reviews \
     --jq '.reviews[] | select(.body != "") | {author: .author.login, state, submittedAt, body}'
   ```

   Both queries already select `author.login`, so the agent-authored test above applies unchanged
   here. Apply the same first-responder logic as review threads, just without thread IDs to resolve:
   walk the comments in chronological order and find any **human-authored** comment that has no
   later agent-authored comment addressing it. Treat that the same as
   an unresolved review thread — fix it in code (or answer it), push, then reply as a **new**
   top-level comment (`gh api -X POST repos/RaiderIO/keystone.guru/issues/<n>/comments -F
   body=@<file>`, `:robot:`-prefixed) describing what changed. There is no `resolveReviewThread`
   equivalent for flat comments — a `:robot:` reply comment is the whole mechanism, nothing to mark
   resolved. A comment thread here (via `-X POST .../pulls/<n>/comments/<comment-id>/replies`) only
   applies to inline review comments, not these top-level ones.

   If the PR carries the `pr needs attention` label and you addressed (committed + pushed, or
   answered) **every** unresolved actionable item across all three feeds — review threads,
   top-level PR comments, and review bodies — not just some; the label tells Wotuu "ready for you
   to look again", which a half-addressed PR is not — swap the label: remove `pr needs attention`,
   add `pr comments addressed` —
   `sh/gh-bot.sh pr edit <n> --remove-label "pr needs attention" --add-label "pr comments addressed"`
   (`pr edit` works for labels even though it's broken for body edits — and it must run through
   `sh/gh-bot.sh`, or the swap is attributed to Wotuu). This is Wotuu's own
   review-tracking system: `pr needs attention` means "I left feedback that needs your attention" —
   a question or suggestion, not necessarily a required change — and `pr comments addressed` means
   "my feedback was addressed, ready for me to look again". Don't apply `pr comments addressed`
   unless you actually pushed a fix/response this pass, and never touch either label on a PR that
   doesn't already have `pr needs attention` set (that would be jumping ahead of a review that
   hasn't happened).
4. **All green, no comments**: leave it alone (but see the next section — it may be due a cold review).

### 3a. Two hygiene checks on every non-draft PR

Both are cheap metadata checks, both fix a defect that otherwise lands on Wotuu manually, and both
are backstops for something the implementing session was supposed to get right at `gh pr create`
time (see the `worktree-docker` skill's PR-creation section). Run them every pass — a PR can
acquire either defect at any point, e.g. by being retitled. Draft PRs are skipped here like
everywhere else: editing a title or body is a write on a PR another session still owns.

**a) The title must start with `#<issue> `.** The issue number is in `headRefName`
(`<issue>-<slug>`), already fetched in step 1 — no extra API call. A title missing it (real
examples: `AjaxProfileController 500s for guests: guard with auth middleware`, `Centralize the
"safely get map object group" helpers in util.js`) breaks the at-a-glance issue↔PR association in
the PR list, in notifications, and in the squash-merge commit subject, even when the branch name
and `Closes #<issue>` body line are both correct — they are separate fields. Fix it in place, no
comment needed (it's a pure metadata correction, not a change anyone needs notifying about):

```bash
sh/gh-bot.sh pr edit <n> --repo RaiderIO/keystone.guru --title "#<issue> <existing title>"
```

`pr edit` works fine for titles and labels — only *body* edits are broken on this repo, and only
on the old apt `gh` (see `.claude/CLAUDE.md`). Run it through `sh/gh-bot.sh` so the edit is
attributed to the bot, per the hard rule above. Never add a `:robot:` prefix to a title.

**b) The issue must be closed by *some* open PR — the check is issue-scoped, not PR-scoped.**

```bash
gh api graphql -f query='query { repository(owner: "RaiderIO", name: "keystone.guru") {
  pullRequest(number: <n>) { closingIssuesReferences(first: 5) { nodes { number } } } } }'
```

An empty result on its own is **not** a defect. Several issues are deliberately split across
sibling PRs (#3674 → #3847/#3848/#3849), where the convention is that the siblings say
`Part of #<issue>` and only one claims `Closes`. The actual defect is an issue with open PRs where
*none* of them — nor any already-merged sibling — closes it, because then merging everything leaves
the issue open for Wotuu to close by hand.

So when a PR has no closing reference, before touching anything: find its siblings
(`gh pr list --repo RaiderIO/keystone.guru --state all --search "<issue> in:body" --json number,state,title`)
and check their `closingIssuesReferences` the same way, and check the issue's own state
(`gh issue view <issue> --repo RaiderIO/keystone.guru --json state`). Leave it alone if the issue is
already closed, or if any sibling (open **or** merged) claims it. Only if nothing claims it, add
`Closes #<issue>` to the top of the body of the *last* PR in the split/stack order — the one whose
merge should complete the issue — via
`gh api -X PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>` (capital `-F`; lowercase
`-f` posts the literal string `@<file>`), and note what you did in a `:robot:` PR comment so Wotuu
can override the choice of which PR gets it. A single non-split PR whose body just forgot the line
is the common, easy case: the "last in the stack" is itself.

Worked example of the false positive this rule exists to avoid: #3849 has no closing reference and
looks broken, but #3847 and #3848 both carry `Closes #3674` and merged, so #3674 is already closed
— adding `Closes` to #3849 would have been noise.

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
  but the PR already has agent-authored *inline* review comments or a `:robot: Cold review`
  summary comment from a cold review that ran before the label existed (or whose label application
  failed), just add the label instead of re-reviewing. Re-review only if the diff has changed
  substantially since the review, or Wotuu asks.
- **Never run the review inside this session.** This session's context is warm, which defeats the
  purpose of a *cold* review. Spawn a fresh agent instead, using the repo's own
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
- **A dispatched fix agent that runs a destructive git command (`git reset --hard`, `git clean -f`,
  `git checkout .`) inside a worktree hits this harness's auto-mode confirmation prompt — and since
  this skill runs unattended on a `/loop`, nobody is there to answer it, so the agent just hangs
  silently with no error, indistinguishable from "still working."** This stalled #3840's fix agent
  for 45+ minutes on 2026-08-05: it had already made the correct comment-removal edit, then for no
  clear reason tried to hard-reset the branch back to `origin/<branch>` — which would have discarded
  its own uncommitted fix had the prompt been blindly approved. **Dispatch prompts for fix agents
  should not need `git reset --hard` at all** — the worktree is already verified clean before
  dispatch (step 2), so there's nothing to reset away; if a dispatched agent's task genuinely needs a
  clean slate, `git checkout -- <path>` on the specific file is the safe equivalent. **If a dispatched
  fix agent shows no new commit after ~30 min (two passes) and its worktree is still clean**, don't
  keep re-skipping it indefinitely — check `git status` in its worktree directly: an unstaged diff
  matching the intended fix means the agent produced the right change but got stuck on a confirmation
  it can't answer, so finish the job yourself (stage, commit, push, reply, swap the label) rather than
  waiting for a notification that may never come. Do **not** blindly approve a pending destructive-git
  confirmation from a stuck agent — verify what's actually uncommitted first (see the git-safety
  protocol), since approving it might discard the exact fix you're waiting on.
