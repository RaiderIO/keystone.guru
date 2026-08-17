---
name: context-hygiene
description: Weekly, human-run sweep (/context-hygiene) that keeps Claude's per-session context lean — archive done-work memories, prune the memory index, enforce size budgets on CLAUDE.md and skill descriptions, flag contradictions. Reports every change; never runs unattended.
---

# /context-hygiene — Keep the per-session context lean

Every session pays a fixed context cost before any work starts: the chained CLAUDE.md files, the
memory index (`MEMORY.md`), and every skill's frontmatter `description:`. All of these drift
upward — each completed task adds memory files and index lines, and skill descriptions accrete
detail that belongs in the (on-demand) body. This sweep, run weekly by the user via
`/context-hygiene`, walks that drift back. It is **human-initiated only** (same pattern as
`combatlog-parse-failure-poll`) — no cron, no cloud routine.

Ground rule: **every step reports what it changed; nothing is changed silently.** End the run
with a short changelog (files archived, lines pruned, bytes saved, findings flagged).

Paths:

- Memory dir: `~/.claude/projects/-home-wouterkoppenol-Git-private-keystone-guru/memory/`
  (index `MEMORY.md`, archive subfolder `archive/`)
- Instruction files: `.claude/CLAUDE.md` (repo), plus each `.claude/skills/*/SKILL.md` frontmatter
  `description:` (per-session cost; the body below the frontmatter is loaded on demand only)

## 1. Memory index sweep

For every entry in MEMORY.md's "Active work" and "Done work" sections that references an issue/MR,
check the real state:

```bash
gh pr view <n> --repo RaiderIO/keystone.guru --json state,mergedAt,isDraft
gh issue view <n> --repo RaiderIO/keystone.guru --json state
```

- **Merged/closed** → extract any durable, still-applicable lesson into the relevant
  `.claude/skills/` skill (preferred — in-repo notes transfer between machines) or an existing
  feedback memory, then `mv` the file to `archive/` and delete its index line. Most done files
  contain no extractable lesson — the common case is a plain move.
- **Still open** → leave the file; tighten the index line if it has grown stale (dates, resolved
  sub-items).
- Never re-index an archived file; `archive/` stays greppable for history but out of recall.

## 2. Link check

- Every `[...](file.md)` link in MEMORY.md must point at an existing file in the memory dir:
  `grep -oP '\]\(\K[^)]+' MEMORY.md | while read f; do [ -f "$f" ] || echo "DANGLING: $f"; done`
- List orphans (files present but not indexed): usually fine for `archive/`, a finding for the
  top-level dir — either index or archive them.
- `[[name]]` body links may dangle by convention (they mark memories worth writing), but report
  ones that reference *archived* memories so the text can be updated.

## 3. Size budgets

Measure and compare against budget; **warn** on breach (fixing may need judgment — trim the worst
offender, don't mechanically truncate):

| Surface | Budget | Measure |
|---|---|---|
| `CLAUDE.md` (Boost-generated) | ≤ 7,300 B | `wc -c CLAUDE.md` |
| `.claude/CLAUDE.md` | ≤ 29,000 B | `wc -c .claude/CLAUDE.md` |
| `MEMORY.md` | ≤ 9,500 B | `wc -c MEMORY.md` |
| All skill descriptions combined | ≤ 18,500 B | sum of each frontmatter `description:` value |
| Any single skill description | ≤ 550 B | same |

These budgets are a **ratchet, not a target**: when a sweep trims a surface below budget, tighten
the budget here to the new level (never loosen one to silence a warning; that needs Wotuu's
sign-off).

The `.claude/CLAUDE.md` budget was **18,000 B until PR #4046**, which raised it to 29,000 B —
proposed by an agent under delegated authority, and a working figure open to revision rather than a
number Wotuu reviewed and signed off on himself.

**Read the history before trusting the new number, because it is a concession, not a correction.**
18,000 B was genuinely met: on 2026-08-03 (#3783, `762974243`) the file was 17,923 B with every rule
present, using the compact-rule-plus-skill-pointer pattern this section recommends. It then grew
**89% in eleven days** — 17,923 → 33,788 B by 2026-08-14 — through real additions, not bloat (model
routing +6.9KB — measured from the diff, not the ~8.8KB first claimed here — the bot identity
section +3.2KB, worktree and MR-handoff rules). #4046 trimmed prose hard and reached 28,977 B, so
29,000 B is the honest floor *for the file's current content*, not evidence that 18,000 B was ever
unreasonable.

Two consequences for a sweep:

- **Watch the growth rate, not just the breach.** A budget re-ratcheted upward every time the file
  grows is a rubber stamp. If `.claude/CLAUDE.md` is approaching 29,000 B again, the question is
  which recent additions earn their place — not what the next number should be.
- **Tighten it only by cutting prose**, and never by moving rules out (below).

The **skill-description budget was deliberately left at 18,500 B** in the same PR, while breached
(19,982 B at #4046's head, 21,023 B on master), and Wotuu offered to raise it. Declining is the
point: descriptions are pure routing text, so trimming one costs no rule — the worst case is a skill
that triggers less readily, which is visible and recoverable. The breach is ~28 B per skill across
52 skills, and #4046 recovered ~1,041 B from four descriptions with no loss, so it is reachable by
ordinary trimming. **That is the test for whether raising a budget is legitimate: can the breach be
cleared without losing a rule?** If yes, trim. If no — as with `.claude/CLAUDE.md`, where the only
route to 18,000 B was deleting current rules — raise it and say plainly that you did.

### Cut prose, never rules — and never by relocating a rule behind a skill trigger

**A rule stays in always-loaded context. Only its rationale may move into a skill.** Trimming means
compressing a rule's wording, or moving the *why* into the skill that owns the topic and leaving a
one-line rule plus a pointer. It does **not** mean relocating the rule itself.

PR #4046 moved ~5.7KB of PHP/Laravel conventions out of `.claude/CLAUDE.md` into the
`laravel-best-practices` skill, and reverted it. Why, strongest argument first:

- **The costs are wildly asymmetric.** Carrying a rule you didn't need costs a few hundred
  well-cached tokens. Missing one costs a foreign key in a migration, or a destructive schema change
  shipped in a single release — which is what took staging down in #3497. No estimate of the saving
  changes that ratio, so the saving is not the deciding factor.
- **The trigger is not reliable.** The very next cold review read the CLAUDE.md files, never loaded
  the skill, and re-raised a finding Wotuu had explicitly banned (#3766) — the exact failure it was
  warned about, on the first attempt. A skill that "should" load is not a guarantee.
- **The saving is smaller than it looks**, and can invert: reclaiming those rules costs a 16.5KB
  `SKILL.md` load (plus a 58KB `rules/` tree) against 5.7KB removed. This leg is the weakest of the
  three — it assumes the skill wouldn't have loaded anyway — so don't lean on it alone.

So if a breach can only be cleared by moving a rule out of always-loaded context, **report the
breach and leave it**. An honest over-budget warning beats a rule that silently isn't there.
Budgets govern prose; they do not govern coverage.

### Boost regenerates some of these surfaces — always diff after `boost:update`

The root `CLAUDE.md` and every skill listed in `boost.json` are **owned by Boost**. `boost:update`
rewrites the `<laravel-boost-guidelines>` block and *deletes and recopies each Boost skill
directory wholesale*, so upstream silently reverts our trims and destroys any project-specific
section added inside one. After every `boost:update` (or Boost upgrade):

```bash
git diff --stat CLAUDE.md .claude/skills/
```

- **Root `CLAUDE.md` grew** — a Boost upgrade added a guideline pack. Audit the pack rather than
  trimming: if it contradicts our rules, add its key to `guidelines.exclude` in `config/boost.php`
  (#4061).
- **A Boost skill's `description:` grew** — upstream reverted a #3783 trim. Re-trim it.
- **A Boost skill lost content** — project-specific text was added inside a Boost-owned directory
  and cannot survive there. Move it to a project-owned skill and repoint whatever links to it
  (this is how "Model caching vs raw writes" ended up in `project-backend-structure`).
- **A skill we removed came back** — Boost re-adds a skill for every package it detects, so
  deleting it from `boost.json` never sticks. Use `skills.exclude` in `config/boost.php`, keyed on
  the skill name as `boost:list-skills` prints it.

Per-skill description bytes:

```bash
for f in .claude/skills/*/SKILL.md; do
  awk '/^description:/{d=1} d{buf=buf $0 "\n"} d && /^[a-z_-]+:/ && !/^description:/{exit} /^---$/ && NR>1{exit}
       END{printf "%6d  %s\n", length(buf), FILENAME}' "$f"
done | sort -rn | head
```

When trimming a description: keep the trigger keywords and the "NOT for X, use Y" routing hints
(they prevent expensive wrong-skill loads); cut prose and enumerations — detail belongs in the
SKILL.md body.

## 4. Contradiction scan

Look for statements that no longer agree with each other or with reality — the kind of drift that
costs tokens through confusion, not size:

- CLAUDE.md vs skill bodies vs memories claiming different states for the same fact (tool
  versions, workflow steps, label semantics).
- Memories whose "How to apply" references files/flags/scripts that no longer exist (spot-check
  with `ls`/`grep` in the repo).
- Skill descriptions promising content the body no longer has (or vice versa).

Fix the unambiguous ones (stale version claims, dead references); **report** the ambiguous ones
for Wotuu to decide rather than guessing.

## 5. Applying the changes

- Memory-dir moves and MEMORY.md edits apply **directly** — they live outside the repo.
- Repo edits (CLAUDE.md, skills) go through the normal worktree + draft MR flow. A small sweep
  (≤ 50 changed lines) qualifies for the **trivial tier** of the ready-for-review checklist (green
  CI, no cold review; label + MR-body note per `.claude/CLAUDE.md`); a larger restructuring of
  instruction files takes the standard tier — docs-only is not low blast radius here.
- After skill-frontmatter edits, sanity-check the YAML: `description:` present, quotes balanced,
  `---` fences intact (see the skill-frontmatter-pitfalls memory — a broken frontmatter silently
  delists the skill).
