---
name: laravel-best-practices
description: "Apply whenever writing, reviewing, or refactoring Laravel PHP code — controllers, models, migrations, form requests, policies, jobs, service classes, Eloquent queries; N+1/performance, caching, authorization/security, validation, error handling, queues, routes, and architectural decisions."
license: MIT
metadata:
  author: laravel
---

# Laravel Best Practices

Best practices for Laravel, organized as an index of rule files. Each rule file teaches what to do and why. For exact API syntax, verify with `search-docs`.

## Consistency First

Before applying any rule, check what the application already does. Laravel offers multiple valid approaches, and the best choice is the one the codebase already uses, even if another pattern would be theoretically better. Inconsistency is worse than a suboptimal pattern.

Check sibling files, related controllers, models, or tests for established patterns. If one exists, follow it. Don't introduce a second way. These rules are defaults for when no pattern exists yet, not overrides.

## Project conventions (project-specific — apply alongside the generic rules above)

These are keystone.guru's own conventions, moved here from `.claude/CLAUDE.md`. Where they
contradict a generic rule from the index below, **these win** — most notably the no-foreign-keys
rule under Migrations, which overrides `rules/migrations.md`'s `constrained()` guidance. Model
caching vs raw writes is a separate project-specific override that lives in the
`project-backend-structure` skill instead of here, because Boost wipes this skill's directory
wholesale on every `boost:update` — see `.claude/CLAUDE.md`, "Model caching is not a reason to
avoid writes that bypass Eloquent events".

#### General
- `sprintf` should always be used over direct concatenation for dynamic strings.

#### PHP style
- If there's existing comments in the code, prefer to keep them around if they aren't completely redundant.
- Class definition order: traits, constants, static properties, private properties, protected properties, public properties, constructor, public methods, protected methods, private methods, static methods, magic methods (like `__call` or `__get`).

#### Database & Eloquent
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

#### Model Creation
- Every new model must also have a repository. Create the interface at `app/Repositories/Interfaces/{Domain}/{ModelName}RepositoryInterface.php`, the implementation at `app/Repositories/Database/{Domain}/{ModelName}Repository.php`, and register the binding in `app/Providers/RepositoryServiceProvider.php`. See the `repository-pattern` skill for the full convention.

#### Seeded models (`SeederModel`)
The `SeederModel` trait is a marker, not a guarantee rows come from seeders — some trait users
(`SpellDungeon`, `NpcCharacteristic`, `NpcSpell`, `CombatLogNpcEvent`, `CombatLogSpellEvent`,
`ParsedCombatLog`) are combat-log-derived and a delete is **permanent**, not recoverable from
`database/seeders`. Only the admin panel, mapping editor, seeders and the hourly
`combatlog:detectstaledata` sweep may delete such rows (convention, not enforced). Full list and
rationale: `seeder-load` skill, "SeederModel rows".

#### Controllers & Validation
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

#### Routes & controller method naming
- Never name a controller method registered with first-class callable syntax
  (`Route::get('new', new FooController()->new(...))`) after a PHP reserved word (`new`, `list`,
  `print`, ...) — `route:cache` serialization crashes per-request in production only, not in tests.
  The route path/name string may keep the reserved word; only the method needs renaming. Full
  mechanics: `api-endpoint` skill, "Reserved-word controller methods".

#### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

#### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

#### Migrations
- **Do not use foreign keys** — this overrides the generic `constrained()` rule above. This
  application does not use them; they can cause issues with seeding and testing.
- **Migrations must be backward-compatible with the currently-running code.** Deploys are not atomic: a cron runs `migrate` independently of the ECS web rollout, so during every deploy the old code and the new schema (and vice-versa) coexist for a window. Additive changes (new nullable/defaulted column, new table, backfill) are safe. **Destructive changes are not** — never drop a table/column, rename it, or narrow its type in the same release that removes the code using it, or the still-running old containers will 500 against the missing schema (this is what broke staging in #3497).
- **MySQL caps identifier names at 64 characters** and Laravel's generated index names include the
  table name — on long table names pass an explicit short name: `->index('short_x_id_index')`,
  `$table->unique([...], 'short_unique')`. (The failure hits *after* the table is created, so the
  half-made table must be dropped by hand before re-running.)
- Split destructive schema changes across two releases (expand/contract): release N removes the
  last code consumer while leaving the old schema in place; release N+1 ships the drop/rename.
  Column rename = add new + backfill + dual-write in N, drop old in N+1.

## How to Apply

1. Check the changed files, nearby code, project configuration, and relevant tests for established patterns. Deviate only for a correctness or security defect, and call the deviation out.
2. Map every affected concern to the rule index below. Read each mapped rule file before editing. Skip unrelated rule files. **Always apply the "Project conventions" section above too, whatever the file type** — it's keystone.guru's own conventions and overrides the generic rules (no foreign keys in migrations, among others).
3. Make the smallest coherent change. Keep the application's architecture and naming instead of introducing a second pattern for the same job.
4. Verify version-sensitive Laravel APIs for the installed version with `search-docs`, or inspect the installed framework when it is unavailable.
5. Run the narrowest relevant tests first, then the project's formatting and static-analysis checks when the change warrants them.
6. Re-read the diff against every mapped rule before finishing.

## Rule Index

Cross-cutting changes often need more than one rule file.

| Concern | Read |
| --- | --- |
| Query count, eager loading, indexes, large datasets | [`rules/db-performance.md`](rules/db-performance.md) |
| Subqueries, aggregates, complex ordering and query plans | [`rules/advanced-queries.md`](rules/advanced-queries.md) |
| Models, relationships, scopes, casts | [`rules/eloquent.md`](rules/eloquent.md) |
| Authentication, authorization, input safety, secrets, uploads | [`rules/security.md`](rules/security.md) |
| Form Requests and validation rules | [`rules/validation.md`](rules/validation.md) |
| Controllers, route binding, resources, middleware | [`rules/routing.md`](rules/routing.md) |
| Schema changes, columns, foreign keys, indexes | [`rules/migrations.md`](rules/migrations.md) |
| Jobs, retries, uniqueness, batches, Horizon | [`rules/queue-jobs.md`](rules/queue-jobs.md) |
| Cache lifetime, invalidation, locks, memoization | [`rules/caching.md`](rules/caching.md) |
| Outbound requests, retries, timeouts, fakes | [`rules/http-client.md`](rules/http-client.md) |
| Exceptions, reporting, rendering, log context | [`rules/error-handling.md`](rules/error-handling.md) |
| Events and notifications | [`rules/events-notifications.md`](rules/events-notifications.md) |
| Mailables and mail assertions | [`rules/mail.md`](rules/mail.md) |
| Scheduled tasks and overlap protection | [`rules/scheduling.md`](rules/scheduling.md) |
| Collections, lazy iteration, bulk operations | [`rules/collections.md`](rules/collections.md) |
| Blade components, attributes, composers | [`rules/blade-views.md`](rules/blade-views.md) |
| Environment values and application configuration | [`rules/config.md`](rules/config.md) |
| Pest/PHPUnit patterns, factories, fakes | [`rules/testing.md`](rules/testing.md) |
| Naming, helpers, file boundaries, PHP style | [`rules/style.md`](rules/style.md) |
| Actions, services, dependencies, application structure | [`rules/architecture.md`](rules/architecture.md) |

## Decision Rules

- Prefer framework features and existing application abstractions over new helpers or dependencies.
- Avoid speculative abstractions. Extract code when it creates a clear domain boundary, removes meaningful duplication, or makes behavior independently testable.
- Keep database access out of Blade views and prevent hidden N+1 queries across controllers, resources, jobs, and serialization.
