---
name: writing-tests
description: Conventions for writing PHPUnit tests in keystone.guru — which base test case to extend, the persistent seeded test DB (no RefreshDatabase; clean up with try/finally), creating admin/non-admin users, factory gotchas, the Group/Test PHP attributes, naming, and running tests in Docker. Use when writing or editing any test (Feature or Unit). Not for generic PHPUnit questions unrelated to this project's setup.
---

# Writing Tests

This project uses **PHPUnit** (never Pest). All test commands run inside Docker.

```bash
docker compose exec -T app php artisan test --compact --filter=SomeTest
docker compose exec -T app php artisan test --compact tests/Feature/Policy/DungeonRoutePolicyTest.php
```

Run the minimum needed while iterating (a `--filter` on the class you changed); the full suite is
run at the end.

## Directory layout

- `tests/Feature/` — most tests live here. Mirrors `app/` (`Controller/`, `Controller/Ajax/`,
  `Controller/Api/V1/`, `Policy/`, `View/`, `Console/Commands/`, …).
- `tests/Unit/` — pure logic with no framework/DB (heavily used for `App/Logic/CombatLog/**`).
- Default to a **Feature** test unless the subject is pure computation.

## Pick the right base class

All extend `Tests\TestCase` (which adds timing checks + the `#[Repeat]`/`#[SlowTest]` attributes).
Do **not** extend `TestCase` directly for feature tests — use one of the `Tests\TestCases\` bases:

| Base class | Use for | What it adds |
|---|---|---|
| `PublicTestCase` | Web/feature tests | `createMockPublic()` / `createPartialMockPublic()` mock helpers |
| `APIPublicTestCase` | `/api/v1` tests | Basic-auth via the `APIAuthentication` trait (`addAuthentication()` in setUp) |
| `AjaxPublicTestCase` | Ajax controller tests | Acts as admin (`User::findOrFail(1)`) + sets the `X-Requested-With: XMLHttpRequest` header |

## The test database — READ THIS

The test DB is a **real MySQL connection** (`phpunit`), and it is **persistent and pre-seeded**.
There is **no `RefreshDatabase` / no transactions** wrapping tests. Two consequences:

1. **Seeded data already exists** and you should rely on it: dungeons + their mapping versions,
   game versions, seasons, and **user id=1 is the admin** (has `Role::ROLE_ADMIN`, seeded by
   `LaratrustSeeder`).
2. **Every record you create must be cleaned up**, or it leaks into later tests. Use `try/finally`:

```php
$owner = User::factory()->create();
$route = DungeonRoute::factory()->create(['author_id' => $owner->id]);

try {
    // Act + Assert
} finally {
    $route->delete();
    $owner->delete();
}
```

`DungeonRoute` has no soft-deletes, so `delete()` truly removes the row.

### Seeded rows leak loudly — a leftover breaks unrelated tests

The ~51 models using the `SeederModel` trait (`Season`, `SeasonDungeon`, `Expansion`, `EnemyPack`,
`GameServerRegion`, … — `grep -rln "use SeederModel;" app/Models/`) hold the data every other test reads, so
one leaked row is not a private mess: a leftover `Season` with no dungeons breaks any test doing
`Season::orderByDesc('id')->first()->dungeons()`. Create as few as you can get away with, and always clean
up in a `finally`.

`$model->delete()` works on them — the trait is a marker now. Prefer it over a query-builder delete: it fires
the model events, so laravel-model-caching invalidates on its own and any cleanup the model registers in
`booted()` still runs. Reach for `Model::query()->where(...)->delete()` only for bulk cleanup, and flush by
hand there (`new Model()->flushCache()`), since no events fire.

Two caches can still make freshly created/deleted rows invisible in tests, and neither is the `array` store
`phpunit.xml` configures:
- **laravel-model-caching** (every `CacheModel`) — flush with `new Model()->flushCache()`.
- **`RemembersToFile`** (`ViewService::cachedGlobal`, map context, …) writes to `Cache::store('tmp_file')`,
  a **file** store that survives between test runs. If your test touches `ViewService`, flush it:
  `Cache::store('tmp_file')->flush()`. `phpunit.xml` points `CACHE_FILE_PATH` at `/tmp/phpunit_cache` so
  that flush cannot reach the cache of the app running out of the same checkout.

Create fixtures **inside** the `try`, not before it, so a failure halfway through setup still cleans up:

```php
$season = null;

try {
    $season = Season::create([...]);
    SeasonDungeon::create([...]);   // this throwing must not leave $season behind
    // Act + Assert
} finally {
    if ($season !== null) { /* delete */ }
}
```

## Creating users & roles (Laratrust)

- **Admin**: `User::findOrFail(1)` — the seeded admin. Assert it defensively:
  ```php
  $admin = User::findOrFail(1);
  $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
  ```
  `$user->is_admin` is an accessor for `hasRole(Role::ROLE_ADMIN)`.
- **Non-admin**: `User::factory()->create()` — a fresh user never has the admin role.
- Roles/constants live on `App\Models\Laratrust\Role` (`Role::ROLE_ADMIN`, …).
- **Only user ids 1–3 exist in CI.** CI seeds with `LaratrustSeeder`, which creates exactly one user
  per role: **1 = admin, 2 = internal_team, 3 = user**. A local dev database usually has extras (a
  `Testuser` at id 4, real accounts beyond that), so `User::findOrFail(4)` passes locally and fails
  in CI with `ModelNotFoundException: No query results for model [App\Models\User] 4`. Never
  reference a seeded id above 3 — create the user instead.
- **A factory user has no role at all**, which matters the moment the route under test sits behind
  Laratrust's `role:` middleware (e.g. everything under `Route::middleware(['auth', 'role:user|admin'])`
  in `routes/web.php`). The middleware rejects it with a **403 before the controller runs**, so an
  authorization test asserting 403 passes for entirely the wrong reason. Attach the role explicitly:
  ```php
  $user = User::factory()->create();
  $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
  ```
  Sanity check: pair every deny-case test with an allow-case using the same setup. If the allow case
  returns 200, the role middleware is not what produced the 403.

## Factories — use them, but know the defaults

Always use factories over hand-built models, and check for states first. Some factory defaults will
silently break a test if you don't override them — the recurring one:

- **`DungeonRoute::factory()`** picks a *random seeded dungeon* (so the seeded DB is required) and
  defaults to `author_id => 1`, `published_state_id => WORLD`, and **`expires_at => now()+2h`, which
  means the route is a *sandbox* route by default** (`isSandbox()` returns true when `expires_at` is
  set). Override `author_id` / `expires_at` / `published_state_id` explicitly whenever they matter:
  ```php
  DungeonRoute::factory()->create([
      'author_id'          => $owner->id,
      'expires_at'         => null, // non-sandbox
      'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
  ]);
  ```
- `User::factory()` has an `unverified()` state. Faker: both `$this->faker->sentence()` and
  `fake()->name()` are used in this codebase — match the surrounding file.

## Attributes, naming & structure

- **Use PHP attributes, not doc-comment metadata** (doc-comment metadata is deprecated and warns):
  - `#[Test]` (`PHPUnit\Framework\Attributes\Test`) to mark a test method.
  - `#[Group('X')]` (`PHPUnit\Framework\Attributes\Group`) at the **class** level for groups —
    e.g. `#[Group('Policy')]`. (This is the real convention across ~200 test files; ignore any
    older guidance about `@group` docblocks.)
  - `#[DataProvider('methodName')]` for data providers; place the provider method **right below the
    last test that uses it**.
- **Method names**: `[method]_given[Condition]_returns[ExpectedResult]`, e.g.
  `edit_givenNonOwner_returnsDenied`.
- **Structure every test Arrange → Act → Assert**, with those comments.
- Keep tests fast: the base `TestCase` **warns at >1s and fails at >10s**. Mark genuinely slow tests
  with `#[SlowTest]`; use `#[Repeat(n)]` to run a flaky-prone test multiple times.

## Testing policies / authorization

Call the policy directly for precise branch control, or go through the Gate to also prove wiring:

```php
$this->assertTrue((new DungeonRoutePolicy())->edit($owner, $route));   // direct
$this->assertTrue($owner->can('edit', $route));                         // through the Gate
```

Policy methods take the actor as their first argument, so passing `$user` is normally enough. Watch
for a method that reaches for `Auth::user()` internally instead — that makes the argument a lie and
the test only passes because `actingAs()` happened to set the same user. `DungeonRoutePolicy::rate()`
did exactly this (fixed in #3665). See `tests/Feature/Policy/DungeonRoutePolicyTest.php` for a full
worked example.

**Abilities invoked with an array** — `Gate::authorize('create-tag', [$tagCategory, $model])` —
resolve the policy from the **first** array element, and the rest are passed as extra arguments.
Test these through the Gate (`$user->can('create-tag', [...])`), not by instantiating a policy, so
the test also pins which policy class handles the ability.

## Swapping services (mocks)

Bind a mock into the container so the code-under-test resolves it:

```php
$mock = $this->createMockPublic(CacheServiceInterface::class);
app()->instance(CacheServiceInterface::class, $mock);
```

## Known gotchas

- `tests/Unit/App/Logging/StructuredLoggingTest` has a **pre-existing, unrelated failure** — ignore
  it when judging whether your change is green.
- The `MapTiles` group is excluded in CI.
- **A brand new model's factory 404s until `composer dump-autoload` runs** in that worktree
  (`Class "Database\Factories\...Factory" not found`). It is not a broken factory.
- **`$team->delete()` can throw a `LazyLoadingViolationException`** in tests, but only when `$team`
  was hydrated as part of a multi-row result — `Builder::hydrate()` only arms `preventsLazyLoading`
  when the query returned more than one row, so a single-row fetch or a factory-created model is
  never armed and `Team::deleting()`'s walk over `members.patreonAdFreeGiveaway` and `dungeonRoutes`
  won't throw for those. If you hit it, load the relations first:
  `$team->load(['members.patreonAdFreeGiveaway', 'dungeonRoutes']);` then delete. Don't pre-load
  defensively at every call site on the strength of this note alone — most existing team-deletion
  tests fetch a single row and are unaffected.
- **`assertSame()` on an array compares key order**, so asserting a seeded table against an `ALL`
  constant fails on ordering alone. Use `assertEquals()` when only the pairs matter.
- After writing a test, run it (`--filter`), then run `composer run fix` / `composer run analyse` —
  note that `composer run fix` also reformats unrelated pre-existing files, so stage only your own.

## Related

- The PHPUnit rules in `.claude/CLAUDE.md` and the root `CLAUDE.md`.
- **api-endpoint** skill for API-specific test setup, **repository-pattern** for repositories.
