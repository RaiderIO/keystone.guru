---
name: project-backend-structure
description: Architectural map of the PHP backend — every app/ directory explained, the Service/Logic/Repository/Model layering, container bindings per provider, and newcomer gotchas. Use when making architectural decisions, writing a new backend feature, or deciding where a new class belongs. Do not use for front-end JavaScript or any language other than PHP, and not for generic Laravel structure questions.
---

# Project Backend Structure

## Overview

Laravel 12 with the streamlined (Laravel 11+) structure: there is **no** `app/Console/Kernel.php`
or `app/Http/Kernel.php`. Middleware and exception wiring live in `bootstrap/app.php`, the
schedule lives in `routes/console.php`, and providers are registered in `bootstrap/providers.php`.
The backend is ~1,500 PHP files across 21 top-level directories under `app/`.

The layering for a typical request:

```
Route → Middleware → Controller (+ FormRequest) → Service (interface-injected)
      → Repository (interface-injected) → Model
```

`app/Logic` sits beside this stack as stateless, container-free domain primitives that Services
consume.

## The `app/` directory map

```
app/
├── Console/Commands    Artisan commands, subfoldered by domain (MDT, Mapping, CombatLog,
│                       Dungeon, Github, Release, Scheduler, ...). Shared traits in
│                       Console/Commands/Traits. Commands extend custom base classes and
│                       sometimes other commands — check the hierarchy before adding one.
├── Email               Custom mailables (e.g. CustomPasswordResetEmail.php). Tiny.
├── Events              Broadcast events for Reverb (see Events section). No PHP listeners.
├── Exceptions          Handler.php (bound in AppServiceProvider) + its structured-logging
│                       companion in Exceptions/Logging/.
├── Features            Laravel Pennant feature-flag classes, one class per flag
│                       (e.g. Heatmap.php, NpcCompendium.php). Pure class definitions.
├── Helpers             Global FUNCTION files (CustomHelper.php, ColorHelper.php), loaded via
│                       require_once in HelperServiceProvider — NOT PSR-4 classes.
├── Http                Controllers, FormRequests, request DTOs, middleware, resources
│                       (see Http section).
├── Jobs                Queued jobs, all `implements ShouldQueue`, no shared base class.
│                       Subfolders Jobs/CombatLog, Jobs/Logging.
├── Larex               Override of the Larex CSV↔translation importer (Crowdin sync tooling,
│                       config/larex.php). Not app domain code.
├── Logging             StructuredLogging base infrastructure (see structured-logging skill).
├── Logic               Stateless domain primitives (see Logic vs Service section).
├── Models              Eloquent models (see Models section).
├── Overrides           Subclasses of framework internals (CustomRateLimiter.php, wired in
│                       AppServiceProvider). Framework-behavior overrides live here.
├── Policies            Laravel authorization policies, one per model that needs one
│                       (DungeonRoutePolicy, TeamPolicy, ...).
├── Providers           Service providers — each has a single binding responsibility
│                       (see Providers section).
├── Repositories        Repository pattern: Interfaces/, Database/, Stub/, Swoole/
│                       (see repository-pattern skill).
├── Rules               Custom validation rules `implements ValidationRule`
│                       (e.g. DungeonRouteLevelRule.php).
├── SeederHelpers       RelationImport machinery for the dungeon JSON seeder
│                       (see seeder-load / seeder-save skills).
├── Service             The business-logic layer (see Service section).
├── Traits              Cross-cutting traits not tied to models (SavesArrayToJsonFile,
│                       CompressesImages, UserCurrentTime). Model traits live in
│                       app/Models/Traits, service traits in app/Service/Traits.
└── Vendor              In-repo vendored third-party code (Vendor/SemVer/Version.php).
                        Not Composer's vendor/.
```

## Service layer — `app/Service`

The primary business-logic layer: ~41 domain folders (DungeonRoute, Season, CombatLog,
CombatLogEvent, MDT, Npc, Spell, MapContext, Mapping, Cache, Cloudflare, Patreon, RaiderIO,
Wowhead, Discord, Metric, Coordinates, PathFinding, User, Expansion, GameVersion, ...).

Conventions:

- **Interface + implementation pair per service**: `Service/Coordinates/CoordinatesServiceInterface.php`
  + `CoordinatesService.php`. Always inject the interface, never the concrete class.
- **Large domains hold multiple services in one folder.** `Service/DungeonRoute/` contains
  `DungeonRouteService`, `DungeonRouteSaveService`, `DungeonRouteSearchService`,
  `CoverageService`, `MapDrawingService`, `ThumbnailService`, `DiscoverService` — each with its
  own interface.
- **Environment variants**: `Dev...`/`...Stub` implementations exist for local/test contexts
  (e.g. `Cache/DevCacheService`, `DungeonRoute/DevDiscoverService`,
  `Season/SeasonServiceStub`). Bindings switch on environment in the provider.
- **Per-service sub-namespaces** as needed: `Logging/` (the structured-logging companion,
  see structured-logging skill), `Dtos/`, `Exceptions/`, `Models/`, `Builders/`, `Filters/`.
  `Service/CombatLog/` is the richest example (Builders, DataExtractors, Dtos, Filters,
  ResultEvents, Splitters).
- Shared service traits: `Service/Traits/` (e.g. `Curl.php`).

**Registration**: all service interfaces are bound in
`app/Providers/KeystoneGuruServiceProvider.php::register()` (~64 bindings). Some are
environment-conditional, e.g.:

```php
if (app()->runningUnitTests() || app()->environment('local')) {
    $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOKeystoneGuruApiService::class);
} else {
    $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOApiService::class);
}
```

Adding a new service = folder (or existing domain folder) + `{Name}ServiceInterface` +
`{Name}Service` + binding in `KeystoneGuruServiceProvider` + (usually) a `Logging/` companion
bound in `LoggingServiceProvider`.

## Logic vs Service — the distinction

- **`app/Logic`** = stateless, framework-light domain primitives. Never bound in the container;
  instantiated directly. Contents: `Logic/Structs` (value objects: `LatLng`, `IngameXY`,
  `MapBounds`, `PathNode`), `Logic/MDT` (MDT Lua parsing: Entity/Lua/Data/Exception),
  `Logic/CombatLog` (CombatEvents, SpecialEvents, Guid), `Logic/Datatables` (+ ColumnHandler),
  `Logic/MapContext`, `Logic/SimulationCraft`, `Logic/Utils` (HtmlSanitizer, Stopwatch, Counter).
- **`app/Service`** = injectable, interface-backed orchestration of repositories, models,
  logging, caching, and external APIs. Services consume Logic structs/parsers.

Rule of thumb: if it needs the container, DB, or config, it's a Service; if it's pure
computation/parsing over values, it's Logic.

## Models — `app/Models`

~45 flat root models plus domain subfolders (153 model files in total): `DungeonRoute`, `Enemies`, `Floor`, `KillZone`,
`Mapping`, `Npc`, `Spell`, `Tags`, `AffixGroup`, `GameVersion`, `Metrics`, `Patreon`,
`CombatLog`, `Laratrust`, ...

### `Model` vs `CacheModel`

`app/Models/CacheModel.php` is `Model` + the `Cachable` trait from laravel-model-caching —
every query on such a model is transparently cached. Mostly-static reference data extends
`CacheModel` (`Season`, `Expansion`, `Dungeon`, `Npc`, `Release`, `MapIconType`,
`CharacterClass`, ...); frequently-written user data extends plain `Model`. Choose
deliberately when creating a model: wrongly picking `CacheModel` causes stale-data bugs, and
cache busting is tied to the model-caching package (`config/laravel-model-caching.php`).

- `User` extends `Authenticatable implements LaratrustUser` (Laratrust roles/permissions).
- Model traits: `app/Models/Traits` — `HasVertices`, `HasLatLng`, `HasTags`/`Taggable`,
  `BitMasks`, `GeneratesPublicKey`, `HasIconFile`, `HasMetrics`, `SeederModel`,
  `SerializesDates`, `Reportable`, `HasStart`, ...
- Model interfaces: `app/Models/Interfaces` — `HasLatLngInterface`, `HasVerticesInterface`,
  `TracksPageViewInterface`, `CloneForNewMappingVersionInterface`, ...
- Every model gets a repository (see repository-pattern skill).

### Mapping-versioned models

The dungeon "mapping" (enemies, packs, patrols, map icons, floor unions, mountable areas, ...)
is versioned via `app/Models/Mapping/MappingVersion.php`. Mapping-mutable models implement
`App\Models\Mapping\MappingModelInterface` and (when cloneable to a new version)
`App\Models\Interfaces\CloneForNewMappingVersionInterface`. Every mapping-versioned row carries
a `mapping_version_id`, so **queries must scope to the relevant mapping version**. When a new
mapping version is cut, rows are cloned forward; audit trail via `MappingChangeLog` /
`MappingCommitLog`. Mapping models also need seeder import/export handling — see the
seeder-load and seeder-save skills.

## Http layer — `app/Http`

### Controller taxonomy (`app/Http/Controllers`)

- Root: base `Controller.php` + main page controllers (`SiteController`, `TeamController`,
  `ProfileController`, `NpcController`, `ReleaseController`, ...).
- `Admin/` — admin CRUD pages; `AdminTools/` — the admin tools/batch pages (see
  admin-tools-page / admin-batch-page skills).
- `Ajax/` — ~30 `Ajax*Controller` classes for the map editor's async CRUD, one per model
  (`AjaxEnemyController`, `AjaxKillZoneController`, ...), with base
  `AjaxMappingModelBaseController.php`.
- `Api/V1/` — public REST API: `Public/`, `InternalTeam/`, and `Spec/` (OpenAPI attributes for
  l5-swagger). Controllers prefixed `API...`. See the api-endpoint skill.
- Domain folders: `Dungeon/`, `DungeonRoute/`, `Compendium/`, `Floor/`, `Speedrun/`, `Auth/`,
  `Webhook/`, and `Javascript/` (serves generated JS data like map context).

### FormRequests vs Request DTOs — don't conflate

- `app/Http/Requests/` — validation `FormRequest` classes (mostly named `*FormRequest`, some just
  `*Request`), subfoldered per model/domain plus `Api/V1/...`.
- `app/Http/Models/Request/` — plain DTO classes (base `RequestModel.php`) that give a validated
  request body a typed shape (e.g. `CombatLog/Route/CombatLogRoute*RequestModel.php`). These are
  **not** validators; a controller typically validates with a FormRequest and then builds a
  RequestModel from it.

### Middleware & Resources

- `app/Http/Middleware`: `OnlyAjax`, `ReadOnlyMode`, `LegalAgreed`, `ViewCacheBuster`,
  `TracksUserIpAddress`, `EnsureFeatureIsActive` (Pennant), plus `Middleware/Api`
  (`ApiAuthentication`, `ApiRole`, `ApiMetrics`) and `Middleware/Language`. Aliases and groups
  are declared in `bootstrap/app.php`.
- `app/Http/Resources`: API JSON resources subfoldered per model, plus shared pagination
  resources (`PaginationMetaResource` etc.).
- `app/Http/View/Composers`: Blade view composers.

## Routing — `routes/`

| File | Purpose |
|---|---|
| `web.php` (~800 lines) | Main site + editor + Ajax routes, organized in `Route::prefix()/middleware()` groups |
| `api.php` | The `/api/v1` REST surface |
| `console.php` | Scheduled commands (replaces the old Kernel schedule) |
| `channels.php` | Broadcast channel authorization (live sessions, route editing) |
| `breadcrumbs.php` | Diglactic breadcrumbs definitions (not routes) |

## Providers — who binds what

| Provider | Responsibility |
|---|---|
| `KeystoneGuruServiceProvider` | All service interface→implementation bindings (~64), env-conditional swaps, view composers, boot glue |
| `RepositoryServiceProvider` | All repository bindings (~116), one per model |
| `LoggingServiceProvider` | Every `{X}LoggingInterface` → implementation (~49, structured logging) |
| `ControllerServiceProvider` | Controller-support services |
| `PathFindingServiceProvider` | Pathfinding + killzone-path services |
| `HelperServiceProvider` | `require_once`s the global-function helper files |
| `AppServiceProvider` | Exception handler binding, rate limiters (via `Overrides\CustomRateLimiter`) |
| `HorizonServiceProvider` / `TelescopeServiceProvider` / `OctaneServiceProvider` | Third-party wiring |

## Events — `app/Events`

Laravel broadcast events pushed to the front-end via Reverb; **there are no PHP listeners** for
these. Base class `ContextEvent` (`abstract`, `implements ShouldBroadcast`) broadcasts on a
presence channel derived from its context model (route-edit or live-session). Model
CRUD events live under `Events/Models/<ModelName>/` with base `ContextModelEvent` /
`ModelChangedEvent` / `ModelDeletedEvent` — this is what keeps collaborative map editing in
sync. Also `Events/LiveSession/`, `Events/OverpulledEnemy/`.

## Feature flags — `app/Features`

Laravel Pennant, pure class definitions only (one class per flag). Routes are gated with the
`feature_active` middleware (`EnsureFeatureIsActive`). See the pennant-development skill.

## API

### Authorization

Authorization is done using Auth: Basic. No API keys exist. `ApiAuthentication` middleware plus
`api_role` (Laratrust) for the internal-team surface.

### OpenAPI spec

All endpoints must be documented with an OpenAPI spec — both request and response payloads
(attributes live with the `Api/V1` controllers and `Spec/`). After changing the spec,
regenerate the Swagger docs:

```bash
php artisan l5-swagger:generate --all && php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

## Config

`config/keystoneguru.php` is the central project config: super admins, asset/tile base URLs,
game constants (keystone levels, affixes), route limits, thumbnail/discover/heatmap/API
settings, external integrations (RaiderIO, Patreon, MDT), combat-log tuning. Other
project-specific configs: `laratrust.php`, `laravel-model-caching.php`, `larex.php`,
`discord-logger.php`, `github.php`, `pennant.php`, `influxdb.php`, `opensearch-laravel.php`.

## Related skills

- **repository-pattern** — adding a repository for a new model
- **structured-logging** — the `{Service}Logging` companion-class pattern
- **seeder-load / seeder-save** — importing/exporting mapping models via the dungeon JSON seeders
- **api-endpoint** — writing a new public API v1 endpoint
- **admin-tools-page / admin-batch-page** — new admin pages
- **combatlog-data-pipeline** — the combat-log extraction subsystem

## Newcomer gotchas

- `app/Helpers` files are global-function includes (`require_once` in `HelperServiceProvider`),
  not autoloaded classes.
- `app/Vendor`, `app/Overrides`, and `app/Larex` are in-repo forks/overrides of third-party or
  framework code — not app domain code.
- Everything is interface-injected: a new service/repository is invisible until its binding is
  added to the right provider (services → `KeystoneGuruServiceProvider`, repositories →
  `RepositoryServiceProvider`, logging companions → `LoggingServiceProvider`).
- Choose `CacheModel` vs `Model` deliberately — `CacheModel` transparently caches all queries.
- Mapping-mutable models must implement the mapping interfaces, scope queries by
  `mapping_version_id`, and be wired into seeder import (`SeederHelpers`) and export
  (`mapping:save`).
- `Http/Models/Request` DTOs are not FormRequests — validation happens in `Http/Requests`.
- `Stub`/`Swoole` repository variants and `Dev.../...Stub` service variants exist because of
  Octane/Swoole long-lived workers — state must not leak across requests; check
  `app/Repositories/Swoole/` before assuming a repository is request-scoped.
- Laravel 12 structure: no Kernel files — schedule in `routes/console.php`, middleware in
  `bootstrap/app.php`.
