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
  fix`/`analyse`) through that worktree's `app` container from inside the worktree dir, and tear it
  down with `sh/worktree.sh remove <issue>-<slug>` when done. Only skip this when the user says to
  work directly in the main checkout.
- **The worktree and its branch are yours: you may commit, push, and open a MR without asking.**
  Commit as you go, push the branch with `sh/worktree.sh push` (uses a scoped write deploy key so no
  password is prompted), and open the MR to `master` (the default branch) with `gh pr create --draft`.
  Start the MR body with `Closes #<issue>` — because it targets the default branch it auto-links the
  issue and closes it on merge. This autonomy applies only to a worktree you created — in the main
  checkout, still ask before committing.
- **Open every MR as a draft and keep it a draft for as long as you still own the worktree** —
  including your own post-push CI monitoring and the "ready for review" checklist below. Only mark
  it ready for review (`gh pr ready <n>`) once all three checklist items are actually done and you
  are handing the worktree off. A draft PR can't be merged (`gh pr merge` 422s on one), so this is
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

Prepend every message you post on GitHub (PR/issue comments, review replies, PR/issue bodies) with
a `:robot:` emoji so it is clear the message is from Claude and not the account owner. This avoids
the appearance of impersonating the user.

**Bodies and comments only — never titles.** A PR or issue *title* gets no `:robot:` prefix (and no
🤖). Titles are read in lists, notifications and the merge commit, where the prefix is just noise;
the body directly underneath already carries it. Same for commit messages — those are attributed via
the `Co-Authored-By: Claude` trailer, not an emoji.

`gh pr edit` works on gh ≥ 2.96.0 (`~/.local/bin/gh`; the apt 2.4.0 at `/usr/bin/gh` fails with a
Projects-classic GraphQL error — if that error appears, check `gh --version`). REST fallback:
`gh api -X PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>` — capital `-F`
dereferences the `@file`; lowercase `-f` sends the literal string `@<file>`.

### Stacked PRs: always target `master`

`php-tests`/`phpstan` only run on PRs targeting `master`, so a PR against a feature branch
silently skips them yet still shows green (9 checks when wired correctly, 3 when not). Open
stacked PRs against `master` anyway and say so in the body — the diff collapses when the parent
merges. Retargeting an existing PR via PATCH does **not** retrigger CI; close and reopen it.

## Command execution
- Never run PHP, Artisan, PHPUnit, or Pest directly on the host machine.
- Always run Laravel, test commands, and any other file system commands inside Docker.

For example:
- `docker compose exec -T app php ...`
- `docker compose exec -T app php artisan ...`
- `docker compose exec -T app vendor/bin/phpunit ...`

## Host Machine
- The host machine runs Windows.
- The project is set up to run in Docker, so all commands should be executed within the Docker environment.
- The project uses WSL2, so you can also run basic Linux commands (such as `mkdir` or `ls`) in the WSL2 terminal if needed.
- Do not run any commands directly on the host machine, such as Powershell commands.
- All newly created files should have LF line endings.
- Do not create new files or folders using `docker compose exec`. You will not be able to edit or remove them properly from the host machine otherwise.
- Do not use `php artisan make:` commands to create new files. Instead, create new files directly in the codebase to ensure they are created with the correct permissions and structure. This overrides the Boost guideline that recommends using `php artisan make:` and running Artisan directly on the command line.

## Finishing up your work
- After completing your work, ensure you run `composer run fix` to run PhpCsFixer and `composer run analyse` to run PhpStan to verify your work.
- `composer run fix` reformats any files with pre-existing style drift, not just the ones you changed. After running it, stage only the files you actually intended to touch (`git checkout -- <other files>` to discard the unrelated reformats) so your diff/PR stays focused.

### Before declaring a MR ready for review
Human review time is the scarcest resource, so review must start from a pre-reviewed, verified MR —
but the checklist is **tiered by change size** so small MRs don't pay full ceremony:

- **Trivial tier** — either (a) a code change with ≤ 20 changed lines (excluding tests/docs), no
  UI-visible impact, no schema/auth/security change, covered by a passing test; or (b) a
  docs/config-only change of ≤ 50 changed lines total. Skip item 1 (cold review); still require
  item 3 (green CI). State in the MR body: "Cold review skipped under the trivial-change rule" and
  add the `pr cold reviewed` label anyway so `babysit-prs` doesn't dispatch its own review.
  Docs-only does NOT mean low blast radius: a larger docs MR — especially one touching `.claude/`
  instruction/process files, which degrade every future session when wrong — takes the standard
  tier.
- **Standard tier** — everything else: all applicable items below.

1. **Independent review**: the `code-review` skill is `disable-model-invocation` — only the user
   can trigger it directly (`/code-review`), an agent calling it itself will error. Once the change
   is built, hand the diff to a fresh-context agent (no shared context with whoever implemented it —
   a plain `Agent` call, not a `fork`) to review, and resolve every finding it raises (or note in the
   MR body why a finding is intentionally not addressed). Afterwards, post the `:robot: Cold review:
   <N> findings` marker comment on the MR and add the `pr cold reviewed` label
   (`gh pr edit <n> --add-label "pr cold reviewed"`) — the same marker/label `babysit-prs` checks
   before dispatching its own cold review, which is what stops a PR from being reviewed twice.

   **Dispatching this reviewer needs no permission — the implementing session fires it itself.**
   That is the entire point of the cold review: the agent that wrote the code is the one that must
   hand it to a fresh pair of eyes. Do not stop and ask first, and do not treat a general
   "don't spawn subagents unless asked" instruction as covering this case — this `Agent` call *is*
   the standing request, and stopping to ask just adds a round-trip before work that is required
   anyway. Pick the reviewer by risk: `cold-reviewer-fable` for migrations, auth, payment or
   data-destructive diffs, `cold-reviewer-opus` for everything else.
2. **Visual verification**: ONLY when rendered output actually changed — verify the affected
   page(s) in headless Chrome (`headless-browser-verify` skill) and post before/after screenshots
   on the MR (`post-screenshot.sh`). Backend-only MRs skip this explicitly; don't screenshot "to
   be safe".
3. **Green CI**: wait for the MR's checks and fix any failure yourself — including flaky or
   seemingly unrelated failures (root-cause them; don't re-run and hope, and don't defer to a
   follow-up issue).

Only once the applicable items hold, mark the MR ready for review (`gh pr ready <n>`) — see the
draft-PR note under Git worktrees above for why it must stay a draft until then.

# Project-specific conventions

## General
- `sprintf` should always be used over direct concatenation for dynamic strings.

## PHP

### Comments
- If there's existing comments in the code, prefer to keep them around if they aren't completely redundant.

### Class definition order
- traits
- constants
- static properties
- private properties
- protected properties
- public properties
- constructor
- public methods
- protected methods
- private methods
- static methods
- magic methods (like `__call` or `__get`)

## Backend (Laravel)

### Database & Eloquent
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

#### Model caching is not a reason to avoid writes that bypass Eloquent events
Raw writes (e.g. `upsert()`) on `CacheModel` models skip `laravel-model-caching`'s invalidation —
but **missing cache invalidation must not be raised as a review finding** (Wotuu, PR #3766): the
`CacheModel` tables are read-only in production, caching is off in development, and every release
rotates the cache prefix anyway. Full rationale and the legitimate reasons to still prefer a
model-level write: see the `laravel-best-practices` skill, "Model caching vs raw writes".

### Model Creation
- Every new model must also have a repository. Create the interface at `app/Repositories/Interfaces/{Domain}/{ModelName}RepositoryInterface.php`, the implementation at `app/Repositories/Database/{Domain}/{ModelName}Repository.php`, and register the binding in `app/Providers/RepositoryServiceProvider.php`. See the `repository-pattern` skill for the full convention.

### Seeded models (`SeederModel`)
The `SeederModel` trait is a marker, not a guarantee rows come from seeders — some trait users
(`SpellDungeon`, `NpcCharacteristic`, `NpcSpell`, `CombatLogNpcEvent`, `CombatLogSpellEvent`,
`ParsedCombatLog`) are combat-log-derived and a delete is **permanent**, not recoverable from
`database/seeders`. Only the admin panel, mapping editor, seeders and the hourly
`combatlog:detectstaledata` sweep may delete such rows (convention, not enforced). Full list and
rationale: `seeder-load` skill, "SeederModel rows".

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.
- Any IDs in the post body of a request should be validated to ensure they exist in the database and are of the correct type. For example: `['user_id' => ['required', 'integer', 'exists:users,id']]`. Do not put this validation in a controller; it should be in a Form Request.
- Any IDs that are validated through an `exists` rule should also have a cached getter so that the Controller can directly get a modal instance. For example:
```php
    public function dungeon(): Dungeon
    {
        return once(fn() => Dungeon::query()
            ->where('challenge_mode_id', $this->validated('challenge_mode_id'))
            ->firstOrFail());
    }
```

### Routes & controller method naming
- Never name a controller method registered with first-class callable syntax
  (`Route::get('new', new FooController()->new(...))`) after a PHP reserved word (`new`, `list`,
  `print`, ...) — `route:cache` serialization crashes per-request in production only, not in tests.
  The route path/name string may keep the reserved word; only the method needs renaming. Full
  mechanics: `api-endpoint` skill, "Reserved-word controller methods".

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Database (migrations)
- Do not use foreign keys for migrations. This application does not use them, and they can cause issues with seeding and testing.
- **Migrations must be backward-compatible with the currently-running code.** Deploys are not atomic: a cron runs `migrate` independently of the ECS web rollout, so during every deploy the old code and the new schema (and vice-versa) coexist for a window. Additive changes (new nullable/defaulted column, new table, backfill) are safe. **Destructive changes are not** — never drop a table/column, rename it, or narrow its type in the same release that removes the code using it, or the still-running old containers will 500 against the missing schema (this is what broke staging in #3497).
- **MySQL caps identifier names at 64 characters** and Laravel's generated index names include the
  table name — on long table names pass an explicit short name: `->index('short_x_id_index')`,
  `$table->unique([...], 'short_unique')`. (The failure hits *after* the table is created, so the
  half-made table must be dropped by hand before re-running.)
- Split destructive schema changes across two releases (expand/contract): release N removes the
  last code consumer while leaving the old schema in place; release N+1 ships the drop/rename.
  Column rename = add new + backfill + dual-write in N, drop old in N+1.

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
