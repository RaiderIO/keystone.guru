---
name: writing-tests
description: How to write and run tests in this project — PHPUnit-class structure, naming, Arrange-Act-Assert, factories, database cleanup, test groups, data providers, running commands, and the Dungeon/MappingVersion/Floor random-data pitfall (with the traits that already solve it). Use whenever creating, editing, or running a test.
---

# Writing Tests

> The rule that **every change must be programmatically tested** lives in `CLAUDE.md`. This skill is the *how*.

## Creating a test file

- **Create the file directly in the codebase — do NOT use `php artisan make:test`.** This project's host rule (see `CLAUDE.md` → *Host Machine*) requires files be created on the host so they have correct permissions and LF line endings. `make:*` generators are disabled here.
- New files must have **LF line endings** and be staged with `git add` immediately.
- Most tests are **feature** tests under `tests/Feature/...`. Use `tests/Unit/...` only for pure, dependency-free logic.
- Mirror the namespace to the path under `tests/` (PSR-4 root `Tests\`). Place a test next to siblings testing the same area and copy their structure.

## Base test cases

Extend the right base — they handle bootstrapping, timing limits, and auth:

| Base class | Use for |
|---|---|
| `Tests\TestCases\PublicTestCase` | Plain feature/unit tests. |
| `Tests\TestCases\AjaxPublicTestCase` | Ajax endpoints — logs in as user 1 and sets the `X-Requested-With` header. |
| `Tests\TestCases\APIPublicTestCase` | Public API v1 endpoints — adds API authentication. |
| `Tests\Feature\Controller\DungeonRouteTestBase` | Controller tests needing a ready-made route — exposes `$this->dungeonRoute` built via the guarded `createNonFacadeDungeonRouteWithEnemies()` and tears it down. |

## Anatomy of a test

```php
#[Group('CombatLog')]            // groups go on the CLASS docblock, not the method
final class MyThingTest extends PublicTestCase
{
    #[Test]
    public function process_givenValidData_persistsRecord(): void
    {
        // Arrange
        $input = ...;

        // Act
        $result = $service->process($input);

        // Assert
        $this->assertSame(..., $result);
    }
}
```

- **PHPUnit only — never Pest.** Use the `#[Test]` attribute (or a `test`-prefixed name). If you encounter a test written in Pest, convert it to a PHPUnit class.
- **Naming pattern:** `[functionName]_given[Condition]_returns[ExpectedResult]`, e.g. `myFunction_givenInvalidDate_throwsInvalidArgumentException`.
- **Arrange-Act-Assert:** structure every test in these three labelled phases.
- **Coverage:** test the happy path, the failure paths, and the weird/edge paths — not just the happy one.
- **Groups:** put `#[Group('...')]` on the **class** docblock. Convention: a folder-wide group (e.g. `CombatLog` for everything under that folder) plus a file-specific group (e.g. `EncounterStart`).
- **Never delete existing tests or test files** without approval — they are core to the app, not scratch files.

## Database & cleanup (important — no transactional rollback)

This suite runs against a **persistent, seeded database** and does **not** use `RefreshDatabase` or `DatabaseTransactions`. Nothing is rolled back automatically.

- **Clean up every record you create in a `try { ... } finally { ... }` block** so a failed assertion still removes the row.
- `CombatLogRouteEnemyFailure` and friends live on the separate **`combatlog` connection**, which is also not rolled back — leftover rows there persist across tests. Account for pre-existing data rather than assuming an empty table.
- Tests have a **10s hard limit** and a 1s soft warning (`tests/TestCase.php`). For a legitimately slow test, add `#[SlowTest]` (`Tests\Attributes\SlowTest`) on the class or method to exempt it from the timing check.

## Factories & Faker

- Build models with their **factories**, and check for a **custom state** before setting attributes by hand (e.g. `DungeonRoute::factory()->create([...])`).
- Faker: follow the surrounding file's convention — either `$this->faker->word()` or `fake()->randomDigit()`.

## Data providers

- Provider is a `public static function` returning the cases.
- Attach it with a **method-level** `#[DataProvider('...')]` attribute (the test method, not the class).
- Place the provider **directly below the last test that uses it** — not at the top or bottom of the class.
- See `tests/Feature/App/Service/Season/SeasonService/GetSeasonFromShortStringTest.php` for the canonical shape.

## Pitfall: random Dungeon / MappingVersion / Floor data — reuse the traits first

A recurring source of CI flakiness: code that picks a random dungeon and then *assumes* it has what's needed.

```php
// ❌ DON'T — a random dungeon may have only facade floors, or no current mapping version
$dungeon = Dungeon::inRandomOrder()->first();
$floor   = $dungeon->floors()->where('facade', 0)->first(); // can be null → null-deref
$mv      = $dungeon->getCurrentMappingVersion();             // can be null → null-deref
```

**Before rolling your own random pick, use the existing helpers — they loop until the prerequisites hold:**

- `Tests\Feature\Traits\ProvidesDungeon::getDungeonWithNonFacadeFloor(?Closure $constraint = null): Dungeon`
  Returns a dungeon guaranteed to have a current mapping version and at least one non-facade floor. Pass a `Closure(Builder $q)` to add extra constraints (e.g. excluding dungeons that already have data) while keeping the guarantees.
- `Tests\Feature\Traits\GeneratesDungeonRoutes` — for full routes:
  - `createNonFacadeDungeonRouteWithEnemies(): DungeonRoute` — a route on a non-facade mapping version that has enemies.
  - `getMDTCompatibleNonFacadeDungeonRoute(array $attributes = []): DungeonRoute` — an MDT-importable, non-facade route.
  - `getMDTCompatibleDungeonRouteWithSafeEnemies(int $enemyCount = 1, array $attributes = []): DungeonRoute` — an MDT route with N enemies that survive an import round-trip.

```php
// ✅ DO
use Tests\Feature\Traits\ProvidesDungeon;
// ...
    use ProvidesDungeon;
    // ...
    $dungeon = $this->getDungeonWithNonFacadeFloor();
```

If you genuinely need a fresh helper, **extend these traits rather than re-implementing the loop**, and add an inline `do/while` guard mirroring `database/factories/DungeonRoute/DungeonRouteFactory.php` when the code is a factory (factories can't use a `Tests\` trait).

**Pin to a known mapping version when you hardcode IDs.** Enemy IDs are unique per mapping version: the same `(npc_id, mdt_id)` resolves to a *different* `enemies.id` in each version. If a test hardcodes enemy IDs, set the route's `mapping_version_id` from those enemies (`Enemy::findOrFail($id)->mapping_version_id`) — not from `getCurrentMappingVersion()`, which can drift when another test leaks a bumped version (see the `inRandomOrder()` + `version + 1` pattern in `database/factories/MappingVersion/MappingVersionFactory.php`).

**Verifying you fixed flakiness.** Add `#[Repeat(1000)]` (`Tests\Attributes\Repeat`) to a method to run it 1000× in one go and surface a flaky *self-inflicted random pick*. Caveats:
- `#[Repeat]` re-rolls only picks made in the **test body**; a pick in `setUp()` runs once per method, so Repeat won't fuzz it.
- A green Repeat run *confirms* a structural fix; it does **not** prove the fix (and won't surface cross-test mapping-version pollution). Prefer fixes that make the bad state impossible *by construction*, and remove the temporary `#[Repeat]` before finishing.

## Running tests (always inside Docker)

- All tests: `docker compose exec -T app php artisan test --compact`
- One file: `docker compose exec -T app php artisan test --compact tests/Feature/ExampleTest.php`
- By name: `docker compose exec -T app php artisan test --compact --filter=testName`
- By group: `docker compose exec -T app php artisan test --compact --group=CombatLog`

Run the **minimum** set needed (a filter or single file) while iterating. Run the test you changed every time you change it. When your feature's tests pass, ask the user whether to run the full suite.

## Checklist

1. Create the test file directly (no `make:test`), LF endings, correct base class.
2. `#[Group]` on the class; `#[Test]` methods named `fn_givenCondition_returnsResult`; Arrange-Act-Assert.
3. Cover happy, failure, and weird paths.
4. Clean up created rows in `try/finally`; account for the non-rolled-back `combatlog` connection.
5. Use factories/states; reuse `ProvidesDungeon` / `GeneratesDungeonRoutes` for dungeon/route data.
6. `git add` the new file.
7. Run the affected tests, then `composer run fix` and `composer run analyse`.
