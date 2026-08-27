# Working in the repository

## Durable notes

- Record durable knowledge in-repo — a relevant skill under `.claude/skills/`, or a `CLAUDE.md` —
  rather than Claude's auto-memory system. Auto-memories don't transfer between machines; in-repo
  notes do.

## Revising a long document

When you revise a plan, design doc, or issue body that the user has **already read**, lead the
reply with a **changelog of what changed** — not a re-statement of the document. Re-reading a
long document to diff it by hand costs the user a lot of time and energy, and the plan file is
often several hundred lines by the time it is agreed.

- Group the changes by kind (fixes / simplifications / additions / reordering) rather than by
  document order, and say what each one *prevents* or *buys*, not just what moved.
- Call out explicitly when a revision made the document **smaller** — dropped abstractions and
  removed steps are the changes most worth surfacing.
- Keep it to a screenful. If it needs a table, the table has one row per change, not per line.
- Note that `ExitPlanMode` shows the user the **whole** plan file again, so the changelog belongs
  in the message *before* it — otherwise they hit the wall of text first.

## Git

Branch formats are as follows:
- `<issue number>-<slug-description-of>`
- `1234-create-the-feature`
- `2345-fix-the-issue`

- The project is under Git version control.
- Any newly created files should be staged.
- In the main checkout, commits should not be done unless explicitly asked (see the worktree
  exception below).
- **Agent-tooling commits are tagged `#3877`** — the permanent anchor issue for changes to
  `.claude/skills/**`, `CLAUDE.md`, `.claude/agents/**` and agent/MCP config. It never closes and
  holds no task list; it exists so these commits have an issue number like everything else, and so
  `create-release` can classify them as non-public deterministically rather than by inference. When
  a skill change is part of a larger piece of work, tag it to *that* issue instead — #3877 is the
  fallback for standalone agent-tooling maintenance, not a catch-all.

### Commit subjects start with `#`, which git silently eats on rebase
Subjects begin with the issue number (`#3722 Fix the thing`), and any `git rebase --continue`
after a conflict re-reads the message through the editor path, where a leading `#` line is a
comment — the subject is stripped and the first body paragraph promoted in its place. After any
rebase that hit a conflict, check `git log --oneline` and repair a mangled subject with
`git commit --amend --cleanup=verbatim -F <msgfile>` before pushing. (Rewriting a non-HEAD
commit without interactive rebase: see the `worktree-docker` skill.)

### Git worktrees
- By default, do every task from an isolated git worktree with its own Docker `app` stack, created
  with `sh/worktree.sh create <issue>-<slug>`. Run all commands (artisan, tests, `composer run
  fix`/`analyse`) through that worktree's `app` container from inside the worktree dir. Only skip
  this when the user says to work directly in the main checkout.
- **This has no size exception.** A 2-line fix gets a GitHub issue and a worktree exactly like a
  multi-file feature — "it's trivial" or "it's mechanical" is not a reason to implement in the main
  checkout instead. Create the issue (`create-github-issue` skill) and the worktree *before* writing
  any code, not after judging how big the change turned out to be. Said directly, repeatedly (6th
  time as of 2026-08-20, after a two-file homepage link fix done straight in the main checkout with
  no issue and no worktree). Wotuu will say so explicitly when he doesn't want this for a given task
  — that is the only opt-out; don't infer it from task size.
- **Never remove a worktree whose MR has not merged — leave it up when you hand the MR off.** Its
  running stack is how Wotuu opens the branch in a browser without building anything, and it costs
  5–15 minutes of seeding to recreate. `babysit-prs` step 5 removes worktrees of merged/closed PRs,
  so cleanup is already someone's job; `sh/worktree.sh remove` is yours only when the branch never
  became an MR (abandoned work, or a scratch worktree). The stack may be stopped
  (`sh/worktree.sh down`) to free resources — that keeps the worktree and its database.
- **Hand off the worktree's URL and path with the MR**, in the message that says the MR is ready:
  `sh/worktree.sh create` prints both (`URL: http://localhost:<port>`, `Path: …`) and
  `sh/worktree.sh list` reprints them later. A "it's ready for review" with no URL makes Wotuu ask
  for one, which is the whole reason the worktree was kept.
- **The worktree and its branch are yours: you may commit, push, and open a MR without asking.**
  Commit as you go, push the branch with `sh/worktree.sh push` (uses a scoped write deploy key so no
  password is prompted), and open the MR to `master` (the default branch) with `gh pr create --draft`.
  Start the MR body with `Closes #<issue>` — because it targets the default branch it auto-links the
  issue and closes it on merge. This autonomy applies only to a worktree you created — in the main
  checkout, still ask before committing.
- **Open every MR as a draft and keep it a draft for as long as you are still working on the
  branch** — including your own post-push CI monitoring and the "ready for review" checklist below.
  Only mark it ready for review (`gh pr ready <n>`) once every applicable checklist item is actually
  done and you are handing the MR off. (Handing it off does not mean removing the worktree — that
  stays up until the MR merges, see above.) A draft PR can't be merged (`gh pr merge` 422s on one), so this is
  what stops `babysit-prs` — which runs in its own session/loop and merges+tears down worktrees for
  any green, `pr can merge`-labeled PR the moment it sees one — from grabbing a PR (and ripping out
  its worktree) while you're still mid-verification on it (see #3719). Don't undraft early just to
  let Wotuu take an early look; a labeled-but-still-in-flight PR is exactly the race this avoids.
- By default a worktree gets its **own freshly-seeded database schemas** (main + combatlog, on the
  shared MySQL servers) plus its own redis prefix — parallel agents can't touch each other's data,
  and destructive operations (`migrate:fresh`, destructive migrations) are safe to *test* there.
  `create ... --shared-db` opts back into sharing the main stack's data; then the old rules apply:
  non-destructive migrations only, never `migrate:fresh`/`migrate:refresh`. See the
  `worktree-docker` skill for details (isolated creates take ~5–15 min to seed).

## Github

You can use `gh issue view <issue number> --repo RaiderIO/keystone.guru --json number,title,body,labels,comments`
to request info from Github. Any call to `gh issue view` MUST be accompanied by `--json` to prevent deprecation warnings
and the command failing.

### Never hard-wrap a GitHub body — one paragraph is one line

GitHub renders a single newline inside a paragraph as a literal `<br>`, so prose wrapped at
80/100 columns comes out with a line break at every wrap point. Write **each paragraph of a PR
body, issue body or comment as one unbroken line**, however long, and let GitHub wrap it.

This is the opposite of the convention for markdown files in the repo (skills, `CLAUDE.md`,
`docs/`), which stay wrapped — and that is exactly why it goes wrong: composing the body in a
file first and passing it to `--body-file` makes it feel like a document, but the file *is* the
rendered body, verbatim. The rule is about the destination, not the authoring method.

Blank lines between paragraphs, list items, table rows, headings and fenced code blocks are
structural — keep those. Only wrapping *within* a paragraph or a list item is the problem.

Applies to every write path: `gh pr create/edit`, `gh issue create/edit`, `gh pr comment`,
review-thread replies, and the same calls through `sh/gh-bot.sh`.

### Agent GitHub identity

Agent-authored GitHub activity is migrating from "Wotuu's own account plus a `:robot:` prefix" to a
dedicated bot account, **`keystone-guru-bot`** (#3924). Both mechanisms stay live during the
transition — pre-bot PRs carry only the prefix — so don't remove either until #3924's follow-up says
pre-migration PRs have drained.

Prepend every message you post on GitHub (comments, review replies, PR/issue bodies) with a
`:robot:` emoji, whichever account posted it — it's the fallback authorship signal wherever the bot
wasn't used. **Bodies/comments only, never titles or commit messages** (commits use the
`Co-Authored-By: Claude` trailer instead).

**The `:robot:` prefix covers messages; the bot account covers *every write*.** Labels, titles and
merges carry no body to prefix, so the account is their only authorship signal — route every write
(not just those) through `sh/gh-bot.sh`, not plain `gh` (which renders as "Wotuu added <label>" and
hides agent triage from his own). The bot holds `push` + `triage`. Reads stay on plain `gh`.

#### Writing as the bot: `sh/gh-bot.sh`

Pass-through wrapper around `gh` that runs as the bot (`GH_TOKEN` or `GH_CONFIG_DIR=~/.config/gh-bot`
— human `gh auth login` on disk is untouched either way):

```bash
sh/gh-bot.sh api user --jq .login      # self-check: must print keystone-guru-bot
sh/gh-bot.sh pr create --repo RaiderIO/keystone.guru --base master --draft --title '...' --body-file b.md
```

**If `sh/gh-bot.sh` fails for any reason** (no token, missing on an old worktree, wrong account,
wrong cwd) — fall back to plain `gh` with a `:robot:`-prefixed body and carry on; never stop to ask.
The wrapper never falls back on its own — a silent fallback would post as Wotuu while you believed
you'd posted as the bot. Setup: `worktree-docker` skill, "Posting to GitHub as the bot account".

A `PreToolUse` hook (`sh/gh-write-guard.sh`, wired in `.claude/settings.json`) blocks plain `gh`
writes (create/edit/comment/ready/close/reopen/merge/review/label, `gh api` with a write method)
before they run, so this rule survives a human forgetting to route through the bot — not just a
convention. Reads and anything already going through `sh/gh-bot.sh` are unaffected. If it blocks a
command you believe should be a legitimate plain-`gh` fallback (`sh/gh-bot.sh` genuinely failed),
that's the intended friction — fix the block by using `sh/gh-bot.sh`, not by editing the hook away.

Writes to **`keystoneguru-infra`** are the one standing exemption — the bot is not a collaborator
there, so plain `gh` with a `:robot:` body prefix is the permanent path. The hook decides that from
where the write actually lands, never from the repo being mentioned: name it with
`--repo RaiderIO/keystoneguru-infra` (or a `repos/RaiderIO/keystoneguru-infra/...` API path), or run
from the infra checkout with no `--repo` at all. A command that targets both repos is guarded, since
its keystone.guru half belongs on the bot. `sh/gh-write-guard-test.sh` is the case table — run it
after touching the guard.

#### Reading authorship: match the bot login, never "not Wotuu"

To decide whether a comment, review thread or PR was written by an agent, test the author against
the bot login — an **allowlist**:

```bash
# agent-authored  <=>  author.login == "keystone-guru-bot"  (primary)
#                 or   body starts with ":robot:"           (fallback, pre-migration content)
# everything else <=>  human — treat as Wotuu's, with all the deference that implies
```

**Never write the inverse test (`author.login != "Wotuu"` ⇒ agent).** This is a public repo — an
outside contributor, `dependabot[bot]`, `github-actions[bot]` etc. all satisfy `!= "Wotuu"`, and a
denylist would make `babysit-prs` resolve a stranger's review thread as its own. The allowlist fails
safe: an unknown author is treated as human, whose thread an agent must never resolve.

`gh pr edit` needs a modern `gh` (`new-machine-setup` skill has the version/install detail). REST
fallback: `gh api -X PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>` — capital `-F`
dereferences the `@file`; lowercase `-f` sends the literal string `@<file>`.

### Stacked PRs: always target `master`

Open stacked PRs against `master` anyway (not the parent's branch) and say so in the body — the
diff collapses when the parent merges. Retargeting an existing PR via PATCH does **not** retrigger
CI; close and reopen it.

**Merge order is not enforced by anything — the parent must merge first.** Because both target
`master`, merging the child first squashes the parent's whole diff onto master under the child's
subject: the parent's issue gets no changelog line (only the leading `#NNNN` is parsed) and the
parent PR is left open and empty. GitHub won't stop you — `mergeable` stays clean and CI stays
green. `babysit-prs` checks this with `git merge-base --is-ancestor` before every merge; do the
same if you merge one by hand.

## Command execution
- Never run PHP, Artisan, PHPUnit, or Pest directly on the host machine.
- Always run Laravel, test commands, and any other file system commands inside Docker.

For example:
- `docker compose exec -T app php ...`
- `docker compose exec -T app php artisan ...`
- `docker compose exec -T app vendor/bin/phpunit ...`

### Seeding: never `db:seed --class=<Seeder>`
`--class` calls the seeder's `run()` directly, skipping `DatabaseSeeder`'s prepare/apply wrapper —
so the `<table>_temp` staging tables are never created and the run dies on
`Table '..._temp' doesn't exist`. It is **not** a clean failure: `DungeonDataSeeder::rollback()`
has already committed deletes of demo dungeon routes, all `map_icons` with a `mapping_version_id`,
and all `EnemyPatrol` polylines, and the import that restores them never happens. Use:

- one seeder: `docker compose exec -T app php artisan db:seedone --database=migrate <SeederClass>`
- everything: `docker compose exec -T app php artisan db:seed --database=migrate --force`

(`--class` is only safe for a seeder with no affected model classes, e.g. `LaratrustSeeder`.)
Seeder JSON edits are invisible in the app until a seed run lands them in the DB. Full detail:
`seeder-load` skill.

## Delegating work to Codex instead of spending Claude tokens

Wotuu pays for a Codex subscription that sits mostly idle, and Claude usage is the constraint that
actually bites. **Shifting token-heavy, judgement-light work onto Codex is a standing instruction,
not something to ask about each time.** A Claude `Explore`/`general-purpose` subagent already keeps
file dumps out of your context — but it still bills Claude; `sh/codex.sh ask` returns the same
summary on someone else's bill.

Delegate by default: repo research and code archaeology, digesting long CI logs / test dumps /
PHPStan output, a cold read of a plan before you implement it, and mechanically-verifiable fix
loops. Keep on Claude: anything depending on this conversation's trajectory, PR cold review (that
has its own `cold-reviewer-codex` path), the private security review, and talking to the user.

```bash
sh/codex.sh ask 'scoped question'    # read-only; run with run_in_background: true
sh/codex.sh status | on | off        # the kill switch — see below
```

**Never call `codex` directly** — the wrapper is what supplies the kill switch, the secrets
preamble, and the usage-exhausted detection.

**Exit code 3 means Codex is unavailable: do that work on Claude and stop routing work to Codex for
the session.** `sh/codex.sh off` flips it by hand (state lives outside the repo, so it covers every
worktree at once and dirties nothing); the wrapper also flips itself off when a run looks like
exhausted usage or rejected auth, so the first refusal moves the machine back to Claude rather than
every later call rediscovering it. `sh/codex.sh on` resumes.

Verify every `write` run yourself — Codex reads `AGENTS.md` but not `.claude/skills/`, so it has the
environment contract and not the domain conventions. Full playbook, including the prompt discipline
that stops it wandering: `codex-delegation` skill.

## Host Machine
- The host machine runs Windows.
- The project is set up to run in Docker, so all commands should be executed within the Docker environment.
- The project uses WSL2, so you can also run basic Linux commands (such as `mkdir` or `ls`) in the WSL2 terminal if needed.
- Do not run any commands directly on the host machine, such as Powershell commands.
- All newly created files should have LF line endings.
- Files and folders created via `docker compose exec` (including `php artisan make:`) land owned as
  your host user, not root — the `PUID`/`PGID` fix from #3414 resolved this, verified 2026-08-16 by
  creating a file and running `php artisan make:controller` inside the `app` container and checking
  ownership from the host. They're editable/removable from the host normally. The exception is a
  container exec run as root (`docker compose exec -u root ...`, e.g. some `chrome` service
  cleanup) — that still writes root-owned files; remove those from inside the container instead.

## Finishing up your work
- After completing your work, ensure you run `composer run fix` to run PhpCsFixer and `composer run analyse` to run PhpStan to verify your work.
- `composer run fix` reformats any files with pre-existing style drift, not just the ones you changed. After running it, stage only the files you actually intended to touch (`git checkout -- <other files>` to discard the unrelated reformats) so your diff/PR stays focused.

### Before declaring a MR ready for review
Human review time is the scarcest resource, so review must start from a pre-reviewed, verified MR —
but the checklist is **tiered by change size** so small MRs don't pay full ceremony:

- **Trivial tier** — either (a) a code change with ≤ 20 changed lines (excluding tests/docs), no
  UI-visible impact, no schema/auth/security change, covered by a passing test; or (b) a
  docs/config-only change of ≤ 50 changed lines total. Skip item 1 (cold review); still require
  items 3 (green CI) and 4 (one issue per MR). State in the MR body: "Cold review skipped under the trivial-change rule" and
  add the `pr cold reviewed` label anyway so `babysit-prs` doesn't dispatch its own review.
  Docs-only does NOT mean low blast radius: a larger docs MR — especially one touching `.claude/`
  instruction/process files, which degrade every future session when wrong — takes the standard
  tier.
- **Standard tier** — everything else: all applicable items below.

1. **Independent review**: cold review is done by Codex, not a Claude agent reviewing its own
   kind's work — Codex has zero shared context with the implementing session, which is the property
   a "cold" review actually needs, and it runs on Wotuu's Codex subscription rather than this
   session's token budget. Once the change is built, dispatch it (`Agent` tool, `subagent_type:
   "cold-reviewer-codex"`, or `"cold-reviewer-codex-adversarial"` for migrations/auth/payment/
   data-destructive diffs) and resolve every finding it raises (or note in the MR body why a finding
   is intentionally not addressed). The dispatched agent posts its own marker comment and adds the
   `pr cold reviewed` label as part of its instructions — the same marker/label `babysit-prs` checks
   before dispatching its own cold review, which is what stops a PR from being reviewed twice. Both
   agents need the PR's branch checked out locally to run Codex's review against (they'll find the
   worktree themselves from the PR number if you don't hand them the path directly) — that's why
   worktrees for open MRs stay up until merge (see "Git worktrees" above).

   **Every review thread the cold reviewer opened must be *resolved* before you hand the MR off** —
   the plain `cold-reviewer-codex` agent posts one summary comment rather than inline threads, so
   just address what it raised and reply on that comment; `cold-reviewer-codex-adversarial` does
   open inline threads (one per finding) — fix each, reply on-thread with `:robot: Fixed...`, then
   close it yourself via `resolveReviewThread`. Baseline is zero open threads; leave one open only
   for a genuine judgement call, and say so explicitly in the reply. Threads *Wotuu* opened are
   never yours to resolve — fix and reply, he closes them.

   ```bash
   # unresolved threads on the PR, with the ids needed to resolve them
   gh api graphql -f query='query { repository(owner: "RaiderIO", name: "keystone.guru") {
     pullRequest(number: <n>) { reviewThreads(first: 100) { nodes {
       id isResolved path line comments(first: 20) { nodes { author { login } body } } } } } } }'

   gh api graphql -f query='mutation { resolveReviewThread(input: {threadId: "<id>"})
     { thread { isResolved } } }'
   ```

   `babysit-prs` is a backstop for leftovers, not a reason to hand off with open threads.

   **Dispatching this reviewer needs no permission — the implementing session fires it itself,**
   without stopping to ask; a general "don't spawn subagents unless asked" instruction does not cover
   this case.
2. **Visual verification**: ONLY when rendered output actually changed — verify the affected
   page(s) in headless Chrome (`headless-browser-verify` skill) and post before/after screenshots
   on the MR (`post-screenshot.sh`). Backend-only MRs skip this explicitly; don't screenshot "to
   be safe".
3. **Green CI**: wait for the MR's checks and fix any failure yourself — including flaky or
   seemingly unrelated failures (root-cause them; don't re-run and hope, and don't defer to a
   follow-up issue). **The full php-tests suite does not run on draft PRs** (#4343 — it was two
   thirds of the Actions bill): while drafting, only the fast checks (php-lint = phpstan +
   cs-fixer, js-tests) run on CI, so run the test groups your change affects in the worktree's own `app`
   container before undrafting — that's your suite signal, and it's free. The suite runs on CI
   automatically when the PR is marked ready (and on every push while it stays ready). To debug a
   CI-only failure on a draft, add the `run full ci` label — it triggers the suite immediately
   and on every push while present; remove it when done. Don't add the label routinely "to be
   safe": the ready_for_review run repeats the suite anyway, so a labeled draft pays for every
   push twice over.
4. **One issue per MR** — a squash commit keeps exactly one leading `#NNNN`, so any other issue
   the branch carries is invisible to `create-release`. Check before undrafting:

   ```bash
   git fetch origin --quiet && git log origin/master..HEAD --pretty=%s | grep -oE '^#[0-9]+' | sort -u
   ```

   The `fetch` is not optional — a worktree created days ago has a stale `origin/master`, and the
   range then includes master commits the branch doesn't own, reporting a pile of unrelated issues.

   More than one number means a fix for another issue rode along. Move it to its own branch if it
   stands alone; if it genuinely can't be separated, say so in the MR body and add `Closes #N` for
   it, so at least the issue closes and a human sees the second number. Applies to a stacked child
   too: it will show the parent's issue numbers until the parent merges, which is fine — it's only
   a defect if the *parent has already merged* and the numbers are still there.

Only once the applicable items hold, mark the MR ready for review (`gh pr ready <n>`) — see the
draft-PR note under Git worktrees above for why it must stay a draft until then.

**Undrafting is a one-way handoff — the last thing you do, not a status update.** Undraft only once
the cold reviewer has finished and every finding is fixed, with the fast checks green on the commit
that contains those fixes and the affected test groups passing locally (see item 3 — the full suite
runs on CI at the moment you undraft, not before). Watch that ready_for_review suite run through to
green; if it goes red, `gh pr ready <n> --undo` immediately, then fix. After `gh pr ready`, don't
push further commits or start another polish pass — `babysit-prs` merges any green, `pr can
merge`-labeled non-draft PR the moment it sees one, and a post-undraft commit races that merge with
CI still pending (#3883). To change an undrafted MR: `gh pr ready <n> --undo` first, push, wait for
the fast checks, then undraft again (which re-runs the suite).

# Project-specific conventions

These are **rules**, kept deliberately terse. The reasoning behind each one lives in the skill named
next to it — load that skill when you need the *why*, but follow the rule either way. The
`laravel-best-practices` skill holds the generic Laravel guidance these override, and the
`project-backend-structure` skill the reasoning behind the model-caching rule below.

## General
- `sprintf` over direct concatenation for dynamic strings.

## PHP
- Keep existing comments unless they're completely redundant.
- Class definition order: traits, constants, static properties, private → protected → public
  properties, constructor, public → protected → private methods, static methods, magic methods.

## Backend (Laravel)

### Database & Eloquent
- Use proper Eloquent relationship methods with return type hints; prefer them over raw queries or
  manual joins, and over `DB::` (use `Model::query()`).
- Eager-load to avoid N+1. Query builder is fine for genuinely complex operations.
- **A retried `DB::transaction($cb, 3)` makes `$model->save()`/`update()` a silent no-op on retry**
  if the instance outlives the closure. Eloquent's dirty tracking survives a rollback: attempt 1's
  `save()` syncs the original attributes, the rollback undoes the *row* but not the *PHP object*,
  and attempt 2 finds the model clean and issues no SQL at all — the write vanishes while the rest
  of the body retries fine. Write through the query builder
  (`Model::query()->whereKey($id)->update([...])`) for anything on a caller-owned model inside a
  retried transaction. Models re-hydrated inside the body each attempt (`$route->load([...])`, a
  fresh `->get()`) are unaffected. Found in #4250 — it silently dropped a route's `enemy_forces`.
- **Missing model-cache invalidation on a raw write (e.g. `upsert()` on a `CacheModel`) must not be
  raised as a review finding** (Wotuu, PR #3766) — the tables are read-only in production, caching
  is off in development, and each release rotates the cache prefix. Rationale and the legitimate
  reasons to still prefer a model-level write: `project-backend-structure` skill, "Model caching vs
  raw writes" (it lives there, not in `laravel-best-practices`, because Boost wipes that skill's
  directory wholesale on every `boost:update`).

### Model Creation
- Every new model needs a repository: interface at
  `app/Repositories/Interfaces/{Domain}/{ModelName}RepositoryInterface.php`, implementation at
  `app/Repositories/Database/{Domain}/{ModelName}Repository.php`, binding in
  `RepositoryServiceProvider`. Full convention: `repository-pattern` skill.

### Seeded models (`SeederModel`)
The trait marks a model whose rows are populated by `database/seeders`;
`DatabaseSeeder::getTempTableName()` uses it to stage `<table>_temp` while seeding. As of #4062 six
combat-log-derived models (`SpellDungeon`, `NpcCharacteristic`, `NpcSpell`, `CombatLogNpcEvent`,
`CombatLogSpellEvent`, `ParsedCombatLog`) had it removed — it was vestigial there, no seeder ever
referenced them.

### Combat-log-derived rows are not recoverable from seeders
Those same six models hold data derived purely from combat log ingestion, not from
`database/seeders` — a delete is **permanent**. Only the admin panel, mapping editor, the hourly
`combatlog:detectstaledata` sweep, and (for `ParsedCombatLog`) the daily `combatlog:pruneparsedlogs`
retention sweep may delete such rows (convention, not enforced). Full list and rationale:
`seeder-load` skill, "Combat-log-derived rows".

### Controllers & Validation
- Validate in Form Request classes, never inline in controllers; include rules and custom messages.
  Check sibling Form Requests for array vs string rule style.
- Every ID in a request body needs an `exists` rule of the right type
  (`['user_id' => ['required', 'integer', 'exists:users,id']]`), in the Form Request.
- Every `exists`-validated ID also gets a cached getter on the Form Request so the controller gets a
  model directly: `return once(fn() => Dungeon::query()->where(...)->firstOrFail());`

### Routes & controller method naming
- Never name a controller method registered with first-class callable syntax after a PHP reserved
  word (`new`, `list`, `print`, ...) — `route:cache` serialization then crashes per-request, in
  production only, not in tests. The route path/name may keep the word; only the method needs
  renaming. Mechanics: `api-endpoint` skill.

### Queues
- Time-consuming operations go in queued jobs implementing `ShouldQueue`.

### Authentication & Authorization
- Use Laravel's built-in gates, policies and Sanctum.

### Configuration
- `env()` only inside config files — always `config('app.name')`, never `env('APP_NAME')`.

## Database (migrations)
- **Do not use foreign keys.** This application does not use them; they break seeding and testing.
  This overrides any generic `constrained()` advice.
- **Migrations must be backward-compatible with the currently-running code.** Deploys are not
  atomic — a cron runs `migrate` independently of the ECS web rollout, so old code and new schema
  coexist during every deploy. Additive changes (nullable/defaulted column, new table, backfill)
  are safe; dropping, renaming or narrowing in the same release that removes the consuming code is
  not, and will 500 the still-running old containers. This broke staging in **#3497**.
- Split destructive changes expand/contract across two releases: release N removes the last code
  consumer, release N+1 ships the drop/rename. Column rename = add new + backfill + dual-write in
  N, drop old in N+1.
- **MySQL caps identifiers at 64 characters** and Laravel's generated index names include the table
  name — on long table names pass an explicit short name (`->index('short_x_id_index')`,
  `$table->unique([...], 'short_unique')`). The failure hits *after* the table is created, so drop
  the half-made table by hand before re-running.

## Mapping

### Lat/lng is display-only — never measure with it
A map object's `lat`/`lng` are **presentation coordinates on a fixed 256×384 image**, not positions in
a shared space. Never use them to compute a distance, a size, a bearing, or to compare two objects'
extents. They are not comparable across floors, and not even isotropic *within* one floor:

- **Across floors** — every floor is stretched to the same 384-lng-wide image regardless of how much
  world it covers. Den of Nalorakk floor 394 spans 750 ingame X over those 384 lng; floor 395 spans
  140. So "this pack is 25 lng wide" means 5.4× more world on 394 than on 395 — the mistake that made
  the floor-395 corridor packs look twice as wide as anything else when they were normal size.
- **Within one floor** — lat and lng do not always share a scale, so even same-floor maths can be
  skewed. Most floors (~325 of 397) have `sizeX/sizeY == -1.5`, which happens to make lat and lng
  isotropic; the other ~72 (34 dungeons, incl. Den of Nalorakk, Murder Row, Altar of Fangs, and most
  Classic/Legacy dungeons) have `sizeX/sizeY == +0.667`, where 10 lat = 43.95 ingame units but
  10 lng = 19.53 — a 2.25× stretch. Never assume which kind a floor is.

Convert first, via `CoordinatesServiceInterface` (`app/Service/Coordinates/`), then measure:

```php
$ingameXY = $coordinatesService->calculateIngameLocationForMapLocation(new LatLng($lat, $lng, $floor));
$distance = $coordinatesService->distanceIngameXY($a, $b);
```

`calculateMapLocationForIngameLocation()` converts back for storage/display. Both need the `Floor`
set on the struct, and both throw on a facade floor — convert a facade lat/lng down to a real floor
with `convertFacadeMapLocationToMapLocation()` first.

## Localization
- Use the `__()` helper function for localization and translation of strings. Use translation keys. For example: `__('view_common.my.folder.structure.welcome_to_the_website')`.
- The language folder exists in the root of the project. Translation files are located in `lang/{locale}/` and should be organized by relevant class name (such as `Spell` -> `spells.php`) or folder structure for views (such as `view_common` or `view_dungeon`). For example: `lang/en_US/auth.php`, `lang/en_US/dashboard.php`, etc.
- Only ever edit localization files in the `lang/en_US` directory. All other languages are handled externally.
- For blade.php files, the translation keys matches exactly the file structure and name. For example, `resources/views/common/footer.blade.php` would have translation keys like `view_common.footer.copyright`.

## Testing
- Structure every test using the Arrange-Act-Assert pattern. Arrange all necessary preconditions and inputs, Act on the object or method under test, and Assert that the expected results have occurred.
- Every test name should follow the pattern of `[functionname]_given[Condition]_returns[ExpectedResult]`. For example: `myFunction_givenValidDate_returnsTrue` or `myFunction_givenInvalidDate_throwsInvalidArgumentException`.
- Any created database records must be cleaned up using try...finally.
- Test groups should be applied with the `#[Group('...')]` attribute at the class level, not the method level (doc-comment `@group` metadata is deprecated and triggers a PHPUnit warning). For example: a `#[Group('CombatLog')]` for all tests in the `CombatLog` folder, a `#[Group('EncounterStart')]` for all tests in the `EncounterStart` file. See the `writing-tests` skill for the full testing conventions.
- A DataProvider should be placed right below the last test using it, not at the top or the bottom of the class.
