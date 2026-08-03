---
name: mapping-versioned-models
description: Lifecycle of mapping-versioned models (Enemy, EnemyPack, MapIcon, FloorUnion, ...) — MappingVersion clone-on-create, mapping_version_id scoping, and the master end-to-end checklist for adding a new model (schema → clone boot → seeder round-trip → map context → ajax editor → Leaflet front-end). Use when creating/modifying a model carrying mapping_version_id or debugging missing/duplicated mapping data. Deep dives in seeder-load/seeder-save and ajax-map-editor-crud.
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
`mountableAreas`, `enemyForcesCheckpoints`, `floorUnions`, `floorUnionAreas`, `npcEnemyForces`
(plus `dungeonRoutes`, which reference but are not cloned with a version).

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
   505–640). `static::created` copies scalar fields from the previous version, eager-loads the 10
   relations, calls `cloneForNewMappingVersion()` on each, and keeps an `$idMapping` per model
   class to re-link FKs in a second pass (Enemy → `enemy_pack_id`/`enemy_patrol_id`/
   `enemy_forces_checkpoint_id`, FloorUnionArea → `floor_union_id`). `static::deleting` manually
   deletes all 10 relations.
   ⚠️ This boot contains **three hardcoded model lists** (eager-load array, merge chain,
   `$idMapping` keys) plus the `deleting` chain — a new model must be added to all of them.
2. **Manual/MDT — `app/Service/Mapping/MappingService.php`**.
   `createNewBareMappingVersion()` / `createNewMappingVersionFromPreviousMapping()` use
   `MappingVersion::create()` (boot fires). `createNewMappingVersionFromMDTMapping()` and
   `copyMappingVersionToDungeon()` use `insertGetId()` **deliberately bypassing the boot** to
   avoid double-cloning, then call `copyMappingVersionContentsToDungeon()` — which clones only a
   **subset**: floor switch markers (with `linked_dungeon_floor_switch_marker_id` re-linking),
   mapIcons, mountableAreas, floorUnions + floorUnionAreas. Enemies/packs/patrols/npcEnemyForces
   are intentionally excluded (they come from the MDT import instead, or are re-linked by the
   caller afterwards — see below). `enemyForcesCheckpoints` are cloned by a **separate paired
   method** the caller must remember to invoke too — see the next subsection (#3702).

### The `copyEnemyForcesCheckpointsToMappingVersion()` pairing (#3702)

`EnemyForcesCheckpoint` clones **outside** `copyMappingVersionContentsToDungeon()` on purpose:
neither that method nor the MDT-copy path clones enemies (a checkpoint's members live in
`enemies`), so only the caller can re-link membership afterwards — cloning the checkpoint *inside*
`copyMappingVersionContentsToDungeon()` would give it zero members with no way for the caller to
fix that up later. `MappingService::copyEnemyForcesCheckpointsToMappingVersion(source, target):
array<int, int>` returns **source checkpoint id => clone id**. That map has to cross the
`MappingService` boundary back to the caller — currently only
`MDTMappingImportService::importMappingVersionFromMDT()` (which threads it into `importEnemies()`)
— so the caller can translate each surviving enemy's `enemy_forces_checkpoint_id` through it;
copying that field verbatim would attach the enemy to the *previous* mapping version's checkpoint.
**Not every caller of `copyMappingVersionContentsToDungeon()` should pair it with this** —
`MappingVersionController::saveNew()`'s "Add bare mapping version" action deliberately does *not*:
a bare mapping version has no enemies at all, so a checkpoint cloned there would sit permanently
empty with nothing to assign it to. Whether a given caller should pair the two is a judgment call
documented on `MappingServiceInterface::copyMappingVersionContentsToDungeon()`'s doc-block, not
enforced by the type system — read that doc-block before adding a new caller. A by-reference
out-param was tried and abandoned instead of a return value: the repo's
`ErickSkrauch/align_multiline_parameters` php-cs-fixer rule mangles `&$param` into
`?array   &   $param`.

A checkpoint cloned with zero members must not be treated as having *lost* its members on the next
MDT import. `MDTMappingImportService::deleteEmptyEnemyForcesCheckpoints()` only prunes a
still-empty clone whose **source** counterpart genuinely had members (checked against the id map
above) — anything else, even if currently empty, survives untouched. Getting this wrong silently
wipes every checkpoint of every dungeon on the very next real MDT change.

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

## Checklist: wiring in a NEW mapping-versioned model, end to end

The complete flow in dependency order, from schema to a map object an admin can place, edit and
toggle in the sidebar. **`EnemyForcesCheckpoint` (PR #3660) is the most recent complete worked
example** — its diff contains exactly one of everything below; when in doubt, mirror it.

### A. Schema, model, repository

1. Migration adding the table with a `mapping_version_id` column (+ `floor_id` and `lat`/`lng` if
   it is placed on the map). No FK constraints — project convention. If membership/children live
   on *another* table (e.g. `enemies.enemy_forces_checkpoint_id`), that is a second additive
   migration.
2. Model: extends `CacheModel`, `implements MappingModelCloneableInterface,
   MappingModelInterface` (+ `HasLatLngInterface` with the `HasLatLng` trait if placeable), `use
   CloneForNewMappingVersionNoRelations` (or custom clone if it has children — see EnemyPatrol),
   `use SeederModel`, implement `getDungeonId()` (typically `$this->floor->dungeon_id`), add
   `mappingVersion(): BelongsTo`. Put `mappingVersion`/`floor`/`laravel_through_key` in `$hidden`
   so the map-context JSON stays lean; `$timestamps = false` like its siblings.
   If deleting the model must release rows pointing at it, prefer hooking `static::deleted()` over
   `deleting`: the referencing rows only need detaching once the delete is confirmed to have gone
   through, not beforehand — see `EnemyForcesCheckpoint::booted()` for a worked example.
3. Repository interface + implementation + `RepositoryServiceProvider` binding — see the
   **repository-pattern** skill.

### B. Mapping-version lifecycle (the hardcoded lists)

4. `MappingVersion.php`: add the `HasMany` relation + `@property` line **and** register the model
   in the `created` boot (eager-load array + merge chain + `$idMapping` key) **and** the
   `deleting` chain. If *other* models reference yours by FK (enemy → checkpoint), also add the
   second-pass re-link in the boot (see the `enemy_forces_checkpoint_id` block) — without it,
   clones of the referencing model keep pointing at the **old** version's row and the new
   version looks fine while being subtly wrong.
5. `MappingService::copyMappingVersionContentsToDungeon()`: add a clone loop **if** the model
   must survive the MDT-copy path — this is separate from the boot path and easy to forget. If the
   model's children live in a table this method doesn't touch (the way `enemyForcesCheckpoints`'
   members live in `enemies`, which this method never copies), clone it via its own paired method
   instead — see `copyEnemyForcesCheckpointsToMappingVersion()` above — and return a
   source id => clone id map so the caller can re-link children itself; only pair it from callers
   that will actually populate those children afterwards (a caller with no children to assign,
   like a bare mapping version, must not pair it or the clone sits permanently empty).
6. `app/Console/Commands/Mapping/Copy.php` `$relations` array: any model carrying a `floor_id`
   must be listed or cross-dungeon copies strand it on the source dungeon's floors — see Gotchas.

### C. Saving to disk + seeder round-trip

7. Export — `mapping:save` (`app/Console/Commands/Mapping/Save.php`): add
   `<name>ForExport` to the eager-load list in `saveFloors()` **and** an entry in the per-floor
   result block (`$result['enemy_forces_checkpoints'] = ...`), with the same
   `setRelation('floor', $floor)` + lat/lng-rounding treatment its siblings get. See the
   **seeder-save** skill.
8. Import — `RelationMapping` subclass in `app/SeederHelpers/RelationImport/Mapping/` (JSON file
   name + model class, `MappingVersionConditional`), registered in the `$relationMapping` array
   in `database/seeders/DungeonDataSeeder.php` — **ordering matters: parents before children**.
   See the **seeder-load** skill. Verify with a real `mapping:save` round-trip in dev, then decide
   deliberately what to keep — nothing rewrites `database/seeders/dungeondata/` on a timer, so
   anything that shows up there came from your own `mapping:save` and is a real change, not churn
   to be discarded blindly.

### D. Serving it to the map (the JS payload)

9. `Floor.php`: the mapping-version-scoped runtime relation **and** the `*ForExport` variant,
   plus both `@property` lines.
10. `MappingVersion::mapContext<YourModel>s()` — clone `mapContextMountableAreas()`: load with
    `floor`, facade-convert each lat/lng when `facade_enabled && $useFacade`. Read the "facade
    conversion rewrites `floor_id`" gotcha in the **new-map-view** skill *first* — if the client
    ever needs the real floor of a converted object, you must also ship `source_floor_id`.
11. `MapContextMappingVersionData::toArray()` (`app/Logic/MapContext/`): add the
    `'yourModels' => $this->mappingVersion->mapContext<YourModel>s(...)` entry. That key becomes
    `dungeon.yourModels` in the JS payload. ⚠️ The payload is cached under
    `dungeon_{id}_{mvid}_{style}` (two layers) — until caches drop, the new key simply won't
    appear and the front-end sees `undefined`.

### E. Edit endpoints

12. FormRequest + `Ajax<YourModel>Controller extends AjaxMappingModelBaseController` + the
    POST/PUT/DELETE routes in `routes/web.php`'s admin ajax group + `<YourModel>ChangedEvent` /
    `<YourModel>DeletedEvent` broadcast events — full server-side checklist in the
    **ajax-map-editor-crud** skill. Membership expressed as an FK on another model rides that
    *other* model's endpoint: add the field to its FormRequest (`APIEnemyFormRequest` gained
    `enemy_forces_checkpoint_id`) and write a test asserting the column actually **persists** —
    the 200 response looks identical if the field is silently dropped.

### F. Front-end (Leaflet)

13. `resources/assets/js/custom/constants.js`: a `MAP_OBJECT_GROUP_<NAME>` constant + an entry in
    `MAP_OBJECT_GROUP_NAMES` (order matters — list it *after* any group it depends on, e.g.
    checkpoints after enemies) + any admin styling under the `c.map` config object.
14. `mapcontext/mapcontext.js`: a getter returning `this._options.dungeon.yourModels ?? []` —
    the key must match step 11 exactly.
15. JS classes, one file each: `custom/models/<name>.js` (extends `MapObject`;
    `_getAttributes()` drives the popup fields *and* the save payload),
    `custom/admin/admin<name>.js` (edit behaviour), and
    `custom/mapobjectgroups/<name>mapobjectgroup.js` (`_getRawObjects()` reads the step-14
    getter; `_createMapObject()` returns the admin/non-admin flavour via
    `getState().isMapAdmin()`), registered in the if/else chain in
    `mapobjectgroups/mapobjectgroupmanager.js`. If admins place it with the draw toolbar:
    Leaflet icon/marker classes + a button in `admindrawcontrols.js` (see `drawcontrols.js` for
    the generic `faClass` handler) — and give the cursor-preview `divIcon` an `html`, or nothing
    renders under the cursor while placing.
16. **Register every new JS file in `scripts/build/custom-scripts-order.mjs`** — `custom/**` is
    NOT auto-globbed (only `custom/inline/**` is), and base classes must be listed before their
    subclasses.
17. Translations in `lang/en_US/js.php`: the bare `js.<mapobjectname>` key (popup title + draw
    button caption), `js.<mapobjectname>_title` (the draw button's hover title in
    `admindrawcontrols.js`), a `_label` key per popup attribute — including the inherited
    `faction`/`teeming` ones; full rules in **ajax-map-editor-crud** — and
    `js.<groupname>_map_object_group_label`, which captions the sidebar show/hide entry.
18. Sidebar selectability is then **automatic** — there is no per-model registration:
    `inline/common/maps/map.js` builds the eye-icon dropdown
    (`#map_map_object_group_visibility_dropdown`, blade shell in
    `common/maps/controls/elements/mapobjectgroupvisibility.blade.php`) from the map's
    instantiated groups (`MAP_OBJECT_GROUP_NAMES` minus the page's `hiddenMapObjectGroups`),
    labels each entry with the step-17 `_map_object_group_label` key, and persists per-user
    hiding in the `hidden_map_object_groups` cookie. Your only decisions: which *pages* should
    not show the group at all — add the group name to `hiddenMapObjectGroups` in
    `resources/views/dungeonroute/preview.blade.php` (route thumbnails) and
    `dungeonroute/embed.blade.php` if it doesn't belong there — and whether the group overrides
    `isUserToggleable()` (default true).
19. Handlebars templates in `resources/assets/js/handlebars/`, CSS in
    `resources/assets/css/sections/map.css`, vitest coverage for any client-side computation
    (see `models/enemyforcescheckpoint.test.js`).

## Gotchas

- The hardcoded model lists (three in `MappingVersion::boot()`, the partial one in
  `MappingService::copyMappingVersionContentsToDungeon()`, the `$relations` array in
  `app/Console/Commands/Mapping/Copy.php`, the seeder's `$relationMapping` array) are the classic
  "new model silently missing from new versions" bug source. A fifth spot of the same shape, but
  inverted: a caller of `copyMappingVersionContentsToDungeon()` that *should* clone enemy forces
  checkpoints (i.e. anything that will also re-create/re-link the enemies to assign to them, like
  an MDT import) must remember to also call `copyEnemyForcesCheckpointsToMappingVersion()`
  (#3702) — nothing enforces this but a doc-block comment on the interface, and forgetting it
  silently drops every checkpoint on that call path. Not every caller should pair it though —
  a caller with no enemies at all (bare mapping versions) must *not*, or the clone sits
  permanently empty. When it does apply, the fix also has to thread an id map (source checkpoint
  id => clone id) back across the service boundary to the caller, because only the caller can
  re-link the children (`enemies`) that method never copies.
- **Don't forget `Copy.php`.** `mapping:copy <gameVersion> <source> <target>` goes through
  `createNewMappingVersionFromPreviousMapping()`, so the boot clones everything correctly — but for a
  **cross-dungeon** copy `Copy.php` then re-points every cloned model's `floor_id` onto the target
  dungeon's floors, iterating its own `$relations` list to do it. A model missing from that list is
  cloned but keeps a `floor_id` belonging to the *source* dungeon, which is worse than not being
  cloned at all: `Floor::<relation>()` on the target returns nothing, `getDungeonId()` reports the
  source dungeon, and `mapping:save` exports the row into the source dungeon's floor folder. Any
  model carrying a `floor_id` belongs in that array (no test covers this command).
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
- Serializing for a facade map **overwrites `floor_id` with the facade floor**, so the front-end
  can never group by the real floor. Most models go through a `MappingVersion::mapContext*()`
  method (`mapContextMapIcons`, `mapContextEnemyPacks`, …) — but enemies don't; they're converted
  inline in `MapContextMappingVersionData::toArray()` via `$enemy->setLatLng(...)`. See "Gotcha:
  facade conversion rewrites `floor_id`" in the **new-map-view** skill before writing anything that
  groups map objects per floor.

## Related skills

- **seeder-save / seeder-load** — exporting/importing mapping models via the dungeon JSON files
- **repository-pattern** — the required repository for a new model
- **ajax-map-editor-crud** — the editor endpoints that mutate mapping models, plus the front-end
  MapObject conventions (per-attribute translation keys, snackbars for transient admin state)
- **new-map-view** — map-page blade/JS anatomy and the facade `floor_id` conversion gotcha
- **project-backend-structure** — where mapping code sits in the wider backend
- **update-mdt-package** — MDT-driven mapping version creation
