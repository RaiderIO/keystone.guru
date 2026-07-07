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

## Creating users & roles (Laratrust)

- **Admin**: `User::findOrFail(1)` — the seeded admin. Assert it defensively:
  ```php
  $admin = User::findOrFail(1);
  $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
  ```
  `$user->is_admin` is an accessor for `hasRole(Role::ROLE_ADMIN)`.
- **Non-admin**: `User::factory()->create()` — a fresh user never has the admin role.
- Roles/constants live on `App\Models\Laratrust\Role` (`Role::ROLE_ADMIN`, …).

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

Methods that read `Auth::user()` internally (e.g. `DungeonRoutePolicy::rate()`) need
`$this->actingAs($user)` before the assertion — passing the user as an argument is not enough.
See `tests/Feature/Policy/DungeonRoutePolicyTest.php` for a full worked example.

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
- After writing a test, run it (`--filter`), then run `composer run fix` / `composer run analyse` —
  note that `composer run fix` also reformats unrelated pre-existing files, so stage only your own.

## Related

- The PHPUnit rules in `.claude/CLAUDE.md` and the root `CLAUDE.md`.
- **api-endpoint** skill for API-specific test setup, **repository-pattern** for repositories.
