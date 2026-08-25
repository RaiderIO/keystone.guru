# Agent instructions — keystone.guru

Read this before running anything. It is the environment contract for any AI agent working in this
repository (Codex, and anything else that reads `AGENTS.md`). Claude Code additionally reads
`CLAUDE.md` / `.claude/CLAUDE.md`, which hold the full project conventions — this file is the short
version of the parts you cannot guess and will otherwise get wrong.

## Never read secrets, and assume everything you read is published

You run in the cloud, so every file you open leaves this machine. The source of this repository is
public, so the code is not the concern — these are:

- **`.env` and `.env.*`** hold real credentials. Never open, print, quote or summarise them. If a
  task looks like it needs one, stop and say so instead. `.env.example` is the safe stand-in and is
  the only one anybody edits.
- **`storage/`** holds logs, caches and user-derived data. Same rule.
- Anything else that reads like a credential, token, API key or personal data — regardless of where
  it sits.

This is not a sandbox restriction you will bump into; nothing stops you mechanically. It is the one
rule where being helpful and going looking is the failure.

## Domain conventions live in `.claude/skills/`

`AGENTS.md` is the environment contract. The project's *domain* knowledge — how the combat-log
pipeline is layered, what a mapping-versioned model must implement, how the seeder round-trips
dungeon JSON — lives in `.claude/skills/<name>/SKILL.md`, roughly sixty of them. They are plain
markdown and nothing stops you reading them.

**Before writing or reviewing code in an area you have not worked in, check whether a skill covers
it, and read it if so.** These describe conventions that are load-bearing and not inferable from the
surrounding code; getting them wrong produces a change that looks right and gets rejected in review.

```bash
ls .claude/skills/                                        # the catalogue
grep -h '^\(name\|description\):' .claude/skills/*/SKILL.md   # what each one covers
```

The ones that most often decide whether a change is correct: `repository-pattern` (every new model
needs one), `writing-tests` (the persistent seeded test DB, no `RefreshDatabase`),
`mapping-versioned-models`, `seeder-load` / `seeder-save`, `api-endpoint`, `blade-expert`, and
`project-backend-structure` (where a new class belongs).

## Never run PHP on the host machine

The host has no usable PHP: it is below this project's required version, so `php`, `composer`,
`vendor/bin/phpunit` and `php artisan` all fail there with a platform-check error. **That failure is
not a finding about the code** — it means the command was run in the wrong place.

Everything PHP goes through the project's Docker `app` container, from the checkout's own directory:

```bash
docker compose exec -T app php artisan test --compact --filter=<TestName>
docker compose exec -T app php artisan test --compact          # full suite, slow
docker compose exec -T app composer run analyse                # PHPStan
docker compose exec -T app composer run fix                    # PhpCsFixer
docker compose exec -T app php artisan <anything>
```

`-T` is required — without it the command wants a TTY and hangs.

**If the container is not running**, say so and skip the step. Do not fall back to host PHP, and do
not start or build stacks yourself. Check with `docker compose ps app` from the checkout directory.

**If you get `permission denied while trying to connect to the docker API at
unix:///var/run/docker.sock`, that is your own sandbox, not a problem with this repository.** The
socket is world-writable and Docker works fine outside the sandbox; a read-only Codex sandbox simply
cannot reach it, and there is no configuration that changes that. So it is expected during a code
review, and the correct response is one plain sentence saying the tests could not be run — never a
finding, never a retry, never a fallback to host PHP, and never a workaround attempt.

Each git worktree under `../keystone.guru-worktrees/<branch>/` has **its own** stack and database.
Run the commands from inside that worktree's directory so they hit that worktree's container, never
the main checkout's.

## Node / front-end

`node`, `npm` and `git` on the host are fine — only PHP is off-limits.

**This project does not use Vite, and `npm run build` does not exist.** Assets are built by
`scripts/build/build.mjs`; the `package.json` scripts are `dev`, `development`, `watch`, `prod`,
`production`. JS tests are `npm run test` (vitest).

## Reviewing code

- Running the test suite is optional. A review that could not run tests is fine — say that plainly
  rather than reporting the platform error as a problem with the change.
- Read `.claude/CLAUDE.md` before flagging a convention violation. Several project rules invert the
  usual Laravel advice, and reviews keep re-raising them:
  - **No foreign keys in migrations.** Deliberate — they break seeding and testing. Never recommend
    `constrained()`.
  - **Migrations must be backward-compatible with the currently-running code** (deploys are not
    atomic). Additive is safe; drops/renames/narrowing ship a release later.
  - **Missing model-cache invalidation on a raw write to a `CacheModel` is not a finding.** The
    tables are read-only in production, caching is off in development, and each release rotates the
    cache prefix.
  - **`lat`/`lng` on map objects are display-only** coordinates on a fixed image — never valid for
    distances, sizes or comparisons. Conversion goes through `CoordinatesServiceInterface`.
  - Only `lang/en_US` is edited by hand; every other locale is generated externally.
- `lang/**` diffs are machine-generated translation churn. Skim them, don't review them line by line.

## Writing code

- Tests are PHPUnit, never Pest, built from factories, and named
  `[functionName]_given[Condition]_returns[ExpectedResult]`. Groups go in a class-level
  `#[Group('...')]` attribute. There is no `RefreshDatabase` — the seeded test DB persists, so clean
  up created rows in a `try ... finally`.
- Never delete or weaken an existing test to make a change pass.
- Finish with `docker compose exec -T app composer run fix` and
  `docker compose exec -T app composer run analyse`; both must be clean.
- `composer run fix` reformats pre-existing drift across the whole repo. Stage only the files you
  meant to touch.
- Do not commit, push, or open/merge pull requests unless you were explicitly asked to.
