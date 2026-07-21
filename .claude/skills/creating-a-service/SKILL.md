---
name: creating-a-service
description: Runbook for adding a new Service to app/Service — interface + implementation pair, binding in KeystoneGuruServiceProvider (bind vs scoped vs instance, env-conditional swaps), the Logging companion wired in LoggingServiceProvider, Dev/Stub variants, and the Octane/Swoole statefulness rules. Use when creating a new service class or wiring an existing one into the container. Not for repositories (repository-pattern), the logging philosophy itself (structured-logging), or deciding Service vs Logic placement (project-backend-structure).
---

# Creating a Service

## Overview

Business logic lives in `app/Service/{Domain}/` as an **interface + implementation pair**.
Callers always inject the interface; the pair is invisible until bound in
`app/Providers/KeystoneGuruServiceProvider.php::register()`.

## File layout

```
app/Service/{Domain}/
├── {Name}ServiceInterface.php
├── {Name}Service.php
└── Logging/                       (usually)
    ├── {Name}ServiceLoggingInterface.php
    └── {Name}ServiceLogging.php
```

Large domains hold multiple services in one folder (`Service/DungeonRoute/` has
`DungeonRouteService`, `ThumbnailService`, `DiscoverService`, ... each with its own interface).
Per-service sub-namespaces as needed: `Dtos/`, `Exceptions/`, `Models/`, `Builders/`, `Filters/`.

## Interface + implementation

Stateless canonical example: `app/Service/Coordinates/CoordinatesServiceInterface.php` +
`CoordinatesService.php` (pure compute, no constructor). Constructor-injection example —
`app/Service/Dungeon/DungeonService.php`:

```php
class DungeonService implements DungeonServiceInterface
{
    public function __construct(
        private readonly CookieServiceInterface         $cookieService,
        private readonly SeasonServiceInterface         $seasonService,
        private readonly DungeonServiceLoggingInterface $log,
        private readonly GameVersionServiceInterface    $gameVersionService,
    ) {
    }
```

Conventions: promoted `private readonly` properties, interface types only (never concretes),
aligned parameter names, the logging companion is always named `$log`. All methods have explicit
return types; array shapes documented in PHPDoc.

## Binding — `KeystoneGuruServiceProvider::register()`

One line per service, grouped under dependency-order comments (e.g. `// Depends on
CacheService, CoordinatesService`):

```php
$this->app->bind(MyNewServiceInterface::class, MyNewService::class);
```

- **Always `bind` (transient)** — `singleton()` is used nowhere in the providers, deliberately:
  transient resolution avoids stale state in Octane's long-lived workers.
- **`scoped()`** only for per-request memoized state, e.g.
  `$this->app->scoped(RequestViewContextInterface::class, RequestViewContext::class);` — scoped
  resets between Octane requests where a singleton would not.
- **`app()->instance(...)`** is reserved for the Octane/Swoole-persistent read-only repositories
  in `app/Providers/OctaneServiceProvider.php` — a code comment there records that `singleton()`
  was recreated every request under Swoole, hence pre-built `instance()` objects. Don't copy
  that pattern for normal services.

**Environment-conditional bindings** — both idioms exist; match the neighbors:

```php
if (app()->runningUnitTests() || app()->environment('local')) {
    $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOKeystoneGuruApiService::class);
} else {
    $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOApiService::class);
}

if (in_array(config('app.env'), ['local', 'testing'])) {
    $this->app->bind(CacheServiceInterface::class, DevCacheService::class);
} else {
    $this->app->bind(CacheServiceInterface::class, CacheService::class);
}
```

## The Logging companion

Most services get a `Logging/` subfolder pair (see the **structured-logging** skill for the
philosophy; these are the creation steps):

1. `{Name}ServiceLoggingInterface` — one `void` method per log point, semantically named
   (`importInstanceIdsFromCsvStart(string $filePath)`, `...End()`, `...SomethingFailed(...)`).
2. `{Name}ServiceLogging extends App\Logging\RollbarStructuredLogging implements` the interface —
   every method body is a one-liner: `$this->start(__METHOD__, get_defined_vars());`,
   `$this->end(__METHOD__);`, or `error`/`warning`/`debug` equivalents.
3. Bind it in `app/Providers/LoggingServiceProvider.php::register()` under the domain comment:
   `$this->app->bind(MyNewServiceLoggingInterface::class, MyNewServiceLogging::class);`
4. Inject into the service as `private readonly MyNewServiceLoggingInterface $log`.

## Dev / Stub variants

Create one only when the real implementation talks to an external system or is too heavy for
local/test runs:

- `Dev{Name}Service` — alternate local implementation, swapped via env-conditional binding
  (e.g. `Cache/DevCacheService`).
- `{Name}ServiceStub` — not container-bound at all; instantiated directly where needed
  (e.g. `SeasonServiceStub` is `new`-ed inside `CombatLogRouteDungeonRouteService` and tests).
- Stub-style real-class swap: `RaiderIOKeystoneGuruApiService` replaces `RaiderIOApiService` for
  unit tests + local.

## Checklist

1. Create `app/Service/{Domain}/{Name}ServiceInterface.php` and `{Name}Service.php`.
2. Bind in `KeystoneGuruServiceProvider::register()` in the dependency-ordered spot
   (env-conditional if a Dev variant exists).
3. Create the `Logging/` pair and bind it in `LoggingServiceProvider`.
4. Inject the interface where used (constructor for services, `handle()` parameters for
   commands, action-method parameters for controllers).
5. Keep the service stateless (or use `scoped()`) — assume the instance may live across
   requests under Octane.
6. Write a test, stage all new files, run `composer run fix` and `composer run analyse`.

## Related skills

- **structured-logging** — log levels, start/end grouping, Discord alerting
- **repository-pattern** — the data-access layer services consume
- **project-backend-structure** — Service vs Logic placement and the provider map
