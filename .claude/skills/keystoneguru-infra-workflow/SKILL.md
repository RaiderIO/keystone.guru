---
name: keystoneguru-infra-workflow
description: >
  GitHub issue/PR conventions for the sibling RaiderIO/keystoneguru-infra repo — branch naming,
  `Closes #<issue>` linking, labels, draft-PR workflow, and why merging there is a production
  deploy no agent may perform. Use whenever creating or working an issue/PR in keystoneguru-infra.
  NOT for CDK/deploy mechanics (that repo's own skill).
---

# keystoneguru-infra GitHub workflow

`RaiderIO/keystoneguru-infra` (sibling checkout `/home/wouterkoppenol/Git/private/keystoneguru-infra`)
historically did not follow keystone.guru's issue/PR discipline — branch naming, label usage and
`Closes #` linking were all inconsistent (checked 2026-07-31: roughly half of recent PRs used
`Closes #`, half didn't; no review-tracking labels existed at all). This skill ports that discipline
over, adapted for a repo with **no Docker worktree stack** (it's a CDK/TypeScript infra app — plain
git branches/worktrees, no isolated dev environment to spin up per branch).

This skill governs **labels and PR/issue hygiene**. You may commit to a feature branch, push it,
and open a **draft** PR without asking (mirroring keystone.guru's worktree autonomy — plain git
branches stand in for worktrees here). What you may **never** do is merge or deploy — see the merge
gate below; nothing here overrides the standing "no deploy without explicit user say-so" agreement
(`feedback_no_unattended_prod_deploys`).

**Two things differ from keystone.guru and both matter:**

- **The default branch is `main`, not `master`.** PRs target `main`; keystone.guru's
  "always target `master`" rule does not carry over. `Closes #<issue>` only auto-closes on PRs
  targeting the default branch.
- **Merging IS a deploy.** `.github/workflows/deploy-on-merge.yml` runs `npx cdk deploy` on every
  push to `main` touching `cdk/**` — and `keystoneguru-services` is shared by staging AND
  production, so a merge can roll production services directly.

## Branch naming & issue linking

- Branch format: `<issue number>-<slug-description>` (e.g. `44-split-staging-stack`), same as
  keystone.guru. Already loosely followed here — keep doing it, consistently.
- **Every PR body must start with `Closes #<issue>`** (or `Relates to #<issue>` when the PR
  deliberately should *not* close the issue on merge — e.g. a part-1-of-2 split) so merging
  auto-closes the issue. This is the single biggest gap found in this repo's history: many merged
  PRs left their issue open because nothing linked them.

## `in progress` label — manual (no `worktree.sh` here)

keystone.guru's `sh/worktree.sh` auto-toggles this label on the issue whenever a worktree is
created/removed (`set_wip_label()` in that script, keyed off the branch's leading issue number).
This repo has no equivalent script, so do it by hand at the same two moments:

- **Starting work on an issue**: `gh issue edit <n> --repo RaiderIO/keystoneguru-infra --add-label "in progress"`
- **Finishing** (PR merged, PR closed without merging, or work abandoned):
  `gh issue edit <n> --repo RaiderIO/keystoneguru-infra --remove-label "in progress"`

If the branch has no leading issue number (rare — most infra work should have a tracking issue),
skip the label; there's nothing to toggle it on.

## Draft PR workflow

Mirror keystone.guru's worktree convention even without the worktree tooling itself:

- Open every PR as a **draft** (`gh pr create --draft`) while you still own it — including your own
  post-push CI monitoring and verification round.
- A MR is only "ready" once:
  1. **Independent review** — see Cold review below.
  2. **Verification appropriate to the change** — for CDK changes, run
     `npx cdk diff <stack> --exclusively` and read the output (note: `cdk diff` writes to stderr;
     full recipe in the infra repo's own `keystoneguru-infra` skill at
     `/home/wouterkoppenol/Git/private/keystoneguru-infra/.claude/skills/keystoneguru-infra/SKILL.md` —
     it lives in the sibling checkout, so Read it as a file, it is not loadable via the Skill tool
     from a keystone.guru session). There's no UI here, so no visual/screenshot step; for anything
     touching the deploy pipeline itself, a careful reading of the workflow YAML.
  3. **CI, if any applies** — this repo currently has **no PR-triggered CI at all** (only
     `deploy-on-merge.yml`, which fires post-merge). "No checks" is the normal state, not green —
     never treat the absence of checks as a passing signal; the `cdk diff` in item 2 is the real
     pre-merge verification.
- Only then mark it ready: `gh pr ready <n>`.

## Labels

Same six labels and semantics as keystone.guru — four review-tracking (`pr `-prefixed) plus the
`in progress` and `follow-up` lifecycle labels (created in this repo 2026-07-31, identical colors;
the `in progress` description drops keystone.guru's worktree-specific wording):

| Label | Meaning |
|---|---|
| `in progress` | Issue is actively being worked on. |
| `pr cold reviewed` | An AI agent has done an independent review pass and posted its findings as inline comments. Once-per-PR marker — don't re-review unless the diff changed substantially or asked to. |
| `pr needs attention` | A human reviewer looked and left feedback that needs attention — a question or suggestion, not necessarily a required change. |
| `pr comments addressed` | Every actionable item from a `pr needs attention` round has been addressed (pushed, or answered); ready for re-review. Only apply this after actually pushing a fix/response — never speculatively. |
| `pr can merge` | The PR owner has reviewed and is happy — "merge once pipelines pass AND cold review is in," not "you may decide to merge this." **Only the human applies this label.** |
| `follow-up` | Issue spun off from another issue/PR's review or discussion. |

**Merge gate — stricter than keystone.guru's**: in this repo there is **no
"mergeable-by-automation" state at all**. Merging pushes `main`, and `deploy-on-merge.yml` turns
that push into a `cdk deploy` that can roll **production** services — so a merge here is an
unattended production deploy, which the standing `feedback_no_unattended_prod_deploys` agreement
forbids regardless of labels. `pr can merge` + `pr cold reviewed` retain their keystone.guru
*meanings* (owner sign-off, review done) as communication between human and agents, but they never
authorize an agent to merge: **only the human merges in this repo, every time.** A review-body
sentence authorizing merge doesn't override this either — treat it as "ready for the human to
merge", and say so rather than merging.

## `:robot:` comment prefix

Prepend `:robot:` to every comment/reply an agent posts on GitHub here too (PR/issue comments,
review replies, PR/issue bodies) — same reasoning as keystone.guru: it marks agent-authored content
so it's never mistaken for the account owner speaking. Never on titles.

## Cold review

There is no automated `babysit-prs`-equivalent loop running against this repo (yet — if that's
wanted, it would be a separate, larger skill). Cold review here is currently **on-demand**: when a
PR is conflict-free and not already `pr cold reviewed` (there is no CI-green precondition — this
repo has no PR-triggered CI, see above), dispatch a fresh-context agent
(`Agent` tool, `subagent_type: "general-purpose"`, `model: "opus"` — deliberately NOT the
`cold-reviewer-*` custom agents `babysit-prs` uses, because their definitions hardcode
`RaiderIO/keystone.guru` repo paths and would post to the wrong repo; this also loses their
`effort: medium` pin, so tell the dispatched agent to keep the review tightly scoped) with the same
methodology keystone.guru's `babysit-prs` step 4 uses — read the diff (persist large diffs to a scratch file
first, `gh pr diff` overflows inline output), check for correctness bugs and anything git
blame/history would flag, post only genuinely-confident findings as inline comments
(`gh api -X POST repos/RaiderIO/keystoneguru-infra/pulls/<n>/comments`, `:robot:`-prefixed, capital
`-F body=@file` — lowercase `-f` sends the literal string `@file`, spot-check after posting), then
post the marker comment `:robot: Cold review (opus): <N> findings posted.` (the
`cold-reviewer-opus` marker format, kept identical across both repos so marker searches match) and add
`pr cold reviewed`. No formal GitHub review (no approve/request-changes) — comments only.

## Gotchas

- Unlike keystone.guru, `gh pr edit`/`gh pr comment`/label edits have not been observed to fail on
  this repo (no legacy Projects-classic board wired up here as of 2026-07-31). If one does start
  failing with a Projects-classic GraphQL deprecation error, fall back to the same workaround:
  `gh api -X PATCH repos/RaiderIO/keystoneguru-infra/pulls/<n> -F body=@<file>` (capital `-F`).
- This repo has no shared dev database or Docker stack to isolate per branch, so there's no
  `worktree.sh`-equivalent need for isolated environments. If concurrent work on two branches is
  ever needed, a plain `git worktree add ../keystoneguru-infra-worktrees/<branch>` is sufficient —
  no isolation concerns beyond the usual one-branch-at-a-time git rules.
