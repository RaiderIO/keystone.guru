---
name: mapping-versioned-models
description: Lifecycle of mapping-versioned models (Enemy, EnemyPack, EnemyPatrol, MapIcon, FloorUnion, MountableArea, ...) — the MappingVersion clone-on-create boot, the clone interfaces/trait, mapping_version_id query scoping, current-version resolution, change logs, and the full wiring checklist (including the hardcoded model lists people forget). Use when creating or modifying a model that carries mapping_version_id, or debugging missing/duplicated mapping data across versions. Not for the JSON seeder mechanics (seeder-load / seeder-save) or the Ajax editor endpoints (ajax-map-editor-crud).
---

# Mapping-Versioned Models

## Overview

The dungeon "mapping" (enemies, packs, patrols, map icons, floor unions, mountable areas, floor
switch markers, NPC enemy forces) is versioned via `app/Models/Mapping/MappingVersion.php`. Every
mapping row carries a `mapping_version_id`; when a new version is cut, all rows are **cloned
forward** to it. Queries must always scope to the relevant mapping version or you'll read rows
from every version at once.

The canonical set of mapping-versioned models is the `HasMany` relation list on `MappingVersion`:
`dungeonFloorSwitchMarkers`, `enemies`, `enemyPacks`, `enemyPatrols`, `mapIcons`,
`mountableAreas`, `floorUnions`, `floorUnionAreas`, `npcEnemyForces` (plus `dungeonRoutes`, which
reference but are not cloned with a version).

## Which version is "current"

Resolution lives on `app/Models/Dungeon.php` — current version is **per dungeon + per game
version** (not per season):

- `getCurrentMappingVersion(?GameVersion $gameVersion = null)` — resolves the game version from
  the authed user via `GameVersionServiceInterface`, falls back to the default game version;
  result cached per request in `$currentMappingVersionCache`.
- `getCurrentMappingVersionForGameVersion(GameVersion)` — highest `version` for that combination.
- `MappingVersion::isLatestForDungeon()` — compares against `max('version')`.

## The clone contract

Interfaces (all in `app/Models/`):

| Interface | Purpose |
|---|---|
| `App\Models\Mapping\MappingModelInterface` | Marks the model as mapping-versioned. One method: `getDungeonId(): ?int` (typically `return $this->floor->dungeon_id;`) |
| `App\Models\Mapping\MappingModelCloneableInterface` | `cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Model` — what models implement |
| `App\Models\Interfaces\CloneForNewMappingVersionInterface` | Near-duplicate of the above with the same signature — what `MappingVersion::boot()` type-hints against. Keep both in mind. |

Default implementation — `app/Models/Mapping/CloneForNewMappingVersionNoRelations.php` trait:

```php
public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Model
{
    $clone         = clone $this;
    $clone->exists = false;
    unset($clone->id);
    $clone->mapping_version_id = $mappingVersion->id;
    $clone->save();

    return $clone;
}
```

Most models just `use` the trait (`Enemy`, `EnemyPack`, `MapIcon`, `FloorUnion`,
`MountableArea`, `DungeonFloorSwitchMarker`, `NpcEnemyForces`). Models with children override it:
`EnemyPatrol` (`app/Models/EnemyPatrol.php`) also clones its `polyline`/`mdtPolyline` passing
itself as `$newParent`; `FloorUnionArea` (`app/Models/Floor/FloorUnionArea.php`) sets
`floor_union_id = $newParent?->id`. That is what the `$newParent` parameter is for: re-parenting
child clones.

## The two clone code paths (different model coverage!)

1. **Automatic — `MappingVersion::boot()`** (`app/Models/Mapping/MappingVersion.php`, ~lines
   505–640). `static::created` copies scalar fields from the previous version, eager-loads the 9
   relations, calls `cloneForNewMappingVersion()` on each, and keeps an `$idMapping` per model
   class to re-link FKs in a second pass (Enemy → `enemy_pack_id`/`enemy_patrol_id`,
   FloorUnionArea → `floor_union_id`). `static::deleting` manually deletes all 9 relations.
   ⚠️ This boot contains **three hardcoded model lists** (eager-load array, merge chain,
   `$idMapping` keys) plus the `deleting` chain — a new model must be added to all of them.
2. **Manual/MDT — `app/Service/Mapping/MappingService.php`**.
   `createNewBareMappingVersion()` / `createNewMappingVersionFromPreviousMapping()` use
   `MappingVersion::create()` (boot fires). `createNewMappingVersionFromMDTMapping()` and
   `copyMappingVersionToDungeon()` use `insertGetId()` **deliberately bypassing the boot** to
   avoid double-cloning, then call `copyMappingVersionContentsToDungeon()` — which clones only a
   **subset**: floor switch markers (with `linked_dungeon_floor_switch_marker_id` re-linking),
   mapIcons, mountableAreas, floorUnions + floorUnionAreas. Enemies/packs/patrols/npcEnemyForces
   are intentionally excluded (they come from the MDT import instead).

## Query scoping

There is **no global scope** — scoping is explicit `where('...mapping_version_id', ...)`. The
main pattern is parameterised relations on `app/Models/Floor/Floor.php`:

```php
public function enemies(?MappingVersion $mappingVersion = null): HasMany
{
    return $this->hasMany(Enemy::class)
        ->where('enemies.mapping_version_id', ($mappingVersion ?? $this->dungeon->getCurrentMappingVersion())->id);
}
```

Floor also has `*ForExport` relation variants (`enemiesForExport`, `mapIconsForExport`, ...) used
by `mapping:save` — a new model needs **both** the scoped runtime relation and a `*ForExport`
one. Repositories that query mapping tables scope manually (e.g.
`app/Repositories/Database/MapIconRepository.php`).

## Change / commit logs

- `MappingChangeLog` (`app/Models/Mapping/MappingChangeLog.php`) — audit row with
  `model_class`, `before_model`/`after_model` JSON. Written by the `ChangesMapping` trait
  (`app/Http/Controllers/Traits/ChangesMapping.php::mappingChanged()`), invoked from
  `AjaxMappingModelBaseController::storeModel()` after every successful mapping edit (gated by
  `shouldCallMappingChanged()`). Replayable via `php artisan mapping:restore {id}`
  (`app/Console/Commands/Mapping/Restore.php`).
- `MappingCommitLog` (`app/Models/Mapping/MappingCommitLog.php`) — just an id + `merged` flag;
  exported/imported by `mapping:save` and the seeder, not written at runtime by app code.

## Checklist: wiring in a NEW mapping-versioned model

1. Migration adding the table with a `mapping_version_id` column (no FK constraints — project
   convention).
2. Model: `implements MappingModelCloneableInterface, MappingModelInterface`, `use
   CloneForNewMappingVersionNoRelations` (or custom clone if it has children — see EnemyPatrol),
   `use SeederModel`, implement `getDungeonId()`, add `mappingVersion(): BelongsTo`.
3. `MappingVersion.php`: add the `HasMany` relation **and** register the model in the `created`
   boot (eager-load array + merge chain + `$idMapping` if FKs need re-linking) **and** the
   `deleting` chain.
4. `MappingService::copyMappingVersionContentsToDungeon()`: add a clone loop **if** the model
   must survive the MDT-copy path — this is separate from the boot path and easy to forget.
5. `Floor.php`: add the mapping-version-scoped runtime relation and the `*ForExport` variant.
6. Export: add the model to `mapping:save` (`app/Console/Commands/Mapping/Save.php`,
   `saveFloor()` result block or the appropriate save method) — see the **seeder-save** skill.
7. Import: add a `RelationMapping` subclass in `app/SeederHelpers/RelationImport/Mapping/` and
   register it in the `$relationMapping` array in `database/seeders/DungeonDataSeeder.php` —
   **ordering matters: parents before children**. See the **seeder-load** skill.
8. Repository interface + implementation + `RepositoryServiceProvider` binding — see the
   **repository-pattern** skill.
9. Ajax editor endpoint if the object is editable on the map — see the **ajax-map-editor-crud**
   skill.

## Gotchas

- The hardcoded model lists (three in `MappingVersion::boot()`, the partial one in
  `MappingService::copyMappingVersionContentsToDungeon()`, the seeder's `$relationMapping`
  array) are the classic "new model silently missing from new versions" bug source.
- `mapIcons` relations use `->whereNotNull('mapping_version_id')` in both `MappingVersion` and
  `Floor`: MapIcon doubles as a user-route icon with a **nullable** `mapping_version_id`
  (`MapIcon::getIsAdminAttribute()` returns `mapping_version_id !== null`).
- `insertGetId()` in `MappingService` bypasses model events on purpose; don't "fix" it to
  `create()` or versions created from MDT will double-clone.
- Clone ordering: children are FK-re-linked in a second pass after all clones exist; in the
  seeder, parents (EnemyPack, FloorUnion) must import before children (Enemy, FloorUnionArea).
- Caching: `Dungeon::$currentMappingVersionCache` and `MappingVersion`'s internal caches are
  per-request; the models extend `CacheModel` (query cache), and `mapping:save` runs
  `modelCache:clear` first because stale model caches corrupt exports.

## Related skills

- **seeder-save / seeder-load** — exporting/importing mapping models via the dungeon JSON files
- **repository-pattern** — the required repository for a new model
- **ajax-map-editor-crud** — the editor endpoints that mutate mapping models
- **project-backend-structure** — where mapping code sits in the wider backend
- **update-mdt-package** — MDT-driven mapping version creation
